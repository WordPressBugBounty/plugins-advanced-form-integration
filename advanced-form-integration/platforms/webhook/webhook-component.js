/**
 * Advanced Form Integration - "webhook" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("webhook").
 */

Vue.component('webhook', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {}
    },
    mounted: function () {

        if (typeof this.fielddata.webhookUrl == 'undefined') {
            this.fielddata.webhookUrl = '';
        }
    },
    template: '#webhook-action-template'
});
