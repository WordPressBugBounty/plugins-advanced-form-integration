/**
 * Advanced Form Integration — "softr" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("softr").
 */

Vue.component('softr', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_softr_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        addCustomField: function () {
            this.fielddata.customFields.push({ key: '', value: '' });
        },
        removeCustomField: function (index) {
            this.fielddata.customFields.splice(index, 1);
        }
    },
    mounted: function () {
        var defaults = {
            credId:       '',
            databaseId:   '',
            tableId:      '',
            customFields: []
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        if (!Array.isArray(this.fielddata.customFields)) {
            this.fielddata.customFields = [];
        }
        this.fetchCredentialsList();
    },
    template: '#softr-action-template'
});
