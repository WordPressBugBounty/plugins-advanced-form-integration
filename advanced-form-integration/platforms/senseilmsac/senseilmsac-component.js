/**
 * Advanced Form Integration - "senseilmsac" action component.
 */

Vue.component('senseilmsac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            courseLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_senseilmsac_fields', { task: 'enroll_course' });
        },
        getCourses: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_senseilmsac_courses', {
                targetKey: 'courseList',
                loadingKey: 'courseLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.courseId == 'undefined') {
            this.fielddata.courseId = '';
        }

        this.getCourses();
        this.getFields();
    },
    template: '#senseilmsac-action-template'
});
