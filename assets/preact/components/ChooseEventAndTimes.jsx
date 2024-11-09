import { h } from 'preact';
import { useEffect, useState } from 'preact/hooks';
import { getOurNeedsByEvent } from '../routes/PlansRoutes';
import moment from 'moment';
import 'moment/locale/fr';

const ChooseEventAndTimes = () => {
  moment.locale('fr');
  const element                           = document.getElementById('ChooseEventAndTimes-wrapper');
  const events                            = JSON.parse(element.getAttribute('data-eventId'));
  const subscriptions_form_availabilities = document.getElementById('subscriptions_form_availabilities');
  const subscriptions_form_event          = document.getElementById('subscriptions_form_event');
  const formWrapper                       = document.getElementById('form-wrapper');

  const [plans, setPlans]               = useState([]);
  const [currentEvent, setCurrentEvent] = useState(null);
  const [availability, setAvailability] = useState([]);

  console.log('availability', availability);

  useEffect(() => {
    subscriptions_form_availabilities.value = [JSON.stringify(availability)]; // Set availabilities in the form
  }, availability);

  const changeEvent = (eventId) => {
    setCurrentEvent(eventId);
    getApiPlans(eventId);
    subscriptions_form_event.value = eventId; // Set the event id in the form

    setPlans([]); // Reset the plans
    setAvailability([]); // Reset the availability
  }

  const getApiPlans = async (eventId) => {
    const plans = await getOurNeedsByEvent(eventId);
    setPlans(plans);
  };

  const moveAvailability = (plan) => {
    // remove plan from availability
    if (availability.includes(plan)) {
      setAvailability(availability.filter((item) => item !== plan));
    }

    // add plan to availability
    if (!availability.includes(plan)) {
      setAvailability([...availability, plan]);
      formWrapper.classList.remove('hidden'); // Show the form
    }
  };

  return (
    <div id="TimelineCalendarChooseTimes-wrapper">

      <div id="events-wrapper">
        <h4 className="mb-5 text-xl font-bold tracking-tight text-gray-900 dark:text-white text-center"><span style="color: #a62475">C</span><span style="color: #35b19a">hoisir un événement</span></h4>
        <div className="grid grid-cols-2 gap-4">
          {events && events.map((event) => (
            <div
              id={event.id}
              className={`p-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 ${event.id === currentEvent ? 'bg-green-200' : 'bg-white hover:bg-gray-100'}`}
              onClick={() => {changeEvent(event.id)}}
            >
              <h5 className="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">{event.name}</h5>
              <p className="font-normal text-gray-700 dark:text-gray-400">{event.startAt}</p>
              <p className="font-normal text-gray-700 dark:text-gray-400">{event.description}</p>
            </div>
          ))}
        </div>
      </div>

      {plans.length > 0 &&
        <div id="times-wrapper" className="pt-10">
          <h4 className="mb-5 text-xl font-bold tracking-tight text-gray-900 dark:text-white text-center"><span style="color: #a62475">C</span><span style="color: #35b19a">hoisir vos disponibilités</span></h4>
          <div class="grid grid-cols-4 gap-4">
            {plans.map((plan, key) => (
             <div
              key={key}
              id={plan.id}
              className={`p-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 ${availability.includes(plan) ? 'bg-green-200' : 'bg-white hover:bg-gray-100'}`}
              onClick={() => { moveAvailability(plan) }} >
                <p className='text-center'>
                  <span>{moment(plan.startDate).format('HH:mm')} à {moment(plan.endDate).format('HH:mm')}</span>
                  <br />
                  <span>{moment(plan.endDate).format('dddd D MMM')}</span>
                  <br />
                  5 places
                </p>
              </div>
            ))}
          </div>
        </div>
      }

    </div>
  );
};

export default ChooseEventAndTimes;