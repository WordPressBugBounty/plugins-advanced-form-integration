/**
 * Advanced Form Integration - "fluentaffiliate" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fluentaffiliate").
 */

Vue.component('fluentaffiliate', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_affiliate: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_affiliate'], description: 'Existing WordPress user ID.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['create_affiliate'], description: 'Used to locate/create the user.' },
                    { type: 'text', value: 'user_login', title: 'Username', task: ['create_affiliate'], description: 'Optional username when creating a new user.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_affiliate'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_affiliate'] },
                    { type: 'text', value: 'display_name', title: 'Display Name', task: ['create_affiliate'] },
                    { type: 'text', value: 'user_url', title: 'Website URL', task: ['create_affiliate'] },
                    { type: 'text', value: 'role', title: 'User Role', task: ['create_affiliate'], description: 'Role for newly created user (e.g. subscriber).' },
                    { type: 'text', value: 'status', title: 'Affiliate Status', task: ['create_affiliate'], description: 'pending, active, or inactive.' },
                    { type: 'text', value: 'rate_type', title: 'Rate Type', task: ['create_affiliate'], description: 'default, group, flat, or percentage.' },
                    { type: 'text', value: 'rate', title: 'Rate', task: ['create_affiliate'], description: 'Required when rate type is flat or percentage.' },
                    { type: 'text', value: 'group_id', title: 'Group ID', task: ['create_affiliate'], description: 'Required when rate type is group.' },
                    { type: 'text', value: 'payment_email', title: 'Payment Email', task: ['create_affiliate'] },
                    { type: 'text', value: 'note', title: 'Note', task: ['create_affiliate'] },
                    { type: 'text', value: 'custom_param', title: 'Custom Param', task: ['create_affiliate'] },
                    { type: 'text', value: 'settings_disable_new_ref_email', title: 'Disable New Referral Email', task: ['create_affiliate'], description: 'Yes/No to disable referral notifications.' }
                ],
                create_referral: [
                    { type: 'text', value: 'affiliate_id', title: 'Affiliate ID', task: ['create_referral'], description: 'Required affiliate ID.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['create_referral'], description: 'Required referral amount.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_referral'], description: 'unpaid, pending, or rejected.' },
                    { type: 'text', value: 'type', title: 'Type', task: ['create_referral'], description: 'sale or opt_in.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_referral'] },
                    { type: 'text', value: 'provider', title: 'Provider', task: ['create_referral'], description: 'Defaults to manual.' },
                    { type: 'text', value: 'provider_id', title: 'Provider ID', task: ['create_referral'] },
                    { type: 'text', value: 'provider_sub_id', title: 'Provider Sub ID', task: ['create_referral'] },
                    { type: 'text', value: 'order_total', title: 'Order Total', task: ['create_referral'] },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_referral'] },
                    { type: 'text', value: 'utm_campaign', title: 'UTM Campaign', task: ['create_referral'] },
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_referral'] },
                    { type: 'text', value: 'visit_id', title: 'Visit ID', task: ['create_referral'] },
                    { type: 'text', value: 'parent_id', title: 'Parent Referral ID', task: ['create_referral'] },
                    { type: 'text', value: 'products', title: 'Products (JSON)', task: ['create_referral'], description: 'Optional JSON encoded products array.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_referral'], description: 'Optional JSON encoded settings.' }
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
    template: '#fluentaffiliate-action-template'
});
