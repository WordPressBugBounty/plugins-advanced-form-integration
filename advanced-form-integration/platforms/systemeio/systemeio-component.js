/**
 * Advanced Form Integration - "systemeio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("systemeio").
 */

Vue.component('systemeio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_systemeio_fields', { task: 'subscribe' });
        },
        getData: function () {
            this.getFields();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#systemeio-action-template'
});
