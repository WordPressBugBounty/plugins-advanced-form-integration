/**
 * Advanced Form Integration - "activecampaign" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("activecampaign").
 */

Vue.component('activecampaign', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            automationLoading: false,
            pipelineLoading: false,
            accountLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email [Contact]', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name [Contact]', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name [Contact]', task: ['subscribe'], required: false },
                { type: 'text', value: 'phoneNumber', title: 'Phone [Contact]', task: ['subscribe'], required: false },
                { type: 'text', value: 'note', title: 'Note', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getLists();
            this.getAutomations();
            this.getAccounts();
            this.getDealFields();
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_activecampaign_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getLists: function() {
            var that = this;
            this.listLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getAutomations: function() {
            var that = this;
            this.automationLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_automations',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                that.fielddata.automations = response.data;
                that.automationLoading = false;
            });
        },
        getAccounts: function() {
            var that = this;
            this.accountLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_accounts',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                that.fielddata.accounts = response.data;
                that.accountLoading = false;
            });
        },
        getDealFields: function() {
            var that = this;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_deal_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.automationId == 'undefined') {
            this.fielddata.automationId = '';
        }

        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
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

        if (typeof this.fielddata.phoneNumber == 'undefined') {
            this.fielddata.phoneNumber = '';
        }

        if (typeof this.fielddata.update == 'undefined') {
            this.fielddata.update = false;
        }

        if (typeof this.fielddata.update != 'undefined') {
            if (this.fielddata.update == "false") {
                this.fielddata.update = false;
            }
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getData();
    },
    template: '#activecampaign-action-template'
});
