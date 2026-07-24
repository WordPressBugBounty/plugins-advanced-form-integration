/**
 * Advanced Form Integration - "eventinac" action component.
 */

Vue.component('eventinac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            eventLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_eventinac_fields', { task: 'add_attendee' });
        },
        getEvents: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_eventinac_events', {
                targetKey: 'eventList',
                loadingKey: 'eventLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.eventId == 'undefined') {
            this.fielddata.eventId = '';
        }

        this.getEvents();
        this.getFields();
    },
    template: '#eventinac-action-template'
});
