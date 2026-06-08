/**
 * Advanced Form Integration - "omnisend" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("omnisend").
 */

Vue.component('omnisend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'countryCode', title: 'Country Code', task: ['add_contact'], required: false, description: 'ISO 3166-1 alpha-2, e.g. US, GB. Takes priority over Country when both are provided.' },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['add_contact'], required: false, description: 'required format YYYY-MM-DD' },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_contact'], required: false, description: 'e.g. Male, Female' },
                { type: 'text', value: 'emailStatus', title: 'Email Status', task: ['add_contact'], required: false, description: 'subscribed | nonSubscribed | unsubscribed (default: subscribed)' }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_omnisend_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                that.credentialLoading = false;
                if (response.success) {
                    that.credentialsList = response.data;
                }
            }).fail(function () {
                that.credentialLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        this.getCredentials();
    },
    template: '#omnisend-action-template'
});
