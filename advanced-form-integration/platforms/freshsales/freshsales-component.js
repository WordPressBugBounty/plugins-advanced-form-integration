/**
 * Advanced Form Integration - "freshsales" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("freshsales").
 */

Vue.component('freshsales', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_freshsales_credentials', {
                loadingKey: 'credentialLoading',
                clearOnFail: true
            });
        },
        getFields: function () {
            if (!this.fielddata.credId) return;

            this.fields = [];
            var that = this;

            var accountRequestData = {
                'action': 'adfoin_get_freshsales_account_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, accountRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });
                    }
                }
            });

            var contactRequestData = {
                'action': 'adfoin_get_freshsales_contact_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, contactRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });
                    }
                }
            });

            var dealRequestData = {
                'action': 'adfoin_get_freshsales_deal_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, dealRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        this.getData();
    },
    template: '#freshsales-action-template'
});
