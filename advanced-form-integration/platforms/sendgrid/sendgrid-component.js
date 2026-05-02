/**
 * Advanced Form Integration - "sendgrid" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendgrid").
 */

Vue.component('sendgrid', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone_number', title: 'Phone Number', task: ['add_contact'], required: false },
                { type: 'select', value: 'list_id', title: 'List', task: ['add_contact'], required: true }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listsLoading = true;

            var listRequestData = {
                action: 'adfoin_get_sendgrid_lists',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data.map(function (list) {
                        return { id: list.id, name: list.name };
                    });
                } else {
                    console.error('Error fetching lists:', response.data);
                }
                that.listsLoading = false;
            });
        }
    },
    created: function () {
        if (typeof this.fielddata.lists === 'undefined') {
            this.fielddata.lists = [];
        }
    },
    mounted: function () {
        if (!this.fielddata.lists.length) {
            this.getLists();
        }
    },
    template: '#sendgrid-action-template'
});
