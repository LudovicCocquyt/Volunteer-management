/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/tinymce.css';
import { h, render } from 'preact';
import Users from './preact/components/Users';
import Activities from './preact/components/Activities';
import Events from './preact/components/Events';
import Archived from './preact/components/Archived';
import TimelineCalendar from './preact/components/TimelineCalendar';
import ChoiceOfVolunteers from './preact/components/ChoiceOfVolunteers';
import EventCalendarView from './preact/components/EventCalendarView';

const usersWrapper              = document.getElementById('Users-wrapper');
const activitiesWrapper         = document.getElementById('Activities-wrapper');
const eventsWrapper             = document.getElementById('Events-wrapper');
const archivedWrapper           = document.getElementById('Archived-wrapper');
const schedulerWrapper          = document.getElementById('Scheduler-wrapper');
const ChoiceOfVolunteersWrapper = document.getElementById('ChoiceOfVolunteers-wrapper');
const EventCalendarViewWrapper  = document.getElementById('EventCalendarView-wrapper');

if (usersWrapper)
    render(<Users />, usersWrapper);

if (activitiesWrapper)
    render(<Activities />, activitiesWrapper);

if (eventsWrapper)
        render(<Events />, eventsWrapper);

if (archivedWrapper)
    render(<Archived />, archivedWrapper);

if (schedulerWrapper)
    render(<TimelineCalendar />, schedulerWrapper);

if (ChoiceOfVolunteersWrapper)
    render(<ChoiceOfVolunteers />, ChoiceOfVolunteersWrapper);

if (EventCalendarViewWrapper)
    render(<EventCalendarView />, EventCalendarViewWrapper);

