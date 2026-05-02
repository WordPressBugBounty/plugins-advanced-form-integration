/**
 * Advanced Form Integration - "clickup" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("clickup").
 */

Vue.component('clickup', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            workspaceLoading: false,
            spaceLoading: false,
            folderLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['create_task'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_task'], required: false },
                { type: 'text', value: 'startDate', title: 'Start Date', task: ['create_task'], required: false },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['create_task'], required: false },
                { type: 'text', value: 'dueOnX', title: 'Due After X Days', task: ['create_task'], required: false, description: 'Accepts numeric value. If filled, due date will be calculated and set' },
                { type: 'text', value: 'priorityId', title: 'Priority ID', task: ['create_task'], required: false, description: 'Urgent: 1, Hight: 2. Normal: 3, Low: 4' },
                { type: 'text', value: 'assignees', title: 'Assignee Emails', task: ['create_task'], required: false, description: 'Enter assignee email. Use comma for multiple emails.' },
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getWorkspaces();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_clickup_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getWorkspaces: function () {
            var that = this;
            this.workspaceLoading = true;

            var workspaceRequestData = {
                'action': 'adfoin_get_clickup_workspaces',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, workspaceRequestData, function (response) {
                that.fielddata.workspaces = response.data;
                that.workspaceLoading = false;
            });
        },
        getSpaces: function () {
            var that = this;
            this.spaceLoading = true;

            var spaceData = {
                'action': 'adfoin_get_clickup_spaces',
                '_nonce': adfoin.nonce,
                'workspaceId': this.fielddata.workspaceId,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, spaceData, function (response) {
                var spaces = response.data;
                that.fielddata.spaces = spaces;
                that.spaceLoading = false;
            });
        },
        getFolders: function () {
            var that = this;
            this.folderLoading = true;

            var folderData = {
                'action': 'adfoin_get_clickup_folders',
                '_nonce': adfoin.nonce,
                'spaceId': this.fielddata.spaceId,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, folderData, function (response) {
                var folders = response.data;
                that.fielddata.folders = folders;
                that.folderLoading = false;
            });

            if (!this.fielddata.folderId) {
                this.getLists();
            }
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_clickup_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true,
                extraParams: { spaceId: this.fielddata.spaceId, folderId: this.fielddata.folderId }
            });
        },
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.workspaceId == 'undefined') {
            this.fielddata.workspaceId = '';
        }

        if (typeof this.fielddata.spaceId == 'undefined') {
            this.fielddata.spaceId = '';
        }

        if (typeof this.fielddata.folderId == 'undefined') {
            this.fielddata.folderId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();

        if (this.fielddata.workspaceId) {
            this.getSpaces();
        }

        if (this.fielddata.workspaceId && this.fielddata.spaceId) {
            this.getFolders();
        }

        if (this.fielddata.workspaceId && this.fielddata.spaceId && this.fielddata.folderId) {
            this.getLists();
        }
    },
    template: '#clickup-action-template'
});
