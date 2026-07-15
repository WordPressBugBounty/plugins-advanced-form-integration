/**
 * Advanced Form Integration — "kartra" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("kartra").
 */

Vue.component('kartra', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credLoading: false,
            fieldsLoading: false,
            listLoading: false,
            fields: [],
            lists: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_kartra_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                        that.loadList();
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchFields: function () {
            var that = this;
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_kartra_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return {
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['subscribe'],
                            required: !!single.required
                        };
                    });
                } else {
                    that.fields = [];
                }
            }).fail(function () {
                that.fields = [];
                that.fieldsLoading = false;
            });
        },
        loadList: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.lists = [];
                return;
            }
            this.listLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_kartra_list',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.lists = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                that.listLoading = false;
            }).fail(function () {
                that.lists = [];
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId: '',
            listId: ''
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
        this.fetchFields();
        if (this.fielddata.credId) {
            this.loadList();
        }
    },
    template: '#kartra-action-template'
});
