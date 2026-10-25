Vue.component('signnow', {
    props: ["trigger", "action", "fielddata"],
    mixins: [window.adfoinEsignTemplatePicker ? adfoinEsignTemplatePicker({
        templatesAction: 'adfoin_get_signnow_templates',
        rolesAction: 'adfoin_get_signnow_template_roles',
        idPattern: /^[0-9a-f]{40}$/i
    }) : {}],
    data: function () { return { credentialsList: [], credentialLoading: false, fieldsLoading: false, fields: [] }; },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_signnow_fields', { task: 'send_invite', clearBefore: true });
        }
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_signnow_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        this.getFields();
    },
    template: '#signnow-action-template'
});
