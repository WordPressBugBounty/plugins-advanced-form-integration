/**
 * Advanced Form Integration - "highlevel" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("highlevel").
 */

Vue.component('highlevel', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            // `credId` must travel via `includeCredId` to actually reach the
            // server (the bare `task` option only tags field defs client-side).
            adfoinHelpers.getFields(this, 'adfoin_get_highlevel_fields', {
                task: 'create_contact',
                includeCredId: true,
                clearBefore: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    template: '#highlevel-action-template'
});
