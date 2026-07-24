/**
 * Advanced Form Integration - "ultimatememberac" action component.
 */

Vue.component('ultimatememberac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_ultimatememberac_fields', { task: 'update_profile_field' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#ultimatememberac-action-template'
});
