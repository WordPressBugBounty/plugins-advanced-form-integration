/**
 * Advanced Form Integration - "saleshandy" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("saleshandy").
 */

Vue.component('saleshandy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            sequenceLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            this.getSequences();
            this.getFields();
        },
        getSequences: function () {
            const that = this;
            this.sequenceLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_saleshandy_sequences',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.sequenceLoading = false;
                if (response.success) {
                    that.fielddata.sequences = response.data;
                }
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_saleshandy_fields', {
                task: 'add_prospect',
                includeCredId: true
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.sequenceId) this.fielddata.sequenceId = '';
        if (!this.fielddata.sequences) this.fielddata.sequences = {};

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#saleshandy-action-template'
});
