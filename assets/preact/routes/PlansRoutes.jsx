// Affiche le message d'erreur renvoyé par l'API (format { status, message, errors })
const reportApiError = async (response) => {
    let message = "L'opération n'a pas pu être enregistrée.";
    try {
        const data = await response.json();
        if (data && data.message) {
            message = data.message;
        }
        if (data && Array.isArray(data.errors) && data.errors.length > 1) {
            message += '\n\n' + data.errors.map(e => '- ' + (e.message || e)).join('\n');
        }
    } catch (e) {
        // Réponse non JSON : message générique
    }
    alert(message);
};

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

// Formulaire public : événements publiés uniquement, noms abrégés si autorisés
export const getPublicNeedsByEvent = async (id) => {
    try {
        const response = await fetch(`/api/public/plans/our_needs_by_event/${id}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) {
            return [];
        }
        return await response.json();

    } catch (error) {
        console.error("[Plans public needs]" + error);
    }
    return [];
};

// Back-office : tous les événements, noms complets
export const getOurNeedsByEvent = async (id, includeEmpty) => {
    try {
        const response = await fetch(`/api/plans/our_needs_by_event/${id}?includeEmpty=${includeEmpty}`, {
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
        await reportApiError(response);
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
        await reportApiError(response);
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
        await reportApiError(response);
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
        await reportApiError(response);
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
