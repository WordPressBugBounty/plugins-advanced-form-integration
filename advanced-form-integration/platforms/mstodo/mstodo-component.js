/**
 * Advanced Form Integration - "mstodo" action component.
 * Talks to Microsoft Graph /me/todo via the OAuth-backed handler in mstodo.php.
 */

Vue.component('mstodo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            listError: '',
            fields: [
                { type: 'text',     value: 'title',                         title: 'Title',                            task: ['create_task'], required: true },
                { type: 'textarea', value: 'bodyContent',                   title: 'Body Content',                     task: ['create_task'], required: false },
                { type: 'text',     value: 'bodyContentType',               title: 'Body Content Type',                task: ['create_task'], required: false, description: 'text or html (defaults to text).' },

                { type: 'text',     value: 'dueDateTime',                   title: 'Due DateTime',                     task: ['create_task'], required: false, description: 'ISO format without offset, e.g. 2024-05-01T17:00:00. Date-only values are accepted and padded.' },
                { type: 'text',     value: 'dueTimeZone',                   title: 'Due Time Zone',                    task: ['create_task'], required: false, description: 'IANA / Windows TZ id (e.g. Pacific Standard Time). Defaults to UTC.' },

                { type: 'text',     value: 'reminderDateTime',              title: 'Reminder DateTime',                task: ['create_task'], required: false, description: 'ISO date/time, no offset. Sets isReminderOn automatically.' },
                { type: 'text',     value: 'reminderTimeZone',              title: 'Reminder Time Zone',               task: ['create_task'], required: false, description: 'Defaults to UTC.' },
                { type: 'text',     value: 'isReminderOn',                  title: 'Reminder Enabled',                 task: ['create_task'], required: false, description: 'Set to "yes" / "true" or "no" / "false" to override.' },

                { type: 'text',     value: 'importance',                    title: 'Importance',                       task: ['create_task'], required: false, description: 'low, normal, or high.' },
                { type: 'text',     value: 'status',                        title: 'Status',                           task: ['create_task'], required: false, description: 'notStarted, inProgress, completed, waitingOnOthers, or deferred.' },
                { type: 'text',     value: 'categories',                    title: 'Categories (CSV)',                 task: ['create_task'], required: false },

                { type: 'text',     value: 'linkedResourceUrl',             title: 'Linked Resource URL',              task: ['create_task'], required: false, description: 'Optional URL to attach as a linkedResource (e.g. the submission link).' },
                { type: 'text',     value: 'linkedResourceDisplayName',     title: 'Linked Resource Display Name',     task: ['create_task'], required: false },
                { type: 'text',     value: 'linkedResourceApplicationName', title: 'Linked Resource App Name',         task: ['create_task'], required: false, description: 'Defaults to "Advanced Form Integration".' }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            var defaults = {
                credId: '',
                listId: '',
                title: '',
                bodyContent: '',
                bodyContentType: 'text',
                dueDateTime: '',
                dueTimeZone: '',
                reminderDateTime: '',
                reminderTimeZone: '',
                isReminderOn: '',
                importance: '',
                status: '',
                categories: '',
                linkedResourceUrl: '',
                linkedResourceDisplayName: '',
                linkedResourceApplicationName: ''
            };
            for (var k in defaults) {
                if (typeof this.fielddata[k] === 'undefined') {
                    this.$set(this.fielddata, k, defaults[k]);
                }
            }
            if (typeof this.fielddata.lists === 'undefined') {
                this.$set(this.fielddata, 'lists', {});
            }
        },
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                this.listError = '';
                return;
            }
            this.getLists(false);
        },
        getLists: function (force) {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                this.listError = '';
                return;
            }

            this.listsLoading = true;
            this.listError = '';

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mstodo_lists',
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
                        : 'Could not load Microsoft To Do lists.';
                }
                that.listsLoading = false;
            }).fail(function () {
                that.listError = 'Network error while loading Microsoft To Do lists.';
                that.listsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists(false);
        }
    },
    template: '#mstodo-action-template'
});
