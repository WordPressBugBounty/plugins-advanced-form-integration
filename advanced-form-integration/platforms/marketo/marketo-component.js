/**
 * Advanced Form Integration - "marketo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("marketo").
 */

Vue.component('marketo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_lead'], required: true }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#marketo-action-template'
});
