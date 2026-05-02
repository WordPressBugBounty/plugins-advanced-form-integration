/**
 * Advanced Form Integration - "insightly" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("insightly").
 */

Vue.component('insightly', {
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
            this.getOwnerList();
            this.getFields();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_insightly_credentials', {
                loadingKey: 'credentialLoading',
                clearOnFail: true
            });
        },
        getOwnerList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_insightly_owner_list', {
                targetKey: 'ownerList',
                loadingKey: 'ownerLoading'
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_insightly_all_fields', {
                task: 'add_contact',
                requireCredId: true,
                clearBefore: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();
    },
    watch: {},
    template: '#insightly-action-template'
});
