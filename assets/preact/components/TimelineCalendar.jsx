import { h } from 'preact';
import { useEffect, useRef, useState } from 'preact/hooks';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import moment from 'moment';
import { Calendar } from '@fullcalendar/core';
import { getPlans, addPlan, updatePlan, removePlan, assignVolunteer, getSubscriptions } from '../routes/PlansRoutes';
import { getSubscriptionsByEvent } from '../routes/SubscriptionsRoutes';
import { getActivities } from '../routes/ActivitiesRoutes';
import { exportList, exportCalendar } from '../utils/ExportToPdf';
import { exportListXls } from '../utils/ExportToXls';


const TimelineCalendar = () => {
  const calendarRef                                   = useRef(null);
  const divRef                                        = useRef(null);
  const nbPersInput                                   = useRef(null);

  const [activities, setActivities]                   = useState([]);
  const [isDataLoaded, setIsDataLoaded]               = useState(false);
  const [events, setEvents]                           = useState([]);
  const [calendarInstance, setCalendarInstance]       = useState(null);
  const [showForm, setShowForm]                       = useState(false);
  const [formData, setFormData]                       = useState({});
  const [isEditing, setIsEditing]                     = useState(false);
  const [plans, setPlans]                             = useState({});
  const [subscriptions, setSubscriptions]             = useState([]);
  const [subscriptionsInPlan, setSubscriptionsInPlan] = useState([]);
  const [info, setInfo]                               = useState({});     // Event information
  const [isPopoverVisible, setPopoverVisible]         = useState(null);   // popover comment vonlunteer
  const [hidePeople, setHidePeople]                   = useState(true);

  const activitiesActive  = Object.keys(plans).map(key => key) || [];
  const element           = document.getElementById('Scheduler-wrapper');
  const eventPreparedId   = JSON.parse(element.getAttribute('data-eventId'));
  const eventPreparedDate = JSON.parse(element.getAttribute('data-eventStartAt'));
  const startCalendar     = JSON.parse(element.getAttribute('data-startCalendar'));
  const endCalendar       = JSON.parse(element.getAttribute('data-endCalendar'));
  const nbPlans           = JSON.parse(element.getAttribute('data-nbPlans'));
  const nbSubscriptions   = JSON.parse(element.getAttribute('data-nbSubscriptions'));
  const reservedAvailability   = JSON.parse(element.getAttribute('data-reservedAvailability'));

  const slotMinTime            = startCalendar ? moment(startCalendar.date).format('HH:mm') : '12:00' //default start time;
  const slotMaxTime            = endCalendar ? moment(endCalendar.date).format('HH:mm') : '23:00';
  // Add 1 hour in slotMinTime
  const slotMinTimePlusOneHour = startCalendar ? moment(startCalendar.date).add(1, 'hours').format('HH:mm') : '13:00' //default start time;

  useEffect(() => {
    const fetchData = async () => {
      await getApiActivities();
      await getApiPlans(eventPreparedId, false);
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

  const getApiPlans = async (eventPreparedId, displayPeople) => {
    const plans = await getPlans(eventPreparedId);
    setPlans(plans);

    const d = Object.values(plans).map(plan => (
      plan.map(p => ({
        id: p.id,
        title: displayPeople ? p.title + p.people : p.title,
        classNames: p.classNames,
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

  const getApiSubscriptions = async (start, end) => {
    const params = { "eventId": eventPreparedId, "start": start, "end": end };
    const subscriptions = await getSubscriptionsByEvent(params);
    setSubscriptions(subscriptions.flat());
  };

  const getApiPlanSubscriptions = async (planId) => {
    const volunteers = await getSubscriptions(planId);
    setSubscriptionsInPlan(volunteers);
  };

  const handleSubmit = async (form) => {
    let validate = false
    if (form.id) {
      validate = await updatePlan(form);
    } else {
      validate = await addPlan(form);
    }

    if (validate) {
      await getApiPlans(eventPreparedId, false);
      setShowForm(false);
      setIsEditing(false);
      return validate;
    }
  };

  const remove = async (planId) => {
    if (planId) {
      const validate = await removePlan(planId);
      if (validate) {
        await getApiPlans(eventPreparedId, false);
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
    setTimeout(() => {
      // After open the form, give focus on the number of people input field
      nbPersInput.current?.focus();
    }, 100);
    setIsEditing(false);
    currenPlan(false, info);
  };

  const handleEventClick = (info) => {
    const event     = info.event;
    const startTime = new Date(event.start).toISOString().substring(11, 16);
    const endTime   = event.end ? new Date(event.end).toISOString().substring(11, 16) : '';
    setFormData({
      id: event.id,
      nbPers: event.title.split('/')[1],
      start: event.start.toISOString().substring(0, 10),
      end: event.end ? event.end.toISOString().substring(0, 10) : '',
      startTime: startTime,
      endTime: endTime,
      resourceId: event._def.resourceIds[0],
      eventId: eventPreparedId
    });
    setShowForm(true);
    setTimeout(() => {
      // After open the form, give focus on the number of people input field
      nbPersInput.current?.focus();
    }, 100);
    setIsEditing(true);
    getApiPlanSubscriptions(info.event._def.publicId);
    getApiSubscriptions(new Date(event.start).toISOString(), new Date(event.end).toISOString());
    setInfo(info);
    currenPlan(true, info);
  };

  const currenPlan = (bool, info) => {
    // Remove the className bg-green-800 from all elements
    const elements = document.getElementsByClassName('bg-green-800');
    for (let i = 0; i < elements.length; i++) {
      elements[i].classList.remove('bg-green-800');
    }

    if (bool)
      info.el.classList.add('bg-green-800'); // Add the className to the clicked element

      divRef.current.scrollIntoView({ behavior: 'smooth' })
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
      "startTime": slotMinTime,
      "endTime": slotMinTimePlusOneHour
    };

    const validate = await handleSubmit(form);
    if (validate) {
      await getApiPlans(eventPreparedId, false);
      setIsDataLoaded(true); // Refresh the calendar
    }
  };

  const AddAndRemoveVolunteers = async (assign, planId, subscription) => {
    const validate = await assignVolunteer({ "assign": assign, "planId": planId, "subscription": subscription });
    if (validate) {
      handleEventClick(info); // Refresh the list of volunteers
      await getApiPlans(eventPreparedId, false);
      setIsDataLoaded(true); // Refresh the calendar
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
    slotMinTime: slotMinTime,
    slotMaxTime: slotMaxTime,
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
  };

  const displayPeople = async(display) => {
    if (display) {
      await getApiPlans(eventPreparedId, true);
      setHidePeople(false);
    } else {
      await getApiPlans(eventPreparedId, false);
      setHidePeople(true);
    }
  };

  const exportData = (type) => {
    if (Object.keys(plans).length === 0)
      return alert('Aucune planification à exporter! \nVeuillez ajouter des activités.');

    if (type === 'calendar')
      exportCalendar(calendarRef.current); // Export the calendar to PDF

    if (type === 'list') {
      const data = Object.fromEntries(Object.entries(plans).reverse()); // Reverse the order of the plans
      exportList(data); // Export the data to PDF
    }
    if (type === 'xls') {
      exportListXls(plans);
    }
  }

  return (
    <div className="relative overflow-x-auto shadow-md sm:rounded-lg">
      <h3 className="w-full flex items-center justify-between p-2 text-gray-900 " style="background-color: #e9eb91;">Planification
        <span>{nbSubscriptions} Bénévole{nbSubscriptions > 1 ? "s" : ""} ({reservedAvailability}/{nbPlans})</span>
      </h3>
    <div className="mx-2">
      <div className="inline-flex rounded-md shadow-xs flex py-3" role="group">
        <button id="dropdownSearchButton" data-dropdown-toggle="activities_choose" className="px-4 text-sm font-medium rounded-s-lg inline-flex items-center text-center text-white bg-green-400 hover:bg-green-800 dark:bg-green-600 dark:hover:bg-green-700" type="button">
          Ajouter des activités
          <svg className="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/></svg>
        </button>
        {hidePeople && (
          <button onClick={() => displayPeople(true)} className="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-400 hover:bg-blue-800" type="button">Afficher les bénévoles
            <i className="fa fa-eye ml-2" aria-hidden="true"></i>
          </button>
        )}
        {!hidePeople && (
          <button onClick={() => displayPeople(false)} className="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-400 hover:bg-blue-800" type="button">Masquer les bénévoles
            <i className="fa fa-eye-slash ml-2" aria-hidden="true"></i>
          </button>
        )}

        <button id="dropdownExport" data-dropdown-toggle="exportTo" className="px-4 text-sm font-medium rounded-e-lg inline-flex items-center text-white bg-pink-400 hover:bg-pink-800 dark:bg-pink-600 dark:hover:bg-pink-700" type="button">
          Exporter
          <i className="fa fa-file-text ml-2" aria-hidden="true"></i>
          <svg className="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
          </svg>
        </button>

        <div id="exportTo" className="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700">
            <ul className="px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownExport">
              <li>
                <span onClick={() => exportData("calendar")} className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">Planning.pdf</span>
              </li>
              <li>
                <span onClick={() => exportData("list")} className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">Liste.pdf</span>
              </li>
              <li>
                <span onClick={() => exportData("xls")} className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">Liste.xls</span>
              </li>
            </ul>
        </div>

        <div id="activities_choose" className="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
          <ul className="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButton">
            {activities && activities.map((activity) => (
              <li>
                <div className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
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
        <div className="pt-5">
          <form className="flex form-plan py-2 rounded border rounded-lg shadow" style="background-color: #e9eb91;" onSubmit={(e) => { e.preventDefault(); handleSubmit(formData); }}>
            <p className="flex items-center capitale underline">{formData.resourceId}:</p>
            <label>
              Nombre de personnes:
              <input
                ref={nbPersInput}
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
              <input
                type="time"
                name="startTime"
                {...(subscriptionsInPlan.length > 0 ? { disabled: true } : {})}
                value={formData.startTime || ''}
                onChange={handleFormChange}
                required
                className={`bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 ml-2 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ${subscriptionsInPlan.length > 0 ? "!bg-gray-200" : ""}`}
              />
            </label>
            <label>
              Heure de fin:
              <input
                type="time"
                name="endTime"
                {...(subscriptionsInPlan.length > 0 ? { disabled: true } : {})}
                value={formData.endTime || ''}
                onChange={handleFormChange}
                required
                className={`bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 ml-2 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ${subscriptionsInPlan.length > 0 ? "!bg-gray-200" : ""}`}
              />
            </label>

            <button className="bg-green-100 text-green-400 dark:bg-green-400 dark:text-white hover:bg-green-800 hover:text-white rounded-full py-1 px-5" type="submit">{isEditing ? 'Modifier' : 'Enregistrer'}</button>

            {formData.id &&
             subscriptionsInPlan.length < 1 &&
              <button className="bg-red-100 text-red-700 dark:bg-red-700 dark:text-white rounded-full py-1 px-5 hover:bg-red-800 hover:text-white" onClick={ () => {remove(formData.id)}}>Supprimer</button>
            }
          </form>
          <hr className={`${isEditing ? "my-2" : "hidden" }`} />
          {isEditing &&
            (subscriptions.length > 0 || subscriptionsInPlan.length > 0) &&
            <div id="subscriptions-wrapper" className="pt-2 flex justify-between items-center">
              {/* List of available volunteers  */}
              <div className={`grid ${ subscriptions.length > 0 ? "grid-cols-4" : "grid-cols-2"} gap-4 bg-green-100 p-2 rounded border rounded-lg shadow w-full`}>
              {subscriptions.length > 0 ? (
                subscriptions.map((sub, key) => (
                  <div
                    key={key}
                    id={sub.id}
                    className="flex border rounded-lg shadow p-2 text-center"
                    onClick={() => {
                      AddAndRemoveVolunteers(true, formData.id, sub)
                    }}
                    >
                    <p className='w-full text-sm'><span>{sub.lastname + " " + sub.firstname }</span></p>
                    {sub.comment && sub.comment.length > 0 &&
                      <div className="relative">
                        <svg data-popover-target={`popover-${sub.id}`} className="pl-2 w-6 h-6 text-blue-500 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 18" onMouseEnter={() => setPopoverVisible(sub.id)} onMouseLeave={() => setPopoverVisible(null)} >
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h9M5 9h5m8-8H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h4l3.5 4 3.5-4h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1Z" />
                        </svg>

                        <div id={`popover-${sub.id}`} role="tooltip" className={`absolute z-10 inline-block w-64 text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800 ${isPopoverVisible == sub.id ? 'opacity-100 visible' : 'opacity-0 invisible'}`} >
                          <div className="px-3 py-2"> <p>{sub.comment}</p></div>
                          <div data-popper-arrow></div>
                        </div>
                      </div>
                    }
                  </div>
                  ))
                ) : (
                <div className="border rounded-lg shadow p-2 text-center">
                  <p className='w-full text-sm'><span>Aucun bénévole disponible pour le moment</span></p>
                </div>
              )}
              </div>
              {/* Center icon change */}
              <div className="flex justify-center mx-4 my-4">
                <svg className="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 18">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 14 3-3m-3 3 3 3m-3-3h16v-3m2-7-3 3m3-3-3-3m3 3H3v3"/>
                </svg>
              </div>
              {/* List of volunteers assigned */}
              <div className={`grid ${ subscriptionsInPlan.length > 0 ? "grid-cols-4" : "grid-cols-2"} gap-4 bg-blue-100 p-2 rounded border rounded-lg shadow w-full`}>
                {subscriptionsInPlan.length > 0 ? (
                  subscriptionsInPlan.map((sub, key) => (
                  <div
                    key={key}
                    id={sub.id}
                    className="flex border rounded-lg shadow p-2 text-center"
                    onClick={() => {
                      AddAndRemoveVolunteers(false, formData.id, sub)
                    }}
                    >
                      <p className='w-full text-sm'><span>{ sub.lastname + " " + sub.firstname }</span></p>
                      {sub.comment && sub.comment.length > 0 &&
                      <div className="relative">
                        <svg data-popover-target={`popover-${sub.id}`} className="pl-2 w-6 h-6 text-blue-500 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 18" onMouseEnter={() => setPopoverVisible(sub.id)} onMouseLeave={() => setPopoverVisible(null)} >
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h9M5 9h5m8-8H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h4l3.5 4 3.5-4h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1Z" />
                        </svg>

                        <div id={`popover-${sub.id}`} role="tooltip" className={`absolute z-10 inline-block w-64 text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800 ${isPopoverVisible == sub.id ? 'opacity-100 visible' : 'opacity-0 invisible'}`} >
                          <div className="px-3 py-2"> <p>{sub.comment}</p></div>
                          <div data-popper-arrow></div>
                        </div>
                      </div>
                    }
                  </div>
                  ))
                ) : (
                    <div className="border rounded-lg shadow p-2">
                      <p className='text-sm'><span>Aucun bénévole assigné pour le moment</span></p>
                    </div>
                )}
                </div>
              </div>
            }
            {subscriptions.length === 0 && subscriptionsInPlan.length === 0 &&
              <div className="text-center text-sm text-gray-600 dark:text-gray-300">Aucun bénévole disponible pour le moment !</div>
            }
          </div>
        )}
        </div>
      <div className="my-12" ref={divRef}></div>
    </div>
  )
};

export default TimelineCalendar;