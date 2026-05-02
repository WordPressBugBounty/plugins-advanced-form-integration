/**
 * Advanced Form Integration - "hubspot" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("hubspot").
 */

Vue.component('hubspot', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            contactLoading: false,
            fields: []

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_hubspot_credentials', {
                loadingKey: 'credentialLoading',
                clearOnFail: true
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_hubspot_contact_fields', {
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

        this.getData();
    },
    watch: {},
    template: '#hubspot-action-template'
});
