/**
 * Advanced Form Integration - "campayn" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("campayn").
 */

Vue.component('campayn', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            listError: '',
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true, description: 'If this email is already on the list, the contact is left unchanged (Campayn\'s API cannot update contacts).' },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listError = '';
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.$forceUpdate();
                return;
            }
            this.listLoading = true;
            this.fielddata.lists = {};

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_campayn_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    that.listError = typeof response.data === 'string' ? response.data : 'Could not load Campayn lists.';
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                that.listError = 'Could not load Campayn lists.';
                that.fielddata.lists = {};
                that.listLoading = false;
                that.$forceUpdate();
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.lists === 'undefined') {
            this.$set(this.fielddata, 'lists', {});
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.$set(this.fielddata, 'listId', '');
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#campayn-action-template'
});
