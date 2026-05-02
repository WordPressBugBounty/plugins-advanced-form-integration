/**
 * Advanced Form Integration - "bbpress" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("bbpress").
 */

Vue.component('bbpress', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {};
    },
    methods: {
        ensureDefaults: function () {
            var defaults = [
                'title',
                'content',
                'slug',
                'parent',
                'author',
                'forumStatus',
                'visibility',
                'forumType',
                'forum',
                'postStatus',
                'stickType',
                'tags',
                'topic',
                'replyTo'
            ];

            for (var i = 0; i < defaults.length; i++) {
                var key = defaults[i];
                if (typeof this.fielddata[key] === 'undefined') {
                    this.$set(this.fielddata, key, '');
                }
            }
        }
    },
    created: function () {
        this.ensureDefaults();
    },
    mounted: function () {
        this.ensureDefaults();
    },
    watch: {
        'action.task': function () {
            this.ensureDefaults();
        }
    },
    template: '#bbpress-action-template'
});
