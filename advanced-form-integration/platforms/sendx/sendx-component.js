/**
 * Advanced Form Integration - "sendx" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendx").
 */

Vue.component('sendx', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['subscribe'], required: false, description: 'YYYY-MM-DD' },
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sendx_credentials_list',
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
        this.getCredentials();
    },
    template: '#sendx-action-template'
});
