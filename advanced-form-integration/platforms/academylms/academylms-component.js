/**
 * Advanced Form Integration - "academylms" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("academylms").
 */

Vue.component('academylms', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            courseLoading: false,
            lessonLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Student Email', task: ['enroll', 'unenroll'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['enroll'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['enroll'], required: false },
                { type: 'text', value: 'username', title: 'Username', task: ['enroll'], required: true },
                { type: 'text', value: 'password', title: 'Password', task: ['enroll'], required: true },
            ]

        }
    },
    methods: {
        getCourses: function (credId = null) {
            var that = this;

            this.courseLoading = true;

            var courseRequestData = {
                'action': 'adfoin_get_academylms_courses',
                // 'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, courseRequestData, function (response) {
                that.fielddata.courses = response.data;
                that.courseLoading = false;
            });
        },
        getLessons: function (courseId = null) {
            var that = this;

            this.lessonLoading = true;

            var lessonRequestData = {
                'action': 'adfoin_get_academylms_lessons',
                'courseId': courseId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, lessonRequestData, function (response) {
                that.fielddata.lesson = response.data;
                that.lessonLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.courseId == 'undefined') {
            this.fielddata.courseId = '';
        }

        if (typeof this.fielddata.lessonId == 'undefined') {
            this.fielddata.lessonId = '';
        }

        this.getCourses();

        if (this.fielddata.courseId != '') {
            this.getLessons(this.fielddata.courseId);
        }
    },
    template: '#academylms-action-template'
});
