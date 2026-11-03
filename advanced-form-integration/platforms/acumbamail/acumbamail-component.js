/**
 * Advanced Form Integration - "acumbamail" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("acumbamail").
 */

Vue.component('acumbamail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            lists: {},
            listLoading: false,
            listError: '',
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listError = '';
            this.lists = {};

            if (!this.fielddata.credId) return;

            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acumbamail_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success) {
                    that.lists = response.data;
                } else {
                    that.listError = (response && typeof response.data === 'string') ? response.data : 'Could not load Acumbamail lists.';
                }
            }).fail(function () {
                that.listError = 'Could not load Acumbamail lists.';
            }).always(function () {
                that.listLoading = false;
            });
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.listId = '';
                this.getLists();
            }
        }
    },
    mounted: function () {
        var defaults = { credId: '', listId: '', doubleOptin: false, updateSubscriber: true };
        var that = this;

        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') that.$set(that.fielddata, k, defaults[k]);
        });

        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') that.$set(that.fielddata, field.value, '');
        });

        adfoinHelpers.fetchCredentials(this, 'adfoin_get_acumbamail_credentials');
        this.getLists();
    },
    template: '#acumbamail-action-template'
});
