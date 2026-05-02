/**
 * Advanced Form Integration - "mailtrain" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mailtrain").
 */

Vue.component('mailtrain', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['add_to_list'], required: false, description: 'Example: Europe/Tallinn' }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.lists === 'undefined') {
                this.$set(this.fielddata, 'lists', {});
            }
            if (typeof this.fielddata.forceSubscribe === 'undefined') {
                this.$set(this.fielddata, 'forceSubscribe', '');
            }
            if (typeof this.fielddata.requireConfirmation === 'undefined') {
                this.$set(this.fielddata, 'requireConfirmation', '');
            }
            if (this.fielddata.forceSubscribe === false) {
                this.fielddata.forceSubscribe = '';
            }
            if (this.fielddata.forceSubscribe === true) {
                this.fielddata.forceSubscribe = 'true';
            }
            if (this.fielddata.requireConfirmation === false) {
                this.fielddata.requireConfirmation = '';
            }
            if (this.fielddata.requireConfirmation === true) {
                this.fielddata.requireConfirmation = 'true';
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        },
        getLists: function () {
            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'lists', {});
                this.fielddata.listId = '';
                return;
            }
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_mailtrain_lists', {
                targetKey: 'lists',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mailtrain-action-template'
});
