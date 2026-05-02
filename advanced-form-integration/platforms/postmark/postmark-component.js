/**
 * Advanced Form Integration - "postmark" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("postmark").
 */

Vue.component('postmark', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, { credId: '' });
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_postmark_fields', { task: 'send_email' });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#postmark-action-template'
});
