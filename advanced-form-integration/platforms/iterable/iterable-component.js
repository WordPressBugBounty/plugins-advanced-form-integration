/**
 * Advanced Form Integration - "iterable" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("iterable").
 */

Vue.component('iterable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            listError: '',
            fields: [
                // Identifier(s) — provide email OR userId (or both for hybrid projects).
                { type: 'email', value: 'email', title: 'Email Address', task: ['subscribe'], required: false, description: 'Required unless User ID is provided.' },
                { type: 'text',  value: 'userId', title: 'User ID', task: ['subscribe'], required: false, description: 'For userId-based or hybrid projects. Required unless Email is provided.' },

                // Common contact dataFields (Iterable\'s dataFields accepts any keys).
                { type: 'text',  value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text',  value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },

                // OptionalApiUser flags + SubscribeRequest options.
                { type: 'text', value: 'preferUserId', title: 'Prefer User ID', task: ['subscribe'], required: false, description: 'Set to "yes" to allow new userId-based profiles in email projects.' },
                { type: 'text', value: 'mergeNestedObjects', title: 'Merge Nested Objects', task: ['subscribe'], required: false, description: 'Set to "yes" to merge top-level objects instead of overwriting.' },
                { type: 'text', value: 'updateExistingUsersOnly', title: 'Update Existing Users Only', task: ['subscribe'], required: false, description: 'Set to "yes" to skip rather than create unknown users.' }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: '',
                lists: {}
            });
        },
        getLists: function (force) {
            const that = this;

            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                this.listError = '';
                return;
            }

            this.listLoading = true;
            this.listError = '';

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_iterable_lists',
                credId: this.fielddata.credId,
                force: force ? 1 : 0,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success) {
                    that.fielddata.lists = response.data || {};
                } else {
                    that.fielddata.lists = {};
                    that.fielddata.listId = '';
                    that.listError = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'Could not load Iterable lists.';
                }
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.lists = {};
                that.fielddata.listId = '';
                that.listError = 'Network error while loading Iterable lists.';
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists(false);
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists(false);
        }
    },
    template: '#iterable-action-template'
});
