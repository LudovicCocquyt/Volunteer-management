export const getSubscriptionsByEvent = async (params) => {
    try {
        const response = await fetch(`/api/subscriptions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(params)
        });
        const data = await response.json();
        return data;

    } catch (error) {
        console.error("[Subscriptions]" + error);
    }
};

