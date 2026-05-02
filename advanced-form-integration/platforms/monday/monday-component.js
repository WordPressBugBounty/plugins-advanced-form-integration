/**
 * Advanced Form Integration - "monday" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("monday").
 */

Vue.component('monday', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            boardLoading: false,
            fieldsLoading: false,
            groupLoading: false,
            itemsLoading: false,
            fields: []
        };
    },
    methods: {
        getBoards: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_monday_boards', {
                targetKey: 'boards',
                loadingKey: 'boardLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        },
        getGroups: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_monday_groups', {
                targetKey: 'groups',
                loadingKey: 'groupLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true,
                extraParams: { boardId: this.fielddata.boardId }
            });
        },
        getColumns: function () {
            var that = this;
            this.itemsLoading = true;

            var requestData = {
                action: 'adfoin_get_monday_columns',
                credId: this.fielddata.credId,
                boardId: this.fielddata.boardId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_item'], required: false, description: single.description });
                        });

                        that.itemsLoading = false;
                    }
                }
            });
        },
        getFields: function () {
            this.getColumns();
            this.getGroups();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.boardId == 'undefined') {
            this.fielddata.boardId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getBoards();
        }

        if (this.fielddata.boardId) {
            this.getFields();
        }
    },
    template: '#monday-action-template'
});
