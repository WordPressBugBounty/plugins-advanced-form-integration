/**
 * Advanced Form Integration — "sfmc" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("sfmc").
 *
 * Account -> Data Extension -> fields. Field keys are `pk__<Name>` for
 * primary-key columns and `de__<Name>` for the rest; the PHP send path uses
 * the prefix to build the rowset "keys" / "values".
 */

Vue.component('sfmc', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return { credentialsList: [], credLoading: false, listLoading: false, fieldsLoading: false, listError: '', fields: [] };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, { action: 'adfoin_get_sfmc_credentials', _nonce: adfoin.nonce }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getDataExtensions();
                        if (that.fielddata.dataExtensionKey) {
                            that.getFields();
                        }
                    }
                }
                that.credLoading = false;
            }).fail(function () { that.credLoading = false; });
        },
        getDataExtensions: function () {
            var that = this;
            this.listError = '';
            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'dataExtensions', {});
                this.fielddata.dataExtensionKey = '';
                this.fields = [];
                return;
            }
            this.listLoading = true;
            jQuery.post(ajaxurl, { action: 'adfoin_get_sfmc_data_extensions', credId: this.fielddata.credId, _nonce: adfoin.nonce }, function (response) {
                that.listLoading = false;
                if (response && response.success && response.data) {
                    that.$set(that.fielddata, 'dataExtensions', response.data);
                } else {
                    that.$set(that.fielddata, 'dataExtensions', {});
                    that.listError = (response && response.data && response.data.message) ? response.data.message : '';
                }
            }).fail(function () {
                that.listLoading = false;
                that.$set(that.fielddata, 'dataExtensions', {});
            });
        },
        getFields: function () {
            var that = this;
            if (!this.fielddata.credId || !this.fielddata.dataExtensionKey) {
                this.fields = [];
                return;
            }
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sfmc_fields',
                credId: this.fielddata.credId,
                dataExtensionKey: this.fielddata.dataExtensionKey,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return { type: 'text', value: single.key, title: single.value, task: ['upsert_row'], required: !!single.required, description: single.description || '' };
                    });
                } else {
                    that.fields = [];
                    that.listError = (response && response.data && response.data.message) ? response.data.message : '';
                }
            }).fail(function () { that.fields = []; that.fieldsLoading = false; });
        }
    },
    mounted: function () {
        var defaults = { credId: '', dataExtensionKey: '', dataExtensions: {} };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
    },
    template: '#sfmc-action-template'
});
