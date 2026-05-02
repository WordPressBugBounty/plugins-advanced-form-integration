/**
 * Advanced Form Integration - "jobber" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("jobber").
 */

Vue.component('jobber', {
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
            adfoinHelpers.loadFields(this, 'adfoin_get_jobber_fields', { task: 'create_job' });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#jobber-action-template'
});
