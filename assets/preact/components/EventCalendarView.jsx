import { h } from 'preact';
import { useEffect, useRef, useState } from 'preact/hooks';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import moment from 'moment';
import { getPlans } from '../routes/PlansRoutes';
import { Calendar } from '@fullcalendar/core';
import { exportToPdf } from '../utils/ExportToPdf';

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
    exportToPdf(calendarRef.current);
  }

  return (
    <div className="mx-2">
      <div className='flex py-3'>
        {hidePeople && (
          <button onClick={() => displayPeople(true)} className="inline-flex items-center px-4 py-2 mx-1 text-sm font-medium text-center text-white bg-blue-400 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-400" type="button">Afficher les bénévoles
            <i class="fa fa-eye ml-2" aria-hidden="true"></i>
          </button>
        )}
        {!hidePeople && (
          <button onClick={() => displayPeople(false)} className="inline-flex items-center px-4 py-2 mx-1 text-sm font-medium text-center text-white bg-blue-400 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-400" type="button">Masquer les bénévoles
            <i class="fa fa-eye-slash ml-2" aria-hidden="true"></i>
          </button>
        )}

        <button onClick={exportPdf} className="inline-flex items-center px-4 py-2 mx-1 text-sm font-medium text-center text-white bg-pink-400 rounded-lg hover:bg-pink-800 focus:ring-4 focus:outline-none focus:ring-pink-300 dark:bg-pink-600 dark:hover:bg-pink-700 dark:focus:ring-pink-400" type="button">Exporter en PDF
          <i class="fa fa-file-text ml-2" aria-hidden="true"></i>
        </button>
      </div>
      <div className="m-2">
        {/* Calendar */}
        <div ref={calendarRef}></div>
      </div>
    </div>
  )
};

export default EventCalendarView;