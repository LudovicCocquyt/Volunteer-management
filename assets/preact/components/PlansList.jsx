import { h } from 'preact';
import moment from 'moment';
import 'moment/locale/fr';
import { useState } from 'preact/hooks';

const PlanList = ({ plans, onSelectPlan }) => {
    const [selected, setSelected] = useState();

    const planState = (startDate) => {
        const date = new Date(startDate);
        const now  = new Date();
        const selectedDate = selected ? new Date(selected) : null;

        if (selectedDate && date.getTime() === selectedDate.getTime())
            return "bg-green-400 text-white";

        if (date < now)
            return "bg-gray-200";

        return "bg-white";
    };

    return (
        <div id="PlanList-wrapper" className="pb-5">
            {plans.length > 0 &&
                <div className="grid grid-cols-10 gap-4">
                    {plans.map((plan, key) => (
                        <div
                            key={key}
                            id={plan.id}
                            className={`p-2 rounded-lg shadow hover:bg-gray-100 ${planState(plan.startDate)}`}
                            onClick={() => {
                                setSelected(plan.startDate);
                                onSelectPlan(plan); // on informe le parent
                            }}
                        >
                            <p className='text-center'>
                                <span>{moment(plan.startDate).format('HH:mm')} à {moment(plan.endDate).format('HH:mm')}</span>
                                <br />
                                <span>{moment(plan.endDate).format('dddd D MMM')}</span>
                            </p>
                        </div>
                    ))}
                </div>
            }
        </div>
    );
};

export default PlanList;