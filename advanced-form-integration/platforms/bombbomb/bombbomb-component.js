/**
 * Advanced Form Integration - "bombbomb" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("bombbomb").
 */

Vue.component('bombbomb', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: ''
            });
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_bombbomb_credentials');
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_bombbomb_fields', {
                task: 'add_contact',
                taskGate: 'add_contact',
                requireCredId: true,
                clearOnEmpty: true,
                onStart: function () { this.getLists(); }
            });
        },
        getLists: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_bombbomb_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
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
    template: '#bombbomb-action-template'
});
