/**
 * Advanced Form Integration - "icontact" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("icontact").
 */

Vue.component('icontact', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'prefix', title: 'Prefix', task: ['subscribe'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'suffix', title: 'Suffix', task: ['subscribe'], required: false },
                { type: 'text', value: 'street', title: 'Street', task: ['subscribe'], required: false },
                { type: 'text', value: 'street2', title: 'Street 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'postalCode', title: 'Postal Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['subscribe'], required: false },
                { type: 'text', value: 'business', title: 'Business', task: ['subscribe'], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: '' }
            ]
        };
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_icontact_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.listId) this.fielddata.listId = '';
        if (!this.fielddata.lists) this.fielddata.lists = {};

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#icontact-action-template'
});
