/**
 * Advanced Form Integration - "civicrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("civicrm").
 */

Vue.component('civicrm', {
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
            adfoinHelpers.getFields(this, 'adfoin_get_civicrm_contact_fields', { task: 'add_contact' });
        },
        getGroups: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_civicrm_groups', {
                targetKey: 'groupList',
                loadingKey: 'groupLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        this.getGroups();
        this.getFields();
    },
    template: '#civicrm-action-template'
});
