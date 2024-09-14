export const getActivities = async () => {
    try {
        const response = await fetch('/api/activities/', {
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

export const addActivity = async (form) => {
    try {
        const response = await fetch('/api/activity/new', {
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
        console.error("[addActivity] " + error);
    }
    return false;
};

export const deleteActivity = async (id) => {
    try {
        const response = await fetch('/api/delete_activity/' + id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        if (response.ok) {
            return true;
        }
    } catch (error) {
        console.error("[deleteActivity] " + error);
    }
    return false;
};