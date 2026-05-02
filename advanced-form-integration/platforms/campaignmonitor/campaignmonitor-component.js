/**
 * Advanced Form Integration - "campaignmonitor" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("campaignmonitor").
 */

Vue.component('campaignmonitor', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            accountLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['create_subscriber'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['create_subscriber'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getAccounts();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_campaignmonitor_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getAccounts: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_campaignmonitor_accounts', {
                targetKey: 'accounts',
                loadingKey: 'accountLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_campaignmonitor_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true,
                extraParams: { accountId: this.fielddata.accountId, task: this.action.task }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        this.getData();

        // Load accounts for existing integrations (backward compatibility)
        if (this.fielddata.accountId && !this.fielddata.credId) {
            this.getAccounts();
        }

        if (this.fielddata.accountId) {
            this.getList();
        }
    },
    template: '#campaignmonitor-action-template'
});
