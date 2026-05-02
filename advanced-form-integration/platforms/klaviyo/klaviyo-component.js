/**
 * Advanced Form Integration - "klaviyo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("klaviyo").
 */

Vue.component('klaviyo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'organization', title: 'Organization', task: ['subscribe'], required: false },
                { type: 'text', value: 'phoneNumber', title: 'Phone Number', task: ['subscribe'], required: false, description: 'Should be passed with proper country code. For example: "+91xxxxxxxxxx"' },
                { type: 'text', value: 'address1', title: 'Address 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'address2', title: 'Address 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'region', title: 'Region', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'latitude', title: 'Latitude', task: ['subscribe'], required: false },
                { type: 'text', value: 'longitude', title: 'Longitude', task: ['subscribe'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['subscribe'], required: false, description: 'e.g. Asia/Dhaka' },
                { type: 'text', value: 'ip', title: 'IP Address', task: ['subscribe'], required: false },
                { type: 'text', value: 'externalId', title: 'External ID', task: ['subscribe'], required: false },
                { type: 'text', value: 'source', title: 'Source', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_klaviyo_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
            });
        },
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_klaviyo_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                        that.getLists();
                    }
                } else {
                    that.credentialsList = [];
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        this.fetchCredentialsList();
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#klaviyo-action-template'
});
