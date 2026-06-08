/**
 * Advanced Form Integration - "keap" action component.
 * Talks to /rest/v2/contacts via adfoin_keap_request in keap.php.
 */

Vue.component('keap', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'email', value: 'email', title: 'Email Address', task: ['add_contact'], required: true, description: 'Required unless a phone number is mapped (Keap v2 contacts need at least one).' },
                { type: 'text', value: 'email2', title: 'Email 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'email3', title: 'Email 3', task: ['add_contact'], required: false },
                { type: 'text', value: 'optin', title: 'Opt-In Marketing', task: ['add_contact'], required: false, description: 'Set to "yes" / "true" to flag the primary email with an opt-in reason on Keap.' },

                { type: 'text', value: 'prefix', title: 'Name Prefix', task: ['add_contact'], required: false, description: 'e.g. Dr., Mr., Ms.' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'suffix', title: 'Name Suffix', task: ['add_contact'], required: false },
                { type: 'text', value: 'preferredName', title: 'Preferred Name', task: ['add_contact'], required: false },

                { type: 'text', value: 'company', title: 'Company Name', task: ['add_contact'], required: false, description: 'Keap reuses an existing company record with the same name automatically.' },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'contactType', title: 'Contact Type', task: ['add_contact'], required: false, description: 'e.g. Lead, Customer, Prospect.' },
                { type: 'text', value: 'website', title: 'Website', task: ['add_contact'], required: false },

                { type: 'text', value: 'mobilePhone', title: 'Mobile Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['add_contact'], required: false },

                { type: 'text', value: 'billingStreet1', title: 'Billing Street 1', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingStreet2', title: 'Billing Street 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingCity', title: 'Billing City', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingRegionCode', title: 'Billing Region Code', task: ['add_contact'], required: false, description: 'ISO 3166-2 code (e.g. US-AZ). Preferred over Billing State.' },
                { type: 'text', value: 'billingState', title: 'Billing State (deprecated)', task: ['add_contact'], required: false, description: 'Free-text state name. Kept for backwards compatibility.' },
                { type: 'text', value: 'billingZip', title: 'Billing Postal/ZIP', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingCountryCode', title: 'Billing Country Code', task: ['add_contact'], required: false, description: 'ISO 3166 alpha-3 (e.g. USA).' },

                { type: 'text', value: 'shippingStreet1', title: 'Shipping Street 1', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingStreet2', title: 'Shipping Street 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingCity', title: 'Shipping City', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingRegionCode', title: 'Shipping Region Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingState', title: 'Shipping State (deprecated)', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingZip', title: 'Shipping Postal/ZIP', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingCountryCode', title: 'Shipping Country Code', task: ['add_contact'], required: false },

                { type: 'text', value: 'birthDate', title: 'Birth Date', task: ['add_contact'], required: false, description: 'ISO date, e.g. 1985-03-15.' },
                { type: 'text', value: 'anniversaryDate', title: 'Anniversary Date', task: ['add_contact'], required: false, description: 'ISO date, e.g. 2015-06-20.' },
                { type: 'text', value: 'spouseName', title: 'Spouse Name', task: ['add_contact'], required: false },

                { type: 'text', value: 'facebook', title: 'Facebook', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter / X', task: ['add_contact'], required: false },
                { type: 'text', value: 'instagram', title: 'Instagram', task: ['add_contact'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            that.credentialLoading = true;
            jQuery.post(ajaxurl, { action: 'adfoin_get_keap_credentials', _nonce: adfoin.nonce }, function (response) {
                that.credentialLoading = false;
                if (response && response.success && response.data) {
                    that.credentialsList = response.data;
                }
            }).fail(function () {
                that.credentialLoading = false;
            });
        }
    },
    mounted: function () {
        var scalarDefaults = {
            credId: '',
            duplicateOption: 'Email',
            email: '',
            firstName: '',
            lastName: ''
        };
        for (var k in scalarDefaults) {
            if (typeof this.fielddata[k] === 'undefined') {
                this.$set(this.fielddata, k, scalarDefaults[k]);
            }
        }
        this.getCredentials();
    },
    template: '#keap-action-template'
});
