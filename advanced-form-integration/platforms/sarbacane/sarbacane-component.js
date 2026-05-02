/**
 * Advanced Form Integration - "sarbacane" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sarbacane").
 */

Vue.component('sarbacane', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_subscriber'], required: false }
            ]
        }
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_sarbacane_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
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
    template: '#sarbacane-action-template'
});
