/**
 * Advanced Form Integration — "smartlead" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("smartlead").
 */

Vue.component('smartlead', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            campaignLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',            title: 'Email',            task: ['add_lead'], required: true },
                { type: 'text', value: 'first_name',       title: 'First Name',       task: ['add_lead'] },
                { type: 'text', value: 'last_name',        title: 'Last Name',        task: ['add_lead'] },
                { type: 'text', value: 'phone_number',     title: 'Phone Number',     task: ['add_lead'] },
                { type: 'text', value: 'company_name',     title: 'Company Name',     task: ['add_lead'] },
                { type: 'text', value: 'website',          title: 'Website',          task: ['add_lead'] },
                { type: 'text', value: 'location',         title: 'Location',         task: ['add_lead'] },
                { type: 'text', value: 'linkedin_profile', title: 'LinkedIn Profile', task: ['add_lead'] },
                { type: 'text', value: 'company_url',      title: 'Company URL',      task: ['add_lead'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_smartlead_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.fetchCampaigns();
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchCampaigns: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.campaigns = {};
                return;
            }
            this.campaignLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_smartlead_campaigns',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.campaigns = (response && response.success && response.data) ? response.data : {};
                that.campaignLoading = false;
            }).fail(function () {
                that.fielddata.campaigns = {};
                that.campaignLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId:     '',
            campaignId: '',
            campaigns:  {}
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.campaignId = '';
                this.fetchCampaigns();
            }
        }
    },
    template: '#smartlead-action-template'
});
