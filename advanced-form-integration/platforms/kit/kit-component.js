/**
 * Advanced Form Integration - "kit" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("kit").
 */

Vue.component('kit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            formsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
            this.getForms();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_kit_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getList: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_kit_list', {
                targetKey: 'list',
                loadingKey: 'listLoading'
            });
        },
        getForms: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_kit_forms', {
                targetKey: 'forms',
                loadingKey: 'formsLoading'
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

        if (typeof this.fielddata.formId == 'undefined') {
            this.fielddata.formId = '';
        }

        this.getData();
    },
    template: '#kit-action-template'
});
