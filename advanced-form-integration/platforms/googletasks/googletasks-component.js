/**
 * Advanced Form Integration - "googletasks" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("googletasks").
 */

Vue.component('googletasks', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            credLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_task'], required: false },
                { type: 'text', value: 'due', title: 'Due DateTime', task: ['create_task'], required: false, description: 'Example: 2024-05-01T10:00:00' },
                { type: 'text', value: 'status', title: 'Status', task: ['create_task'], required: false, description: 'needsAction or completed' },
                { type: 'text', value: 'parent', title: 'Parent Task ID', task: ['create_task'], required: false },
                { type: 'text', value: 'position', title: 'Position', task: ['create_task'], required: false }
            ]
        }
    },
    methods: {
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
                    console.log('Error fetching task lists:', response.data);
                }
                that.listsLoading = false;
            });
        },
        fetchLists: function () {
            // Legacy method for backward compatibility
            this.getTaskLists();
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.taskLists === 'undefined') {
            this.fielddata.taskLists = {};
        }

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        // Load credentials
        this.credLoading = true;

        var credRequestData = {
            'action': 'adfoin_get_googletasks_credentials',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credRequestData, function (response) {
            if (response.success) {
                that.fielddata.credId = response.data;
                
                // Auto-select first credential if none selected
                if (!that.fielddata.credId || that.fielddata.credId === '') {
                    var firstKey = Object.keys(response.data)[0];
                    if (firstKey) {
                        that.fielddata.credId = firstKey;
                    }
                }
                
                // Load task lists if credential is selected
                if (that.fielddata.credId) {
                    that.getTaskLists();
                }
            }
            that.credLoading = false;
        });
    },
    template: '#googletasks-action-template'
});
