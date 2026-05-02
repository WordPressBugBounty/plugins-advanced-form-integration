/**
 * Advanced Form Integration - "googledrive" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("googledrive").
 */

Vue.component('googledrive', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            folderLoading: false,
            fields: [
                { type: 'text', value: 'fileField', title: 'File Field', task: ['upload_file'], required: true }
            ]
        };
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_googledrive_credentials', {
                loadingKey: 'credentialLoading',
                autoSelect: 'legacy',
                onLoaded: function () { that.getFolders(); }
            });
        },
        getFolders: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.folderList = {};
                return;
            }

            this.folderLoading = true;

            var folderData = {
                'action': 'adfoin_get_googledrive_folders',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, folderData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.folderList = response.data;
                    }
                }
                that.folderLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (!this.fielddata.folderId) {
            this.fielddata.folderId = '';
        }

        this.getData();
    },
    template: '#googledrive-action-template'
});
