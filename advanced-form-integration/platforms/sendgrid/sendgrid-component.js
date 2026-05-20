/**
 * Advanced Form Integration — "sendgrid" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("sendgrid").
 */

Vue.component('sendgrid', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',      title: 'Email',      task: ['subscribe'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'] },
                { type: 'text', value: 'last_name',  title: 'Last Name',  task: ['subscribe'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sendgrid_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.fetchLists();
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchLists: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                return;
            }
            this.listLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_sendgrid_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.lists = (response && response.success && response.data) ? response.data : {};
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.lists = {};
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId: '',
            listId: '',
            lists:  {}
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.listId = '';
                this.fetchLists();
            }
        }
    },
    template: '#sendgrid-action-template'
});
