/**
 * Advanced Form Integration - "pabbly" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("pabbly").
 */

Vue.component('pabbly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['subscribe'], required: false },
                { type: 'text', value: 'google', title: 'Google', task: ['subscribe'], required: false },
                { type: 'text', value: 'age', title: 'Age', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_pabbly_credentials',
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
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_pabbly_list', {
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
    template: '#pabbly-action-template'
});
