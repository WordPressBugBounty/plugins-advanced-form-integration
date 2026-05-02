/**
 * Advanced Form Integration - "sapmarketingcloud" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sapmarketingcloud").
 */

Vue.component('sapmarketingcloud', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'origin', title: 'Contact Origin', task: ['create_contact'], required: false, description: 'Defaults to WEB_FORM when left empty.' },
                { type: 'text', value: 'externalId', title: 'External Contact ID', task: ['create_contact'], required: false, description: 'Provide a unique identifier per origin; email is used when blank.' },
                { type: 'text', value: 'country', title: 'Country', task: ['create_contact'], required: false },
                { type: 'text', value: 'emailPermission', title: 'Email Opt-In', task: ['create_contact'], required: false, description: 'Map to true/false to control HasEmailOptIn.' }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.origin === 'undefined' || !this.fielddata.origin) {
            this.$set(this.fielddata, 'origin', 'WEB_FORM');
        }

        if (typeof this.fielddata.emailPermission === 'undefined') {
            this.$set(this.fielddata, 'emailPermission', 'true');
        }

        if (typeof this.fielddata.externalId === 'undefined') {
            this.$set(this.fielddata, 'externalId', '');
        }
    },
    template: '#sapmarketingcloud-action-template'
});
