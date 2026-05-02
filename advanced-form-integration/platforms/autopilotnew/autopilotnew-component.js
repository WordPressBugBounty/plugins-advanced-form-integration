/**
 * Advanced Form Integration - "autopilotnew" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("autopilotnew").
 */

Vue.component('autopilotnew', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_autopilotnew_credentials_list', {
                loadingKey: 'credentialLoading',
                autoSelect: 'legacy_or_first'
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#autopilotnew-action-template'
});
