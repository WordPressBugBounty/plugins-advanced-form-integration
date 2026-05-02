/**
 * Advanced Form Integration - "customerio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("customerio").
 */

Vue.component('customerio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'userId', title: 'User ID', task: ['add_people'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['add_people'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['add_people'], required: false },
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () { },
    template: '#customerio-action-template'
});
