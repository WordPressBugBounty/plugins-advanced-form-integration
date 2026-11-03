/**
 * Advanced Form Integration - "elasticemail" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("elasticemail").
 */

Vue.component('elasticemail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            listError: '',
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false, description: 'For an existing contact, an empty value clears the name in Elastic Email.' },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false, description: 'For an existing contact, an empty value clears the name in Elastic Email.' }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getList();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_elasticemail_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getList: function () {
            var that = this;
            this.listError = '';

            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'list', {});
                return;
            }

            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_elasticemail_list',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success) {
                    that.$set(that.fielddata, 'list', response.data);
                } else {
                    that.$set(that.fielddata, 'list', {});
                    that.listError = (response && typeof response.data === 'string') ? response.data : 'Could not load Elastic Email lists.';
                }
            }).fail(function () {
                that.listError = 'Could not load Elastic Email lists.';
            }).always(function () {
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var defaults = { credId: '', listId: '', email: '', firstName: '', lastName: '' };
        var that = this;

        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#elasticemail-action-template'
});
