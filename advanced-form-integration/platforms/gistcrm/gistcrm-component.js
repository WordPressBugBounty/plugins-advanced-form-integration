/**
 * Advanced Form Integration - "gistcrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("gistcrm").
 */

Vue.component('gistcrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            customFieldsLoading: false,
            customFields: []
        };
    },
    methods: {
        fetchCustomFields: function () {
            var that = this;
            this.customFieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_gist_custom_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.customFields = response.data;
                } else {
                    alert(response.data.message || 'Error fetching custom fields.');
                }
                that.customFieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.fetchCustomFields();
    },
    template: '#gistcrm-action-template'
});
