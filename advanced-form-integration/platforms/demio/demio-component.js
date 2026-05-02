/**
 * Advanced Form Integration - "demio" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("demio").
 */

Vue.component('demio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            eventLoading: false,
            sessionLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['reg_people'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['reg_people'], required: true },
                // {type: 'text', value: 'last_name', title: 'Last Name', task: ['reg_people'], required: false},
                // {type: 'text', value: 'company', title: 'Company', task: ['reg_people'], required: false},
                // {type: 'text', value: 'website', title: 'Website', task: ['reg_people'], required: false},
                // {type: 'text', value: 'phone_number', title: 'Phone Number', task: ['reg_people'], required: false},
                // {type: 'text', value: 'gdpr', title: 'GDPR', task: ['reg_people'], required: false},
                // {type: 'text', value: 'refUrl', title: 'Event Registration page URL', task: ['reg_people'], required: false},

            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getEvents();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_demio_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getEvents: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_demio_events', {
                targetKey: 'events',
                loadingKey: 'eventLoading'
            });
        },
        getSessions: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_demio_sessions', {
                targetKey: 'sessions',
                loadingKey: 'sessionLoading',
                extraParams: { eventId: this.fielddata.eventId }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.eventId == 'undefined') {
            this.fielddata.eventId = '';
        }

        if (typeof this.fielddata.sessionId == 'undefined') {
            this.fielddata.sessionId = '';
        }

        this.getData();

        if (this.fielddata.eventId) {
            this.getSessions();
        }

    },

    template: '#demio-action-template'
});
