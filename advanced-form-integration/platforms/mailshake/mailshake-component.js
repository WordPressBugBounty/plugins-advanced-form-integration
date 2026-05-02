/**
 * Advanced Form Integration - "mailshake" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailshake").
 */

Vue.component('mailshake', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                campaignId: '',
                leadCatcherId: '',
                customFields: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#mailshake-action-template'
});
