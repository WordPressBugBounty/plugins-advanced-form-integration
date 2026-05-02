/**
 * Advanced Form Integration - "easysendy" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("easysendy").
 */

Vue.component('easysendy', {
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
            this.getList();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_easysendy_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_easysendy_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
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

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#easysendy-action-template'
});
