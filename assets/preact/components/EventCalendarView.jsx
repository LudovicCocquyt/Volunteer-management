import { h } from 'preact';
import { useEffect, useRef, useState } from 'preact/hooks';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import moment from 'moment';
import { getPlans } from '../routes/PlansRoutes';
import { Calendar } from '@fullcalendar/core';

const EventCalendarView = () => {
  const calendarRef                                   = useRef(null);
  const [isDataLoaded, setIsDataLoaded]               = useState(false);
  const [events, setEvents]                           = useState([]);
  const [calendarInstance, setCalendarInstance]       = useState(null);
  const [plans, setPlans]                             = useState({});

  const element           = document.getElementById('EventCalendarView-wrapper');
  const eventPreparedId   = JSON.parse(element.getAttribute('data-eventId'));
  const eventPreparedDate = JSON.parse(element.getAttribute('data-eventStartAt'));
  const startCalendar     = JSON.parse(element.getAttribute('data-startCalendar'));
  const endCalendar       = JSON.parse(element.getAttribute('data-endCalendar'));

  const slotMinTime            = startCalendar ? moment(startCalendar.date).format('HH:mm') : '12:00' //default start time;
  const slotMaxTime            = endCalendar ? moment(endCalendar.date).format('HH:mm') : '23:00';

  useEffect(() => {
    const fetchData = async () => {
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
  }, [calendarInstance]);

  const getApiPlans = async (eventPreparedId) => {
    const plans = await getPlans(eventPreparedId);
    setPlans(plans);

    const d = Object.values(plans).map(plan => (
      plan.map(p => ({
        id: p.id,
        title: p.title,
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


  return (
    <div className="m-2">
      {/* Calendar */}
      <div ref={calendarRef}></div>
    </div>
  )
};

export default EventCalendarView;