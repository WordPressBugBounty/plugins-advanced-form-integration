/**
 * Advanced Form Integration - "gotowebinar" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("gotowebinar").
 */

Vue.component('gotowebinar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, { credId: '' });
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_gotowebinar_credentials');
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_gotowebinar_fields', {
                task: 'create_registrant',
                taskGate: 'create_registrant',
                requireCredId: true,
                clearOnEmpty: true
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        },
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.getCredentials();
        
        // Load fields if credId is already set
        if (this.fielddata.credId) {
            this.loadFields();
        }
    },
    template: '#gotowebinar-action-template'
});
