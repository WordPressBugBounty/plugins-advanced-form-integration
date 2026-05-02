/**
 * Advanced Form Integration - "mailchimp" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailchimp").
 */

Vue.component('mailchimp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
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
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_mailchimp_credentials', {
                loadingKey: 'credentialLoading',
                autoSelect: 'first',
                onLoaded: function () { that.getList(); }
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailchimp_list', {
                targetKey: 'list',
                loadingKey: 'listLoading'
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.getList();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.doubleoptin == 'undefined') {
            this.fielddata.doubleoptin = false;
        }

        if (typeof this.fielddata.doubleoptin != 'undefined') {
            if (this.fielddata.doubleoptin == "false") {
                this.fielddata.doubleoptin = false;
            }
        }

        this.getData();
    },
    template: '#mailchimp-action-template'
});
