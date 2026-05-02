/**
 * Advanced Form Integration - "fluentboards" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fluentboards").
 */

Vue.component('fluentboards', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_board: [
                    { type: 'text', value: 'title', title: 'Title', task: ['create_board'], description: 'Required board title.' },
                    { type: 'text', value: 'type', title: 'Type', task: ['create_board'], description: 'Optional board type (to-do or roadmap).' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_board'] },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_board'] },
                    { type: 'text', value: 'background', title: 'Background', task: ['create_board'], description: 'Optional background identifier/URL.' },
                    { type: 'text', value: 'created_by', title: 'Created By (User ID)', task: ['create_board'], description: 'User ID to assign as board creator.' }
                ],
                create_task: [
                    { type: 'text', value: 'board_id', title: 'Board ID', task: ['create_task'], description: 'Required board ID.' },
                    { type: 'text', value: 'stage_id', title: 'Stage ID', task: ['create_task'], description: 'Required stage ID (or use Stage Name field).' },
                    { type: 'text', value: 'stage_name', title: 'Stage Name', task: ['create_task'], description: 'Optional stage name fallback.' },
                    { type: 'text', value: 'title', title: 'Title', task: ['create_task'], description: 'Required task title.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_task'] },
                    { type: 'text', value: 'priority', title: 'Priority', task: ['create_task'], description: 'low, medium, high.' },
                    { type: 'text', value: 'crm_contact_id', title: 'CRM Contact ID', task: ['create_task'] },
                    { type: 'text', value: 'due_at', title: 'Due At', task: ['create_task'], description: 'Date/time string.' },
                    { type: 'text', value: 'started_at', title: 'Started At', task: ['create_task'] },
                    { type: 'text', value: 'type', title: 'Task Type', task: ['create_task'] },
                    { type: 'text', value: 'scope', title: 'Scope', task: ['create_task'] },
                    { type: 'text', value: 'source', title: 'Source', task: ['create_task'] },
                    { type: 'text', value: 'reminder_type', title: 'Reminder Type', task: ['create_task'] },
                    { type: 'text', value: 'remind_at', title: 'Remind At', task: ['create_task'] },
                    { type: 'text', value: 'is_template', title: 'Is Template', task: ['create_task'], description: 'yes to mark as template.' },
                    { type: 'text', value: 'assignee_ids', title: 'Assignee IDs', task: ['create_task'], description: 'Comma separated user IDs.' },
                    { type: 'text', value: 'label_ids', title: 'Label IDs', task: ['create_task'], description: 'Comma separated label IDs.' },
                    { type: 'text', value: 'watcher_ids', title: 'Watcher IDs', task: ['create_task'], description: 'Comma separated watcher user IDs.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_task'], description: 'Optional JSON settings payload.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#fluentboards-action-template'
});
