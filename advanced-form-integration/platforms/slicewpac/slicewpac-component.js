/**
 * Advanced Form Integration - "slicewpac" action component.
 */

Vue.component('slicewpac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_slicewpac_fields', { task: 'add_affiliate' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#slicewpac-action-template'
});
