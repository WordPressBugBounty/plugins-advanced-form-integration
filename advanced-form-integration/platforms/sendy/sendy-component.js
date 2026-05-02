/**
 * Advanced Form Integration - "sendy" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendy").
 */

Vue.component('sendy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_sendy_credentials_list', {
                loadingKey: 'credentialLoading',
                autoSelect: 'legacy_or_first'
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#sendy-action-template'
});
