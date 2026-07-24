/**
 * Advanced Form Integration - "acfac" action component.
 */

Vue.component('acfac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_acfac_fields', { task: 'update_field' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#acfac-action-template'
});
