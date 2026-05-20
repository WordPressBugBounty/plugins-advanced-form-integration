/**
 * Advanced Form Integration — "googletasks" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("googletasks").
 *
 * Mirrors the multi-account pattern used by salesforcepro/gistpro:
 * `credentialsList` holds the fetched accounts; `fielddata.credId` holds
 * the user's selection. Keeping them separate is what was broken before
 * — overwriting fielddata.credId with the cred list destroyed the
 * selected value on first AJAX response.
 */

Vue.component('googletasks', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_task'], required: false },
                { type: 'text', value: 'due', title: 'Due DateTime', task: ['create_task'], required: false, description: 'Example: 2024-05-01T10:00:00' },
                { type: 'text', value: 'status', title: 'Status', task: ['create_task'], required: false, description: 'needsAction or completed' },
                { type: 'text', value: 'parent', title: 'Parent Task ID', task: ['create_task'], required: false },
                { type: 'text', value: 'position', title: 'Position', task: ['create_task'], required: false }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_googletasks_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;

                    // Auto-select the only credential when nothing is picked yet —
                    // matches single-account UX without surprising multi-account users.
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }

                    if (that.fielddata.credId) {
                        that.getTaskLists();
                    }
                }
                that.credLoading = false;
            });
        },
        getTaskLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.taskLists = {};
                return;
            }

            this.listsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_googletasks_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.taskLists = response.data;
                } else {
                    that.fielddata.taskLists = {};
                }
                that.listsLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        if (typeof this.fielddata.taskLists === 'undefined') {
            this.fielddata.taskLists = {};
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        this.fetchCredentialsList();
    },
    template: '#googletasks-action-template'
});
