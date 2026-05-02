/**
 * Advanced Form Integration - "benchmark" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("benchmark").
 */

Vue.component('benchmark', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_benchmark_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_benchmark_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();

        // Load list for existing integrations (backward compatibility)
        if (this.fielddata.listId && !this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#benchmark-action-template'
});
