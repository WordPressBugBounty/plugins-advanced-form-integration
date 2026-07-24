/**
 * Advanced Form Integration - "jetpackcrmac" action component.
 */

Vue.component('jetpackcrmac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            tagLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_jetpackcrmac_contact_fields', { task: 'add_contact' });
        },
        getTags: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_jetpackcrmac_tags', {
                targetKey: 'tagList',
                loadingKey: 'tagLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.tagId == 'undefined') {
            this.fielddata.tagId = '';
        }

        this.getTags();
        this.getFields();
    },
    template: '#jetpackcrmac-action-template'
});
