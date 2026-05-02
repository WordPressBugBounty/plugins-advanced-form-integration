/**
 * Advanced Form Integration - "appointmenthourbooking" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("appointmenthourbooking").
 */

Vue.component('appointmenthourbooking', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_booking: [
                    { type: 'text', value: 'calendar_id', title: 'Calendar ID', task: ['create_booking'], description: 'Numeric ID of the calendar/form.' },
                    { type: 'text', value: 'service_field', title: 'Service Field Name', task: ['create_booking'], description: 'Appointment field name (default fieldname1).' },
                    { type: 'text', value: 'service_index', title: 'Service Index', task: ['create_booking'], description: 'Index of the service option (starting at 0).' },
                    { type: 'text', value: 'service_name', title: 'Service Name', task: ['create_booking'] },
                    { type: 'text', value: 'service_duration', title: 'Service Duration (minutes)', task: ['create_booking'] },
                    { type: 'text', value: 'service_price', title: 'Service Price', task: ['create_booking'] },
                    { type: 'text', value: 'service_id', title: 'Service ID', task: ['create_booking'], description: 'Optional service identifier.' },
                    { type: 'text', value: 'date', title: 'Booking Date', task: ['create_booking'], description: 'Expected format YYYY-MM-DD.' },
                    { type: 'text', value: 'start_time', title: 'Start Time', task: ['create_booking'], description: 'Expected format HH:MM (24-hour).' },
                    { type: 'text', value: 'end_time', title: 'End Time', task: ['create_booking'], description: 'Expected format HH:MM (24-hour).' },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['create_booking'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_booking'], description: 'Approved by default. Examples: Pending, Cancelled.' },
                    { type: 'text', value: 'customer_email', title: 'Customer Email', task: ['create_booking'] },
                    { type: 'text', value: 'customer_name', title: 'Customer Name', task: ['create_booking'] },
                    { type: 'text', value: 'customer_phone', title: 'Customer Phone', task: ['create_booking'] },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['create_booking'] }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#appointmenthourbooking-action-template'
});
