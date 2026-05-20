/**
 * Advanced Form Integration — "kintone" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("kintone").
 */

Vue.component('kintone', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        loadFields: function () {
            var that = this;
            if (!this.fielddata.credId || !this.fielddata.appId) {
                this.fields = [];
                return;
            }
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_kintone_fields',
                credId: this.fielddata.credId,
                appId:  this.fielddata.appId,
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
                            required: !!single.required,
                            description: single.description || ''
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
            credId:     '',
            appId:      '',
            recordJson: ''
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.loadFields();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.loadFields();
            }
        },
        'fielddata.appId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    template: '#kintone-action-template'
});
