/**
 * Advanced Form Integration - "lemlist" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("lemlist").
 */

Vue.component('lemlist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'picture', title: 'Profile Picture URL', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['subscribe'], required: false },
                { type: 'text', value: 'linkedinUrl', title: 'LinkedIn Profile URL', task: ['subscribe'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'companyDomain', title: 'Company Domain', task: ['subscribe'], required: false },
                { type: 'text', value: 'icebreaker', title: 'Icebreaker', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_lemlist_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getList: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_lemlist_list', {
                targetKey: 'list',
                loadingKey: 'listLoading'
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();
    },
    template: '#lemlist-action-template'
});
