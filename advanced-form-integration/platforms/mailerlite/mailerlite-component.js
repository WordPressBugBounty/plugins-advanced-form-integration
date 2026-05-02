/**
 * Advanced Form Integration - "mailerlite" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailerlite").
 */

Vue.component('mailerlite', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_mailerlite_credentials', {
                loadingKey: 'credentialLoading',
                autoSelect: 'first',
                onLoaded: function () { that.getLists(); }
            });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailerlite_list', {
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
    created: function () {

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

        this.getCredentials();
    },
    template: '#mailerlite-action-template'
});
