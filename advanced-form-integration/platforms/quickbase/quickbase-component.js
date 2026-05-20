/**
 * Advanced Form Integration - "quickbase" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("quickbase").
 */

Vue.component('quickbase', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            appLoading: false,
            tableLoading: false,
            fieldLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getApps();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_quickbase_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getApps: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_quickbase_apps', {
                targetKey: 'apps',
                loadingKey: 'appLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        },
        getTables: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_quickbase_tables', {
                targetKey: 'tables',
                loadingKey: 'tableLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true,
                extraParams: { appId: this.fielddata.appId }
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_quickbase_fields', {
                task: 'add_record',
                loadingKey: 'fieldLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: { tableId: this.fielddata.tableId }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') this.fielddata.credId = '';
        if (typeof this.fielddata.appId === 'undefined') this.fielddata.appId = '';
        if (typeof this.fielddata.tableId === 'undefined') this.fielddata.tableId = '';

        this.getData();

        if (this.fielddata.appId) {
            this.getTables();
            if (this.fielddata.tableId) {
                this.getFields();
            }
        }
    },
    template: '#quickbase-action-template'
});
