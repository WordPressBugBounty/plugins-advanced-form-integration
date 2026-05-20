/**
 * Advanced Form Integration — "dynamics365sales" action component.
 * Reuses the Dynamics 365 CRM credential list.
 */

Vue.component('dynamics365sales', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    created: function () {
        if (typeof this.fielddata.credId === 'undefined') { this.$set(this.fielddata, 'credId', ''); }
    },
    mounted: function () {
        if (this.fielddata.credId) { this.getFields(); }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) { this.getFields(); }
        },
        'action.task': function (newVal, oldVal) {
            if (newVal !== oldVal) { this.getFields(); }
        }
    },
    methods: {
        getFields: function () {
            var that = this;
            if (!this.fielddata.credId) { this.fields = []; return; }
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_dynamics365sales_fields',
                task:   this.action.task,
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fields = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                that.fieldsLoading = false;
            }).fail(function () {
                that.fields = [];
                that.fieldsLoading = false;
            });
        }
    },
    template: '#dynamics365sales-action-template'
});
