/**
 * Advanced Form Integration - "zohoma" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohoma").
 */

Vue.component('zohoma', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getList: function () {
            var that = this;
            this.listLoading = true;
            this.fields = [];

            var listRequestData = {
                'action': 'adfoin_get_zohoma_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.lists = response.data;
            }).always(function () {
                that.listLoading = false;
            });

            this.getFields();
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_zohoma_fields', {
                task: 'subscribe',
                loadingKey: 'fieldLoading',
                includeCredId: true,
                clearBefore: true
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

        if (this.fielddata.listId) {
            this.getList();
        }
    },
    template: '#zohoma-action-template'
});
