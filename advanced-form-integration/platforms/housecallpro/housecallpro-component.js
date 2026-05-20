Vue.component('housecallpro', {
    props: ["trigger", "action", "fielddata"],
    data: function () { return { fieldsLoading: false, fields: [] }; },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_housecallpro_fields', {
                task: this.action.task, includeCredId: true, clearBefore: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        if (this.fielddata.credId) this.getFields();
    },
    watch: {
        'fielddata.credId': function (n, o) { if (n !== o) this.getFields(); },
        'action.task':      function (n, o) { if (n !== o && this.fielddata.credId) this.getFields(); }
    },
    template: '#housecallpro-action-template'
});
