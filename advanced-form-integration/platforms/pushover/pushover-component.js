/**
 * Advanced Form Integration - "pushover" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("pushover").
 */

Vue.component('pushover', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['push'], required: false },
                { type: 'text', value: 'message', title: 'Message', task: ['push'], required: false },
                { type: 'text', value: 'device', title: 'Device', task: ['push'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_pushover_credentials_list',
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

        if (typeof this.fielddata.title == 'undefined') {
            this.fielddata.title = '';
        }

        if (typeof this.fielddata.message == 'undefined') {
            this.fielddata.message = '';
        }

        if (typeof this.fielddata.device == 'undefined') {
            this.fielddata.device = '';
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#pushover-action-template'
});
