/**
 * Advanced Form Integration - "attio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("attio").
 */

Vue.component('attio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            objectLoading: false,
            fieldsLoading: false,
            fields: []

        }
    },
    methods: {

        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_attio_object_fields', {
                task: 'subscribe',
                includeCredId: true,
                clearBefore: true,
                extraParams: { objectId: this.fielddata.objectId, task: this.action.task }
            });
        },
        getObjects: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_attio_objects', {
                targetKey: 'objects',
                loadingKey: 'objectLoading',
                requireCredId: false,
                includeCredId: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.objectId == 'undefined') {
            this.fielddata.objectId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.update == 'undefined') {
            this.fielddata.update = false;
        }

        if (typeof this.fielddata.update != 'undefined') {
            if (this.fielddata.update == "false") {
                this.fielddata.update = false;
            }
        }

        if (this.fielddata.credId) {
            this.getObjects();
        }

        if (this.fielddata.credId && this.fielddata.objectId) {
            this.getFields();
        }

    },
    watch: {},
    template: '#attio-action-template'
});
