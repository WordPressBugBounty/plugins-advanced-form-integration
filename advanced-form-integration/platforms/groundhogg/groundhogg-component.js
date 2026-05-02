/**
 * Advanced Form Integration - "groundhogg" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("groundhogg").
 */

Vue.component('groundhogg', {
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
            adfoinHelpers.getFields(this, 'adfoin_get_groundhogg_contact_fields', { task: 'add_contact' });
        },
        getTags: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_groundhogg_tags', {
                targetKey: 'tagList',
                loadingKey: 'tagLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.tagId == 'undefined') {
            this.fielddata.tagId = '';
        }

        this.getTags();
        this.getFields();
    },
    template: '#groundhogg-action-template'
});
