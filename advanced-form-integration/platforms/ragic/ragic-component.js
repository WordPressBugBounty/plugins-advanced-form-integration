/**
 * Advanced Form Integration - "ragic" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("ragic").
 */

Vue.component('ragic', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            fields: [
                { type: 'text', value: 'account_name', title: 'Account Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'tab', title: 'Tab', task: ['subscribe'], required: false },
                { type: 'text', value: 'sheet_id', title: 'Sheet ID', task: ['subscribe'], required: false },
                { type: 'text', value: 'field1', title: 'Field 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'field2', title: 'Field 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'field3', title: 'Field 3', task: ['subscribe'], required: false },
                { type: 'text', value: 'field4', title: 'Field 4', task: ['subscribe'], required: false },
                { type: 'text', value: 'field5', title: 'Field 5', task: ['subscribe'], required: false },
                { type: 'text', value: 'field6', title: 'Field 6', task: ['subscribe'], required: false },
                { type: 'text', value: 'field7', title: 'Field 7', task: ['subscribe'], required: false },
                { type: 'text', value: 'field8', title: 'Field 8', task: ['subscribe'], required: false },
                { type: 'text', value: 'field9', title: 'Field 9', task: ['subscribe'], required: false },
                { type: 'text', value: 'field10', title: 'Field 10', task: ['subscribe'], required: false },
                { type: 'text', value: 'field11', title: 'Field 11', task: ['subscribe'], required: false },
                { type: 'text', value: 'field12', title: 'Field 12', task: ['subscribe'], required: false },
                { type: 'text', value: 'field13', title: 'Field 13', task: ['subscribe'], required: false },
                { type: 'text', value: 'field14', title: 'Field 14', task: ['subscribe'], required: false },
                { type: 'text', value: 'field15', title: 'Field 15', task: ['subscribe'], required: false },
                { type: 'text', value: 'field16', title: 'Field 16', task: ['subscribe'], required: false },
                { type: 'text', value: 'field17', title: 'Field 17', task: ['subscribe'], required: false },
                { type: 'text', value: 'field18', title: 'Field 18', task: ['subscribe'], required: false },
                { type: 'text', value: 'field19', title: 'Field 19', task: ['subscribe'], required: false },
                { type: 'text', value: 'field20', title: 'Field 20', task: ['subscribe'], required: false },
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_ragic_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#ragic-action-template'
});
