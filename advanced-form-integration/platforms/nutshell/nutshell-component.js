/**
 * Advanced Form Integration - "nutshell" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("nutshell").
 */

Vue.component('nutshell', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fieldsLoading: false,
            ownerLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_nutshell_credentials_list', {
                loadingKey: 'credentialLoading',
                autoSelect: 'legacy',
                onLoaded: function () {
                    that.getFields();
                    that.getOwners();
                }
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_nutshell_fields', {
                task: 'add_contact',
                clearBefore: true,
                extraParams: { task: this.action.task }
            });
        },
        getOwners: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_nutshell_owners', {
                targetKey: 'owners',
                loadingKey: 'ownerLoading',
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.ownerId === 'undefined') {
            this.fielddata.ownerId = '';
        }

        this.getData();
    },
    watch: {
        'action.task': function (newTask) {
            if (this.fielddata.credId) {
                this.getFields();
                this.getOwners();
            }
        }
    },
    template: '#nutshell-action-template'
});
