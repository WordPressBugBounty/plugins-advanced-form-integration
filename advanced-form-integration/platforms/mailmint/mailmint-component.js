/**
 * Advanced Form Integration - "mailmint" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailmint").
 */

Vue.component('mailmint', {
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
            adfoinHelpers.getFields(this, 'adfoin_get_mailmint_fields', { task: 'subscribe' });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailmint_list', {
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
    template: '#mailmint-action-template'
});
