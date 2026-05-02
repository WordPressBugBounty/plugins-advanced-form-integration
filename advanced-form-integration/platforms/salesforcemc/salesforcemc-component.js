/**
 * Advanced Form Integration - "salesforcemc" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("salesforcemc").
 */

Vue.component('salesforcemc', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['upsert_subscriber'], required: true },
                { type: 'text', value: 'subscriberKey', title: 'Subscriber Key', task: ['upsert_subscriber'], required: false, description: 'Defaults to the email address when left empty.' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['upsert_subscriber'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['upsert_subscriber'], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['upsert_subscriber'], required: false, description: 'Active, Unsubscribed, Held, etc. Defaults to Active.' }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#salesforcemc-action-template'
});
