/**
 * Advanced Form Integration - "mailster" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailster").
 */

Vue.component('mailster', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: [],
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_mailster_fields', { task: 'subscribe' });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailster_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                requireSuccess: true
            });
        },
    },
    mounted: function () {
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.status === 'undefined') {
            this.fielddata.status = '0';
        }

        this.getLists();
        this.getFields();
    },
    template: '#mailster-action-template',
});
