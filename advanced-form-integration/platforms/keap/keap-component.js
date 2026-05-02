/**
 * Advanced Form Integration - "keap" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("keap").
 */

Vue.component('keap', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'title', title: 'Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'suffix', title: 'Suffix', task: ['add_contact'], required: false },
                { type: 'text', value: 'contactType', title: 'Contact Type', task: ['add_contact'], description: 'Lead, Customer, Other', required: false },
                { type: 'text', value: 'company', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'optin', title: 'Opt-In', task: ['add_contact'], description: 'Has this person opted-in to receiving marketing communications from you? Insert "true" to send them email through Keap.', required: false },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'email2', title: 'Email 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'email3', title: 'Email 3', task: ['add_contact'], required: false },
                { type: 'text', value: 'mobilePhone', title: 'Mobile Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingStreet1', title: 'Billing Street1', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingStreet2', title: 'Billing Street2', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingCity', title: 'Billing City', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingState', title: 'Billing State', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingZip', title: 'Billing Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingCountryCode', title: 'Billing Country Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingStreet1', title: 'Shipping Street1', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingStreet2', title: 'Shipping Street2', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingCity', title: 'Shipping City', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingState', title: 'Shipping State', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingZip', title: 'Shipping Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingCountryCode', title: 'Shipping Country Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['add_contact'], required: false },
                { type: 'text', value: 'anniversary', title: 'Anniversary', task: ['add_contact'], required: false },
                { type: 'text', value: 'spouseName', title: 'Spouse Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['add_contact'], required: false },
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        // var pipelineRequestData = {
        //     'action': 'adfoin_get_keap_pipelines',
        //     '_nonce': adfoin.nonce
        // };

        // jQuery.post( ajaxurl, pipelineRequestData, function( response ) {

        //     if( response.success ) {
        //         if( response.data ) {
        //             response.data.map(function(single) {
        //                 that.fields.push( { type: 'text', value: single.key, title: single.value, task: ['add_contact'], required: false, description: single.description } );
        //             });
        //         }
        //     }
        // });
    },
    template: '#keap-action-template'
});
