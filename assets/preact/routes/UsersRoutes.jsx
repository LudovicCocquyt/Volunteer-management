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
        if (response.ok) {
            return true;
        }
    } catch (error) {
        console.error("[addUser] " + error);
    }
    return false;
};

export const deleteUser = async (id) => {
    try {
        const response = await fetch('/api/delete_user/' + id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        if (response.ok) {
            return true;
        }
    } catch (error) {
        console.error("[deleteUser] " + error);
    }
    return false;
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
