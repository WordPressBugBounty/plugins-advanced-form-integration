/**
 * Advanced Form Integration - "mailersend" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailersend").
 */

Vue.component('mailersend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.list === 'undefined') {
            this.$set(this.fielddata, 'list', {});
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_mailersend_lists',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            if (response.success) {
                that.fielddata.list = response.data;
            } else {
                that.fielddata.list = {};
            }

            that.listLoading = false;
        }).fail(function () {
            that.listLoading = false;
        });
    },
    template: '#mailersend-action-template'
});
