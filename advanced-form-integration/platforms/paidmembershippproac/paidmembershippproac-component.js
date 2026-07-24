/**
 * Advanced Form Integration - "paidmembershippproac" action component.
 */

Vue.component('paidmembershippproac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            levelLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_paidmembershippproac_fields', { task: 'add_member' });
        },
        getLevels: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_paidmembershippproac_levels', {
                targetKey: 'levelList',
                loadingKey: 'levelLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.levelId == 'undefined') {
            this.fielddata.levelId = '';
        }

        this.getLevels();
        this.getFields();
    },
    template: '#paidmembershippproac-action-template'
});
