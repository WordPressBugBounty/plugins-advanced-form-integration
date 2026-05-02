/**
 * Advanced Form Integration - "maropost" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("maropost").
 */

Vue.component('maropost', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false }
            ]
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.lists === 'undefined') {
            this.$set(this.fielddata, 'lists', {});
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.listLoading = true;

        jQuery.post(ajaxurl, {
            'action': 'adfoin_get_maropost_lists',
            '_nonce': adfoin.nonce
        }, function (response) {
            if (response.success) {
                that.fielddata.lists = response.data;
            } else {
                that.fielddata.lists = {};
            }
            that.listLoading = false;
        }).fail(function () {
            that.listLoading = false;
        });
    },
    template: '#maropost-action-template'
});
