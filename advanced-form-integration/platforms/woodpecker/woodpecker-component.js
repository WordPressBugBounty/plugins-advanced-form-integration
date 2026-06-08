/**
 * Advanced Form Integration - "woodpecker" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("woodpecker").
 */

Vue.component('woodpecker', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_woodpecker_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
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
    template: '#woodpecker-action-template'
});
