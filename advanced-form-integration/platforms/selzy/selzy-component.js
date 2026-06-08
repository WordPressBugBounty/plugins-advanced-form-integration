/**
 * Advanced Form Integration - "selzy" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("selzy").
 */

Vue.component('selzy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_selzy_credentials',
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
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_selzy_list', {
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

        if (typeof this.fielddata.doubleOptin == 'undefined') {
            this.fielddata.doubleOptin = false;
        }

        if (this.fielddata.doubleOptin == 'false') {
            this.fielddata.doubleOptin = false;
        }

        if (typeof this.fielddata.overwrite == 'undefined') {
            this.fielddata.overwrite = '0';
        }

        this.getCredentials();
    },
    template: '#selzy-action-template'
});
