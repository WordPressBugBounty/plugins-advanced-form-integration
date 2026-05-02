/**
 * Advanced Form Integration - "doppler" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("doppler").
 */

Vue.component('doppler', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'FIRSTNAME', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'LASTNAME', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'GENDER', title: 'Gender', task: ['subscribe'], required: false },
                { type: 'text', value: 'BIRTHDAY', title: 'Birthday', task: ['subscribe'], required: false },
                { type: 'text', value: 'CONSENT', title: 'Consent', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            this.fielddata.lists = [];
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_doppler_lists', {
                targetKey: 'lists',
                loadingKey: 'groupLoading',
                requireCredId: false,
                includeCredId: true,
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
    template: '#doppler-action-template'
});
