/**
 * Advanced Form Integration - "emailchef" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("emailchef").
 */

Vue.component('emailchef', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            listError: '',
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listError = '';
            this.$set(this.fielddata, 'lists', []);

            if (!this.fielddata.credId) return;

            this.groupLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_emailchef_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success) {
                    that.$set(that.fielddata, 'lists', response.data);
                } else {
                    that.listError = (response && typeof response.data === 'string') ? response.data : 'Could not load Emailchef lists.';
                }
            }).fail(function () {
                that.listError = 'Could not load Emailchef lists.';
            }).always(function () {
                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = { credId: '', listId: '', lists: [] };
        var that = this;

        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.listId = '';
            }
        }
    },
    template: '#emailchef-action-template'
});
