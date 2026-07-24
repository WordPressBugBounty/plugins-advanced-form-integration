/**
 * Advanced Form Integration - "buddypressac" action component.
 */

Vue.component('buddypressac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            profileFieldLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_buddypressac_fields', {
                task: this.action.task,
                taskGate: this.action.task,
                clearBefore: true
            });
        },
        getGroups: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_buddypressac_groups', {
                targetKey: 'groupList',
                loadingKey: 'groupLoading',
                requireCredId: false,
                requireSuccess: true
            });
        },
        getProfileFields: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_buddypressac_profile_fields', {
                targetKey: 'profileFieldList',
                loadingKey: 'profileFieldLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (typeof this.fielddata.profileFieldId == 'undefined') {
            this.fielddata.profileFieldId = '';
        }

        this.getGroups();
        this.getProfileFields();
        this.getFields();
    },
    watch: {
        'action.task': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#buddypressac-action-template'
});
