/**
 * Advanced Form Integration - "icontact" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("icontact").
 */

Vue.component('icontact', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email',      title: 'Email',       task: ['subscribe'], required: true  },
                { type: 'text', value: 'prefix',     title: 'Prefix',      task: ['subscribe'], required: false },
                { type: 'text', value: 'firstName',  title: 'First Name',  task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName',   title: 'Last Name',   task: ['subscribe'], required: false },
                { type: 'text', value: 'suffix',     title: 'Suffix',      task: ['subscribe'], required: false },
                { type: 'text', value: 'street',     title: 'Street',      task: ['subscribe'], required: false },
                { type: 'text', value: 'street2',    title: 'Street 2',    task: ['subscribe'], required: false },
                { type: 'text', value: 'city',       title: 'City',        task: ['subscribe'], required: false },
                { type: 'text', value: 'state',      title: 'State',       task: ['subscribe'], required: false },
                { type: 'text', value: 'postalCode', title: 'Postal Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone',      title: 'Phone',       task: ['subscribe'], required: false },
                { type: 'text', value: 'fax',        title: 'Fax',         task: ['subscribe'], required: false },
                { type: 'text', value: 'business',   title: 'Business',    task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getData: function () {
            this.getLists();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_icontact_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.fielddata.credId) {
                        that.getData();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_icontact_lists', {
                targetKey:    'lists',
                loadingKey:   'listLoading',
                requireCredId: true,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId)  this.fielddata.credId  = '';
        if (!this.fielddata.listId)  this.fielddata.listId  = '';
        if (!this.fielddata.lists)   this.fielddata.lists   = {};

        this.getCredentials();
    },
    template: '#icontact-action-template'
});
