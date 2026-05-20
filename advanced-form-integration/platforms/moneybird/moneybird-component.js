/**
 * Advanced Form Integration — "moneybird" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("moneybird").
 *
 * Mirrors the multi-account pattern used by salesforcepro / gistpro:
 * `credentialsList` holds fetched accounts, `fielddata.credId` holds
 * the selection. Once an account is chosen we fetch the list of
 * Moneybird administrations the OAuth token has access to and
 * populate the Administration dropdown.
 */

Vue.component('moneybird', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsLoading: false,
            administrationsLoading: false,
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
                action: 'adfoin_get_moneybird_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.loadAdministrations();
                        that.loadFields();
                    }
                }
                that.credentialsLoading = false;
            });
        },
        loadAdministrations: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.administrations = {};
                return;
            }
            this.administrationsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_moneybird_administrations',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.administrations = (response && response.success && response.data) ? response.data : {};
                // Auto-select when there's only one administration on the
                // account — most Moneybird customers have just one.
                var keys = Object.keys(that.fielddata.administrations);
                if (!that.fielddata.administrationId && keys.length === 1) {
                    that.fielddata.administrationId = keys[0];
                }
                that.administrationsLoading = false;
            }).fail(function () {
                that.fielddata.administrations = {};
                that.administrationsLoading = false;
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
                action: 'adfoin_get_moneybird_fields',
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
            credId:            '',
            administrationId:  '',
            administrations:   {}
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
                this.loadAdministrations();
                this.loadFields();
            }
        },
        'action.task': function () {
            if (this.fielddata.credId) {
                this.loadFields();
            }
        }
    },
    template: '#moneybird-action-template'
});
