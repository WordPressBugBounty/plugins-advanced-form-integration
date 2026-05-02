/**
 * Advanced Form Integration - "eventsmanager" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("eventsmanager").
 */

Vue.component('eventsmanager', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_event: [
                    { type: 'text', value: 'eventName', title: 'Event Name', task: ['create_event'], required: true, description: 'Required event title.' },
                    { type: 'text', value: 'eventDescription', title: 'Event Description', task: ['create_event'], description: 'Optional description (HTML allowed).' },
                    { type: 'text', value: 'eventExcerpt', title: 'Event Excerpt', task: ['create_event'] },
                    { type: 'text', value: 'slug', title: 'Event Slug', task: ['create_event'], description: 'Optional custom slug.' },
                    { type: 'text', value: 'startDate', title: 'Start Date', task: ['create_event'], required: true, description: 'Required start date (YYYY-MM-DD).' },
                    { type: 'text', value: 'startTime', title: 'Start Time', task: ['create_event'], description: 'Start time (HH:MM, 24-hour or AM/PM).' },
                    { type: 'text', value: 'endDate', title: 'End Date', task: ['create_event'], description: 'Defaults to the start date when empty.' },
                    { type: 'text', value: 'endTime', title: 'End Time', task: ['create_event'], description: 'Defaults to start time or all-day end.' },
                    { type: 'text', value: 'allDay', title: 'All Day (yes/no)', task: ['create_event'], description: 'Enter yes to mark as all-day.' },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_event'], description: 'Optional. Example: Europe/London.' },
                    { type: 'text', value: 'locationId', title: 'Location ID', task: ['create_event'], description: 'Existing Events Manager location ID.' },
                    { type: 'text', value: 'ownerId', title: 'Owner User ID', task: ['create_event'], description: 'WordPress user ID for event owner.' },
                    { type: 'text', value: 'forceStatus', title: 'Force Post Status', task: ['create_event'], description: 'publish, draft, pending, or private.' },
                    { type: 'text', value: 'eventStatus', title: 'Event Status', task: ['create_event'], description: 'Numeric status (1 = approved, 0 = pending).' },
                    { type: 'text', value: 'eventPrivate', title: 'Private Event (yes/no)', task: ['create_event'] },
                    { type: 'text', value: 'rsvpEnabled', title: 'Enable RSVPs (yes/no)', task: ['create_event'] },
                    { type: 'text', value: 'rsvpDate', title: 'RSVP Deadline Date', task: ['create_event'], description: 'YYYY-MM-DD format.' },
                    { type: 'text', value: 'rsvpTime', title: 'RSVP Deadline Time', task: ['create_event'], description: 'HH:MM format.' },
                    { type: 'text', value: 'totalSpaces', title: 'Total Spaces', task: ['create_event'] },
                    { type: 'text', value: 'rsvpSpaces', title: 'RSVP Spaces Per Booking', task: ['create_event'] }
                ]
            }
        };
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#eventsmanager-action-template'
});
