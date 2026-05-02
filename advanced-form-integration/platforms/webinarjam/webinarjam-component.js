/**
 * Advanced Form Integration - "webinarjam" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("webinarjam").
 */

Vue.component('webinarjam', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            webinarLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['register_webinar'], required: true, description: 'Required' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['register_webinar'], required: true, description: 'Required' },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['register_webinar'], required: false },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['register_webinar'], required: false },
                { type: 'text', value: 'phoneCountryCode', title: 'Phone Country Code', task: ['register_webinar'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['register_webinar'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['register_webinar'], required: false },
                { type: 'text', value: 'date', title: 'Date', task: ['register_webinar'], required: false }
            ]

        }
    },
    methods: {
        getWebinars: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_webinarjam_webinars', {
                targetKey: 'webinars',
                loadingKey: 'webinarLoading',
                requireCredId: false,
                includeCredId: true
            });
        },
        getSchedule: function () {
            var that = this;
            this.webinarLoading = true;

            var scheduleData = {
                'action': 'adfoin_get_webinarjam_schedules',
                '_nonce': adfoin.nonce,
                'webinarId': this.fielddata.webinarId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, scheduleData, function (response) {
                var schedules = response.data;
                that.fielddata.schedules = schedules;
                that.webinarLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.webinarId == 'undefined') {
            this.fielddata.webinarId = '';
        }

        if (typeof this.fielddata.scheduleId == 'undefined') {
            this.fielddata.scheduleId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_webinarjam_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load webinars if credential is selected
                if (that.fielddata.credId) {
                    that.getWebinars();
                }
            }
        });
    },
    template: '#webinarjam-action-template'
});
