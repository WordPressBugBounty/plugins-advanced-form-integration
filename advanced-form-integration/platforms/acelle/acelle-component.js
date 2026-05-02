/**
 * Advanced Form Integration - "acelle" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("acelle").
 */

Vue.component('acelle', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'EMAIL', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'FIRST_NAME', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'LAST_NAME', title: 'Last Name', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_acelle_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists(this.fielddata.credId);
        }
    },
    template: '#acelle-action-template'
});
