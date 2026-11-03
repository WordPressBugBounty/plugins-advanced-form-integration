/**
 * Advanced Form Integration - "acuity" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("acuity").
 */

Vue.component('acuity', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            appointmentTypeLoading: false,
            calendarLoading: false,
            appointmentTypeError: '',
            calendarError: '',
            appointmentTypes: {},
            calendars: {},
            credentialsList: [],
            fields: [
                { type: 'text', value: 'datetime', title: 'Appointment Date & Time', task: ['create_appointment'], required: false, description: 'Required unless Date and Time are mapped. Any common format, e.g. 2026-11-05 14:00 or 2026-11-05T14:00:00-0500. Without an offset it is read in the calendar\'s timezone.' },
                { type: 'text', value: 'date', title: 'Date', task: ['create_appointment'], required: false, description: 'Use with Time when the form has separate date and time fields.' },
                { type: 'text', value: 'time', title: 'Time', task: ['create_appointment'], required: false, description: 'Use with Date, e.g. 14:00 or 2:00 PM.' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_appointment'], required: true },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_appointment'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['create_appointment'], required: true, description: 'Optional only when booking as admin.' },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_appointment'], required: false, description: 'Your Acuity settings may make this required.' },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_appointment'], required: false, description: 'America/New_York etc.' },
                { type: 'text', value: 'certificate', title: 'Certificate / Coupon Code', task: ['create_appointment'], required: false },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_appointment'], required: false, description: 'Saved only when booking as admin.' },
                { type: 'textarea', value: 'fieldDefinitions', title: 'Form Fields (JSON)', task: ['create_appointment'], required: false, description: 'Intake form answers: [{"id":123,"value":"Answer"}]' },
                { type: 'text', value: 'addonIds', title: 'Addon IDs (comma separated)', task: ['create_appointment'], required: false },
                { type: 'text', value: 'labelId', title: 'Label ID', task: ['create_appointment'], required: false }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.appointmentTypeId === 'undefined') {
            this.$set(this.fielddata, 'appointmentTypeId', '');
        }

        if (typeof this.fielddata.calendarId === 'undefined') {
            this.$set(this.fielddata, 'calendarId', '');
        }

        if (typeof this.fielddata.adminMode === 'undefined') {
            this.$set(this.fielddata, 'adminMode', 'client');
        }

        if (typeof this.fielddata.noEmail === 'undefined') {
            this.$set(this.fielddata, 'noEmail', false);
        }
    },
    mounted: function () {
        this.fetchCredentialsList();
        this.fetchAppointmentTypes();
        this.fetchCalendars();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.appointmentTypeId = '';
                this.fielddata.calendarId = '';
                this.fetchAppointmentTypes();
                this.fetchCalendars();
            }
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acuity_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
            });
        },
        fetchAppointmentTypes: function () {
            var that = this;
            this.appointmentTypeError = '';
            if (!this.fielddata.credId) {
                this.appointmentTypes = {};
                return;
            }
            this.appointmentTypeLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acuity_appointment_types',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.appointmentTypes = response.data;
                } else {
                    that.appointmentTypes = {};
                    that.appointmentTypeError = (response && typeof response.data === 'string') ? response.data : 'Could not load appointment types.';
                }
                that.appointmentTypeLoading = false;
            }).fail(function () {
                that.appointmentTypes = {};
                that.appointmentTypeError = 'Could not load appointment types.';
                that.appointmentTypeLoading = false;
            });
        },
        fetchCalendars: function () {
            var that = this;
            this.calendarError = '';
            if (!this.fielddata.credId) {
                this.calendars = {};
                return;
            }
            this.calendarLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acuity_calendars',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.calendars = response.data;
                } else {
                    that.calendars = {};
                    that.calendarError = (response && typeof response.data === 'string') ? response.data : 'Could not load calendars.';
                }
                that.calendarLoading = false;
            }).fail(function () {
                that.calendars = {};
                that.calendarError = 'Could not load calendars.';
                that.calendarLoading = false;
            });
        }
    },
    template: '#acuity-action-template'
});
