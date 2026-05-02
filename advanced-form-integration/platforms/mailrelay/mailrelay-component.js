/**
 * Advanced Form Integration - "mailrelay" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailrelay").
 */

Vue.component('mailrelay', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'sms_phone', title: 'SMS Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['subscribe'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'locale', title: 'Locale', task: ['subscribe'], required: false, description: 'e.g. en' },
                { type: 'text', value: 'time_zone', title: 'Time Zone', task: ['subscribe'], required: false, description: 'e.g. Africa/Abidjan' },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active, inactive' },
            ]
        };
    },
    methods: {
        getGroups: function (credId = null) {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_mailrelay_groups',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getGroups(this.fielddata.credId);
        }
    },
    template: '#mailrelay-action-template'
});
