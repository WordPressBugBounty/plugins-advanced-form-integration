/**
 * Advanced Form Integration - "livestorm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("livestorm").
 */

Vue.component('livestorm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            eventLoading: false,
            sessionLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_people'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_people'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_people'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getEvents();
        },
        getCredentials: function () {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_livestorm_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getEvents: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_livestorm_events', {
                targetKey: 'events',
                loadingKey: 'eventLoading'
            });
        },
        getSessions: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_livestorm_sessions', {
                targetKey: 'sessions',
                loadingKey: 'sessionLoading',
                extraParams: { eventId: this.fielddata.eventId }
            });
        }
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
    template: '#livestorm-action-template'
});
