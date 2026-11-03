/**
 * Advanced Form Integration - "smartrmail" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("smartrmail").
 */

Vue.component('smartrmail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            listError: '',
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['add_subscriber'], required: false, description: 'International format with country code, e.g. +61478940895. Local numbers starting with 0 are skipped.' },
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
                'action': 'adfoin_get_smartrmail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    that.listError = typeof response.data === 'string' ? response.data : 'Could not load SmartrMail lists.';
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                that.listError = 'Could not load SmartrMail lists.';
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
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.listId = '';
                if (newVal) {
                    this.getLists();
                } else {
                    this.fielddata.lists = {};
                    this.$forceUpdate();
                }
            }
        }
    },
    template: '#smartrmail-action-template'
});
