/**
 * Advanced Form Integration - "latepoint" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("latepoint").
 */

Vue.component('latepoint', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_customer: [
                    { type: 'text', value: 'email', title: 'Email', task: ['create_customer'], required: true, description: 'Required and used to locate existing customers.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_customer'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_customer'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_customer'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_customer'], description: 'Optional status key (defaults to pending_verification).' },
                    { type: 'text', value: 'is_guest', title: 'Is Guest', task: ['create_customer'], description: 'true/false to mark the profile as guest.' },
                    { type: 'text', value: 'wordpress_user_id', title: 'WordPress User ID', task: ['create_customer'], description: 'Optional linked WP user ID.' },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['create_customer'], description: 'Public notes visible to customer.' },
                    { type: 'text', value: 'admin_notes', title: 'Admin Notes', task: ['create_customer'], description: 'Private notes visible to admins only.' },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_customer'], description: 'Optional timezone name (e.g. America/New_York).' },
                    { type: 'text', value: 'password', title: 'Password', task: ['create_customer'], description: 'Optional password; hashes and updates the account if supplied.' },
                    { type: 'text', value: 'timeline_note', title: 'Timeline Note', task: ['create_customer'], description: 'Optional note appended to the customer timeline.' },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_customer'], description: 'Optional JSON object of meta key/value pairs.' }
                ],
                update_customer: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['update_customer'], description: 'Provide customer ID or email to locate the record.' },
                    { type: 'text', value: 'email', title: 'Email', task: ['update_customer'], description: 'Email address to locate or update the customer.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['update_customer'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['update_customer'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['update_customer'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_customer'] },
                    { type: 'text', value: 'is_guest', title: 'Is Guest', task: ['update_customer'], description: 'true/false to toggle guest flag.' },
                    { type: 'text', value: 'wordpress_user_id', title: 'WordPress User ID', task: ['update_customer'] },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['update_customer'] },
                    { type: 'text', value: 'admin_notes', title: 'Admin Notes', task: ['update_customer'] },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['update_customer'] },
                    { type: 'text', value: 'password', title: 'Password', task: ['update_customer'], description: 'Optional new password.' },
                    { type: 'text', value: 'timeline_note', title: 'Timeline Note', task: ['update_customer'] },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['update_customer'] }
                ],
                update_booking_status: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['update_booking_status'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_booking_status'], required: true, description: 'Valid LatePoint status key (approved, pending, cancelled, no_show, completed, or custom).' },
                    { type: 'text', value: 'note', title: 'Note', task: ['update_booking_status'], description: 'Optional activity note logged after the status change.' },
                    { type: 'text', value: 'initiated_by', title: 'Initiated By', task: ['update_booking_status'], description: 'Optional initiator type (wp_user, agent, customer, etc.).' },
                    { type: 'text', value: 'initiated_by_id', title: 'Initiator ID', task: ['update_booking_status'], description: 'Optional ID associated with the initiator.' }
                ],
                add_booking_note: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['add_booking_note'], required: true },
                    { type: 'text', value: 'note', title: 'Note', task: ['add_booking_note'], required: true, description: 'Activity description stored on the booking timeline.' },
                    { type: 'text', value: 'code', title: 'Activity Code', task: ['add_booking_note'], description: 'Optional activity code (defaults to booking_note).' },
                    { type: 'text', value: 'initiated_by', title: 'Initiated By', task: ['add_booking_note'] },
                    { type: 'text', value: 'initiated_by_id', title: 'Initiator ID', task: ['add_booking_note'] }
                ],
                add_customer_note: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['add_customer_note'], description: 'Provide customer ID or email.' },
                    { type: 'text', value: 'email', title: 'Email', task: ['add_customer_note'] },
                    { type: 'text', value: 'note', title: 'Note', task: ['add_customer_note'], required: true }
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
    template: '#latepoint-action-template'
});
