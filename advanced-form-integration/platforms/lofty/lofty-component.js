/**
 * Advanced Form Integration - "lofty" action component.
 */
Vue.component('lofty', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return { fieldsLoading: false, fields: [] };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_lofty_fields', {
                task: 'create_lead', includeCredId: true, clearBefore: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        if (this.fielddata.credId) this.getFields();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) { if (newVal !== oldVal) this.getFields(); }
    },
    template: '#lofty-action-template'
});
