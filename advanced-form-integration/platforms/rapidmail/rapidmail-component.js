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
                { type: 'text', value: 'gender', title: 'Gender', task: ['subscribe'], required: false, description: 'male or female' },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdate', title: 'Birthdate', task: ['subscribe'], required: false, description: 'YYYY-MM-DD or DD.MM.YYYY' }
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

        if (!this.fielddata.optin) {
            this.$set(this.fielddata, 'optin', 'active');
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#rapidmail-action-template'
});
