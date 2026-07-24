/**
 * Advanced Form Integration - "awesomesupportac" action component.
 */

Vue.component('awesomesupportac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_awesomesupportac_fields', { task: 'add_ticket' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#awesomesupportac-action-template'
});
