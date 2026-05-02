/**
 * Advanced Form Integration - "revue" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("revue").
 */

Vue.component('revue', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_revue_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.doptin == 'undefined') {
            this.fielddata.doptin = false;
        }

        if (typeof this.fielddata.doptin != 'undefined') {
            if (this.fielddata.doptin == "false") {
                this.fielddata.doptin = false;
            }
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#revue-action-template'
});
