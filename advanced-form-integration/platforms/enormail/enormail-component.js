/**
 * Advanced Form Integration - "enormail" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("enormail").
 */

Vue.component('enormail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_subscriber'], required: false, description: 'M or F' },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'postal', title: 'Postal / Region', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'telephone', title: 'Telephone', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['add_subscriber'], required: false, description: 'YYYY-MM-DD' }
            ]
        }
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_enormail_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
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
            this.getLists();
        }
    },
    template: '#enormail-action-template'
});
