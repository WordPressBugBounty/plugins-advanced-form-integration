/**
 * Advanced Form Integration - "companyhub" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("companyhub").
 */

Vue.component('companyhub', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_companyhub_credentials', {
                loadingKey: 'credentialLoading'
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        this.getData();
    },
    template: '#companyhub-action-template'
});
