/**
 * Advanced Form Integration - "sendlane" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendlane").
 */

Vue.component('sendlane', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sendlane_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                that.credentialLoading = false;
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.fielddata.credId) {
                        that.getList();
                    }
                }
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_sendlane_lists', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getList();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.list === 'undefined') {
            this.$set(this.fielddata, 'list', {});
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#sendlane-action-template'
});
