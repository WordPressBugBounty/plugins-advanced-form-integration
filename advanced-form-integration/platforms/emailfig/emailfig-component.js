Vue.component('emailfig', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'tags', title: 'Tags', task: ['subscribe'], required: false, description: 'Comma-separated list of tags' }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getLists();
        },
        getCredentials: function () {
            var that = this;
            that.credentialLoading = true;
            jQuery.post(ajaxurl, { 
                action: 'adfoin_get_emailfig_credentials', 
                _nonce: adfoin.nonce 
            }, function (response) {
                that.credentialLoading = false;
                if (response && response.success && response.data) {
                    that.credentialsList = response.data;
                    if (that.fielddata.credId) { 
                        that.getData(); 
                    }
                }
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            if (!this.fielddata.credId) return;

            var that = this;
            that.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_emailfig_lists',
                credId: that.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.listLoading = false;
                if (response && response.success && response.data) {
                    that.fielddata.lists = response.data;
                }
            }).fail(function () {
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }
        if (typeof this.fielddata.lists == 'undefined') {
            this.fielddata.lists = {};
        }
        this.getCredentials();
    },
    template: '#emailfig-action-template'
});
