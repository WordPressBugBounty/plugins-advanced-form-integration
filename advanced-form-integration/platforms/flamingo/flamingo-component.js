/**
 * Advanced Form Integration - "flamingo" action component.
 */

Vue.component('flamingo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_flamingo_fields', {
                task: this.action.task,
                taskGate: this.action.task,
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
    template: '#flamingo-action-template'
});
