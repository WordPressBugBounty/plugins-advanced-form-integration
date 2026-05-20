/**
 * Advanced Form Integration - "zohocreator" action component.
 */

Vue.component('zohocreator', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            apps: {},
            // No `fields` — this platform uses a free-form mappings textarea
            // rendered directly in the PHP template above. The Vue scope only
            // hydrates fielddata defaults and fetches the workspace list.
            fields: []
        };
    },
    created: function () {
        var defaults = ['account_owner_name', 'app_link_name', 'form_link_name', 'mappings', 'mode'];
        var that = this;
        defaults.forEach(function (key) {
            if (typeof that.fielddata[key] === 'undefined') {
                that.$set(that.fielddata, key, '');
            }
        });
        if (typeof this.fielddata.credId === 'undefined') { this.$set(this.fielddata, 'credId', ''); }
        if (typeof this.fielddata.mode === 'undefined' || this.fielddata.mode === '') {
            var triggerKey = (this.trigger && (this.trigger.key || this.trigger.trigger || this.trigger.value)) || '';
            this.$set(this.fielddata, 'mode', triggerKey === 'woocommerce' ? 'wc_items' : 'single');
        }
    },
    mounted: function () {
        if (this.fielddata.credId) { this.fetchApps(); }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) { this.fetchApps(); }
        }
    },
    methods: {
        fetchApps: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.apps = {};
                return;
            }
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_zohocreator_apps',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.apps = response.data;
                } else {
                    that.apps = {};
                }
            }).fail(function () {
                that.apps = {};
            });
        }
    },
    template: '#zohocreator-action-template'
});
