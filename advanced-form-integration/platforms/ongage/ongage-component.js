/**
 * Advanced Form Integration - "ongage" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("ongage").
 */

Vue.component('ongage', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_to_list'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_to_list'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: '',
                tags: '',
                customFields: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#ongage-action-template'
});
