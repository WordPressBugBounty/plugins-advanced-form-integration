/**
 * Advanced Form Integration - "wrike" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("wrike").
 */

Vue.component('wrike', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            folderLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_task'], required: false },
                { type: 'text', value: 'startDate', title: 'Start Date', task: ['create_task'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['create_task'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'importance', title: 'Importance', task: ['create_task'], required: false, description: 'low, normal, high' },
                { type: 'text', value: 'status', title: 'Status', task: ['create_task'], required: false, description: 'ACTIVE, COMPLETED, DEFERRED, CANCELLED' },
                { type: 'text', value: 'responsibles', title: 'Responsibles', task: ['create_task'], required: false, description: 'Comma separated Wrike user IDs' }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.folders = {};
                this.fielddata.folderId = '';
                return;
            }

            this.getFolders();
        },
        getFolders: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.folderLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_wrike_folders',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.folders = response.data;
                } else {
                    that.fielddata.folders = {};
                }
                that.folderLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.folderId === 'undefined') {
            this.fielddata.folderId = '';
        }

        if (typeof this.fielddata.folders === 'undefined') {
            this.fielddata.folders = {};
        }

        if (this.fielddata.credId) {
            this.getFolders();
        }
    },
    template: '#wrike-action-template'
});
