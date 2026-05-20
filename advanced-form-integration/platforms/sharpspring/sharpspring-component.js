/**
 * Advanced Form Integration — "sharpspring" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("sharpspring").
 */

Vue.component('sharpspring', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',     title: 'Email',      task: ['add_lead'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_lead'] },
                { type: 'text', value: 'lastName',  title: 'Last Name',  task: ['add_lead'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sharpspring_credentials',
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
    template: '#sharpspring-action-template'
});
