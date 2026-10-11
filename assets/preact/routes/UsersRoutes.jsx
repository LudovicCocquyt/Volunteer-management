export const getUsers = async () => {
    try {
        const response = await fetch('/api/users', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        return data;

    } catch (error) {
        console.error("[UserList]" + error);
    }
};

export const addUser = async (form) => {
    try {
        const response = await fetch('/api/add_user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(form)
        });
        const data = await response.json().catch(() => ({}));
        const errors = (data.errors ?? []).map(e => (typeof e === 'string' ? e : e.message));
        return { ok: response.ok, errors: errors.length ? errors : (response.ok ? [] : [data.message ?? 'Erreur inconnue.']) };
    } catch (error) {
        console.error("[addUser] " + error);
    }
    return { ok: false, errors: ["Erreur réseau, réessayez."] };
};

export const deleteUser = async (id) => {
    try {
        const response = await fetch('/api/delete_user/' + id, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json().catch(() => ({}));
        const errors = (data.errors ?? []).map(e => (typeof e === 'string' ? e : e.message));
        return { ok: response.ok, errors: errors.length ? errors : (response.ok ? [] : [data.message ?? 'Erreur inconnue.']) };
    } catch (error) {
        console.error("[deleteUser] " + error);
    }
    return { ok: false, errors: ["Erreur réseau, réessayez."] };
};

export const getCurrentUser = async () => {
    try {
        const response = await fetch('/api/current_user', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        return data;

    } catch (error) {
        console.error("[currentUser]" + error);
    }
}
