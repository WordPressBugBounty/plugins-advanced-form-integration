/**
 * Advanced Form Integration - "sitereviewsac" action component.
 */

Vue.component('sitereviewsac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_sitereviewsac_fields', { task: 'add_review' });
        }
    },
    mounted: function () {
        this.getFields();
    },
    template: '#sitereviewsac-action-template'
});
