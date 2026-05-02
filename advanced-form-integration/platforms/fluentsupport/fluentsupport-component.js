/**
 * Advanced Form Integration - "fluentsupport" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fluentsupport").
 */

Vue.component('fluentsupport', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            mailboxLoading: false,
            agentLoading: false,
            fields: [
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_ticket'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_ticket'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['create_ticket'], required: false },
                { type: 'text', value: 'subject', title: 'Subject', task: ['create_ticket'], required: false },
                { type: 'textarea', value: 'message', title: 'Message', task: ['create_ticket'], required: false },
            ]
        };
    },
    methods: {
        /**
         * Fetch Fluent Support mailboxes via AJAX
         */
        fetchMailboxes: function () {
            var that = this;
            this.mailboxLoading = true;

            var requestData = {
                action: 'adfoin_get_fluentsupport_mailboxes',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.mailboxes = response.data;
                } else {
                    alert(response.data.message || 'Error fetching mailboxes.');
                }
                that.mailboxLoading = false;
            });
        },

        /**
         * Fetch Fluent Support agents via AJAX
         */
        fetchAgents: function () {
            var that = this;
            this.agentLoading = true;

            var requestData = {
                action: 'adfoin_get_fluentsupport_agents',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.agents = response.data;
                } else {
                    alert(response.data.message || 'Error fetching agents.');
                }
                that.agentLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.mailboxId == 'undefined') {
            this.fielddata.mailboxId = '';
        }

        if (typeof this.fielddata.agentId == 'undefined') {
            this.fielddata.agentId = '';
        }

        this.fetchMailboxes();
        this.fetchAgents();
    },
    template: '#fluentsupport-action-template'
});
