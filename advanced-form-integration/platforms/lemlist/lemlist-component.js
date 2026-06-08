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
                { type: 'text', value: 'icebreaker', title: 'Icebreaker', task: ['subscribe'], required: false },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['subscribe'], required: false, description: 'IANA format, e.g. Europe/Paris, America/New_York' },
                { type: 'text', value: 'contactOwner', title: 'Contact Owner', task: ['subscribe'], required: false, description: 'User ID or login email of the contact owner' }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            that.credentialLoading = true;
            jQuery.post(ajaxurl, { action: 'adfoin_get_lemlist_credentials', _nonce: adfoin.nonce }, function (response) {
                that.credentialLoading = false;
                if (response && response.success && response.data) {
                    that.credentialsList = response.data;
                    if (that.fielddata.credId) { that.getData(); }
                }
            }).fail(function () {
                that.credentialLoading = false;
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

        this.getCredentials();
    },
    template: '#lemlist-action-template'
});
