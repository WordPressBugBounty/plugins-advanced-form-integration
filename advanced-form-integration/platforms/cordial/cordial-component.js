/**
 * Advanced Form Integration - "cordial" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("cordial").
 */

Vue.component('cordial', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['sync_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['sync_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['sync_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['sync_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['sync_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['sync_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['sync_contact'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listIds: '',
                customAttributes: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#cordial-action-template'
});
