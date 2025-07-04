import { useState, useEffect } from 'preact/hooks';
import { h } from 'preact';
import { getEvents } from '../routes/EventsRoutes';

const Archived = () => {
    const [events, setEvents] = useState([]);

    useEffect(() => {
        getApiEvents();
    }, []);

    const getApiEvents = async () => {
        const events = await getEvents("archived");
        setEvents(events);
    };

    const goToUpdateEvent = (id) => {
        window.location.href = '/events/' + id + '/edit';
    };

    const goToEvent = (id) => {
        window.location.href = '/events/' + id;
    };

    return (
        <div class="w-full p-2 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <h3 class="w-full flex items-center justify-between p-2 text-gray-900" style="background-color: #f9cee8;">Archives</h3>
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                        <th scope="col" class="px-6 py-3">Nom</th>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3">Lieu</th>
                            <th scope="col" class="px-6 py-3">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {events && events.map(event => (
                                <tr>
                                    <td class="px-6 py-4">{ event.name }</td>
                                    <td class="px-6 py-4">{ event.date }</td>
                                    <td class="px-6 py-4">{ event.location }</td>
                                    <td class="flex px-6 py-4">
                                        <div dir="ltr">
                                            <button
                                                onClick={() => goToEvent(event.id)}
                                                class="bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-white rounded-s-lg px-2 py-1 text-xs font-medium"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                        <p class="bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-white px-2 py-1 text-xs font-medium text-center" style="min-width: 50px;">
                                            { event.id }
                                        </p>
                                        <div dir="rtl">
                                            <button
                                                onClick={() => goToUpdateEvent(event.id)}
                                                class="bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-s-lg px-2 py-1 text-xs font-medium"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default Archived;