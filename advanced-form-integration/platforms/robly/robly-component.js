/**
 * Advanced Form Integration - "robly" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("robly").
 */

Vue.component('robly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'fname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lname', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'double_opt_in', title: 'Double Opt-in', task: ['subscribe'], required: false, description: 'Set to "true" to send a confirmation email before subscribing' }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_robly_credentials',
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
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_robly_list', {
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
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getCredentials();
    },
    template: '#robly-action-template'
});
