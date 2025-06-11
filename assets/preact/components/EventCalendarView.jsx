import { h } from 'preact';
import { useEffect, useRef, useState } from 'preact/hooks';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import moment from 'moment';
import { getPlans } from '../routes/PlansRoutes';
import { Calendar } from '@fullcalendar/core';
import { exportList, exportCalendar } from '../utils/ExportToPdf';
import { exportListXls } from '../utils/ExportToXls';

const EventCalendarView = () => {
  const calendarRef                             = useRef(null);
  const [isDataLoaded, setIsDataLoaded]         = useState(false);
  const [events, setEvents]                     = useState([]);
  const [calendarInstance, setCalendarInstance] = useState(null);
  const [plans, setPlans]                       = useState({});
  const [hidePeople, setHidePeople]             = useState(true);

  const element           = document.getElementById('EventCalendarView-wrapper');
  const eventPreparedId   = JSON.parse(element.getAttribute('data-eventId'));
  const eventPreparedDate = JSON.parse(element.getAttribute('data-eventStartAt'));
  const startCalendar     = JSON.parse(element.getAttribute('data-startCalendar'));
  const endCalendar       = JSON.parse(element.getAttribute('data-endCalendar'));

  const slotMinTime = startCalendar ? moment(startCalendar.date).format('HH:mm') : '12:00'  //default start time;
  const slotMaxTime = endCalendar ? moment(endCalendar.date).format('HH:mm') : '23:00';

  useEffect(() => {
    const fetchData = async () => {
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

    console.log(displayPeople);
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

  const exportPdf = () => {
    if (Object.keys(plans).length === 0)
      return alert('Aucune planification à exporter! \nVeuillez ajouter des activités.');

    const data = Object.fromEntries(Object.entries(plans).reverse()); // Reverse the order of the plans
    exportVolunteers(data); // Export the data to PDF
  }

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
    <div className="mx-2">
<div className="inline-flex rounded-md shadow-xs flex py-3" role="group">
        {hidePeople && (
          <button onClick={() => displayPeople(true)} className="rounded-s-lg inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-400 hover:bg-blue-800" type="button">Afficher les bénévoles
            <i className="fa fa-eye ml-2" aria-hidden="true"></i>
          </button>
        )}
        {!hidePeople && (
          <button onClick={() => displayPeople(false)} className="rounded-s-lg inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-400 hover:bg-blue-800" type="button">Masquer les bénévoles
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
      </div>
      <div className="m-2">
        {/* Calendar */}
        <div ref={calendarRef}></div>
      </div>
    </div>
  )
};

export default EventCalendarView;