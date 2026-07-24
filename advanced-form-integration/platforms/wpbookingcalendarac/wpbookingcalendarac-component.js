/**
 * Advanced Form Integration - "wpbookingcalendarac" action component.
 */

Vue.component('wpbookingcalendarac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_wpbookingcalendarac_fields', { task: 'add_booking' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#wpbookingcalendarac-action-template'
});
