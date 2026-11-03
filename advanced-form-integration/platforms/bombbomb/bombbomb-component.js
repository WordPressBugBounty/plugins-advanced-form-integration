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
            listError: '',
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
            var that = this;
            this.listError = '';
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_bombbomb_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireSuccess: true
            }).done(function (response) {
                if (!response || !response.success) {
                    that.$set(that.fielddata, 'lists', {});
                    that.listError = (response && typeof response.data === 'string') ? response.data : 'Could not load BombBomb lists.';
                }
            }).fail(function () {
                that.listError = 'Could not load BombBomb lists.';
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
