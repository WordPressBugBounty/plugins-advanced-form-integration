/**
 * Advanced Form Integration - "mailup" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailup").
 */

Vue.component('mailup', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            groupLoading: false,
            fields: [],
            credentialsList: []
        };
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: '',
                groupId: ''
            });
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_mailup_credentials');
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_mailup_fields', {
                task: 'subscribe',
                taskGate: 'subscribe',
                requireCredId: true,
                clearOnEmpty: true,
                onStart: function () { this.getLists(); }
            });
        },
        getLists: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailup_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        },
        getGroups: function () {
            if (!this.fielddata.credId || !this.fielddata.listId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailup_groups', {
                targetKey: 'groups',
                loadingKey: 'groupLoading',
                requireSuccess: true,
                extraParams: { listId: this.fielddata.listId }
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
        },
        'fielddata.listId': function(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getGroups();
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
    template: '#mailup-action-template'
});
