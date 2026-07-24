/**
 * Advanced Form Integration - "wpcrmsystem" action component.
 */

Vue.component('wpcrmsystem', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_wpcrmsystem_fields', { task: 'add_contact' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#wpcrmsystem-action-template'
});
