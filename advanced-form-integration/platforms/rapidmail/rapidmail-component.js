/**
 * Advanced Form Integration - "rapidmail" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("rapidmail").
 */

Vue.component('rapidmail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['subscribe'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdate', title: 'Birthdate', task: ['subscribe'], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active, new' },
                { type: 'text', value: 'extra1', title: 'Extra 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra2', title: 'Extra 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra3', title: 'Extra 3', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra4', title: 'Extra 4', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra5', title: 'Extra 5', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra6', title: 'Extra 6', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra7', title: 'Extra 7', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra8', title: 'Extra 8', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra9', title: 'Extra 9', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra10', title: 'Extra 10', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        getLists: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_rapidmail_lists', {
                targetKey: 'lists',
                loadingKey: 'groupLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    created: function () {

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
    template: '#rapidmail-action-template'
});
