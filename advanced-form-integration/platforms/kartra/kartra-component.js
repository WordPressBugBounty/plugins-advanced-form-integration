/**
 * Advanced Form Integration - "kartra" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("kartra").
 */

Vue.component('kartra', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName2', title: 'Last Name 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'phoneCountryCode', title: 'Phone Country Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'ip', title: 'IP', task: ['subscribe'], required: false },
                { type: 'text', value: 'address', title: 'Address 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['subscribe'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['subscribe'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_kartra_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            that.fielddata.list = response.data;
            that.listLoading = false;
        });
    },
    template: '#kartra-action-template'
});
