import { h } from 'preact';
import { useEffect, useState, useRef } from 'preact/hooks';
import { getOurNeedsByEvent } from '../routes/PlansRoutes';
import moment from 'moment';
import 'moment/locale/fr';

const SubscriptionEdit = () => {
  moment.locale('fr');
  const element                 = document.getElementById('SubscriptionEdit-wrapper');
  const eventId                 = JSON.parse(element.getAttribute('data-eventId') || null);
  const availabilitiesVolunteer = JSON.parse(element.getAttribute('data-availabilities') || []);
  const fullname                = JSON.parse(element.getAttribute('data-fullname') || '');
  const invalideMessage         = useRef(null);

  const [plans, setPlans]                   = useState([]);
  const [availabilities, setAvailabilities] = useState(JSON.parse(availabilitiesVolunteer));

  useEffect(() => {
    const fetchPlans = async () => {
      // If there are availabilities in volunteer
      const plans = await getApiPlans(eventId);

      availabilities.forEach((a) => {
        // If the plan is already in availabilities, set it as selected
        if (!plans.some((p) => p.startDate === a.startDate && p.endDate === a.endDate))
          plans.push(a);
      });

      setPlans(plans.sort((a, b) => new Date(a.startDate) - new Date(b.startDate))); // Set plans and sort by start date
    };
    fetchPlans();
  }, [eventId]);

  useEffect(() => {
    subscriptions_form_availabilities.value = [JSON.stringify(availabilities)]; // Set availabilities in the form
  }, availabilities);

  const getApiPlans = async (eventId) => {
    const plans = await getOurNeedsByEvent(eventId);
    return plans
  };

  const moveAvailability = (plan) => {
    // Hide the error message if it was previously shown
    if (invalideMessage.current)
      invalideMessage.current.classList.add('hidden');
    if (!plan.available) {
      // If the plan is not available, show an error message
      invalideMessage.current.classList.remove('hidden');
      return;
    }
    // remove plan from availability
    if (inAvailabilities(plan)) {
      setAvailabilities(availabilities.filter((item) => item.startDate !== plan.startDate && item.endDate !== plan.endDate));
    }
    // add plan to availability
    if (!inAvailabilities(plan)) {
      setAvailabilities([...availabilities, plan]);
    }
  };

  // Check if a plan is in availabilities
  const inAvailabilities = (plan) => {
    return availabilities.some((a) => a.startDate === plan.startDate && a.endDate === plan.endDate);
  };

  return (
    <div id="ChooseTimes-wrapper" className='p-5'>
      {plans.length > 0 &&
        <div id="times-wrapper" className="pt-10">
          <h4 className="mb-5 text-xl font-bold tracking-tight text-gray-900 dark:text-white"><span style="color: #a62475">M</span><span style="color: #35b19a">odifier les disponibilités de { fullname }</span></h4>
          <div className="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {plans.map((plan, key) => (
             <div
              key={key}
              id={plan.id}
              className={`p-6 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700
                ${ inAvailabilities(plan) ? 'bg-green-200' : 'bg-white hover:bg-gray-100'}
                ${ plan.available ? 'border border-gray-200' : 'border-2 border-red-200'}
                `}
              onClick={() => { moveAvailability(plan) }}
              >
                <p className='text-center'>
                  <span>{moment(plan.startDate).format('HH:mm')} à {moment(plan.endDate).format('HH:mm')}</span>
                  <br />
                  <span>{moment(plan.endDate).format('dddd D MMM')}</span>
                </p>
                  { !plan.available &&
                      <p ref={invalideMessage} className='hidden text-red-700 text-center text-sm'>Vous devez retirer ce créneau de l'événement, car il a déjà été assigné !</p>
                  }
              </div>
            ))}
          </div>
        </div>
      }
    </div>
  );
};

export default SubscriptionEdit;