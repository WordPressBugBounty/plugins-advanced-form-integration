/**
 * Advanced Form Integration - "fluentcartac" action component.
 */

Vue.component('fluentcartac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_fluentcartac_fields', { task: 'add_customer' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#fluentcartac-action-template'
});
