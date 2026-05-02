/**
 * Advanced Form Integration - "buddyboss" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("buddyboss").
 */

Vue.component('buddyboss', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_buddyboss_fields', {
                task: 'create_member',
                taskGate: 'create_member',
                clearBefore: true
            });
        }
    },
    mounted: function () {
        this.getFields();
    },
    watch: {
        'action.task': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#buddyboss-action-template'
});
