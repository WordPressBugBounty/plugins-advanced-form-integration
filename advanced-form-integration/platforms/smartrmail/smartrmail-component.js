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
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
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
                    console.error("Error fetching SmartrMail lists:", response.data);
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching SmartrMail lists:", status, error);
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
