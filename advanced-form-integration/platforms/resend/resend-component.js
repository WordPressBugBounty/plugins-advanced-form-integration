/**
 * Advanced Form Integration - "resend" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("resend").
 */

Vue.component('resend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false },

            ]
        };
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_resend_lists', {
                targetKey: 'lists',
                loadingKey: 'groupLoading',
                requireCredId: false,
                includeCredId: true,
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

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#resend-action-template'
});
