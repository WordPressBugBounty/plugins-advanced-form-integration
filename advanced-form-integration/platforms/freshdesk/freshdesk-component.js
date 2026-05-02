/**
 * Advanced Form Integration - "freshdesk" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("freshdesk").
 */

Vue.component('freshdesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            ticketFieldsLoading: false,
            fields: []
        };
    },
    methods: {
        fetchTicketFields: function () {
            var that = this;
            this.ticketFieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_freshdesk_ticket_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_ticket'], required: false, description: single.description });
                        });

                        that.ticketFieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.fetchTicketFields();
        }
    },
    template: '#freshdesk-action-template'
});
