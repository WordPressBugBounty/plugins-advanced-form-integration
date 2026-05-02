/**
 * Advanced Form Integration - "smartlead" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("smartlead").
 */

Vue.component('smartlead', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            campaignLoading: false,
            fields: [
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_lead'], required: true, description: '' },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_lead'], required: true, description: '' },
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead'], required: true, description: '' },
                { type: 'text', value: 'phone_number', title: 'Phone Number', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'website', title: 'Website', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'location', title: 'Location', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'linkedin_profile', title: 'LinkedIn Profile', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'company_url', title: 'Company URL', task: ['add_lead'], required: false, description: '' }
            ]
        };
    },
    methods: {
        getCampaigns: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_smartlead_campaigns', {
                targetKey: 'campaigns',
                loadingKey: 'campaignLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.campaignId) this.fielddata.campaignId = '';
        if (!this.fielddata.campaigns) this.fielddata.campaigns = {};

        if (this.fielddata.credId) {
            this.getCampaigns();
        }
    },
    template: '#smartlead-action-template'
});
