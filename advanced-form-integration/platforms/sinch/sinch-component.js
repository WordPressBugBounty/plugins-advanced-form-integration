/**
 * Advanced Form Integration - "sinch" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sinch").
 */

Vue.component('sinch', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'msisdn', title: 'Single Number', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                groupId: '',
                addNumbers: '',
                removeNumbers: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#sinch-action-template'
});
