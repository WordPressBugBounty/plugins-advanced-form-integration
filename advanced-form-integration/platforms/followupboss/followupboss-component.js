/**
 * Advanced Form Integration - "followupboss" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("followupboss").
 */

Vue.component('followupboss', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_followupboss_fields', {
                task: 'create_contact',
                includeCredId: true,
                clearBefore: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#followupboss-action-template'
});
