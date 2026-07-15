Vue.component('totalexpert', {
    props: ["trigger", "action", "fielddata"],
    data: function () { return { credentialsList: [], credentialLoading: false, fieldsLoading: false, fields: [] }; },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_totalexpert_fields', { task: 'create_contact', includeCredId: true, clearBefore: true });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_totalexpert_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        if (this.fielddata.credId) this.getFields();
    },
    watch: { 'fielddata.credId': function (n, o) { if (n !== o) this.getFields(); } },
    template: '#totalexpert-action-template'
});
