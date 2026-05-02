/**
 * Advanced Form Integration - "copernica" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("copernica").
 */

Vue.component('copernica', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            dbLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getDatabases: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_copernica_databases', {
                targetKey: 'databases',
                loadingKey: 'dbLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_copernica_fields', {
                task: 'add_subscriber',
                loadingKey: 'fieldLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: { databaseId: this.fielddata.databaseId }
            });
        }
    },
    watch: {
        'fielddata.databaseId': function (val) {
            if (val) {
                this.getFields();
            }
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.databaseId === 'undefined') {
            this.fielddata.databaseId = '';
        }

        if (!this.fielddata.databases) {
            this.fielddata.databases = {};
        }

        if (this.fielddata.credId) {
            this.getDatabases();
        }

        if (this.fielddata.databaseId) {
            this.getFields();
        }
    },
    template: '#copernica-action-template'
});
