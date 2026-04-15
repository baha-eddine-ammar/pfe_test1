/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This file defines the Alpine component used by the calendar page.
|
| What it does:
| - opens/closes the day modal
| - opens/closes the event detail modal
| - maps priority/status values to badge classes
| - keeps page body scrolling correct while modals are open
|
| FILES TO READ (IN ORDER):
| 1. resources/views/calendar/index.blade.php
| 2. resources/views/components/calendar-event.blade.php
| 3. resources/js/calendar-workspace.js
| 4. app/Http/Controllers/CalendarController.php
*/
const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: '2-digit',
    minute: '2-digit',
});

const priorityBadgeMap = {
    urgent: 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
    high: 'bg-orange-100 text-orange-700 ring-1 ring-inset ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/20',
    medium: 'bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20',
    low: 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
};

const statusBadgeMap = {
    pending: 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
    assigned: 'bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20',
    in_progress: 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
    completed: 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
    cancelled: 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
};

export default (config = {}) => ({
    ready: false,
    // Values provided by the Blade page.
    role: config.role || 'staff',
    currentUri: config.currentUri || window.location.pathname,
    days: config.days || {},
    activeEvent: null,
    selectedDayKey: null,
    eventModalOpen: false,
    dayModalOpen: false,
    lastLoadedAt: '',
    // Entry point called by x-init in the Blade page.
    init() {
        requestAnimationFrame(() => {
            this.ready = true;
        });

        this.lastLoadedAt = timeFormatter.format(new Date());
    },
    // Opens the event detail modal for one maintenance task.
    openEvent(event) {
        this.activeEvent = event;
        this.eventModalOpen = true;
        this.syncBodyScroll();
    },
    // Closes the event detail modal.
    closeEventModal() {
        this.eventModalOpen = false;
        this.activeEvent = null;
        this.syncBodyScroll();
    },
    // Opens the modal showing all tasks for one day.
    openDay(dateKey) {
        if (!this.days[dateKey]) {
            return;
        }

        this.selectedDayKey = dateKey;
        this.dayModalOpen = true;
        this.syncBodyScroll();
    },
    // Closes the day modal.
    closeDayModal() {
        this.dayModalOpen = false;
        this.selectedDayKey = null;
        this.syncBodyScroll();
    },
    // Convenience flow: open a specific event after leaving the day modal list.
    openEventFromDay(event) {
        this.dayModalOpen = false;
        this.selectedDayKey = null;
        this.openEvent(event);
    },
    // Prevents the main page from scrolling behind an open modal.
    syncBodyScroll() {
        document.body.classList.toggle('overflow-y-hidden', this.eventModalOpen || this.dayModalOpen);
    },
    // Labels shown in the day modal header.
    activeDayLabel() {
        return this.selectedDayKey && this.days[this.selectedDayKey]
            ? this.days[this.selectedDayKey].date_label
            : '';
    },
    // Count shown in the day modal header.
    activeDayCount() {
        return this.selectedDayKey && this.days[this.selectedDayKey]
            ? this.days[this.selectedDayKey].events.length
            : 0;
    },
    // Reuses the same priority/status color rules throughout the modal UI.
    badgeClass(type, value) {
        if (!value) {
            return priorityBadgeMap.low;
        }

        if (type === 'priority') {
            return priorityBadgeMap[value] || priorityBadgeMap.low;
        }

        return statusBadgeMap[value] || statusBadgeMap.pending;
    },
    // Title used by the event modal.
    currentModalTitle() {
        return this.activeEvent?.title || '';
    },
});
