/**
 * Advanced Form Integration - "salesforce" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("salesforce").
 */

Vue.component('salesforce', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            fieldsLoading: false,
            campaignLoading: false,
            ownerLoading: false,
            credentialsList: [],
            fields: []
        };
    },
    methods: {
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_salesforce_credentials', {
                loadingKey: 'credLoading',
                autoSelect: 'legacy',
                onLoaded: function () {
                    that.getFields();
                    that.getOwners();
                    if (that.action.task === 'add_lead') {
                        that.getCampaigns();
                    }
                }
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_salesforce_fields', {
                task: ['add_lead', 'add_contact'],
                requireCredId: true,
                clearBefore: true,
                extraParams: { task: this.action.task }
            });
        },
        getCampaigns: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_salesforce_campaigns', {
                targetKey: 'campaigns',
                loadingKey: 'campaignLoading',
                requireSuccess: true
            });
        },
        getOwners: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_salesforce_owners', {
                targetKey: 'owners',
                loadingKey: 'ownerLoading',
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.accountId === 'undefined') {
            this.fielddata.accountId = '';
        }
        if (typeof this.fielddata.campaignId === 'undefined') {
            this.fielddata.campaignId = '';
        }
        if (typeof this.fielddata.ownerId === 'undefined') {
            this.fielddata.ownerId = '';
        }

        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
                this.getOwners();
                if (this.action.task === 'add_lead') {
                    this.getCampaigns();
                }
            }
        },
        'action.task': function (newTask) {
            if (this.fielddata.credId) {
                this.getFields();
                this.getOwners();
                if (newTask === 'add_lead') {
                    this.getCampaigns();
                }
            }
        }
    },
    template: '#salesforce-action-template',
});
