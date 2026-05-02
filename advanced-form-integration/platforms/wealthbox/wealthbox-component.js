/**
 * Advanced Form Integration - "wealthbox" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("wealthbox").
 */

Vue.component('wealthbox', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            ownerLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'prefix', title: 'Prefix', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'suffix', title: 'Suffix', task: ['add_contact'], required: false },
                { type: 'text', value: 'nickname', title: 'Nick Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitterName', title: 'Twitter Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedinUrl', title: 'LinkedIn URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'contactSource', title: 'Contact Source', task: ['add_contact'], required: false, description: 'Referral | Conference | Direct Mail | Cold Call | Other' },
                { type: 'text', value: 'contactType', title: 'Contact Type', task: ['add_contact'], required: false, description: 'Client | Past Client | Prospect | Vendor | Organization' },
                { type: 'text', value: 'status', title: 'Status', task: ['add_contact'], required: false, description: 'Active | Inactive' },
                { type: 'text', value: 'maritalStatus', title: 'Marital Status', task: ['add_contact'], required: false, description: 'Married | Single | Divorced | Widowed | Life Partner | Seperated | Unknown' },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['add_contact',], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'backgroundInfo', title: 'Background Information', task: ['add_contact'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_contact'], required: false, description: 'Female | Male | Non-binary | Unknown' },
                { type: 'text', value: 'householdTitle', title: 'Household Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'householdName', title: 'Household Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'personalEmail', title: 'Pesonal Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'workEmail', title: 'Work Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile', task: ['add_contact'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'birthDate', title: 'Birth Date', task: ['add_contact'], required: false },
                { type: 'text', value: 'addressLine1', title: 'Address line 1', task: ['add_contact'], required: false },
                { type: 'text', value: 'addressLine2', title: 'Address line 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'zipCode', title: 'ZIP Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'kind', title: 'Address Type', task: ['add_contact'], required: false, description: 'e.g. Work | Home' },
                { type: 'text', value: 'webAddress', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'webType', title: 'Web Address Type', task: ['add_contact'], required: false }
            ]
        }
    },
    methods: {
        getOwnerList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_wealthbox_owner_list', {
                targetKey: 'ownerList',
                loadingKey: 'ownerLoading',
                requireCredId: false,
                includeCredId: true
            });
        }
    },
    created: function () { },
    mounted: function () {
        var that = this;

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_wealthbox_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load owner list if credential is selected
                if (that.fielddata.credId) {
                    that.getOwnerList();
                }
            }
        });
    },
    template: '#wealthbox-action-template'
});
