/**
 * Advanced Form Integration — "mailersend" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("mailersend").
 *
 * MailerSend is a transactional email API (not a subscriber/list
 * platform — see mailersend.php notes). The free task is "Send Email"
 * via POST /v1/email. Field set is static: from / to / subject / text.
 * Pro overlays template_id, variables, cc/bcc, reply_to, tags via the
 * separate maropostpro Vue component.
 */

Vue.component('mailersend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text',     value: 'from_email', title: 'From Email', task: ['send_email'], required: true,  description: 'Must be a verified sender domain in MailerSend.' },
                { type: 'text',     value: 'from_name',  title: 'From Name',  task: ['send_email'], required: false },
                { type: 'text',     value: 'to_email',   title: 'To Email',   task: ['send_email'], required: true },
                { type: 'text',     value: 'to_name',    title: 'To Name',    task: ['send_email'], required: false },
                { type: 'text',     value: 'subject',    title: 'Subject',    task: ['send_email'], required: true },
                { type: 'textarea', value: 'text',       title: 'Plain Text Body', task: ['send_email'], required: false, description: 'Provide plain text or upgrade to AFI Pro for HTML body and templates.' }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailersend_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                that.credLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        this.fetchCredentialsList();
    },
    template: '#mailersend-action-template'
});
