/**
 * Advanced Form Integration - "autopilot" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("autopilot").
 */

Vue.component('autopilot', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['subscribe'], required: false },
                { type: 'text', value: 'salutation', title: 'Salutation', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'numberOfEmployees', title: 'Number Of Employees', task: ['subscribe'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'industry', title: 'Industry', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobilePhone', title: 'MobilePhone', task: ['subscribe'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingStreet', title: 'MailingStreet', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingCity', title: 'MailingCity', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingState', title: 'MailingState', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingPostalCode', title: 'MailingPostalCode', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingCountry', title: 'MailingCountry', task: ['subscribe'], required: false },
                { type: 'text', value: 'leadSource', title: 'LeadSource', task: ['subscribe'], required: false },
                { type: 'text', value: 'linkedIn', title: 'LinkedIn', task: ['subscribe'], required: false }

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

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_autopilot_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            that.fielddata.list = response.data;
            that.listLoading = false;
        });
    },
    template: '#autopilot-action-template'
});
