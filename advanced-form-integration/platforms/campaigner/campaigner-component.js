/**
 * Advanced Form Integration - "campaigner" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("campaigner").
 */

Vue.component('campaigner', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_campaigner_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists(this.fielddata.credId);
    },
    template: '#campaigner-action-template'
});
