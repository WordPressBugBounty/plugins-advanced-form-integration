/**
 * Advanced Form Integration — "ontraport" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("ontraport").
 */

Vue.component('ontraport', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',     title: 'Email',      task: ['add_contact'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_contact'] },
                { type: 'text', value: 'lastname',  title: 'Last Name',  task: ['add_contact'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_ontraport_credentials',
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
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        this.fetchCredentialsList();
    },
    template: '#ontraport-action-template'
});
