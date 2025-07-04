import { useState, useEffect } from 'preact/hooks';
import { h } from 'preact';
import { getUsers, addUser, deleteUser } from '../routes/UsersRoutes';

const Users = () => {
    const allRoles = {
        'ROLE_USER'     : 'Utilisateur'
    };
    const [users, setUsers] = useState([]);
    const [open, setOpen]   = useState(false);
    const [form, setForm]   = useState({"email": "", "firstname": "", "lastname": "", "password": ""});

    useEffect(() => {
        getApiUsers();
    }, []);

    const getApiUsers = async () => {
        const users = await getUsers();
        setUsers(users);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        const validate = await addUser(form);
        if (validate) {
            getApiUsers();
            setForm({"email": "", "firstname": "", "lastname": "", "password": ""});
            setOpen(false);
        }
    };

    const ApiDeleteUser = async (id) => {
        const validate = await deleteUser(id);
        if (validate) {
            getApiUsers();
        }
    };

    const goToUpdateUser = (id) => {
        window.location.href = '/user/edit/' + id;
    };

    const generatePassword = (length) => {
        const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#/-()&*";
        let password = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    return (
        <div class="w-full p-2 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <h3 class="w-full flex items-center justify-between p-2 text-gray-900" style="background-color: #f9ecce;">Utilisateurs</h3>
                <div className='md:flex py-3 ml-2'>
                    <button type="button" onClick={() => setOpen(!open)} style="margin-right: 10px;"
                    class="text-yellow-700 border border-yellow-700 hover:bg-yellow-700 hover:text-yellow-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:border-yellow-500 dark:text-yellow-500 dark:hover:text-white dark:focus:ring-yellow-800 dark:hover:bg-yellow-500">
                        <svg class="w-6 h-6 text-white-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                            </svg>
                        <span class="sr-only">Icon plus</span>
                    </button>

                    {open &&
                        <form class="md:flex" onSubmit={handleSubmit}>
                            <input type="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ml-1 mr-1" placeholder="apel@gmail.com" required onChange={(e) => setForm({...form, 'email': e.target.value})} />

                            <input type="text" id="firstname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Nom" required onChange={(e) => setForm({...form, 'firstname': e.target.value})} />

                            <input type="text" id="lastname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Prénom" required onChange={(e) => setForm({...form, 'lastname': e.target.value})} />

                            <input type="text" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500  ml-1 mr-1" required value={generatePassword(6)} onChange={(e) => setForm({...form, 'password': e.target.value})} />

                            <button type="submit" class="bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-full py-1 px-16">Enregistrer</button>
                        </form>
                    }
                </div>

                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                            <th scope="col" class="px-6 py-3">Email</th>
                            <th scope="col" class="px-6 py-3">Nom & prénom</th>
                            <th scope="col" class="px-6 py-3">Rôle</th>
                                <th scope="col" class="px-6 py-3">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {users && users.map(user => (
                                <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                                    <td class="px-6 py-4">{ user.email }</td>
                                    <td class="px-6 py-4">{ user.firstname } { user.lastname }</td>
                                    <td class="px-6 py-4">
                                        { user.roles?.map((role, key) => (
                                            <span key={key} class="mx-2 bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-full px-2 py-1 text-xs font-medium">{ allRoles[role] ?? role  }</span>
                                        ))}
                                    </td>
                                    <td class="flex px-6 py-4">
                                        <div dir="ltr">
                                            <button
                                                onClick={() => goToUpdateUser(user.id)}
                                                class="bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-s-lg px-2 py-1 text-xs font-medium"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        </div>
                                        <p class="bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-white px-2 py-1 text-xs font-medium text-center" style="min-width: 50px;">
                                            { user.id }
                                        </p>
                                        <div dir="rtl">
                                            <button
                                                onClick={() => {
                                                    if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.firstname} ${user.lastname} ?`)) {
                                                        ApiDeleteUser(user.id);
                                                    }
                                                }}
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

export default Users;
