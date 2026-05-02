/**
 * Advanced Form Integration - "mstodo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mstodo").
 */

Vue.component('mstodo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'bodyContent', title: 'Body Content', task: ['create_task'], required: false },
                { type: 'text', value: 'bodyContentType', title: 'Body Content Type', task: ['create_task'], required: false, description: 'text or html (defaults to text)' },
                { type: 'text', value: 'dueDateTime', title: 'Due DateTime', task: ['create_task'], required: false, description: 'Example: 2024-05-01T17:00:00' },
                { type: 'text', value: 'dueTimeZone', title: 'Due Time Zone', task: ['create_task'], required: false, description: 'IANA/Windows TZ, defaults to UTC' },
                { type: 'text', value: 'reminderDateTime', title: 'Reminder DateTime', task: ['create_task'], required: false },
                { type: 'text', value: 'reminderTimeZone', title: 'Reminder Time Zone', task: ['create_task'], required: false, description: 'Defaults to UTC' },
                { type: 'text', value: 'importance', title: 'Importance', task: ['create_task'], required: false, description: 'low, normal, or high' },
                { type: 'text', value: 'categories', title: 'Categories (CSV)', task: ['create_task'], required: false }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                return;
            }

            this.getLists();
        },
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mstodo_lists', {
                targetKey: 'lists',
                loadingKey: 'listsLoading',
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.lists === 'undefined') {
            this.fielddata.lists = {};
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mstodo-action-template'
});
