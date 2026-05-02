/**
 * Advanced Form Integration - "sapsalescloud" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sapsalescloud").
 */

Vue.component('sapsalescloud', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'leadName', title: 'Lead Name', task: ['create_lead'], required: false, description: 'Defaults to contact or company name when empty.' },
                { type: 'text', value: 'company', title: 'Company', task: ['create_lead'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_lead'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['create_lead'], required: false },
                { type: 'text', value: 'originCode', title: 'Origin Code', task: ['create_lead'], required: false, description: 'Defaults to 001 (Web).' }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.originCode === 'undefined' || !this.fielddata.originCode) {
            this.$set(this.fielddata, 'originCode', '001');
        }
    },
    template: '#sapsalescloud-action-template'
});
