/**
 * Advanced Form Integration - "zoho_meeting" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zoho_meeting").
 */

Vue.component('zoho_meeting', {
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
            adfoinHelpers.loadFields(this, 'adfoin_get_zoho_meeting_fields', {
                task: 'register_contact',
                taskGate: 'register_contact'
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
    template: '#zoho_meeting-action-template'
});
