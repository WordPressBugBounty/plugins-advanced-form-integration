/**
 * Advanced Form Integration - "quickbase" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("quickbase").
 */

Vue.component('quickbase', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            appsLoading: false,
            tablesLoading: false
        };
    },
    methods: {
        getTables: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_quickbase_tables', {
                targetKey: 'apps',
                loadingKey: 'appsLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_quickbase_fields', {
                task: 'add',
                loadingKey: 'tablesLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: { appId: this.fielddata.appId }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') this.fielddata.credId = '';
        if (typeof this.fielddata.appId === 'undefined') this.fielddata.appId = '';
        if (this.fielddata.credId) this.getTables();
        if (this.fielddata.credId && this.fielddata.appId) this.getFields();
    },
    template: '#quickbase-action-template'
});
