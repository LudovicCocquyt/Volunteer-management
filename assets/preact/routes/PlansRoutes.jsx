export const getPlans = async (id) => {
    try {
        const response = await fetch(`/api/plans/by_event/${id}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        return data;

    } catch (error) {
        console.error("[Plans]" + error);
    }
};

export const getOurNeedsByEvent = async (id) => {
    try {
        const response = await fetch(`/api/public/plans/our_needs_by_event/${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        return data;

    } catch (error) {
        console.error("[Plans our_needs_by_event]" + error);
    }
};

export const addPlan = async (form) => {
    try {
        const response = await fetch('/api/plan/new', {
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
        console.error("[addPlan] " + error);
    }
    return false;
};

export const updatePlan = async (form) => {
    try {
        const response = await fetch('/api/plan/edit/' + form.id, {
            method: 'PUT',
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
        console.error("[updatePlan] " + error);
    }
    return false;
};

export const removePlan = async (planId) => {
    try {
        const response = await fetch('/api/plan/remove/' + planId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        if (response.ok) {
            return true;
        }
    } catch (error) {
        console.error("[RemovePlan] " + error);
    }
    return false;
};

export const assignVolunteer = async (params) => {
    try {
        const response = await fetch('/api/plan/volunteer', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(params)
        });
        if (response.ok) {
            return true;
        }
    } catch (error) {
        console.error("[assignVolunteer] " + error);
    }
    return false;
};

export const getSubscriptions = async (planId) => {
    try {
        const response = await fetch(`/api/plan/get_subscriptions/${planId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        return data.plan;
    } catch (error) {
        console.error("[Plan/getSubscriptions] " + error);
    }
    return false;
};
