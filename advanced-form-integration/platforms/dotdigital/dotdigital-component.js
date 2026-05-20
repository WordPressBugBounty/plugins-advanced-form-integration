/**
 * Advanced Form Integration — "dotdigital" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("dotdigital").
 */

Vue.component('dotdigital', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',     title: 'Email',      task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'] },
                { type: 'text', value: 'lastName',  title: 'Last Name',  task: ['add_contact'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_dotdigital_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId:    '',
            optInType: 'Unknown'
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
    },
    template: '#dotdigital-action-template'
});
