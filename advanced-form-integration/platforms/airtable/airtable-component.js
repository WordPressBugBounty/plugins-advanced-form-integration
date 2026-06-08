/**
 * Advanced Form Integration - "airtable" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("airtable").
 */

Vue.component('airtable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            baseLoading: false,
            tableLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getBases();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_airtable_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getBases: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_airable_bases', {
                targetKey: 'bases',
                loadingKey: 'baseLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        },
        getTables: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_airtable_tables', {
                targetKey: 'tables',
                loadingKey: 'tableLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true,
                extraParams: { baseId: this.fielddata.baseId, task: this.action.task }
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_airtable_fields', {
                task: 'add_row',
                loadingKey: 'fieldLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: {
                    baseId: this.fielddata.baseId,
                    tableId: this.fielddata.tableId,
                    task: this.action.task
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.baseId == 'undefined') {
            this.fielddata.baseId = '';
        }

        if (typeof this.fielddata.tableId == 'undefined') {
            this.fielddata.tableId = '';
        }

        // Default Smart Field Coercion (typecast) on for new actions.
        if (typeof this.fielddata.typecast == 'undefined') {
            this.fielddata.typecast = true;
        }

        if (this.fielddata.typecast == "false") {
            this.fielddata.typecast = false;
        }

        this.getData();

        // Load bases for existing integrations (backward compatibility)
        if (this.fielddata.baseId && !this.fielddata.credId) {
            this.getBases();
        }

        if (this.fielddata.baseId) {
            this.getTables();

            if (this.fielddata.tableId) {
                this.getFields();
            }
        }
    },
    template: '#airtable-action-template'
});
