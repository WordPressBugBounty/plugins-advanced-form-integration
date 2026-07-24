/**
 * Advanced Form Integration - "lifterlmsac" action component.
 */

Vue.component('lifterlmsac', {
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
            adfoinHelpers.getFields(this, 'adfoin_get_lifterlmsac_fields', { task: 'enroll_course' });
        },
        getCourses: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_lifterlmsac_courses', {
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
    template: '#lifterlmsac-action-template'
});
