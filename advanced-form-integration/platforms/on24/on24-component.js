/**
 * Advanced Form Integration - "on24" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("on24").
 */

Vue.component('on24', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                eventId: '',
                sourceCode: ''
            });
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_on24_fields', {
                task: 'register_attendee',
                taskGate: 'register_attendee'
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#on24-action-template'
});
