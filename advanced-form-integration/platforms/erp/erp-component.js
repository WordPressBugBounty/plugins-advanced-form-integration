/**
 * Advanced Form Integration - "erp" action component.
 */

Vue.component('erp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_erp_fields', { task: 'add_contact' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#erp-action-template'
});
