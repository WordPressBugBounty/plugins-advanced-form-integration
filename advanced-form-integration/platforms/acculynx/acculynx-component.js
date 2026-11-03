Vue.component('acculynx', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return { fieldsLoading: false, fields: [], options: { contactTypes: {}, leadSources: {} }, optionsError: '' };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_acculynx_fields', {
                task: 'create_lead', includeCredId: true, clearBefore: true
            });
            this.getOptions();
        },
        getOptions: function () {
            var that = this;
            this.optionsError = '';
            this.options = { contactTypes: {}, leadSources: {} };

            if (!this.fielddata.credId) return;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acculynx_options',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success) {
                    that.options = response.data;
                } else {
                    that.optionsError = (response && typeof response.data === 'string') ? response.data : 'Could not load AccuLynx settings.';
                }
            }).fail(function () {
                that.optionsError = 'Could not load AccuLynx settings.';
            });
        }
    },
    mounted: function () {
        var defaults = { credId: '', contactTypeId: '', leadSourceId: '' };
        var that = this;

        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') that.$set(that.fielddata, k, defaults[k]);
        });

        if (this.fielddata.credId) this.getFields();
    },
    watch: { 'fielddata.credId': function (n, o) { if (n !== o) this.getFields(); } },
    template: '#acculynx-action-template'
});
