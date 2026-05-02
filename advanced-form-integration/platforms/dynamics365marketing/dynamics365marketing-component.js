/**
 * Advanced Form Integration - "dynamics365marketing" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("dynamics365marketing").
 */

Vue.component('dynamics365marketing', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'firstname', title: 'First Name', task: ['create_marketing_contact'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['create_marketing_contact'], required: false },
                { type: 'text', value: 'emailaddress1', title: 'Email Address', task: ['create_marketing_contact'], required: true }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#dynamics365marketing-action-template'
});
