/**
 * Advanced Form Integration - "sender" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sender").
 */

Vue.component('sender', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getGroups: function (credId = null) {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_sender_groups',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_sender_fields', { task: 'subscribe' });
        },
        getData: function () {
            this.getGroups();
            this.getFields();
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
            this.getData();
        }
    },
    template: '#sender-action-template'
});
