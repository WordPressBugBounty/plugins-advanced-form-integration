/**
 * Advanced Form Integration - "dropbox" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("dropbox").
 */

Vue.component('dropbox', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            folderLoading: false,
            fields: [
                { type: 'text', value: 'fileField', title: 'File', task: ['upload_file'], required: true }
            ]
        };
    },
    methods: {
        getFolders: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_dropbox_folders', {
                targetKey: 'folders',
                loadingKey: 'folderLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.folderId) this.fielddata.folderId = '';

        if (this.fielddata.credId) {
            this.getFolders();
        }
    },
    template: '#dropbox-action-template'
});
