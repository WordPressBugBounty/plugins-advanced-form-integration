/**
 * Advanced Form Integration - "reply" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("reply").
 */

Vue.component('reply', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'title', title: 'Job Title', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_to_list'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_to_list'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_to_list'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_to_list'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone_numbers', title: 'Optional Phone', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                sequenceId: '',
                customFields: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#reply-action-template'
});
