/**
 * Advanced Form Integration - "zendesk" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zendesk").
 */

Vue.component('zendesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'ticket_subject', title: 'Ticket Subject', task: ['create_ticket'], required: true },
                { type: 'textarea', value: 'ticket_comment', title: 'Ticket Description', task: ['create_ticket'], required: true },
                { type: 'text', value: 'requester_email', title: 'Requester Email', task: ['create_ticket'], required: false },
                { type: 'text', value: 'requester_name', title: 'Requester Name', task: ['create_ticket'], required: false },
                { type: 'text', value: 'ticket_priority', title: 'Priority', task: ['create_ticket'], required: false, description: 'Allowed: low, normal, high, urgent' },
                { type: 'text', value: 'ticket_status', title: 'Status', task: ['create_ticket'], required: false, description: 'Allowed: new, open, pending, hold, solved, closed' }
            ]
        };
    },
    created: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#zendesk-action-template'
});
