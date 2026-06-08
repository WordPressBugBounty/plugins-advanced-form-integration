/**
 * Advanced Form Integration - "zohocampaigns" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohocampaigns").
 */

Vue.component('zohocampaigns', {
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
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_zohocampaigns_list', {
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
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohocampaigns_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
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

        if (this.fielddata.credId) {
            this.getList();
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            if (this.fielddata.credId) {
                this.getList();
            }
        }
    },
    template: '#zohocampaigns-action-template'
});
