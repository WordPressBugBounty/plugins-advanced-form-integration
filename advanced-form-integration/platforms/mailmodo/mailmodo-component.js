/**
 * Advanced Form Integration - "mailmodo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailmodo").
 */

Vue.component('mailmodo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['subscribe'], required: false },
                { type: 'text', value: 'age', title: 'Age', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'address1', title: 'Address Line 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'address2', title: 'Address Line 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'postal_code', title: 'Postal Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'designation', title: 'Designation', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'industry', title: 'Industry', task: ['subscribe'], required: false },
                { type: 'text', value: 'description', title: 'Description', task: ['subscribe'], required: false },
                { type: 'text', value: 'anniversary_date', title: 'Anniversary Date', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailmodo_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.$set(this.fielddata, 'listId', '');
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mailmodo-action-template'
});
