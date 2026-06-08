/**
 * Advanced Form Integration - "mautic" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mautic").
 */

Vue.component('mautic', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'title', title: 'Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile Number', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['add_contact'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['add_contact'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_contact'], required: false },
                { type: 'text', value: 'position', title: 'Position', task: ['add_contact'], required: false },
                { type: 'text', value: 'address1', title: 'Address Line 1', task: ['add_contact'], required: false },
                { type: 'text', value: 'address2', title: 'Address Line 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'zipcode', title: 'ZIP', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['add_contact'], required: false },
                { type: 'text', value: 'instagram', title: 'Instagram', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['add_contact'], required: false },
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mautic_credentials',
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
    template: '#mautic-action-template'
});
