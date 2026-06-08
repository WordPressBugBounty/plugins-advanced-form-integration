/**
 * Advanced Form Integration - "trello" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("trello").
 */

Vue.component('trello', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            boardLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['add_card'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['add_card'], required: false },
                { type: 'text', value: 'pos', title: 'Position', task: ['add_card'], required: false, description: 'top, bottom, or a positive float' }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_trello_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getBoards: function () {
            var that = this;
            if (!this.fielddata.credId) return;
            this.boardLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_trello_boards',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.boards = response.data;
                }
                that.boardLoading = false;
            }).fail(function () {
                that.boardLoading = false;
            });
        },
        getLists: function () {
            var that = this;
            if (!this.fielddata.boardId || !this.fielddata.credId) return;
            this.listLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_trello_lists',
                boardId: this.fielddata.boardId,
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            }).fail(function () {
                that.listLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fielddata.boardId = '';
            this.fielddata.listId = '';
            this.fielddata.boards = {};
            this.fielddata.lists = {};
            this.getBoards();
        },
        handleBoardChange: function () {
            this.fielddata.listId = '';
            this.fielddata.lists = {};
            this.getLists();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.boardId === 'undefined') {
            this.fielddata.boardId = '';
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        this.getCredentials();
        if (this.fielddata.credId) {
            this.getBoards();
        }
        if (this.fielddata.boardId && this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#trello-action-template'
});
