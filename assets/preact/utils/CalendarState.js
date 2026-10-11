import moment from 'moment';

/**
 * Outils partagés par les calendriers de planning (pages détail et modification d'un événement).
 * La vue et la date sont mémorisées par événement dans le navigateur.
 */
export const CALENDAR_VIEWS = [
  'resourceTimelineDay',
  'resourceTimelineWeek',
  'resourceTimelineMonth',
  'resourceTimelineYear',
];

export const VIEW_BUTTONS = CALENDAR_VIEWS.join(',');

export const loadCalendarState = (eventId) => {
  try {
    const state = JSON.parse(localStorage.getItem(`vm.calendar.${eventId}`) || '{}');
    return {
      view: CALENDAR_VIEWS.includes(state.view) ? state.view : null,
      date: typeof state.date === 'string' ? state.date : null,
    };
  } catch (e) {
    return { view: null, date: null };
  }
};

export const saveCalendarState = (eventId, state) => {
  try {
    localStorage.setItem(`vm.calendar.${eventId}`, JSON.stringify(state));
  } catch (e) {
    // Stockage indisponible (navigation privée...) : le calendrier fonctionne sans mémoire
  }
};

/**
 * Date du créneau la plus proche d'aujourd'hui sans être dans le futur
 * (le créneau le plus récent déjà commencé ou du jour). Si tous les créneaux
 * sont à venir, renvoie le premier d'entre eux.
 */
export const nearestPastSlotDate = (slots) => {
  const today = moment().format('YYYY-MM-DD');
  const days  = [...new Set(slots.map(slot => String(slot.start).substring(0, 10)))].sort();
  if (days.length === 0) return null;

  const pastOrToday = days.filter(day => day <= today);
  return pastOrToday.length > 0 ? pastOrToday[pastOrToday.length - 1] : days[0];
};

/**
 * Actualisation (F5) ou retour arrière : on garde la date mémorisée.
 * Arrivée depuis un lien : on ouvre sur le créneau le plus proche.
 */
export const isReloadOrHistory = () => {
  try {
    const navigation = performance.getEntriesByType('navigation')[0];
    return !!navigation && navigation.type !== 'navigate';
  } catch (e) {
    return false;
  }
};

/** Date d'ouverture du calendrier. */
export const initialCalendarDate = (saved, slots, eventDate) =>
  (isReloadOrHistory() && saved.date) || nearestPastSlotDate(slots) || eventDate;

/** Libellés français des boutons de vue et du bouton « créneau le plus proche ». */
export const calendarButtonText = {
  resourceTimelineDay: 'Jour',
  resourceTimelineWeek: 'Semaine',
  resourceTimelineMonth: 'Mois',
  resourceTimelineYear: 'Année',
};
