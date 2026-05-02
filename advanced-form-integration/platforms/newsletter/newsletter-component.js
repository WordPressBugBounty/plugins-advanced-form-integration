/**
 * Advanced Form Integration - "newsletter" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("newsletter").
 */

Vue.component('newsletter', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: [],
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_newsletter_fields', { task: 'subscribe' });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.getFields();
    },
    template: '#newsletter-action-template',
});
