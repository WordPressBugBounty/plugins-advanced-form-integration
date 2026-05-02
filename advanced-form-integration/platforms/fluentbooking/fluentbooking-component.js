/**
 * Advanced Form Integration - "fluentbooking" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fluentbooking").
 */

Vue.component('fluentbooking', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_booking: [
                    { type: 'text', value: 'eventId', title: 'Calendar Event ID', task: ['create_booking'], required: true, description: 'Numeric ID of the Fluent Booking calendar slot.' },
                    { type: 'text', value: 'personTimeZone', title: 'Attendee Timezone', task: ['create_booking'], required: true, description: 'IANA timezone e.g. America/New_York.' },
                    { type: 'text', value: 'startTimeUtc', title: 'Start Time (UTC)', task: ['create_booking'], description: 'Optional. UTC date/time (YYYY-MM-DD HH:MM:SS).' },
                    { type: 'text', value: 'startTime', title: 'Start Time (Local)', task: ['create_booking'], description: 'Optional local date/time. Requires attendee timezone.' },
                    { type: 'text', value: 'endTimeUtc', title: 'End Time (UTC)', task: ['create_booking'], description: 'Optional. Overrides automatic calculation.' },
                    { type: 'text', value: 'endTime', title: 'End Time (Local)', task: ['create_booking'], description: 'Optional local end time. Requires attendee timezone.' },
                    { type: 'text', value: 'durationMinutes', title: 'Duration (Minutes)', task: ['create_booking'], description: 'Override slot duration. Used if end time omitted.' },
                    { type: 'text', value: 'email', title: 'Attendee Email', task: ['create_booking'], required: true },
                    { type: 'text', value: 'name', title: 'Attendee Name', task: ['create_booking'], description: 'Full name. Split into first/last if needed.' },
                    { type: 'text', value: 'firstName', title: 'First Name', task: ['create_booking'] },
                    { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_booking'] },
                    { type: 'text', value: 'status', title: 'Booking Status', task: ['create_booking'], description: 'Default scheduled. Examples: scheduled, pending, cancelled.' },
                    { type: 'text', value: 'message', title: 'Message', task: ['create_booking'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_booking'] },
                    { type: 'text', value: 'country', title: 'Country', task: ['create_booking'] },
                    { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['create_booking'] },
                    { type: 'text', value: 'hostUserId', title: 'Host User ID', task: ['create_booking'], description: 'Optional WordPress user ID to assign as host.' },
                    { type: 'text', value: 'personUserId', title: 'Attendee User ID', task: ['create_booking'] },
                    { type: 'text', value: 'personContactId', title: 'Attendee Contact ID', task: ['create_booking'] },
                    { type: 'text', value: 'eventType', title: 'Event Type', task: ['create_booking'], description: 'Override event type (defaults to slot type).' },
                    { type: 'text', value: 'paymentMethod', title: 'Payment Method', task: ['create_booking'] },
                    { type: 'text', value: 'paymentStatus', title: 'Payment Status', task: ['create_booking'] },
                    { type: 'text', value: 'source', title: 'Source', task: ['create_booking'], description: 'Label for booking source.' },
                    { type: 'text', value: 'sourceUrl', title: 'Source URL', task: ['create_booking'] },
                    { type: 'text', value: 'utmSource', title: 'UTM Source', task: ['create_booking'] },
                    { type: 'text', value: 'utmMedium', title: 'UTM Medium', task: ['create_booking'] },
                    { type: 'text', value: 'utmCampaign', title: 'UTM Campaign', task: ['create_booking'] },
                    { type: 'text', value: 'utmTerm', title: 'UTM Term', task: ['create_booking'] },
                    { type: 'text', value: 'utmContent', title: 'UTM Content', task: ['create_booking'] },
                    { type: 'text', value: 'browser', title: 'Browser', task: ['create_booking'] },
                    { type: 'text', value: 'device', title: 'Device', task: ['create_booking'] },
                    { type: 'text', value: 'locationType', title: 'Location Type', task: ['create_booking'], description: 'Match a configured location key (e.g. online_meeting).' },
                    { type: 'text', value: 'locationDescription', title: 'Location Description', task: ['create_booking'] },
                    { type: 'text', value: 'locationUrl', title: 'Location URL', task: ['create_booking'], description: 'Online meeting link when applicable.' },
                    { type: 'text', value: 'additionalGuests', title: 'Additional Guests', task: ['create_booking'], description: 'Comma or newline separated guest emails.' },
                    { type: 'text', value: 'additionalGuestsJson', title: 'Additional Guests JSON', task: ['create_booking'], description: 'JSON array of {"name":"","email":""} items.' },
                    { type: 'text', value: 'customFieldsJson', title: 'Custom Fields JSON', task: ['create_booking'], description: 'JSON object where keys match custom booking field names.' }
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
    template: '#fluentbooking-action-template'
});
