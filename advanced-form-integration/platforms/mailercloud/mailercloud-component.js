/**
 * Advanced Form Integration - "mailercloud" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailercloud").
 */

Vue.component('mailercloud', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: []

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_mailercloud_credentials', {
                loadingKey: 'credentialLoading',
                autoSelect: 'first',
                onLoaded: function () { that.getData(); }
            });
        },
        getData: function () {
            this.getLists();
            this.getFields();
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailercloud_list', {
                targetKey: 'list',
                loadingKey: 'listLoading'
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_mailercloud_contact_fields', {
                task: 'subscribe',
                requireCredId: true,
                clearBefore: true
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.fields = [];
            this.getData();
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailercloud-action-template'
});
