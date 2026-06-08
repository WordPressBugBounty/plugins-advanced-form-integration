/**
 * Advanced Form Integration - "snovio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("snovio").
 */

Vue.component('snovio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'fullName', title: 'Full Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phones', title: 'Phones (comma-separated)', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'locality', title: 'Locality (City, State)', task: ['add_contact'], required: false },
                { type: 'text', value: 'socialLinks[linkedIn]', title: 'LinkedIn Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'social[twitter]', title: 'Twitter (X) Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'socialLinks[facebook]', title: 'Facebook Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'position', title: 'Job Position', task: ['add_contact'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'companySite', title: 'Company Website', task: ['add_contact'], required: false },
            ]
        };
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_snovio_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            var that = this;
            if (!this.fielddata.credId) return;
            this.listLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_snovio_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
                that.listLoading = false;
            }).fail(function () {
                that.listLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getLists();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        this.getCredentials();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#snovio-action-template'
});
