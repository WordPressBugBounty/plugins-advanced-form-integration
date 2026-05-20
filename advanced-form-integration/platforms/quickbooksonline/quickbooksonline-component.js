/**
 * Advanced Form Integration — "quickbooksonline" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("quickbooksonline").
 *
 * Mirrors moneybird / gistpro / salesforcepro:
 *   - `credentialsList` holds the fetched accounts (AJAX),
 *     `fielddata.credId` holds the selection.
 *   - Field list is fetched per-task so create_customer and
 *     create_invoice get the right schemas.
 */

Vue.component('quickbooksonline', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsLoading: false,
            fieldsLoading: false,
            credentialsList: [],
            fields: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credentialsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_quickbooksonline_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.loadFields();
                    }
                }
                that.credentialsLoading = false;
            });
        },
        loadFields: function () {
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }
            var that = this;
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_quickbooksonline_fields',
                credId: this.fielddata.credId,
                task: this.action.task,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldsLoading = false;
                if (response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type || 'text',
                            value: field.key,
                            title: field.value,
                            task: [that.action.task],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        this.fetchCredentialsList();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.loadFields();
            }
        },
        'action.task': function () {
            if (this.fielddata.credId) {
                this.loadFields();
            }
        }
    },
    template: '#quickbooksonline-action-template'
});
