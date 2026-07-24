/**
 * Advanced Form Integration - "wpjobmanagerac" action component.
 */

Vue.component('wpjobmanagerac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            jobTypeLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_wpjobmanagerac_fields', { task: 'add_job_listing' });
        },
        getJobTypes: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_wpjobmanagerac_job_types', {
                targetKey: 'jobTypeList',
                loadingKey: 'jobTypeLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.jobTypeId == 'undefined') {
            this.fielddata.jobTypeId = '';
        }

        this.getJobTypes();
        this.getFields();
    },
    template: '#wpjobmanagerac-action-template'
});
