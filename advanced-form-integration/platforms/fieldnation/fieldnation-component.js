/**
 * Advanced Form Integration - "fieldnation" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fieldnation").
 */

Vue.component('fieldnation', {
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
            adfoinHelpers.loadFields(this, 'adfoin_get_fieldnation_fields', { task: 'create_work_order' });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#fieldnation-action-template'
});
