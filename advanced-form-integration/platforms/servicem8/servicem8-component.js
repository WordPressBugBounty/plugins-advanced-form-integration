/**
 * Advanced Form Integration - "servicem8" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("servicem8").
 */

Vue.component('servicem8', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, { credId: '' });
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_servicem8_fields', { task: 'create_job' });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();

        // Pick the account when there is only one.
        var accounts = this.$el.querySelectorAll('select[name="fieldData[credId]"] option[value]:not([value=""])');
        if (!this.fielddata.credId && accounts.length === 1) {
            this.fielddata.credId = accounts[0].value;
        }
    },
    template: '#servicem8-action-template'
});
