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
      hideEmptyDays: state.hideEmptyDays === true,
    };
  } catch (e) {
    return { view: null, date: null, hideEmptyDays: false };
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
/** Jours (AAAA-MM-JJ) qui ont au moins un créneau, triés. */
export const slotDays = (slots) =>
  [...new Set(slots.map(slot => String(slot.start).substring(0, 10)))].sort();

export const nearestPastSlotDate = (slots) => {
  const today = moment().format('YYYY-MM-DD');
  const days  = slotDays(slots);
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
export const initialCalendarDate = (saved, slots, eventDate) => {
  const date = (isReloadOrHistory() && saved.date) || nearestPastSlotDate(slots) || eventDate;

  // Mode « jours vides masqués » : ne jamais ouvrir sur un jour sans créneau
  if (saved.hideEmptyDays && !slotDays(slots).includes(String(date).substring(0, 10))) {
    return nearestPastSlotDate(slots) || date;
  }

  return date;
};

/** Vue d'ouverture : la vue Jour est imposée quand les jours vides sont masqués. */
export const initialCalendarView = (saved) =>
  saved.hideEmptyDays ? 'resourceTimelineDay' : (saved.view || 'resourceTimelineDay');

/**
 * Navigation entre les jours qui ont des créneaux, et mode « masquer les jours vides » :
 * le calendrier passe en vue Jour et les flèches sautent directement au jour
 * précédent / suivant qui a des créneaux.
 *
 * FullCalendar ne sait pas retirer des dates précises d'une vue semaine/mois :
 * on masque donc les jours vides en ne les affichant jamais, jour par jour.
 */
export const createSlotNavigation = ({ eventId, calendarApiRef, eventsRef, hideEmptyRef, fallbackDate }) => {
  const calendar   = () => calendarApiRef.current;
  const currentDay = () => calendar()?.getDate().toISOString().substring(0, 10);
  const days       = () => slotDays(eventsRef.current);
  const goTo       = (day) => { if (day) calendar()?.gotoDate(day); };

  const goToNearest = () => goTo(nearestPastSlotDate(eventsRef.current) || fallbackDate);

  const goToAdjacent = (direction) => {
    const today = currentDay();
    const list  = days();
    goTo(direction > 0 ? list.find(day => day > today) : [...list].reverse().find(day => day < today));
  };

  const save = () => {
    const c = calendar();
    if (c) saveCalendarState(eventId, { view: c.view.type, date: currentDay(), hideEmptyDays: hideEmptyRef.current });
  };

  const buttons = () => ({
    nearestSlot: {
      text: 'Créneau le plus proche',
      hint: 'Aller au créneau le plus récent (ou au premier à venir)',
      click: goToNearest,
    },
    toggleEmptyDays: {
      text: hideEmptyRef.current ? 'Afficher tous les jours' : 'Masquer les jours vides',
      hint: hideEmptyRef.current ? 'Revenir à la navigation normale' : 'Ne parcourir que les jours qui ont des créneaux',
      click: () => toggle(),
    },
    prevSlotDay: { icon: 'chevron-left', hint: 'Jour précédent avec des créneaux', click: () => goToAdjacent(-1) },
    nextSlotDay: { icon: 'chevron-right', hint: 'Jour suivant avec des créneaux', click: () => goToAdjacent(1) },
  });

  const toolbar = () => hideEmptyRef.current
    ? { left: 'toggleEmptyDays', center: 'title', right: 'nearestSlot prevSlotDay,nextSlotDay' }
    : { left: VIEW_BUTTONS, center: 'title', right: 'toggleEmptyDays nearestSlot prev,next' };

  const toggle = () => {
    hideEmptyRef.current = !hideEmptyRef.current;
    const c = calendar();
    if (!c) return;

    c.setOption('customButtons', buttons());
    c.setOption('headerToolbar', toolbar());

    if (hideEmptyRef.current) {
      c.changeView('resourceTimelineDay');
      if (!days().includes(currentDay())) goToNearest();
    }
    save();
  };

  return { buttons, toolbar, save };
};

/** Libellés français des boutons de vue et du bouton « créneau le plus proche ». */
export const calendarButtonText = {
  resourceTimelineDay: 'Jour',
  resourceTimelineWeek: 'Semaine',
  resourceTimelineMonth: 'Mois',
  resourceTimelineYear: 'Année',
};
