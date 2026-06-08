/**
 * Advanced Form Integration - "intercom" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("intercom").
 */

Vue.component('intercom', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            that.credentialLoading = true;
            jQuery.post(ajaxurl, { 'action': 'adfoin_get_intercom_credentials', '_nonce': adfoin.nonce }, function (response) {
                that.credentialLoading = false;
                if (response.success && response.data) {
                    that.credentialsList = response.data;
                    if (that.fielddata.credId) { that.getFields(); }
                }
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_intercom_fields', {
                task: 'create_contact',
                includeCredId: true,
                clearBefore: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#intercom-action-template'
});
