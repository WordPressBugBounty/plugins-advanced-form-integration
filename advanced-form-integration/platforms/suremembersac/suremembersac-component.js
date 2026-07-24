/**
 * Advanced Form Integration - "suremembersac" action component.
 */

Vue.component('suremembersac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_suremembersac_fields', { task: 'add_to_group' });
        },
        getGroups: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_suremembersac_groups', {
                targetKey: 'groupList',
                loadingKey: 'groupLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        this.getGroups();
        this.getFields();
    },
    template: '#suremembersac-action-template'
});
