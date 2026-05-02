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
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            this.fielddata.lists = [];
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_emailchef_lists', {
                targetKey: 'lists',
                loadingKey: 'groupLoading',
                emptyValue: [],
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId && (!this.fielddata.lists || this.fielddata.lists.length === 0)) {
            this.getLists();
        }
    },
    template: '#emailchef-action-template'
});
