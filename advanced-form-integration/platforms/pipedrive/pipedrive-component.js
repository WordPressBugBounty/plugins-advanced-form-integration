/**
 * Advanced Form Integration - "pipedrive" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("pipedrive").
 */

Vue.component('pipedrive', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_pipedrive_fields', {
                task: 'add_ocdna',
                includeCredId: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.duplicate == 'undefined') {
            this.fielddata.duplicate = false;
        }

        if (typeof this.fielddata.duplicate != 'undefined') {
            if (this.fielddata.duplicate == "false") {
                this.fielddata.duplicate = false;
            }
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {},
    template: '#pipedrive-action-template'
});
