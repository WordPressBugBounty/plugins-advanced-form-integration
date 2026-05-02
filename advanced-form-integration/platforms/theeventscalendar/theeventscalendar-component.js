/**
 * Advanced Form Integration - "theeventscalendar" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("theeventscalendar").
 */

Vue.component('theeventscalendar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_event: [
                    { type: 'text', value: 'title', title: 'Title', task: ['create_event'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_event'], description: 'publish, draft, pending, private, or future.' },
                    { type: 'text', value: 'content', title: 'Content', task: ['create_event'] },
                    { type: 'text', value: 'excerpt', title: 'Excerpt', task: ['create_event'] },
                    { type: 'text', value: 'author_id', title: 'Author ID', task: ['create_event'], description: 'Optional WordPress user ID.' },
                    { type: 'text', value: 'start_date', title: 'Start Date (Y-m-d)', task: ['create_event'], required: true },
                    { type: 'text', value: 'start_time', title: 'Start Time (H:i)', task: ['create_event'] },
                    { type: 'text', value: 'end_date', title: 'End Date (Y-m-d)', task: ['create_event'], required: true },
                    { type: 'text', value: 'end_time', title: 'End Time (H:i)', task: ['create_event'] },
                    { type: 'text', value: 'all_day', title: 'All Day', task: ['create_event'], description: 'true/false.' },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_event'], description: 'Optional IANA timezone.' },
                    { type: 'text', value: 'venue_id', title: 'Venue ID', task: ['create_event'] },
                    { type: 'text', value: 'organizer_id', title: 'Organizer ID', task: ['create_event'] },
                    { type: 'text', value: 'cost', title: 'Cost', task: ['create_event'] },
                    { type: 'text', value: 'featured', title: 'Featured', task: ['create_event'], description: 'true/false.' },
                    { type: 'text', value: 'website_url', title: 'Website URL', task: ['create_event'] },
                    { type: 'text', value: 'hide_from_list', title: 'Hide From List', task: ['create_event'], description: 'true/false to hide from calendars.' },
                    { type: 'text', value: 'category_ids', title: 'Category IDs', task: ['create_event'], description: 'Comma separated category IDs.' },
                    { type: 'text', value: 'category_slugs', title: 'Category Slugs', task: ['create_event'], description: 'Comma separated slugs.' },
                    { type: 'text', value: 'tag_slugs', title: 'Tag Slugs', task: ['create_event'], description: 'Comma separated tag slugs.' },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_event'], description: 'Optional JSON object of additional meta.' }
                ],
                update_event: [
                    { type: 'text', value: 'event_id', title: 'Event ID', task: ['update_event'], required: true },
                    { type: 'text', value: 'title', title: 'Title', task: ['update_event'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_event'] },
                    { type: 'text', value: 'content', title: 'Content', task: ['update_event'] },
                    { type: 'text', value: 'excerpt', title: 'Excerpt', task: ['update_event'] },
                    { type: 'text', value: 'author_id', title: 'Author ID', task: ['update_event'] },
                    { type: 'text', value: 'start_date', title: 'Start Date (Y-m-d)', task: ['update_event'] },
                    { type: 'text', value: 'start_time', title: 'Start Time (H:i)', task: ['update_event'] },
                    { type: 'text', value: 'end_date', title: 'End Date (Y-m-d)', task: ['update_event'] },
                    { type: 'text', value: 'end_time', title: 'End Time (H:i)', task: ['update_event'] },
                    { type: 'text', value: 'all_day', title: 'All Day', task: ['update_event'] },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['update_event'] },
                    { type: 'text', value: 'venue_id', title: 'Venue ID', task: ['update_event'] },
                    { type: 'text', value: 'organizer_id', title: 'Organizer ID', task: ['update_event'] },
                    { type: 'text', value: 'cost', title: 'Cost', task: ['update_event'] },
                    { type: 'text', value: 'featured', title: 'Featured', task: ['update_event'] },
                    { type: 'text', value: 'website_url', title: 'Website URL', task: ['update_event'] },
                    { type: 'text', value: 'hide_from_list', title: 'Hide From List', task: ['update_event'] },
                    { type: 'text', value: 'category_ids', title: 'Category IDs', task: ['update_event'] },
                    { type: 'text', value: 'category_slugs', title: 'Category Slugs', task: ['update_event'] },
                    { type: 'text', value: 'tag_slugs', title: 'Tag Slugs', task: ['update_event'] },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['update_event'] }
                ],
                delete_event: [
                    { type: 'text', value: 'event_id', title: 'Event ID', task: ['delete_event'], required: true }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#theeventscalendar-action-template'
});
