/**
 * Advanced Form Integration - "listmonk" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("listmonk").
 */

Vue.component('listmonk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: '',
                lists: {},
                status: 'enabled',
                preconfirm: '',
                customFields: ''
            });
        },
        getLists: function () {
            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'lists', {});
                this.fielddata.listId = '';
                return;
            }
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_listmonk_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#listmonk-action-template'
});
