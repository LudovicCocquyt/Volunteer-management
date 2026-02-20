import { h, Fragment } from 'preact';
import { useEffect, useRef, useState } from 'preact/hooks';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import moment from 'moment';
import { Calendar } from '@fullcalendar/core';
import { getPlans, addPlan, updatePlan, removePlan, assignVolunteer, getSubscriptions, getOurNeedsByEvent } from '../routes/PlansRoutes';
import { getSubscriptionsByEvent } from '../routes/SubscriptionsRoutes';
import { exportList, exportCalendar } from '../utils/ExportToPdf';
import { exportListXls } from '../utils/ExportToXls';
import { UseWindowSize } from '../utils/UseWindowSize';
import PlansList from './PlansList.jsx';

const TimelineCalendar = () => {
  const calendarRef      = useRef(null);
  const divRef           = useRef(null);
  const nbPersInput      = useRef(null);
  const addActivityInput = useRef(null);

  const [licenseKey, setLicenseKey]                   = useState("");
  const [isLicenseLoaded, setIsLicenseLoaded]         = useState(false);
  const [isDataLoaded, setIsDataLoaded]               = useState(false);
  const [events, setEvents]                           = useState([]);
  const [calendarInstance, setCalendarInstance]       = useState(null);
  const [showForm, setShowForm]                       = useState(false);
  const [formData, setFormData]                       = useState({});
  const [isEditing, setIsEditing]                     = useState(false);
  const [plans, setPlans]                             = useState({});
  const [subscriptions, setSubscriptions]             = useState([]);
  const [subscriptionsInPlan, setSubscriptionsInPlan] = useState([]);
  const [info, setInfo]                               = useState({}); // Event information
  const [isPopoverVisible, setPopoverVisible]         = useState(null); // popover comment vonlunteer
  const [hidePeople, setHidePeople]                   = useState(true);
  const [hidePlans, setHidePlans]                     = useState(false);
  const [planFilterList, setPlanFilterList]           = useState([]);

  const element              = document.getElementById('Scheduler-wrapper');
  const eventPreparedId      = JSON.parse(element.getAttribute('data-eventId'));
  const eventPreparedDate    = JSON.parse(element.getAttribute('data-eventStartAt'));
  const startCalendar        = JSON.parse(element.getAttribute('data-startCalendar'));
  const endCalendar          = JSON.parse(element.getAttribute('data-endCalendar'));
  const nbPlans              = JSON.parse(element.getAttribute('data-nbPlans'));
  const nbSubscriptions      = JSON.parse(element.getAttribute('data-nbSubscriptions'));
  const reservedAvailability = JSON.parse(element.getAttribute('data-reservedAvailability'));
  const widthScreen          = UseWindowSize();

  const slotMinTime            = startCalendar ? moment(startCalendar.date).format('HH:mm') : '12:00' //default start time;
  const slotMaxTime            = endCalendar ? moment(endCalendar.date).format('HH:mm') : '23:00';
  // Add 1 hour in slotMinTime
  const slotMinTimePlusOneHour = startCalendar ? moment(startCalendar.date).add(1, 'hours').format('HH:mm') : '13:00' //default start time;

  const [currentDate, setCurrentDate] = useState(localStorage.getItem('calendarDate') || eventPreparedDate.date);

  useEffect(() => {
    fetch('/api/config')
      .then(res => res.json())
      .then(data => {
        setLicenseKey(data.schedulerLicenseKey);
        setIsLicenseLoaded(true); // indique que la licence est chargée
      })
      .catch(err => {
        console.error('Erreur de chargement de la licence :', err);
      });
  }, []);

  useEffect(() => {
    if (isLicenseLoaded) {
      const fetchData = async () => {
        await getApiPlans(eventPreparedId, false);
        setIsDataLoaded(true); // Refresh the calendar
      };

      fetchData();
    }
  }, [isLicenseLoaded]);

  useEffect(() => {
    if (isDataLoaded && isLicenseLoaded) {
      const calendar = new Calendar(calendarRef.current, calendarConfig);
      calendar.render();
      setCalendarInstance(calendar);
      setIsDataLoaded(false);
    }
  }, [isDataLoaded, isLicenseLoaded]);

  useEffect(() => {
    if (calendarInstance && isLicenseLoaded) {
      calendarInstance.removeAllEvents();
      calendarInstance.addEventSource(events);
    }
  }, [events, calendarInstance, isLicenseLoaded]);

  const getApiPlans = async (eventPreparedId, displayPeople) => {
    const plans = await getPlans(eventPreparedId);
    setPlans(plans);

    const d = Object.values(plans).map(plan => (
      plan.map(p => ({
        id: p.id,
        title: displayPeople ? p.title + p.people : p.title,
        classNames: p.classNames,
        resourceId: p.activityName,
        start: p.startDate,
        end: p.endDate,
        information: p.information
      }))
    ));
    setEvents(d.flat());
  };

  const getOurNeeds = async (eventPreparedId) => {
    const planFilter = await getOurNeedsByEvent(eventPreparedId, true); // includeEmpty true
    setPlanFilterList(planFilter);
  };

  const handleSelectPlan = (plan) => {
    calendarInstance.gotoDate(plan.startDate);
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
    setSubscriptionsInPlan([]); // Reset subscriptionsInPlan when clicking on a date
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
      eventId: eventPreparedId,
      information: info.information || null,
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
      eventId: eventPreparedId,
      information: event.extendedProps.information || null
    });
    setShowForm(true);
    if (widthScreen > 1080) { // Does not give focus on mobile and tablet in edition plan
      setTimeout(() => {
        // After open the form, give focus on the number of people input field
        nbPersInput.current?.focus();
      }, 100);
    }
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

    setTimeout(() => {
        divRef.current.scrollIntoView({ behavior: 'smooth' })
    }, 200);
  };

  const handleFormChange = (e) => {
    const { name, value } = e.target;
    setFormData(prevState => ({
      ...prevState,
      [name]: value
    }));
  };

  const addActivity = async () => {
    const activityName = addActivityInput.current.value;
    if (!activityName) {
      return alert('Veuillez entrer un nom d\'activité.');
    }

    const form = {
      "resourceId": String(activityName),
      "eventId": eventPreparedId,
      "nbPers": 0,
      "start": eventPreparedDate.date,
      "end": eventPreparedDate.date,
      "startTime": slotMinTime,
      "endTime": slotMinTimePlusOneHour
    };

    const validate = await handleSubmit(form);
    if (validate) {
      addActivityInput.current.value = ''; // Clear the input field
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

  // When the calendar date range changes (e.g., user navigates to a different month/week/day)
  const handleDatesSet = (arg) => {
    const newDate = arg.startStr;
    if (localStorage.getItem('calendarDate') == null) {
      localStorage.setItem('calendarDate', eventPreparedDate.date);
    } else {
      localStorage.setItem('calendarDate', newDate);
    }
    setCurrentDate(newDate);
  };

  const calendarConfig = {
    schedulerLicenseKey: licenseKey,
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
    headerToolbar: {
      left: 'resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth',
      center: 'title',
      right: 'prev,next',
    },
    initialDate: currentDate,
    datesSet: handleDatesSet,
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

  const showPlans = async () => {
    await getOurNeeds(eventPreparedId);
  };

  const togglePlans = () => {
    setHidePlans(prev => {
      const newValue = !prev;

      if (newValue) {
        getOurNeeds(eventPreparedId);
      }

      return newValue;
    });
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
    <div className="relative overflow-x-auto shadow-md rounded-lg">
      <h3 className="w-full flex items-center justify-between p-2 text-gray-900 " style="background-color: #e9eb91;">Planification
        <span>{nbSubscriptions} Bénévole{nbSubscriptions > 1 ? "s" : ""} ({reservedAvailability}/{nbPlans})</span>
      </h3>
    <div className="mx-2">
      <div className="md:flex rounded-md shadow-xs py-3" role="group">
        <div className={`flex flex-wrap rounded-md shadow-xs py-3 justify-center md:justify-start ${widthScreen < 878 ? 'gap-2' : ''}`} role="group">

          {/* Bouton Ajouter des activités */}
          <div className={`flex ${widthScreen < 768 ? 'w-full' : ''}`}>
            <input
                type        = "text"
                id          = "addActivityInput"
                ref         = {addActivityInput}
                className   = {`md:w-auto px-4 py-2 text-sm font-medium flex justify-center items-center text-white bg-green-400 hover:bg-green-800 focus:outline-none focus:ring-0 rounded-s-lg ${widthScreen < 768 ? 'w-full' : ''} ${widthScreen < 878 ? 'text-center' : ''}`}
                placeholder = " Ajouter une activité"
                onKeyDown   = {e => {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                    addActivity();
                  }
                }}
            />
            <button
              id="addActivityButton"
              type="button"
              className={`bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 ${widthScreen < 878 ? 'rounded-e-lg' : ''} `}
              onClick={() => {addActivity()}}
            >
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>

          {/* Afficher/Masquer bénévoles */}
          <button
            onClick={() => displayPeople(hidePeople)}
            className={`w-full md:w-auto px-4 py-2 text-sm font-medium flex justify-center items-center text-white bg-blue-400 hover:bg-blue-800 ${ widthScreen < 878 ? 'rounded-lg' : '' }`}
            type="button"
          >
            {hidePeople ? 'Afficher les bénévoles' : 'Masquer les bénévoles'}
            <i className={`fa ${hidePeople ? 'fa-eye' : 'fa-eye-slash'} ml-2`} aria-hidden="true"></i>
          </button>

          {/* Afficher/Masquer créneaux */}
          <button
            onClick={togglePlans}
            className={`w-full md:w-auto px-4 py-2 text-sm font-medium flex justify-center items-center text-white bg-blue-400 hover:bg-blue-800 ${ widthScreen < 878 ? 'rounded-lg' : '' }`}
            type="button"
          >
            {!hidePlans ? 'Afficher les créneaux' : 'Masquer les créneaux'}
            <i className={`fa ${!hidePlans ? 'fa-eye' : 'fa-eye-slash'} ml-2`} aria-hidden="true"></i>
          </button>

          {/* Exporter */}
          <button
            id="dropdownExport"
            data-dropdown-toggle="exportTo"
            className={`w-full md:w-auto px-4 py-2 text-sm font-medium flex justify-center items-center text-white bg-pink-400 hover:bg-pink-800 ${ widthScreen < 878 ? 'rounded-lg' : 'rounded-e-lg'} `}
            type="button"
          >
            Exporter
            <i className="fa fa-file-text ml-2" aria-hidden="true"></i>
            <svg className="w-2.5 h-2.5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
              <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="m1 1 4 4 4-4"/>
            </svg>
          </button>
        </div>

        <div id="exportTo" className="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
            <ul className="px-3 pb-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownExport">
              <li>
                <span onClick={() => exportData("calendar")} className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100">Planning.pdf</span>
              </li>
              <li>
                <span onClick={() => exportData("list")} className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100">Liste.pdf</span>
              </li>
              <li>
                <span onClick={() => exportData("xls")} className="cursor-pointer flex items-center p-2 rounded hover:bg-gray-100">Liste.xls</span>
              </li>
            </ul>
        </div>
      </div>

      {hidePlans &&
        <PlansList plans={planFilterList} onSelectPlan={handleSelectPlan} />
      }
      {/* Calendar */}
      <div ref={calendarRef}></div>

      {showForm && (
        <div className="pt-5">
          <form
            className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-4 rounded border shadow"
            style={{ backgroundColor: '#e9eb91' }}
            onSubmit={(e) => {
              e.preventDefault()
            }}
            >
            <label>
              Nb de personnes
              <input
                ref={nbPersInput}
                type="number"
                name="nbPers"
                min="0"
                value={~~formData.nbPers}
                onChange={handleFormChange}
                required
                className="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 mt-1 p-2.5"
              />
            </label>

            <label>
              Heure de début
              <input
                type="time"
                name="startTime"
                {...(subscriptionsInPlan.length > 0 ? { disabled: true } : {})}
                value={formData.startTime || ''}
                onChange={handleFormChange}
                required
                className={`w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 mt-1 p-2.5 ${
                  subscriptionsInPlan.length > 0 ? '!bg-gray-200' : ''
                }`}
              />
            </label>

            <label>
              Heure de fin
              <input
                type="time"
                name="endTime"
                {...(subscriptionsInPlan.length > 0 ? { disabled: true } : {})}
                value={formData.endTime || ''}
                onChange={handleFormChange}
                required
                className={`w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 mt-1 p-2.5 ${
                  subscriptionsInPlan.length > 0 ? '!bg-gray-200' : ''
                }`}
              />
            </label>

            <label>
              Information
                <div className="relative group inline-block ml-2">
                  <i className="fa-solid fa-circle-info text-blue-600"></i>
                  {/* Tooltip */}
                  <div className="absolute z-10 left-1/2 -translate-x-1/2 mt-2 px-3 py-2 text-sm text-white bg-gray-900 rounded shadow-lg opacity-0 invisible   transition-opacity duration-200 group-hover:opacity-100 group-hover:visible">
                    Veuillez noter que cette information s'applique à tous les créneaux du même horaire.
                  </div>
                </div>
              <input
                type="text"
                name="information"
                placeholder="10 caractères max"
                value={formData.information}
                onChange={handleFormChange}
                className="w-full border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 mt-1 p-2.5"
              />
            </label>

            <label>
              Nom de l'activité
              <input
                type="text"
                name="resourceId"
                {...(formData.id == undefined ? { disabled: true } : {})}
                value={formData.resourceId}
                onChange={handleFormChange}
                required
                className={`w-full ${formData.id == undefined ? 'bg-gray-300' : 'bg-gray-50'} border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 mt-1 p-2.5`}
              />
            </label>

            <button
              className="bg-green-100 text-green-700 hover:bg-green-800 hover:text-white rounded-lg py-2 px-5 sm:col-span-2 lg:col-span-1 mt-2"
              type="submit"
              onClick={async () => {
              await handleSubmit(formData);
              setIsDataLoaded(true); // Rafraîchir le calendrier
              if (hidePlans)
                await showPlans(); // on force l’affichage proprement
              }}
            >
              {isEditing ? 'Modifier' : 'Enregistrer'}
            </button>

            {formData.id && subscriptionsInPlan.length < 1 && (
              <button
                className="bg-red-100 text-red-700 hover:bg-red-800 hover:text-white rounded-lg py-2 px-5 sm:col-span-2 lg:col-span-1 mt-2"
                onClick={() => remove(formData.id)}
                >
                Supprimer
              </button>
            )}
            </form>

            <hr className={`${isEditing ? "my-2" : "hidden" }`} />
            {isEditing &&
            (subscriptions.length > 0 || subscriptionsInPlan.length > 0) &&
            <div id="subscriptions-wrapper"
              className="flex flex-col md:flex-row gap-4 items-start p-4 rounded border shadow"
              style="background-color: rgb(233, 235, 145);"
            >
              {/* List of available volunteers  */}
              <div class="w-full md:w-[45%]">
                <div className="bg-green-100 p-2 rounded-lg border shadow">
                  <h3 className="text-center">Bénévoles disponibles</h3>
                  <hr className="mb-2" />
                  <div className={`grid grid-cols-1  ${subscriptions.length > 0 ? "sm:grid-cols-2 md:grid-cols-2" : ""} gap-2`}>
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
                            <svg data-popover-target={`popover-${sub.id}`} className="pl-2 w-6 h-6 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 18" onMouseEnter={() => setPopoverVisible(sub.id)} onMouseLeave={() => setPopoverVisible(null)} >
                              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h9M5 9h5m8-8H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h4l3.5 4 3.5-4h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1Z" />
                            </svg>

                            <div id={`popover-${sub.id}`} role="tooltip" className={`absolute z-10 inline-block w-64 text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm ${isPopoverVisible == sub.id ? 'opacity-100 visible' : 'opacity-0 invisible'}`} >
                              <div className="px-3 py-2"> <p>{sub.comment}</p></div>
                              <div data-popper-arrow></div>
                            </div>
                          </div>
                        }
                      </div>
                      ))
                    ) : (
                    <div className="border rounded-lg shadow p-2 text-center">
                      <p className=' text-sm'><span>Aucun bénévole pour le moment</span></p>
                    </div>
                  )}
                  </div>
                </div>
              </div>
              {/* Center icon change */}
              <div className={`w-full md:w-auto flex justify-center items-center ${ widthScreen > 767 ? "my-3" : ""}`}>
                <svg className={`w-6 h-6 text-gray-800 ${ widthScreen < 768 ? "rotate-90" : ""}`} aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 18">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 14 3-3m-3 3 3 3m-3-3h16v-3m2-7-3 3m3-3-3-3m3 3H3v3"/>
                </svg>
              </div>
              {/* List of volunteers assigned */}
              <div class="w-full md:w-[45%]">
                <div className="bg-blue-100 p-2 rounded-lg border shadow">
                  <h3 className="text-center">Bénévoles assignés</h3>
                  <hr className="mb-2" />
                  <div className={`grid grid-cols-1 ${subscriptionsInPlan.length > 0 ? "sm:grid-cols-2 md:grid-cols-2" : ""} gap-4`}>
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
                        <div className="border rounded-lg shadow p-2 text-center">
                          <p className='text-sm'><span>Aucun bénévole assigné pour le moment</span></p>
                        </div>
                    )}
                    </div>
                  </div>
                </div>
              </div>
            }
            {
              subscriptions.length === 0 && subscriptionsInPlan.length === 0 &&
              formData && "id" in formData &&
                <div
                  className="p-4 rounded border shadow text-center"
                  style="background-color: rgb(233, 235, 145);"
                >Aucun bénévole disponible pour le moment !
                </div>
            }
          </div>
        )}
        </div>
      <div className="my-12" ref={divRef}></div>
    </div>
  )
};

export default TimelineCalendar;