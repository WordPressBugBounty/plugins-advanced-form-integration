/**
 * Advanced Form Integration - "copper" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("copper").
 */

Vue.component('copper', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            ownerLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getOwners();
            this.getFields();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_copper_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getOwners: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_copper_owner_list', {
                targetKey: 'ownerList',
                loadingKey: 'ownerLoading'
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_copper_all_fields', {
                task: 'add_contact',
                requireCredId: true,
                clearBefore: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();
    },
    watch: {},
    template: '#copper-action-template'
});
