/**
 * Advanced Form Integration - "mailpoet" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailpoet").
 */

Vue.component('mailpoet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_mailpoet_subscriber_fields', { task: 'subscribe' });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailpoet_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists();
        this.getFields();
    },
    template: '#mailpoet-action-template'
});
