/**
 * Advanced Form Integration - "engagebay" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("engagebay").
 */

Vue.component('engagebay', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: true },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'role', title: 'Role', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'sate', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'Zip', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
            ]
        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_engagebay_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getList: function() {
            var that = this;
            
            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_engagebay_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () { },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();
    },
    template: '#engagebay-action-template'
});
