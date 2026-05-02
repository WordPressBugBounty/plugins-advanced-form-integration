/**
 * Advanced Form Integration - "blueshift" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("blueshift").
 */

Vue.component('blueshift', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'uid', title: 'Customer ID', task: ['sync_user'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['sync_user'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['sync_user'], required: false },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['sync_user'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['sync_user'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['sync_user'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['sync_user'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                tags: '',
                customAttributes: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#blueshift-action-template'
});
