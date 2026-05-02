/**
 * Advanced Form Integration - "trello" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("trello").
 */

Vue.component('trello', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            boardLoading: false,
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['add_card'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['add_card'], required: false },
                { type: 'text', value: 'pos', title: 'Position', task: ['add_card'], required: false, description: 'The position of the new card. top, bottom, or a positive float' }
            ]

        }
    },
    methods: {
        getBoards: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_trello_boards', {
                targetKey: 'boards',
                loadingKey: 'boardLoading',
                requireCredId: false,
                includeCredId: true
            });
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_trello_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true,
                extraParams: { boardId: this.fielddata.boardId, task: this.action.task }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.boardId == 'undefined') {
            this.fielddata.boardId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_trello_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load boards if credential is selected
                if (that.fielddata.credId) {
                    that.getBoards();
                }
            }
        });

        // Load lists if board is already selected (for existing integrations)
        if (this.fielddata.boardId && this.fielddata.credId) {
            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_get_trello_lists',
                '_nonce': adfoin.nonce,
                'boardId': this.fielddata.boardId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var lists = response.data;
                that.fielddata.lists = lists;
                that.listLoading = false;
            });
        }
    },
    template: '#trello-action-template'
});
