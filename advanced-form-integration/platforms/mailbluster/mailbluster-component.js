/**
 * Advanced Form Integration - "mailbluster" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailbluster").
 */

Vue.component('mailbluster', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'fullName', title: 'Full Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['add_contact'], required: false },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['add_contact'], required: false },
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_mailbluster_credentials', {
                loadingKey: 'credentialLoading'
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.doptin == 'undefined') {
            this.fielddata.doptin = false;
        }

        if (typeof this.fielddata.doptin != 'undefined') {
            if (this.fielddata.doptin == "false") {
                this.fielddata.doptin = false;
            }
        }

        this.getData();
    },
    template: '#mailbluster-action-template'
});
