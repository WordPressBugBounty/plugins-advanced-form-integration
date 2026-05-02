/**
 * Advanced Form Integration - "slack" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("slack").
 */

Vue.component('slack', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'textarea', value: 'message', title: 'Message', task: ['sendmsg'], required: false }
            ]

        }
    },
    methods: {
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.url == 'undefined') {
            this.fielddata.url = '';
        }

        if (typeof this.fielddata.message == 'undefined') {
            this.fielddata.message = '';
        }
    },
    template: '#slack-action-template'
});
