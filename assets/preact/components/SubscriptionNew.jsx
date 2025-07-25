import { h } from 'preact';
import { useEffect, useState } from 'preact/hooks';
import { getOurNeedsByEvent } from '../routes/PlansRoutes';
import moment from 'moment';
import 'moment/locale/fr';

const SubscriptionNew = () => {
  moment.locale('fr');
  const element                           = document.getElementById('SubscriptionNew-wrapper');
  const events                            = JSON.parse(element.getAttribute('data-eventId'));
  const subscriptions_form_availabilities = document.getElementById('subscriptions_form_availabilities');
  const subscriptions_form_event          = document.getElementById('subscriptions_form_event');
  const formWrapper                       = document.getElementById('form-wrapper');

  const [plans, setPlans]               = useState([]);
  const [currentEvent, setCurrentEvent] = useState(null);
  const [availability, setAvailability] = useState([]);
  const [description, setDescription]   = useState("");

  useEffect(() => {
    if (availability.length > 0)
      formWrapper.classList.remove('hidden'); // Show the form
    else
      formWrapper.classList.add('hidden'); // Hide the form

    subscriptions_form_availabilities.value = [JSON.stringify(availability)]; // Set availabilities in the form
  }, availability);

  useEffect(() => {
    // If there is only one event, we select it by default
    if (events.length == 1) {
      changeEvent(events[0].id);
      descriptionEvent(events[0].description);
    }
  }, []);

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

  const descriptionEvent = (description) => {
    setDescription(description);
  };

  const HtmlRenderer = ({ htmlContent }) => {
    return <div className="tinymce-content text-sm font-normal text-gray-700 dark:text-gray-400 text-justify p-5" dangerouslySetInnerHTML={{ __html: htmlContent }} />;
  };

  return (
    <div id="TimelineCalendarChooseTimes-wrapper" className='p-5'>

      <div id="events-wrapper">
        {events.length == 0 &&
          <h4 className="text-center mb-5 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
            <span style="color: #a62475">A</span><span style="color: #35b19a">ucun événement pour le moment !</span></h4>
        }
        {events && events.length > 1 &&
          <h4 className="mb-5 text-xl font-bold tracking-tight text-gray-900 dark:text-white"><span style="color: #a62475">C</span><span style="color: #35b19a">hoisir un événement</span></h4>
        }

        <div className={`grid sm:grid-cols-1 md:grid-cols-${events.length == 1 ? '1' : '2'} gap-4`}>
          {events && events.map((event) => (
            <div
              id={event.id}
              className={`p-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 ${event.id === currentEvent ? 'bg-green-200' : 'bg-white hover:bg-gray-100'}`}
              onClick={() => {changeEvent(event.id), descriptionEvent(event.description)}}
            >
              <h5 className="mb-2 text-xl text-center font-bold tracking-tight text-gray-900 dark:text-white">{event.name}</h5>
              <p className="font-normal text-center text-gray-700 dark:text-gray-400 capitale">{moment(event.startAt.date).format('dddd')} {moment(event.startAt.date).format('LL')}</p>
              <p className="font-normal text-center text-gray-700 dark:text-gray-400 capitale">{event.location ?? ""}</p>
            </div>
          ))}
        </div>
        { description && description.length > 0 &&
          <div class="p-6 mt-3 border border-gray-200 rounded-lg shadow bg-gray-100">
            <HtmlRenderer htmlContent={description} />
          </div>
        }
      </div>

      {plans.length > 0 &&
        <div id="times-wrapper" className="pt-10">
          <h4 className="mb-5 text-xl font-bold tracking-tight text-gray-900 dark:text-white"><span style="color: #a62475">C</span><span style="color: #35b19a">hoisir vos disponibilités</span></h4>
          <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                </p>
              </div>
            ))}
          </div>
        </div>
      }

    </div>
  );
};

export default SubscriptionNew;