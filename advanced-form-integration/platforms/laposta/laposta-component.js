/**
 * Advanced Form Integration - "laposta" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("laposta").
 */

Vue.component('laposta', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'ip', title: 'IP Address', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_subscriber'], required: false }
            ]
        };
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_laposta_lists', {
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
    template: '#laposta-action-template'
});
