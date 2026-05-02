/**
 * Advanced Form Integration - "drip" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("drip").
 */

Vue.component('drip', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            accountLoading: false,
            campaignLoading: false,
            workflowLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['create_subscriber'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'address1', title: 'Address 1', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'address2', title: 'Address 2', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['create_subscriber'], required: false },
            ]

        }
    },
    methods: {
        getData: function () {
            this.getAccounts();
            if (this.fielddata.accountId) {
                this.getList();
            }
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_drip_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getAccounts: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_drip_accounts', {
                targetKey: 'accounts',
                loadingKey: 'accountLoading',
                requireCredId: false,
                includeCredId: true
            });
        },
        getList: function () {
            var that = this;
            this.campaignLoading = true;
            this.workflowLoading = true;

            var listData = {
                'action': 'adfoin_get_drip_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'accountId': this.fielddata.accountId
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var list = response.data;
                that.fielddata.list = list;
                that.campaignLoading = false;

                var workflowData = {
                    'action': 'adfoin_get_drip_workflows',
                    '_nonce': adfoin.nonce,
                    'credId': that.fielddata.credId,
                    'accountId': that.fielddata.accountId
                };

                jQuery.post(ajaxurl, workflowData, function (response) {
                    var workflows = response.data;
                    that.fielddata.workflows = workflows;
                    that.workflowLoading = false;
                });
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

        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }

        if (typeof this.fielddata.campaignId == 'undefined') {
            this.fielddata.campaignId = '';
        }

        if (typeof this.fielddata.workflowId == 'undefined') {
            this.fielddata.workflowId = '';
        }

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#drip-action-template'
});
