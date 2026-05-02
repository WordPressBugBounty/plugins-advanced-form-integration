/**
 * Advanced Form Integration - "instantly" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("instantly").
 */

Vue.component('instantly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            campaignLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'personalization', title: 'Personalization', task: ['add_lead'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_lead'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_lead'], required: false },
            ]
        };
    },
    methods: {
        getCampaigns: function (credId = null) {
            var that = this;

            this.campaignLoading = true;

            var campaignRequestData = {
                'action': 'adfoin_get_instantly_campaigns',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, campaignRequestData, function (response) {
                if (response.success) {
                    that.fielddata.campaigns = response.data;
                } else {
                    that.fielddata.campaigns = [];
                }
                that.campaignLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.campaignId == 'undefined') {
            this.fielddata.campaignId = '';
        }

        if (this.fielddata.credId) {
            this.getCampaigns(this.fielddata.credId);
        }
    },
    template: '#instantly-action-template'
});
