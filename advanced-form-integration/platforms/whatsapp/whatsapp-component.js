/**
 * Advanced Form Integration - "whatsapp" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("whatsapp").
 */

Vue.component('whatsapp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            loading: false,
            fields: []
        };
    },
    methods: {
        getTemplates: function () {
            var that = this;
            this.loading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_whatsapp_templates',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fields = response.data.map(template => ({
                        type: 'text',
                        value: template.name,
                        title: template.name,
                        required: false
                    }));
                }
                that.loading = false;
            });
        }
    },
    mounted: function () {
        if (this.action.task === 'send_message') {
            this.getTemplates();
        }
    },
    template: '#whatsapp-action-template'
});
