/**
 * Advanced Form Integration - "emma" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("emma").
 */

Vue.component('emma', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_contact'], required: false }
            ]
        };
    },
    methods: {
        getGroups: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_emma_groups', {
                targetKey: 'groups',
                loadingKey: 'groupLoading',
                requireCredId: false,
                includeCredId: true,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.groupId) this.fielddata.groupId = '';
        if (!this.fielddata.groups) this.fielddata.groups = {};

        if (this.fielddata.credId) {
            this.getGroups();
        }
    },
    template: '#emma-action-template'
});
