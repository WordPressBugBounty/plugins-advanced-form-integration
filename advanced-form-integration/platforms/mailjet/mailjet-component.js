/**
 * Advanced Form Integration - "mailjet" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailjet").
 */

Vue.component('mailjet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false, description: 'Create a contact property titled "name" in Mailjet' },
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_mailjet_credentials', {
                loadingKey: 'credentialLoading',
                autoSelect: 'first',
                onLoaded: function () { that.getLists(); }
            });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailjet_list', {
                targetKey: 'list',
                loadingKey: 'listLoading'
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getLists();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailjet-action-template'
});
