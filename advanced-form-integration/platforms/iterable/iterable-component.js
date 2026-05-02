/**
 * Advanced Form Integration - "iterable" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("iterable").
 */

Vue.component('iterable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: '',
                lists: {}
            });
        },
        getLists: function () {
            const that = this;

            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                return;
            }

            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_iterable_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    that.fielddata.lists = {};
                    that.fielddata.listId = '';
                }
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.lists = {};
                that.fielddata.listId = '';
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists();
        }
    },
    template: '#iterable-action-template'
});
