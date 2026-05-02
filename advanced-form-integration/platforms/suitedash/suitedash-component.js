/**
 * Advanced Form Integration - "suitedash" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("suitedash").
 */

Vue.component('suitedash', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getFields();
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_suitedash_fields', {
                task: this.action.task,
                includeCredId: true,
                clearBefore: true,
                extraParams: { task: this.action.task }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#suitedash-action-template'
});
