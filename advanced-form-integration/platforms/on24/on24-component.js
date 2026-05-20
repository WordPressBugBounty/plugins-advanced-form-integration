/**
 * Advanced Form Integration — "on24" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("on24").
 */

Vue.component('on24', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
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
                action: 'adfoin_get_on24_credentials',
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
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_on24_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return {
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['register_attendee'],
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
            credId:  '',
            eventId: ''
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
        this.loadFields();
    },
    template: '#on24-action-template'
});
