/**
 * Advanced Form Integration - "constantcontact" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("constantcontact").
 */

Vue.component('constantcontact', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobilePhone', title: 'Cell Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdayMonth', title: 'Birthday Month', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdayDay', title: 'Birthday Day', task: ['subscribe'], required: false },
                { type: 'text', value: 'anniversary', title: 'Anniversary', task: ['subscribe'], required: false },
                { type: 'text', value: 'addressType', title: 'Address Type', task: ['subscribe'], required: false, description: 'home, work, other' },
                { type: 'text', value: 'address1', title: 'Address Line 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },

            ]

        }
    },
    methods: {
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_constantcontact_credentials');
        },
        getConstantContactList: function() {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = [];
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_constantcontact_list',
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
    watch: {
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getConstantContactList();
            }
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.permission == 'undefined') {
            this.fielddata.permission = 'explicit';
        }

        if (typeof this.fielddata.createSource == 'undefined') {
            this.fielddata.createSource = 'Account';
        }

        // Get credentials first
        this.getCredentials();

        // Load lists if credId is already set
        if (this.fielddata.credId) {
            this.getConstantContactList();
        }
    },
    template: '#constantcontact-action-template'
});
