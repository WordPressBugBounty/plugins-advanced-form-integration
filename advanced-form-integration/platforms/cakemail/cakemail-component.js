/**
 * Advanced Form Integration - "cakemail" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("cakemail").
 */

Vue.component('cakemail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: [
                { type: 'text', value: 'doubleOptIn', title: 'Double Opt-In', task: ['add_subscriber'], required: false, description: 'true or false' },
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true }
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
                'action': 'adfoin_get_cakemail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    console.error("Error fetching Cakemail lists:", response.data);
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching Cakemail lists:", status, error);
                that.fielddata.lists = {};
                that.listLoading = false;
                that.$forceUpdate();
            });
        },
        getCustomFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_cakemail_custom_fields',
                'credId': this.fielddata.credId,
                'listId': this.fielddata.listId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(function (field) {
                        that.fields.push({
                            type: 'text',
                            value: field.key,
                            title: field.label || field.key,
                            task: ['add_subscriber'],
                            required: false,
                            description: field.description || ''
                        });
                    });

                    that.fields.push(
                        {
                            type: 'text',
                            value: 'tags',
                            title: 'Tags',
                            task: ['add_subscriber'],
                            required: false,
                            description: 'Comma separated tags'
                        },
                        {
                            type: 'text',
                            value: 'interests',
                            title: 'Interests',
                            task: ['add_subscriber'],
                            required: false,
                            description: 'Comma separated interests'
                        }
                    );
                    that.fieldsLoading = false;
                }
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching Cakemail custom fields:", status, error);
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
        if (this.fielddata.credId && this.fielddata.listId) {
            this.getCustomFields();
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
        },
        'fielddata.listId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.getCustomFields();
            }
        }
    },
    template: '#cakemail-action-template'
});
