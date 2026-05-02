/**
 * Advanced Form Integration - "addcal" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("addcal").
 */

Vue.component('addcal', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_event'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_event'], required: false },
                { type: 'text', value: 'location', title: 'Location', task: ['create_event'], required: false },
                { type: 'text', value: 'date_start', title: 'Start Date & Time', task: ['create_event'], required: true, description: 'ISO 8601, e.g. 2024-03-25T14:00:00-05:00' },
                { type: 'text', value: 'date_end', title: 'End Date & Time', task: ['create_event'], required: true, description: 'ISO 8601 format' },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_event'], required: false, description: 'America/New_York etc.' },
                { type: 'text', value: 'is_all_day', title: 'All Day?', task: ['create_event'], required: false, description: 'true/false' },
                { type: 'text', value: 'recurrence_rule', title: 'Recurrence Rule', task: ['create_event'], required: false, description: 'RRULE string' },
                { type: 'text', value: 'has_rsvp', title: 'Enable RSVP', task: ['create_event'], required: false, description: 'true/false' },
                { type: 'text', value: 'rsvp_limit', title: 'RSVP Limit', task: ['create_event'], required: false },
                { type: 'text', value: 'busy_type', title: 'Busy Type', task: ['create_event'], required: false, description: 'busy or free' },
                { type: 'text', value: 'reminder_before', title: 'Reminder Minutes', task: ['create_event'], required: false },
                { type: 'text', value: 'short_link', title: 'Custom Short Link', task: ['create_event'], required: false },
                { type: 'text', value: 'team_uid', title: 'Team UID', task: ['create_event'], required: false },
                { type: 'text', value: 'calendar_uid', title: 'Calendar UID', task: ['create_event'], required: false },
                { type: 'text', value: 'calendar_name', title: 'Calendar Name', task: ['create_event'], required: false, description: 'Auto-create/use by name' },
                { type: 'text', value: 'image_url', title: 'Image URL', task: ['create_event'], required: false },
                { type: 'text', value: 'location_url', title: 'Location URL', task: ['create_event'], required: false },
                { type: 'text', value: 'internal_name', title: 'Internal Name', task: ['create_event'], required: false },
                { type: 'text', value: 'is_draft', title: 'Save as Draft', task: ['create_event'], required: false, description: 'true/false' }
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

        if (typeof this.fielddata.withHtml === 'undefined') {
            this.$set(this.fielddata, 'withHtml', false);
        }
    },
    template: '#addcal-action-template'
});
