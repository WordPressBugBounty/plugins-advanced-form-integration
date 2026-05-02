/**
 * Advanced Form Integration - "apollo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("apollo").
 */

Vue.component('apollo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            userLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getFields();
            this.getUsers();
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_apollo_fields', { task: 'add_contact' });
        },
        getUsers: function () {
            var that = this;
            this.userLoading = true;
            var userRequestData = {
                'action': 'adfoin_get_apollo_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, userRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.users = response.data;
                        that.userLoading = false;
                    }
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.userId === 'undefined') {
            this.fielddata.userId = '';
        }
        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#apollo-action-template'
});
