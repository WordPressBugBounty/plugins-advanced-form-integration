/**
 * Advanced Form Integration - "audienceful" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("audienceful").
 */

Vue.component('audienceful', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_person'], required: true },
                { type: 'text', value: 'tags', title: 'Tags (comma-separated)', task: ['add_person'], required: false },
                { type: 'text', value: 'notes', title: 'Notes', task: ['add_person'], required: false },
            ]
        };
    },
    methods: {
        getFields: function (task = null) {
            adfoinHelpers.getFields(this, 'adfoin_get_audienceful_fields', {
                task: 'add_person',
                loadingKey: 'fieldLoading',
                includeCredId: true,
                extraParams: { task: task }
            });
        }
    },

    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';

        if (this.fielddata.credId) {
            this.getFields(this.action.task);
        }
    },
    template: '#audienceful-action-template'
});
