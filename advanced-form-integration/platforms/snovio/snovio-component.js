/**
 * Advanced Form Integration - "snovio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("snovio").
 */

Vue.component('snovio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'fullName', title: 'Full Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phones', title: 'Phones (comma-separated)', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'locality', title: 'Locality (City, State)', task: ['add_contact'], required: false },
                { type: 'text', value: 'socialLinks[linkedIn]', title: 'LinkedIn Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'social[twitter]', title: 'Twitter Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'position', title: 'Job Position', task: ['add_contact'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'companySite', title: 'Company Website', task: ['add_contact'], required: false },
            ]
        };
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_snovio_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = [];
                }
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists(this.fielddata.credId);
        }
    },
    template: '#snovio-action-template'
});
