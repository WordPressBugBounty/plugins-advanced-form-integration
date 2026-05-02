/**
 * Advanced Form Integration - "woocommerce" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("woocommerce").
 */

Vue.component('woocommerce', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            addressKeys: [
                { key: 'first_name', title: 'First Name' },
                { key: 'last_name', title: 'Last Name' },
                { key: 'company', title: 'Company' },
                { key: 'address_1', title: 'Address Line 1' },
                { key: 'address_2', title: 'Address Line 2' },
                { key: 'city', title: 'City' },
                { key: 'state', title: 'State' },
                { key: 'postcode', title: 'Postal Code' },
                { key: 'country', title: 'Country' },
                { key: 'email', title: 'Email' },
                { key: 'phone', title: 'Phone' }
            ],
            fieldLists: {
                create_customer: [
                    { type: 'text', value: 'email', title: 'Email', task: ['create_customer'], required: true },
                    { type: 'text', value: 'username', title: 'Username', task: ['create_customer'], description: 'Optional username. Defaults to email when blank.' },
                    { type: 'text', value: 'password', title: 'Password', task: ['create_customer'], description: 'Leave blank to auto-generate.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_customer'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_customer'] },
                    { type: 'text', value: 'display_name', title: 'Display Name', task: ['create_customer'] },
                    { type: 'textarea', value: 'customer_note', title: 'Customer Note', task: ['create_customer'] },
                    { type: 'text', value: 'role', title: 'Role', task: ['create_customer'], description: 'Optional WordPress role to assign.' }
                ],
                create_order: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_order'], description: 'User/customer ID. Leave blank for guest orders.' },
                    { type: 'text', value: 'customer_email', title: 'Customer Email', task: ['create_order'] },
                    { type: 'text', value: 'customer_phone', title: 'Customer Phone', task: ['create_order'] },
                    { type: 'textarea', value: 'customer_note', title: 'Customer Note', task: ['create_order'] },
                    { type: 'text', value: 'status', title: 'Order Status', task: ['create_order'], description: 'pending, processing, completed, etc.' },
                    { type: 'text', value: 'payment_method', title: 'Payment Method ID', task: ['create_order'] },
                    { type: 'text', value: 'payment_method_title', title: 'Payment Method Title', task: ['create_order'] },
                    { type: 'text', value: 'transaction_id', title: 'Transaction ID', task: ['create_order'] },
                    { type: 'text', value: 'shipping_total', title: 'Shipping Total', task: ['create_order'] },
                    { type: 'text', value: 'discount_total', title: 'Discount Total', task: ['create_order'] },
                    { type: 'text', value: 'discount_tax', title: 'Discount Tax', task: ['create_order'] },
                    { type: 'text', value: 'shipping_tax', title: 'Shipping Tax', task: ['create_order'] },
                    { type: 'text', value: 'cart_tax', title: 'Cart Tax', task: ['create_order'] },
                    { type: 'text', value: 'total', title: 'Order Total', task: ['create_order'], description: 'Overrides calculated total when supplied.' },
                    { type: 'text', value: 'set_paid', title: 'Mark Paid', task: ['create_order'], description: 'Use 1/true to mark payment complete.' },
                    { type: 'textarea', value: 'order_note', title: 'Order Note', task: ['create_order'], description: 'Internal order note.' },
                    { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON', task: ['create_order'], description: 'JSON array of line items. Example: [{"product_id":123,"quantity":1,"totals":{"total":29.99,"subtotal":29.99}}]' },
                    { type: 'textarea', value: 'shipping_lines_json', title: 'Shipping Lines JSON', task: ['create_order'], description: 'JSON array matching WooCommerce REST shipping schema.' },
                    { type: 'textarea', value: 'fee_lines_json', title: 'Fee Lines JSON', task: ['create_order'] },
                    { type: 'textarea', value: 'coupon_lines_json', title: 'Coupon Lines JSON', task: ['create_order'] }
                ],
                create_subscription: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_subscription'], required: true },
                    { type: 'text', value: 'status', title: 'Subscription Status', task: ['create_subscription'], description: 'pending, active, on-hold, etc.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_subscription'], description: 'Defaults to store currency.' },
                    { type: 'text', value: 'billing_period', title: 'Billing Period', task: ['create_subscription'], description: 'day, week, month, year.' },
                    { type: 'text', value: 'billing_interval', title: 'Billing Interval', task: ['create_subscription'], description: 'Numeric interval, defaults to 1.' },
                    { type: 'text', value: 'start_date', title: 'Start Date', task: ['create_subscription'], description: 'Any strtotime-compatible string.' },
                    { type: 'text', value: 'trial_end', title: 'Trial End Date', task: ['create_subscription'] },
                    { type: 'text', value: 'next_payment', title: 'Next Payment Date', task: ['create_subscription'] },
                    { type: 'text', value: 'end_date', title: 'End Date', task: ['create_subscription'] },
                    { type: 'text', value: 'payment_method', title: 'Payment Method ID', task: ['create_subscription'] },
                    { type: 'text', value: 'requires_manual_renewal', title: 'Requires Manual Renewal', task: ['create_subscription'], description: 'Use 1/true to require manual renewal.' },
                    { type: 'text', value: 'total', title: 'Subscription Total', task: ['create_subscription'] },
                    { type: 'text', value: 'discount_total', title: 'Discount Total', task: ['create_subscription'] },
                    { type: 'text', value: 'discount_tax', title: 'Discount Tax', task: ['create_subscription'] },
                    { type: 'text', value: 'shipping_total', title: 'Shipping Total', task: ['create_subscription'] },
                    { type: 'text', value: 'shipping_tax', title: 'Shipping Tax', task: ['create_subscription'] },
                    { type: 'text', value: 'cart_tax', title: 'Cart Tax', task: ['create_subscription'] },
                    { type: 'textarea', value: 'subscription_note', title: 'Subscription Note', task: ['create_subscription'] },
                    { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON', task: ['create_subscription'], description: 'JSON array of subscription products.' },
                    { type: 'textarea', value: 'shipping_lines_json', title: 'Shipping Lines JSON', task: ['create_subscription'] },
                    { type: 'textarea', value: 'fee_lines_json', title: 'Fee Lines JSON', task: ['create_subscription'] },
                    { type: 'textarea', value: 'coupon_lines_json', title: 'Coupon Lines JSON', task: ['create_subscription'] }
                ],
                create_booking: [
                    { type: 'text', value: 'product_id', title: 'Bookable Product ID', task: ['create_booking'], required: true },
                    { type: 'text', value: 'resource_id', title: 'Resource ID', task: ['create_booking'], description: 'Optional resource for the booking.' },
                    { type: 'text', value: 'person_ids_json', title: 'Person IDs JSON', task: ['create_booking'], description: 'JSON array of person IDs/quantities as needed.' },
                    { type: 'text', value: 'start_date', title: 'Start Date (Y-m-d H:i)', task: ['create_booking'], required: true },
                    { type: 'text', value: 'end_date', title: 'End Date (Y-m-d H:i)', task: ['create_booking'], required: true },
                    { type: 'text', value: 'all_day', title: 'All Day (1/0)', task: ['create_booking'] },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['create_booking'], description: 'Number of slots/persons.' },
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_booking'], description: 'Existing user ID. Leave blank for guest bookings.' },
                    { type: 'text', value: 'customer_email', title: 'Customer Email', task: ['create_booking'] },
                    { type: 'text', value: 'customer_name', title: 'Customer Name', task: ['create_booking'] },
                    { type: 'text', value: 'customer_phone', title: 'Customer Phone', task: ['create_booking'] },
                    { type: 'text', value: 'order_status', title: 'Order Status', task: ['create_booking'], description: 'pending, confirmed, cancelled, etc.' },
                    { type: 'textarea', value: 'order_note', title: 'Order Note', task: ['create_booking'] },
                    { type: 'text', value: 'pricing_base_cost', title: 'Base Cost Override', task: ['create_booking'] },
                    { type: 'text', value: 'pricing_block_cost', title: 'Block Cost Override', task: ['create_booking'] },
                    { type: 'text', value: 'pricing_display_cost', title: 'Display Cost Override', task: ['create_booking'] },
                    { type: 'textarea', value: 'booking_note', title: 'Booking Note', task: ['create_booking'] },
                    { type: 'textarea', value: 'meta_json', title: 'Booking Meta (JSON)', task: ['create_booking'], description: 'Optional meta key/value pairs.' },
                    { type: 'textarea', value: 'order_meta_json', title: 'Order Meta (JSON)', task: ['create_booking'], description: 'Optional order meta key/value pairs.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            var task = this.action.task;
            var list = [];

            if (!task) {
                return list;
            }

            if (this.fieldLists[task]) {
                list = list.concat(this.fieldLists[task]);
            }

            if (task === 'create_customer') {
                list = list.concat(this.buildAddressFields('billing', ['create_customer'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_customer'], 'Shipping '));
            }

            if (task === 'create_order') {
                list = list.concat(this.buildAddressFields('billing', ['create_order'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_order'], 'Shipping '));
            }

            if (task === 'create_subscription') {
                list = list.concat(this.buildAddressFields('billing', ['create_subscription'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_subscription'], 'Shipping '));
            }

            if (task === 'create_booking') {
                list = list.concat(this.buildAddressFields('billing', ['create_booking'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_booking'], 'Shipping '));
            }

            return list;
        }
    },
    methods: {
        buildAddressFields: function (prefix, tasks, labelPrefix) {
            var fields = [];
            labelPrefix = labelPrefix || '';

            this.addressKeys.forEach(function (item) {
                fields.push({
                    type: 'text',
                    value: prefix + '_' + item.key,
                    title: labelPrefix + item.title,
                    task: tasks
                });
            });

            return fields;
        }
    },
    template: '#woocommerce-action-template'
});
