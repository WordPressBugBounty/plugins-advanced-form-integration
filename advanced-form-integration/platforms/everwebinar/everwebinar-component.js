/**
 * Advanced Form Integration - "everwebinar" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("everwebinar").
 */

Vue.component('everwebinar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            webinarLoading: false,
            scheduleLoading: false,
            credentialLoading: false,
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
        getData: function() {
            this.getCredentials();
            this.getWebinar();
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_everwebinar_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getWebinar: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_everwebinar_webinars', {
                targetKey: 'webinars',
                loadingKey: 'webinarLoading'
            });
        },
        getSchedule: function () {
            var that = this;
            this.scheduleLoading = true;

            var scheduleData = {
                'action': 'adfoin_get_everwebinar_schedules',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'webinarId': this.fielddata.webinarId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, scheduleData, function (response) {
                var schedules = response.data;
                that.fielddata.schedules = schedules;
                that.scheduleLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.webinarId == 'undefined') {
            this.fielddata.webinarId = '';
        }

        if (typeof this.fielddata.scheduleId == 'undefined') {
            this.fielddata.scheduleId = '';
        }

        this.getData();

        if (this.fielddata.webinarId) {
            this.getSchedule();
        }
    },
    template: '#everwebinar-action-template'
});
