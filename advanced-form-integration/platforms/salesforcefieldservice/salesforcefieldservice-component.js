/**
 * Advanced Form Integration - "salesforcefieldservice" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("salesforcefieldservice").
 */

Vue.component('salesforcefieldservice', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    mounted: function() {
        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    methods: {
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_salesforcefieldservice_credentials', {
                nonce: adfoin_admin.nonce
            });
        },
        loadFields: function() {
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            var self = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_salesforcefieldservice_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                self.fieldsLoading = false;
                if (response.success) {
                    self.fields = response.data.map(function(field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: [self.action.task],
                            required: field.required || false
                        };
                    });
                }
            });
        }
    },
    template: '#salesforcefieldservice-action-template'
});
