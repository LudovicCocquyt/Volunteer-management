import { h } from 'preact';
import { useEffect, useRef, useState } from 'preact/hooks';
import { Calendar } from '@fullcalendar/core';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import { getPlans, addPlan, updatePlan, removePlan } from '../routes/PlansRoutes';
import { getActivities } from '../routes/ActivitiesRoutes';
import dayGridPlugin from '@fullcalendar/daygrid';

const TimelineCalendar = () => {
  const calendarRef                             = useRef(null);
  const [activities, setActivities]             = useState([]);
  const [isDataLoaded, setIsDataLoaded]         = useState(false);
  const [events, setEvents]                     = useState([]);
  const [calendarInstance, setCalendarInstance] = useState(null);
  const [showForm, setShowForm]                 = useState(false);
  const [formData, setFormData]                 = useState({});
  const [isEditing, setIsEditing]               = useState(false);

  const [plans, setPlans]                       = useState({});
  const activitiesActive = Object.keys(plans).map(key => key) || [];

  const element           = document.getElementById('Scheduler-wrapper');
  const eventPreparedId   = JSON.parse(element.getAttribute('data-eventId'));
  const eventPreparedDate = JSON.parse(element.getAttribute('data-eventStartAt'));

  useEffect(() => {
    const fetchData = async () => {
      await getApiActivities();
      await getApiPlans(eventPreparedId);
      setIsDataLoaded(true); // Refresh the calendar
    };

    fetchData();
  }, []);

  useEffect(() => {
    if (isDataLoaded) {
      const calendar = new Calendar(calendarRef.current, calendarConfig);
      calendar.render();
      setCalendarInstance(calendar);
      setIsDataLoaded(false);
    }
  }, [isDataLoaded]);

  useEffect(() => {
    if (calendarInstance) {
      calendarInstance.removeAllEvents();
      calendarInstance.addEventSource(events);
    }
  }, [events, calendarInstance]);

  const getApiPlans = async (eventPreparedId) => {
    const plans = await getPlans(eventPreparedId);
    setPlans(plans);

    const d = Object.values(plans).map(plan => (
      plan.map(p => ({
        id: p.id,
        title: p.nbPers, // TODO: add the number of people
        resourceId: p.activity.name,
        start: p.startDate,
        end: p.endDate
      }))
    ));
    setEvents(d.flat());
  };

  const getApiActivities = async () => {
    const activities = await getActivities();
    setActivities(activities);
  };

  const handleSubmit = async (form) => {
    let validate = false
    if (form.id) {
      validate = await updatePlan(form);
    } else {
      validate = await addPlan(form);
    }

    if (validate) {
      await getApiPlans(eventPreparedId);
      setShowForm(false);
      setIsEditing(false);
      return validate;
    }
  };

  const remove = async (planId) => {
    if (planId) {
      const validate = await removePlan(planId);
      if (validate) {
        await getApiPlans(eventPreparedId);
        setShowForm(false);
        setIsEditing(false);
        setIsDataLoaded(true); // Refresh the calendar
      }
    }
  };

  const handleDateClick = (info) => {
    const clickedTime = new Date(info.dateStr);
    const startTime   = clickedTime.toISOString().substring(11, 16);  // Extract time in HH:MM format
    clickedTime.setHours(clickedTime.getHours() + 1);
    const endTime = clickedTime.toISOString().substring(11, 16); // Add one hour

    setFormData({
      start: info.dateStr,
      end: info.dateStr,
      startTime: startTime,
      endTime: endTime,
      resourceId: info.resource.id,
      eventId: eventPreparedId
    });
    setShowForm(true);
    setIsEditing(false);
  };

  const handleEventClick = (info) => {
    const event     = info.event;
    const startTime = new Date(event.start).toISOString().substring(11, 16);
    const endTime   = event.end ? new Date(event.end).toISOString().substring(11, 16) : '';

    setFormData({
      id: event.id,
      nbPers: event.title.split(' / ')[0],
      start: event.start.toISOString().substring(0, 10),
      end: event.end ? event.end.toISOString().substring(0, 10) : '',
      startTime: startTime,
      endTime: endTime,
      resourceId: event.extendedProps.resourceId,
      eventId: eventPreparedId
    });
    setShowForm(true);
    setIsEditing(true);
  };

  const handleFormChange = (e) => {
    const { name, value } = e.target;
    setFormData(prevState => ({
      ...prevState,
      [name]: value
    }));
  };

  const addActivity = async (activityName) => {
    const form = {
      "resourceId": activityName,
      "eventId": eventPreparedId,
      "nbPers": 0,
      "start": eventPreparedDate.date,
      "end": eventPreparedDate.date,
      "startTime": "12:00",
      "endTime": "13:00"
    };

    const validate = await handleSubmit(form);
    if (validate) {
      await getApiPlans(eventPreparedId);
      setIsDataLoaded(true); // Refresh the calendar
      console.log("Activité ajoutée et plans mis à jour");
    } else {
      console.log("Échec de l'ajout de l'activité");
    }
  };

  const calendarConfig = {
    schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
    headerToolbar: {
      left: '',
      center: 'title',
      right: 'prev,next'
    },
    resourceAreaColumns: [
      {
        field: 'title',
        headerContent: 'Activités'
      }
    ],
    slotMinTime: '12:00:00', // TODO: Make it configurable from event
    slotMaxTime: '23:00:00', // TODO: Make it configurable from event
    timeZone: 'UTC',
    locale: 'fr',
    plugins: [dayGridPlugin, resourceTimelinePlugin, interactionPlugin],
    initialView: 'resourceTimelineDay',
    initialDate: eventPreparedDate.date,
    locale: frLocale,
    height: 'auto',
    resources: Object.keys(plans).map(key => ({
      id: key,
      title: key
    })),
    events: events,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    eventMouseEnter: function(info) {
      const tooltip = document.createElement('div');
      tooltip.className = "tooltip";
      tooltip.innerHTML = "Cocquyt Ludovic,<br> Morelle Magalie,<br> Tournay Jean-Philippe";
      document.body.appendChild(tooltip);

      info.el.addEventListener('mousemove', (e) => {
        tooltip.style.left = e.pageX + 'px';
        tooltip.style.top = e.pageY + 'px';
      });

      info.el.addEventListener('mouseleave', () => {
        tooltip.remove();
      });
    }
  };

  return (
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <h3 class="w-full flex items-center justify-between p-2 text-gray-900 " style="background-color: #e9eb91;">Planification</h3>
    <div>
      <div className='flex py-3 ml-2'>
        <button id="dropdownSearchButton" data-dropdown-toggle="activities_choose" className="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">Ajouter des activités <svg className="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
        </svg>
        </button>

        <div id="activities_choose" className="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
          <ul className="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButton">
            {activities && activities.map((activity) => (
              <li>
                <div className="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                  <input
                    id={`activity-${activity.id}`}
                    type="checkbox"
                    onClick={() => addActivity(activity.name)}
                    checked={activitiesActive.includes(activity.name)}
                    disabled={activitiesActive.includes(activity.name)}
                    className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                  />
                  <label for={`activity-${activity.id}`} className="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">{ activity.name }</label>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </div>
      {/* Calendar */}
      <div ref={calendarRef}></div>

      {showForm && (
        <form class="flex form-plan py-2" onSubmit={(e) => { e.preventDefault(); handleSubmit(formData); }}>
          <label>
            Nombre de personnes:
            <input
              type="number"
              name="nbPers"
              min="0"
              value={~~formData.nbPers}
              onChange={handleFormChange}
              required
              className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 ml-2 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
          </label>
          <label>
            Heure de début:
            <input type="time" name="startTime" value={formData.startTime || ''} onChange={handleFormChange} required
              className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 ml-2 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
          </label>
          <label>
            Heure de fin:
            <input type="time" name="endTime" value={formData.endTime || ''} onChange={handleFormChange} required
              className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 ml-2 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
          </label>

          <button class="bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-white rounded-full px-2 py-1 text-xs font-medium my-2" type="submit">{isEditing ? 'Modifer' : 'Valider'}</button>

          {formData.id &&
            <a class="bg-red-100 text-red-700 dark:bg-red-700 dark:text-white rounded-full px-2 py-1 text-xs font-medium my-2" onClick={ () => {remove(formData.id)}}>Supprimer</a>
          }
        </form>
      )}
      </div>
      <div className="my-12"></div>
    </div>
  )
};

export default TimelineCalendar;