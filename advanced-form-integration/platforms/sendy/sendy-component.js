/**
 * Advanced Form Integration - "sendy" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendy").
 */

Vue.component('sendy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'gdpr', title: 'GDPR', task: ['subscribe'], required: false, description: 'Set to "true" for GDPR compliant opt-in of EU users' },
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sendy_credentials_list',
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
        handleAccountChange: function () {
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        this.getCredentials();
    },
    template: '#sendy-action-template'
});
