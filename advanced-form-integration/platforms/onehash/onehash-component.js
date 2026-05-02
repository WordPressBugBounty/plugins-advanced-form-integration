/**
 * Advanced Form Integration - "onehash" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("onehash").
 */

Vue.component('onehash', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead', 'add_customer', 'add_contact'], required: true },
                { type: 'text', value: 'fullName', title: 'Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'customerName', title: 'Customer Name', task: ['add_customer'], required: false },
                { type: 'text', value: 'customerType', title: 'Customer Type', task: ['add_customer'], required: false },
                { type: 'text', value: 'customerGroup', title: 'Customer Group', task: ['add_customer'], required: false },
                { type: 'text', value: 'territory', title: 'Territory', task: ['add_customer'], required: false },
                { type: 'text', value: 'leadName', title: 'Lead Name', task: ['add_customer'], required: false },
                { type: 'text', value: 'opportunityName', title: 'Opportunity Name', task: ['add_customer'], required: false },
                { type: 'text', value: 'company', title: 'Company Name', task: ['add_lead',], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['add_lead'], required: false, description: 'Active | Inactive' },
                { type: 'text', value: 'salutation', title: 'Salutation', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'designation', title: 'Designation', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'source', title: 'Source', task: ['add_lead'], required: false },
                { type: 'text', value: 'campaignName', title: 'Campaign Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'contactBy', title: 'Contact By', task: ['add_lead'], required: false },
                { type: 'text', value: 'contactDate', title: 'Contact Date', task: ['add_lead'], required: false },
                { type: 'text', value: 'endsOn', title: 'Ends On', task: ['add_lead'], required: false },
                { type: 'text', value: 'addressType', title: 'Address Type', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'addressTitle', title: 'Address Title', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'addressLine1', title: 'Address line 1', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'addressLine2', title: 'Address line 2', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'county', title: 'County', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'pincode', title: 'Postal Code', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'phonNO', title: 'Phone', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'mobileNo', title: 'Mobile No.', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'doctype', title: 'Doctype', task: ['add_lead', 'add_customer'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_onehash_credentials_list', {
                loadingKey: 'credentialLoading',
                autoSelect: 'legacy_or_first'
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#onehash-action-template'
});
