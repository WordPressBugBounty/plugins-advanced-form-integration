/**
 * Advanced Form Integration - "braze" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("braze").
 */

Vue.component('braze', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_user'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_user'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_user'], required: false }
            ]
        }
    },
    template: '#braze-action-template'
});
