/**
 * Advanced Form Integration - "twilio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("twilio").
 */

Vue.component('twilio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'to', title: 'To', task: ['subscribe'], required: true },
                { type: 'textarea', value: 'body', title: 'Body', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getPhoneNumbers: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_twilio_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.from == 'undefined') {
            this.fielddata.from = '';
        }

        if (typeof this.fielddata.to == 'undefined') {
            this.fielddata.to = '';
        }

        if (typeof this.fielddata.body == 'undefined') {
            this.fielddata.body = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_twilio_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load phone numbers if credential is selected
                if (that.fielddata.credId) {
                    that.getPhoneNumbers();
                }
            }
        });
    },
    template: '#twilio-action-template'
});
