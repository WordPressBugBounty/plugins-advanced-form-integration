/**
 * Advanced Form Integration - "eddac" action component.
 */

Vue.component('eddac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_eddac_fields', { task: 'add_customer' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#eddac-action-template'
});
