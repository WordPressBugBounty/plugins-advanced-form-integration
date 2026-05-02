/**
 * Advanced Form Integration - "ninjatables" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("ninjatables").
 */

Vue.component('ninjatables', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_row: [
                    { type: 'text', value: 'table_id', title: 'Table ID', task: ['create_row'], required: true },
                    { type: 'text', value: 'row_json', title: 'Row (JSON)', task: ['create_row'], required: true, description: 'Example: {"column_key":"Value"}' },
                    { type: 'text', value: 'created_at', title: 'Created At', task: ['create_row'], description: 'Optional datetime (Y-m-d H:i:s).' },
                    { type: 'text', value: 'insert_after_id', title: 'Insert After Row ID', task: ['create_row'], description: 'Optional row ID to insert after.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_row'], description: 'Optional row settings JSON.' }
                ],
                update_row: [
                    { type: 'text', value: 'table_id', title: 'Table ID', task: ['update_row'], required: true },
                    { type: 'text', value: 'row_id', title: 'Row ID', task: ['update_row'], required: true },
                    { type: 'text', value: 'row_json', title: 'Row (JSON)', task: ['update_row'], required: true, description: 'Example: {"column_key":"Value"}' },
                    { type: 'text', value: 'created_at', title: 'Created At', task: ['update_row'], description: 'Optional datetime (Y-m-d H:i:s).' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['update_row'], description: 'Optional row settings JSON.' }
                ],
                delete_row: [
                    { type: 'text', value: 'table_id', title: 'Table ID', task: ['delete_row'], required: true },
                    { type: 'text', value: 'row_id', title: 'Row ID', task: ['delete_row'], required: true }
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
    template: '#ninjatables-action-template'
});
