/**
 * Advanced Form Integration — "dynamics365marketing" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("dynamics365marketing").
 *
 * Free tier. Reuses the Dynamics 365 CRM credential list. The single
 * task `create_marketing_contact` upserts a contact by email and (when
 * a list is selected) adds the contact to a static Marketing List in
 * one pass.
 */

Vue.component('dynamics365marketing', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            listLoading: false,
            fields: []
        };
    },
    created: function () {
        if (typeof this.fielddata.credId === 'undefined') { this.$set(this.fielddata, 'credId', ''); }
        if (typeof this.fielddata.listId === 'undefined') { this.$set(this.fielddata, 'listId', ''); }
        if (typeof this.fielddata.lists  === 'undefined') { this.$set(this.fielddata, 'lists',  {}); }
    },
    mounted: function () {
        if (this.fielddata.credId) {
            this.getFields();
            this.loadLists();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
                this.loadLists();
            }
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
                action: 'adfoin_get_dynamics365marketing_fields',
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
        },
        loadLists: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                return;
            }
            this.listLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_dynamics365marketing_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.lists = (response && response.success && response.data) ? response.data : {};
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.lists = {};
                that.listLoading = false;
            });
        }
    },
    template: '#dynamics365marketing-action-template'
});
