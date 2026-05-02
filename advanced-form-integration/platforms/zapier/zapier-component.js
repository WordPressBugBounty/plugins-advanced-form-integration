/**
 * Advanced Form Integration - "zapier" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zapier").
 */

Vue.component('zapier', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {}
    },
    mounted: function () {

        if (typeof this.fielddata.webhookUrl == 'undefined') {
            this.fielddata.webhookUrl = '';
        }
    },
    template: '#zapier-action-template'
});
