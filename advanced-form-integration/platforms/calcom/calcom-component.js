/**
 * Advanced Form Integration — "calcom" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("calcom").
 */

Vue.component('calcom', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credLoading: false,
            eventTypesList: [],
            eventTypesLoading: false,
            fields: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_calcom_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.fetchEventTypes();
                        that.fetchFields();
                    }
                }
            }).always(function () { that.credLoading = false; });
        },
        fetchEventTypes: function () {
            var that = this;
            if (!this.fielddata.credId) { this.eventTypesList = []; return; }
            this.eventTypesLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_calcom_event_types',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.eventTypesList = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                if (response && !response.success && response.data && response.data.message) {
                    window.alert('Cal.com: ' + response.data.message);
                }
            }).always(function () { that.eventTypesLoading = false; });
        },
        fetchFields: function () {
            var that = this;
            if (!this.fielddata.credId) { this.fields = []; return; }
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_calcom_fields',
                credId: this.fielddata.credId,
                eventTypeId: this.fielddata.event_type_id || '',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return { type: 'text', value: single.key, title: single.value, task: ['create_booking'], required: !!single.required, description: single.description };
                    });
                } else {
                    that.fields = [];
                }
            }).fail(function () { that.fields = []; });
        }
    },
    mounted: function () {
        var defaults = { credId: '', event_type_id: '' };
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
                this.fielddata.event_type_id = '';
                this.fetchEventTypes();
                this.fetchFields();
            }
        },
        'fielddata.event_type_id': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fetchFields();
            }
        }
    },
    template: '#calcom-action-template'
});
