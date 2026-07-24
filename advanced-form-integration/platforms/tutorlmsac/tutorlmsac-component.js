/**
 * Advanced Form Integration - "tutorlmsac" action component.
 */

Vue.component('tutorlmsac', {
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
            adfoinHelpers.getFields(this, 'adfoin_get_tutorlmsac_fields', { task: 'enroll_course' });
        },
        getCourses: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_tutorlmsac_courses', {
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
    template: '#tutorlmsac-action-template'
});
