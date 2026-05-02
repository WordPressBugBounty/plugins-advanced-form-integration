/**
 * Advanced Form Integration - "moneybird" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("moneybird").
 */

Vue.component('moneybird', {
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
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_moneybird_credentials', {
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
                'action': 'adfoin_get_moneybird_fields',
                'credId': this.fielddata.credId,
                'task': this.action.task,
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                self.fieldsLoading = false;
                if (response.success) {
                    self.fields = response.data.map(function(field) {
                        return {
                            type: field.type || 'text',
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
    template: '#moneybird-action-template'
});
