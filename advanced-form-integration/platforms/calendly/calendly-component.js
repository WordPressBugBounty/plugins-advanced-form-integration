/**
 * Advanced Form Integration — "calendly" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("calendly").
 */

Vue.component('calendly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credLoading: false,
            eventTypesList: [],
            eventTypesLoading: false
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_calendly_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.fetchEventTypes();
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchEventTypes: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.eventTypesList = [];
                return;
            }
            this.eventTypesLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_calendly_event_types',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.eventTypesList = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                that.eventTypesLoading = false;
            }).fail(function () {
                that.eventTypesList = [];
                that.eventTypesLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId:          '',
            event_type_uri:  '',
            max_event_count: 1
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
                this.fielddata.event_type_uri = '';
                this.fetchEventTypes();
            }
        }
    },
    template: '#calendly-action-template'
});
