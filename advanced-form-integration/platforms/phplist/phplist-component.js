/**
 * Advanced Form Integration - "phplist" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("phplist").
 */

Vue.component('phplist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#phplist-action-template'
});
