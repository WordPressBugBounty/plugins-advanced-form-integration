/**
 * Advanced Form Integration - "cleverreach" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("cleverreach").
 */

Vue.component('cleverreach', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: [],
            credentialsList: []
        };
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                groupId: ''
            });
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_cleverreach_credentials');
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_cleverreach_fields', {
                task: 'subscribe',
                taskGate: 'subscribe',
                requireCredId: true,
                clearOnEmpty: true,
                onStart: function () { this.getGroups(); }
            });
        },
        getGroups: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_cleverreach_groups', {
                targetKey: 'groups',
                loadingKey: 'groupLoading',
                requireSuccess: true
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
    template: '#cleverreach-action-template'
});
