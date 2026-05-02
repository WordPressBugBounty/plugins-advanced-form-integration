/**
 * Advanced Form Integration - "encharge" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("encharge").
 */

Vue.component('encharge', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: []

        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_encharge_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getFields: function() {
            adfoinHelpers.getFields(this, 'adfoin_get_encharge_fields', {
                task: 'subscribe',
                loadingKey: 'fieldLoading',
                requireCredId: true,
                clearBefore: true
            });
        }
    },
    created: function () { },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getData();
    },
    template: '#encharge-action-template'
});
