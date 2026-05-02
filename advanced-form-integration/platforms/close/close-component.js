/**
 * Advanced Form Integration - "close" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("close").
 */

Vue.component('close', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            ownerLoading: false,
            fields: []

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_close_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_close_all_fields', {
                task: 'add_lead',
                includeCredId: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();
    },
    template: '#close-action-template'
});
