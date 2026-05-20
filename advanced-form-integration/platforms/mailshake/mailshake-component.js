/**
 * Advanced Form Integration — "mailshake" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("mailshake").
 */

Vue.component('mailshake', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            campaignLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',     title: 'Email',      task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_to_list'] },
                { type: 'text', value: 'lastName',  title: 'Last Name',  task: ['add_to_list'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailshake_credentials',
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
                action: 'adfoin_get_mailshake_campaigns',
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
    template: '#mailshake-action-template'
});
