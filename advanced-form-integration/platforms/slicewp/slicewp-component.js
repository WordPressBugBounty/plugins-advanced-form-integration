/**
 * Advanced Form Integration - "slicewp" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("slicewp").
 */

Vue.component('slicewp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'amount', title: 'Commission Amount', task: ['add_commission'], required: true },
                { type: 'text', value: 'reference', title: 'Reference', task: ['add_commission'], required: true },
                { type: 'date', value: 'commission_date', title: 'Commission Date', task: ['add_commission'], required: true },
                { type: 'select', value: 'status', title: 'Commission Status', task: ['add_commission'], required: true, options: ['unpaid', 'paid', 'rejected'] },
                { type: 'select', value: 'type', title: 'Commission Type', task: ['add_commission'], required: true, options: ['sale', 'lead', 'click'] },
            ]
        };
    },
    methods: {
        checkPluginStatus: function () {
            jQuery.post(ajaxurl, {
                action: 'adfoin_slicewp_check_plugin',
                _nonce: adfoin.nonce
            }, function (response) {
                if (!response.success) {
                    console.error('SliceWP plugin is not active or authorization failed.');
                }
            });
        }
    },
    mounted: function () {
        this.checkPluginStatus();
    },
    template: '#slicewp-action-template'
});
