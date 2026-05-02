/**
 * Advanced Form Integration - "webbabookinglite" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("webbabookinglite").
 */

Vue.component('webbabookinglite', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_booking: [
                    { type: 'text', value: 'service_id', title: 'Service ID', task: ['create_booking'], required: true },
                    { type: 'text', value: 'timestamp', title: 'Timestamp (seconds)', task: ['create_booking'], required: true, description: 'Unix timestamp in seconds.' },
                    { type: 'text', value: 'duration', title: 'Duration (minutes)', task: ['create_booking'], description: 'Defaults to service duration.' },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['create_booking'], description: 'Defaults to 1.' },
                    { type: 'text', value: 'name', title: 'Customer Name', task: ['create_booking'], required: true },
                    { type: 'text', value: 'email', title: 'Customer Email', task: ['create_booking'], required: true },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_booking'] },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_booking'] },
                    { type: 'text', value: 'service_category', title: 'Service Category ID', task: ['create_booking'] },
                    { type: 'text', value: 'time_offset', title: 'Time Offset (minutes)', task: ['create_booking'] },
                    { type: 'text', value: 'locale', title: 'Locale', task: ['create_booking'] },
                    { type: 'text', value: 'attachment', title: 'Attachment URL', task: ['create_booking'] },
                    { type: 'text', value: 'extra_json', title: 'Custom Fields (JSON)', task: ['create_booking'], description: 'Array of custom field tuples.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_booking'], description: 'pending, approved, cancelled, or rejected.' }
                ],
                update_booking: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['update_booking'], required: true },
                    { type: 'text', value: 'service_id', title: 'Service ID', task: ['update_booking'] },
                    { type: 'text', value: 'timestamp', title: 'Timestamp (seconds)', task: ['update_booking'] },
                    { type: 'text', value: 'duration', title: 'Duration (minutes)', task: ['update_booking'] },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['update_booking'] },
                    { type: 'text', value: 'name', title: 'Customer Name', task: ['update_booking'] },
                    { type: 'text', value: 'email', title: 'Customer Email', task: ['update_booking'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['update_booking'] },
                    { type: 'text', value: 'description', title: 'Description', task: ['update_booking'] },
                    { type: 'text', value: 'service_category', title: 'Service Category ID', task: ['update_booking'] },
                    { type: 'text', value: 'time_offset', title: 'Time Offset (minutes)', task: ['update_booking'] },
                    { type: 'text', value: 'locale', title: 'Locale', task: ['update_booking'] },
                    { type: 'text', value: 'attachment', title: 'Attachment URL', task: ['update_booking'] },
                    { type: 'text', value: 'extra_json', title: 'Custom Fields (JSON)', task: ['update_booking'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_booking'] }
                ],
                delete_booking: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['delete_booking'], required: true },
                    { type: 'text', value: 'delete_mode', title: 'Delete Mode', task: ['delete_booking'], description: 'auto, admin, customer, or permanent.' },
                    { type: 'text', value: 'force_delete', title: 'Force Delete', task: ['delete_booking'], description: 'true/false to bypass soft delete.' }
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
    template: '#webbabookinglite-action-template'
});
