import { useState, useEffect } from 'preact/hooks';
import { h } from 'preact';
import { getEvents } from '../routes/EventsRoutes';

const Activities = () => {
    const [events, setEvents] = useState([]);

    useEffect(() => {
        getApiEvents();
    }, []);

    const getApiEvents = async () => {
        const events = await getEvents();
        setEvents(events);
    };

    const goToNewEvent = () => {
        window.location.href = '/events/new';
    };

    const goToUpdateEvent = (id) => {
        window.location.href = '/events/' + id;
    };

    return (
        <div class="w-full p-2 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
             <div class="text-2xl font-bold text-gray-800 dark:text-white">
                <div className='flex justify-end py-3'>
                    <button type="button" onClick={() => goToNewEvent()} style="margin-right: 10px;" class="text-blue-700 border border-blue-700 hover:bg-blue-700 hover:text-white focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-1 text-center inline-flex items-center dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:focus:ring-blue-800 dark:hover:bg-blue-500">
                        <svg class="w-3 h-3 text-white-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                            </svg>
                        <span class="sr-only">Icon plus</span>
                    </button>
                </div>
            </div>
            <ul class="space-y-1 ">
                { events && events.map((event) => (
                    <li>
                        <button onClick={() => goToUpdateEvent(event.id)} class={`w-full flex items-center justify-between p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group ${ event.published ? "bg-pink-200" : ""}`}>
                            <span class="ms-3">{event.name}</span>
                            <svg class="flex-shrink-0 w-5 h-5 text-pink-400 transition duration-75 dark:text-pink-200 group-hover:text-pink-600 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 8 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 13 5.7-5.326a.909.909 0 0 0 0-1.348L1 1"/>
                            </svg>
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default Activities;
