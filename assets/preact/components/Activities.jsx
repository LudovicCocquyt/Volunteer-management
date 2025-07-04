import { useState, useEffect } from 'preact/hooks';
import { h } from 'preact';
import { getActivities, addActivity, deleteActivity } from '../routes/ActivitiesRoutes';

const Activities = () => {
    const [activities, setActivities] = useState([]);
    const [open, setOpen]   = useState(false);
    const [form, setForm]   = useState({"name": "", "description": ""});

    useEffect(() => {
        getApiActivities();
    }, []);

    const getApiActivities = async () => {
        const activities = await getActivities();
        setActivities(activities);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        const validate = await addActivity(form);
        if (validate) {
            getApiActivities();
            setForm({"name": "", "description": ""});
            setOpen(false);
        }
    };

    const ApiDeleteActivity = async (id) => {
        const validate = await deleteActivity(id);
        if (validate) {
            getApiActivities();
        }
    };

    const goToUpdateActivity = (id) => {
        window.location.href = '/activities/edit/' + id;
    };

    return (
        <div class="w-full p-2 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <h3 class="w-full flex items-center justify-between p-2 text-gray-900" style="background-color: #a5f9e9;">Activités</h3>
                <div className='flex py-3 ml-2'>
                    <button type="button" onClick={() => setOpen(!open)} style="margin-right: 10px;" class="text-green-700 border border-green-700 hover:bg-green-500 hover:text-white focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:border-green-500 dark:text-green-500 dark:hover:text-white dark:focus:ring-green-800 dark:hover:bg-green-500">
                        <svg class="w-6 h-6 text-white-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                            </svg>
                        <span class="sr-only">Icon plus</span>
                    </button>

                    {open &&
                    <form class="flex w-full" style="max-height: 45px;" onSubmit={handleSubmit}>
                        <input type="text" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" style="width: 50%;" placeholder="Nom de l'activité" required onChange={(e) => setForm({...form, 'name': e.target.value})} />

                        <textarea id="description" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Description ..." onChange={(e) => setForm({...form, 'description': e.target.value})} />

                        <button type="submit" class="bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-full py-1 px-16">Enregistrer</button>
                    </form>
                }
                </div>

                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                            <th scope="col" class="px-6 py-3">Nom</th>
                            <th scope="col" class="px-6 py-3">Description</th>
                            <th scope="col" class="px-6 py-3">
                                Action
                            </th>
                            </tr>
                        </thead>
                        <tbody>
                            {activities && activities.map(activity => (
                                <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                                    <td class="px-6 py-4">{ activity.name }</td>
                                    <td class="px-6 py-4">{ activity.description }</td>
                                    <td class="flex px-6 py-4">
                                        <div dir="ltr">
                                            <button
                                                onClick={() => goToUpdateActivity(activity.id)}
                                                class="bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-s-lg px-2 py-1 text-xs font-medium"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        </div>
                                        <p class="bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-white px-2 py-1 text-xs font-medium text-center" style="min-width: 50px;">
                                            { activity.id }
                                        </p>
                                        <div dir="rtl">
                                            <button
                                                onClick={() => {
                                                    if (confirm(`Êtes-vous sûr de vouloir supprimer (${activity.name}) ?`)) {
                                                        ApiDeleteActivity(activity.id)}
                                                    }
                                                }
                                                class="bg-red-100 text-red-700 dark:bg-red-700 dark:text-white rounded-s-lg px-2 py-1 text-xs font-medium"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                            </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default Activities;
