Vue.component('docusign', {
    props: ["trigger", "action", "fielddata"],
    data: function () { return { credentialsList: [], credentialLoading: false, fieldsLoading: false, fields: [] }; },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_docusign_fields', { task: 'send_envelope', includeCredId: true, clearBefore: true });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_docusign_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        if (this.fielddata.credId) this.getFields();
    },
    watch: { 'fielddata.credId': function (n, o) { if (n !== o) this.getFields(); } },
    template: '#docusign-action-template'
});
