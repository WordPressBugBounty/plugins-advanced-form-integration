/**
 * Advanced Form Integration - "wpeventmanagerac" action component.
 */

Vue.component('wpeventmanagerac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_wpeventmanagerac_fields', { task: 'add_event' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#wpeventmanagerac-action-template'
});
