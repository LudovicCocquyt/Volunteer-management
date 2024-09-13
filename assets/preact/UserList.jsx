import { useState, useEffect } from 'preact/hooks';
import { h } from 'preact';

const UserList = () => {
    const [users, setUsers] = useState([]);
    const [open, setOpen]   = useState(false);
    const [form, setForm]   = useState({"email": "", "firstname": "", "lastname": "", "password": ""});

    useEffect(() => {
        getUsers();
    }, []);

    const getUsers = async () => {
        try {
            fetch('/api/users', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                setUsers(data);
            })
            .catch(error => console.log('Erreur:', error));
        } catch (error) {
            console.error("[UserList]" + error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('/api/add_user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(form)
            });

            if (response.ok) {
                getUsers();
            }
        } catch (error) {
            console.error("[UserList]" + error);
        }
    }

    function generatePassword(length) {
        const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#$%&*";
        let password = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    return (
        <div class="text-2xl font-bold text-gray-800 dark:text-white">
            <div className='flex py-3'>
                <button type="button" onClick={() => setOpen(!open)} style="margin-right: 10px;" class="text-blue-700 border border-blue-700 hover:bg-blue-700 hover:text-white focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:focus:ring-blue-800 dark:hover:bg-blue-500">
                    <svg class="w-6 h-6 text-white-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                        </svg>
                    <span class="sr-only">Icon plus</span>
                </button>

                {open &&
                    <form class="flex" onSubmit={handleSubmit}>
                        <input type="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ml-1 mr-1" placeholder="name@flowbite.com" required onChange={(e) => setForm({...form, 'email': e.target.value})} />

                        <input type="text" id="firstname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Nom" required onChange={(e) => setForm({...form, 'firstname': e.target.value})} />

                        <input type="text" id="lastname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Prénom" required onChange={(e) => setForm({...form, 'lastname': e.target.value})} />

                        <input type="text" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500  ml-1 mr-1" required value={generatePassword(6)} onChange={(e) => setForm({...form, 'password': e.target.value})} />

                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 ml-1 mr-1">Envoyer</button>
                    </form>
                }
            </div>

            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                        <th scope="col" class="px-6 py-3">#</th>
                        <th scope="col" class="px-6 py-3">Nom & prénom</th>
                        <th scope="col" class="px-6 py-3">Email</th>
                            <th scope="col" class="px-6 py-3">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {users && users.map(user => (
                            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    { user.id }
                                </th>
                                <td class="px-6 py-4">Nom & prénom</td>
                                <td class="px-6 py-4">{ user.email }</td>
                                <td class="px-6 py-4">
                                    <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                                </td>
                        </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default UserList;
