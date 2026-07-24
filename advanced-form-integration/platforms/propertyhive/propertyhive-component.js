/**
 * Advanced Form Integration - "propertyhive" action component.
 */

Vue.component('propertyhive', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_propertyhive_fields', { task: 'add_property' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#propertyhive-action-template'
});
