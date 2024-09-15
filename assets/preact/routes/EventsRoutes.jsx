export const getEvents = async (filter) => {
    try {
        const response = await fetch('/api/events/', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
        });
        const data = await response.json();

        if (filter != "archived")
            return data.filter(event => !event.archived);

        if (filter === "archived")
            return data.filter(event => event.archived);

        return data;

    } catch (error) {
        console.error("[EventsList]" + error);
    }
};
