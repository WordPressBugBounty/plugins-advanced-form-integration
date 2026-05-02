/**
 * Advanced Form Integration - "fluentcrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fluentcrm").
 */

Vue.component('fluentcrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldLoading: false,
            fields: [
            ]

        }
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_fluentcrm_lists',
                // 'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.lists = response.data;
                that.listLoading = false;
            });
        },
        getFields: function (task = null) {
            adfoinHelpers.getFields(this, 'adfoin_get_fluentcrm_fields', {
                task: ['addContact', 'removeContact', 'addTag', 'removeTag'],
                loadingKey: 'fieldLoading',
                extraParams: { task: task }
            });
        }
    },
    watch: {
        'action.task': function (val) {
            this.getFields(this.action.task);
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists();

        if (this.action.task) {
            this.getFields(this.action.task);
        }
    },
    template: '#fluentcrm-action-template'
});
