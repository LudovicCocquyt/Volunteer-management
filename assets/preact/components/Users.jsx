import { useState, useEffect } from 'preact/hooks';
import { h } from 'preact';
import { getUsers, addUser, deleteUser, getCurrentUser } from '../routes/UsersRoutes';

const emptyForm = { email: "", firstname: "", lastname: "", password: "", role: "ROLE_USER" };

// Mot de passe aléatoire cryptographiquement sûr (sans caractères ambigus 0/O, 1/l/I)
const generatePassword = (length = 12) => {
    const charset = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#-_*";
    const values  = crypto.getRandomValues(new Uint32Array(length));
    return Array.from(values, (v) => charset[v % charset.length]).join('');
};

const Users = () => {
    const [users, setUsers]             = useState([]);
    const [currentUser, setCurrentUser] = useState(null);
    const [open, setOpen]               = useState(false);
    const [form, setForm]               = useState(emptyForm);
    const [errors, setErrors]           = useState([]);

    const assignableRoles = currentUser?.assignableRoles ?? {};
    const canAssignRoles  = Object.keys(assignableRoles).length > 0;

    useEffect(() => {
        getApiUsers();
        getCurrentUser().then(setCurrentUser);
    }, []);

    const getApiUsers = async () => {
        const users = await getUsers();
        setUsers(users ?? []);
    };

    const toggleForm = () => {
        if (!open) {
            setForm({ ...emptyForm, password: generatePassword() });
            setErrors([]);
        }
        setOpen(!open);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        const result = await addUser(form);
        if (result.ok) {
            getApiUsers();
            setForm(emptyForm);
            setErrors([]);
            setOpen(false);
        } else {
            setErrors(result.errors);
        }
    };

    const ApiDeleteUser = async (id) => {
        const result = await deleteUser(id);
        if (result.ok) {
            getApiUsers();
        } else {
            alert(result.errors.join("\n") || "Suppression impossible.");
        }
    };

    const goToUpdateUser = (id) => {
        window.location.href = '/user/edit/' + id;
    };

    return (
        <div class="w-full p-2 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <h3 class="w-full flex items-center justify-between p-2 text-gray-900" style="background-color: #f9ecce;">Utilisateurs</h3>
                <div className='md:flex py-3 ml-2'>
                    <button type="button" onClick={toggleForm} style="margin-right: 10px;"
                    class="text-yellow-700 border border-yellow-700 hover:bg-yellow-700 hover:text-yellow-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:border-yellow-500 dark:text-yellow-500 dark:hover:text-white dark:focus:ring-yellow-800 dark:hover:bg-yellow-500">
                        <svg class="w-6 h-6 text-white-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                            </svg>
                        <span class="sr-only">Icon plus</span>
                    </button>

                    {open &&
                        <form class="md:flex" onSubmit={handleSubmit}>
                            <input type="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ml-1 mr-1" placeholder="apel@gmail.com" required value={form.email} onInput={(e) => setForm({...form, email: e.target.value})} />

                            <input type="text" id="firstname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Prénom" required value={form.firstname} onInput={(e) => setForm({...form, firstname: e.target.value})} />

                            <input type="text" id="lastname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ml-1 mr-1" placeholder="Nom" required value={form.lastname} onInput={(e) => setForm({...form, lastname: e.target.value})} />

                            <input type="text" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500  ml-1 mr-1" required minLength={8} value={form.password} onInput={(e) => setForm({...form, password: e.target.value})} title="Mot de passe à transmettre à l'utilisateur" />

                            {canAssignRoles &&
                                <select id="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white ml-1 mr-1" value={form.role} onChange={(e) => setForm({...form, role: e.target.value})}>
                                    {Object.entries(assignableRoles).map(([role, label]) => (
                                        <option key={role} value={role}>{label}</option>
                                    ))}
                                </select>
                            }

                            <button type="submit" class="bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-full py-1 px-16">Enregistrer</button>
                        </form>
                    }
                </div>
                {errors.length > 0 &&
                    <ul class="mx-2 mb-3 p-2 text-sm text-red-700 bg-red-100 rounded-lg">
                        {errors.map((error, key) => <li key={key}>{error}</li>)}
                    </ul>
                }

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
                                        <span class="mx-2 bg-green-100 text-green-700 dark:bg-green-700 dark:text-white rounded-full px-2 py-1 text-xs font-medium">{ user.roleLabel }</span>
                                    </td>
                                    <td class="flex px-6 py-4">
                                        <div dir="ltr" style={{ visibility: user.canEdit ? 'visible' : 'hidden' }}>
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
                                        <div dir="rtl" style={{ visibility: user.canDelete ? 'visible' : 'hidden' }}>
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
