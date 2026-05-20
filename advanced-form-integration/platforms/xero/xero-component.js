/**
 * Advanced Form Integration — "xero" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("xero").
 *
 * Mirrors moneybird:
 *   - credentialsList + tenants (organisations) both fetched via AJAX
 *     after the account is picked.
 *   - Field list switches per task (create_contact vs create_invoice).
 */

Vue.component('xero', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsLoading: false,
            tenantsLoading: false,
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
                action: 'adfoin_get_xero_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.loadTenants();
                        that.loadFields();
                    }
                }
                that.credentialsLoading = false;
            });
        },
        loadTenants: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.tenants = {};
                return;
            }
            this.tenantsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_xero_tenants',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.tenants = (response && response.success && response.data) ? response.data : {};
                var keys = Object.keys(that.fielddata.tenants);
                if (!that.fielddata.tenantId && keys.length === 1) {
                    that.fielddata.tenantId = keys[0];
                }
                that.tenantsLoading = false;
            }).fail(function () {
                that.fielddata.tenants = {};
                that.tenantsLoading = false;
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
                action: 'adfoin_get_xero_fields',
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
        var defaults = {
            credId:    '',
            tenantId:  '',
            tenants:   {}
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });

        this.fetchCredentialsList();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.loadTenants();
                this.loadFields();
            }
        },
        'action.task': function () {
            if (this.fielddata.credId) {
                this.loadFields();
            }
        }
    },
    template: '#xero-action-template'
});
