/**
 * Advanced Form Integration — "knack" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("knack").
 */

Vue.component('knack', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            objectLoading: false,
            fieldsLoading: false,
            credentialsList: [],
            fields: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_knack_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.fetchObjects();
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchObjects: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.objects = [];
                return;
            }
            this.objectLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_knack_objects',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.objects = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                that.objectLoading = false;
                if (that.fielddata.objectKey) {
                    that.fetchFields();
                }
            }).fail(function () {
                that.fielddata.objects = [];
                that.objectLoading = false;
            });
        },
        fetchFields: function () {
            var that = this;
            if (!this.fielddata.credId || !this.fielddata.objectKey) {
                this.fields = [];
                return;
            }
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_knack_fields',
                credId: this.fielddata.credId,
                objectKey: this.fielddata.objectKey,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return {
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['create_record'],
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
        }
    },
    mounted: function () {
        var defaults = {
            credId:    '',
            objectKey: '',
            objects:   []
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
                this.fielddata.objectKey = '';
                this.fields = [];
                this.fetchObjects();
            }
        },
        'fielddata.objectKey': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fetchFields();
            }
        }
    },
    template: '#knack-action-template'
});
