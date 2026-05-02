/**
 * Advanced Form Integration - "affiliatewp" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("affiliatewp").
 */

Vue.component('affiliatewp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                add_affiliate: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['add_affiliate'], description: 'Existing WordPress user ID.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['add_affiliate'], description: 'If user ID is not provided a new user will be created from this email.' },
                    { type: 'text', value: 'user_name', title: 'Username', task: ['add_affiliate'], description: 'Optional username to associate with the affiliate.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['add_affiliate'], description: 'Accepted values: active, pending, rejected.' },
                    { type: 'text', value: 'rate', title: 'Rate', task: ['add_affiliate'], description: 'Affiliate specific rate (leave empty to use default).' },
                    { type: 'text', value: 'rate_type', title: 'Rate Type', task: ['add_affiliate'], description: 'Accepted values: percentage, flat.' },
                    { type: 'text', value: 'flat_rate_basis', title: 'Flat Rate Basis', task: ['add_affiliate'], description: 'Optional product type used for flat rates.' },
                    { type: 'text', value: 'payment_email', title: 'Payment Email', task: ['add_affiliate'] },
                    { type: 'text', value: 'website_url', title: 'Website URL', task: ['add_affiliate'] },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['add_affiliate'], description: 'Optional internal notes.' },
                    { type: 'text', value: 'date_registered', title: 'Date Registered', task: ['add_affiliate'], description: 'Format: YYYY-MM-DD HH:MM:SS' },
                    { type: 'text', value: 'dynamic_coupon', title: 'Dynamic Coupon', task: ['add_affiliate'], description: 'Use yes/true/1 to enable dynamic coupon creation.' },
                    { type: 'text', value: 'registration_method', title: 'Registration Method', task: ['add_affiliate'] },
                    { type: 'text', value: 'registration_url', title: 'Registration URL', task: ['add_affiliate'] }
                ],
                add_referral: [
                    { type: 'text', value: 'affiliate_id', title: 'Affiliate ID', task: ['add_referral'], description: 'Direct affiliate ID. Optional if user ID / username provided.' },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['add_referral'], description: 'Used to locate the affiliate when ID is unknown.' },
                    { type: 'text', value: 'user_name', title: 'Username', task: ['add_referral'], description: 'Affiliate username fallback lookup.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['add_referral'] },
                    { type: 'text', value: 'order_total', title: 'Order Total', task: ['add_referral'] },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['add_referral'], description: 'Order or transaction reference.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['add_referral'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['add_referral'], description: 'Accepted values: pending, unpaid, paid, rejected.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['add_referral'], description: 'Currency code (e.g. USD).' },
                    { type: 'text', value: 'campaign', title: 'Campaign', task: ['add_referral'] },
                    { type: 'text', value: 'context', title: 'Context', task: ['add_referral'] },
                    { type: 'text', value: 'custom', title: 'Custom Data', task: ['add_referral'], description: 'Plain text or JSON string.' },
                    { type: 'text', value: 'products', title: 'Products', task: ['add_referral'], description: 'Optionally pass JSON encoded product data.' },
                    { type: 'text', value: 'parent_id', title: 'Parent Referral ID', task: ['add_referral'] },
                    { type: 'text', value: 'visit_id', title: 'Visit ID', task: ['add_referral'] },
                    { type: 'text', value: 'type', title: 'Referral Type', task: ['add_referral'] },
                    { type: 'text', value: 'date', title: 'Referral Date', task: ['add_referral'], description: 'Format: YYYY-MM-DD HH:MM:SS' },
                    { type: 'text', value: 'flag', title: 'Flag', task: ['add_referral'], description: 'Optional internal flag.' }
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
    template: '#affiliatewp-action-template'
});
