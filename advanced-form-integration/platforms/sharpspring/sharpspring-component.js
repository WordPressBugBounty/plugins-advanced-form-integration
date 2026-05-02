/**
 * Advanced Form Integration - "sharpspring" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sharpspring").
 */

Vue.component('sharpspring', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_lead'], required: false }
            ]
        }
    },
    template: '#sharpspring-action-template'
});
