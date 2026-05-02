/**
 * Advanced Form Integration - "mailwizz" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailwizz").
 */

Vue.component('mailwizz', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailwizz_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailwizz_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        // Fetch credentials list
        this.fetchCredentialsList();

        // Load lists if credId is set
        if (this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#mailwizz-action-template'
});
