/**
 * Advanced Form Integration — "maropost" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("maropost").
 *
 * Multi-account flow (mirrors notion / gist / brevo):
 *   - credentialsList — fetched on mount via adfoin_get_maropost_credentials.
 *   - fielddata.lists — refetched per-account when credId changes.
 *
 * The basic field set (email / firstName / lastName) is hardcoded in
 * `fields` since it never varies; the lists dropdown is what depends
 * on the chosen account.
 */

Vue.component('maropost', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_maropost_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getLists();
                    }
                }
                that.credLoading = false;
            });
        },
        getLists: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                return;
            }
            this.listLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_maropost_lists',
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
                this.getLists();
            }
        }
    },
    template: '#maropost-action-template'
});
