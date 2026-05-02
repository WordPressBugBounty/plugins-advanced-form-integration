/**
 * Advanced Form Integration - "aweber" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("aweber").
 */

Vue.component('aweber', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            accountLoading: false,
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_aweber_credentials_list', {
                loadingKey: 'credLoading',
                autoSelect: 'first',
                onLoaded: function () { that.getAccounts(); }
            });
        },
        getAccounts: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_aweber_accounts', {
                targetKey: 'accounts',
                loadingKey: 'accountLoading'
            });
        },
        getLists: function () {
            if (!this.fielddata.credId || !this.fielddata.accountId) {
                this.fielddata.lists = {};
                return;
            }
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_aweber_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                extraParams: { accountId: this.fielddata.accountId, task: this.action.task }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.accounts == 'undefined') {
            this.fielddata.accounts = {};
        }
        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }
        if (typeof this.fielddata.lists == 'undefined') {
            this.fielddata.lists = {};
        }
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }
        
        this.getCredentials();
        
        if (this.fielddata.credId && this.fielddata.accountId) {
            this.getLists();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getAccounts();
            }
        },
        'fielddata.accountId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getLists();
            }
        }
    },
    template: '#aweber-action-template'
});
