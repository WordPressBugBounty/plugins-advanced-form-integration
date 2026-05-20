/**
 * Advanced Form Integration — "dynamics365" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("dynamics365").
 */

Vue.component('dynamics365', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    created: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    mounted: function () {
        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        // Field list varies per task (contact / lead / account). Refetch
        // whenever the account OR the task changes.
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

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_dynamics365_fields',
                task:   this.action.task,
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data;
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fields = [];
                that.fieldsLoading = false;
            });
        }
    },
    template: '#dynamics365-action-template'
});
