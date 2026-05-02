/**
 * Advanced Form Integration - "smartsheet" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("smartsheet").
 */

Vue.component('smartsheet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_smartsheet_credentials_list', {
                loadingKey: 'credentialLoading',
                autoSelect: 'legacy_or_first',
                onLoaded: function () { that.getList(); }
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_smartsheet_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
            });
        },
        getFields: function () {
            var that = this;
            this.fieldLoading = true;
            this.fields = [];

            var listData = {
                'action': 'adfoin_get_smartsheet_fields',
                '_nonce': adfoin.nonce,
                'listId': this.fielddata.listId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                if (response.success) {
                    if (response.data) {
                        for (var key in response.data) {
                            that.fields.push({ type: 'text', value: key, title: response.data[key], task: ['add_row'], required: false });
                        }
                    }
                }

                that.fieldLoading = false;
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.listId && this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.getList();
            }
        }
    },
    template: '#smartsheet-action-template'
});
