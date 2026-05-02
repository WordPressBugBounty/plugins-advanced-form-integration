/**
 * Advanced Form Integration - "slicktext" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("slicktext").
 */

Vue.component('slicktext', {
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
            adfoinHelpers.loadFields(this, 'adfoin_get_slicktext_fields', {
                task: 'create_contact',
                taskGate: 'create_contact'
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#slicktext-action-template'
});
