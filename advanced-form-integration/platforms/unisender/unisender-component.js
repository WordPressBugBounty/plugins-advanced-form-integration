/**
 * Advanced Form Integration - "unisender" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("unisender").
 */

Vue.component('unisender', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: '',
                doubleOptin: '0',
                overwrite: '1',
                tags: '',
                customFields: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#unisender-action-template'
});
