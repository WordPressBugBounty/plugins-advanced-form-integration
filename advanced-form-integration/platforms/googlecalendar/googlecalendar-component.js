/**
 * Advanced Form Integration - "googlecalendar" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("googlecalendar").
 */

Vue.component('googlecalendar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            credLoading: false,
            selected: '',
            fields: [],
            title: '',
            description: '',
            start: '',
            end: '',
            timezone: '',
            location: '',
            attendees: ''
        }
    },
    methods: {
        updateFieldValue: function (value) {
            if (this.selected || this.selected == 0) {
                if (this.fielddata[value] || "0" == this.fielddata[value]) {
                    this.fielddata[value] += ' {{' + this[value] + '}}';
                } else {
                    this.fielddata[value] = '{{' + this[value] + '}}';
                }
            }
        },
        getCalendars: function () {
            var that = this;
            
            if (!this.action.credId) {
                this.fielddata.calendarList = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_googlecalendar_list',
                'credId': this.action.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.calendarList = response.data;
                } else {
                    that.fielddata.calendarList = {};
                    console.log('Error fetching calendars:', response.data);
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_googlecalendar_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        var that = this;

        if (typeof this.fielddata.calendarId == 'undefined') {
            this.fielddata.calendarId = '';
        }

        if (typeof this.fielddata.allDayEvent == 'undefined') {
            this.fielddata.allDayEvent = false;
        }

        if (typeof this.fielddata.allDayEvent != 'undefined') {
            if (this.fielddata.allDayEvent == "false") {
                this.fielddata.allDayEvent = false;
            }
        }

        if (typeof this.fielddata.notifyAttendees == 'undefined') {
            this.fielddata.notifyAttendees = false;
        }

        if (this.fielddata.notifyAttendees == "false") {
            this.fielddata.notifyAttendees = false;
        }

        // Initialize credId if not already set
        if (typeof this.action.credId == 'undefined') {
            this.action.credId = '';
        }

        // Load calendars if credential is already selected (when editing an existing integration)
        if (this.action.credId) {
            this.getCalendars();
        }
    },
    watch: {},
    template: '#googlecalendar-action-template'
});
