Vue.component('fundraiseup', {
    props: ["trigger", "action", "fielddata"],
    data: function () { return { fieldsLoading: false, fields: [] }; },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_fundraiseup_fields', { task: 'create_supporter', includeCredId: true, clearBefore: true });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        if (this.fielddata.credId) this.getFields();
    },
    watch: { 'fielddata.credId': function (n, o) { if (n !== o) this.getFields(); } },
    template: '#fundraiseup-action-template'
});
