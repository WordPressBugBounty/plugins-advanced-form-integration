/**
 * Advanced Form Integration - "mailcoach" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailcoach").
 */

Vue.component('mailcoach', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailcoach_lists', {
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
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mailcoach-action-template'
});
