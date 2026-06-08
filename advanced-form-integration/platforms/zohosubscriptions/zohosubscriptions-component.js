/**
 * Advanced Form Integration - "zohosubscriptions" (Zoho Billing) action.
 */

Vue.component('zohosubscriptions', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        var customerTasks = ['upsert_customer', 'create_subscription', 'create_hostedpage', 'create_invoice'];
        return {
            credentialsList: [],
            credentialLoading: false,
            organizationLoading: false,
            organizations: {},
            planLoading: false,
            plans: {},
            fields: [
                { type: 'text', value: 'first_name',   title: 'First Name',   task: customerTasks, required: false },
                { type: 'text', value: 'last_name',    title: 'Last Name',    task: customerTasks, required: false },
                { type: 'text', value: 'display_name', title: 'Display Name', task: customerTasks, required: false },
                { type: 'text', value: 'email',        title: 'Email',        task: customerTasks, required: true },
                { type: 'text', value: 'phone',        title: 'Phone',        task: customerTasks, required: false },
                { type: 'text', value: 'company_name', title: 'Company',      task: customerTasks, required: false },

                { type: 'text', value: 'billing_address', title: 'Billing — Street',  task: customerTasks, required: false },
                { type: 'text', value: 'billing_street2', title: 'Billing — Line 2',  task: customerTasks, required: false },
                { type: 'text', value: 'billing_city',    title: 'Billing — City',    task: customerTasks, required: false },
                { type: 'text', value: 'billing_state',   title: 'Billing — State',   task: customerTasks, required: false },
                { type: 'text', value: 'billing_zip',     title: 'Billing — Zip',     task: customerTasks, required: false },
                { type: 'text', value: 'billing_country', title: 'Billing — Country', task: customerTasks, required: false },

                { type: 'text', value: 'shipping_address', title: 'Shipping — Street',  task: customerTasks, required: false },
                { type: 'text', value: 'shipping_city',    title: 'Shipping — City',    task: customerTasks, required: false },
                { type: 'text', value: 'shipping_state',   title: 'Shipping — State',   task: customerTasks, required: false },
                { type: 'text', value: 'shipping_zip',     title: 'Shipping — Zip',     task: customerTasks, required: false },
                { type: 'text', value: 'shipping_country', title: 'Shipping — Country', task: customerTasks, required: false },

                { type: 'text', value: 'quantity',     title: 'Quantity',     task: ['create_subscription', 'create_hostedpage'], required: false },
                { type: 'text', value: 'plan_price',   title: 'Plan Price (override)', task: ['create_subscription', 'create_hostedpage'], required: false },
                { type: 'text', value: 'coupon_code',  title: 'Coupon Code',  task: ['create_subscription', 'create_hostedpage'], required: false },
                { type: 'text', value: 'reference_id', title: 'Reference ID', task: ['create_subscription', 'create_hostedpage'], required: false },

                { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON',
                  task: ['create_invoice'], required: false,
                  description: 'Example: [{"name":"Item","price":10,"quantity":1}] — leave blank when "WooCommerce Order Items" is selected.' }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });
        if (typeof this.fielddata.credId === 'undefined') { this.$set(this.fielddata, 'credId', ''); }
        if (typeof this.fielddata.organizationId === 'undefined') { this.$set(this.fielddata, 'organizationId', ''); }
        if (typeof this.fielddata.planCode === 'undefined') { this.$set(this.fielddata, 'planCode', ''); }
        if (typeof this.fielddata.line_items_source === 'undefined') {
            var triggerKey = (this.trigger && (this.trigger.key || this.trigger.trigger || this.trigger.value)) || '';
            this.$set(this.fielddata, 'line_items_source', triggerKey === 'woocommerce' ? 'woocommerce' : 'manual');
        }
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohosubscriptions_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        if (this.fielddata.credId) { this.fetchOrganizations(); }
        if (this.fielddata.credId && this.fielddata.organizationId) { this.fetchPlans(); }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) { this.fetchOrganizations(); }
        }
    },
    methods: {
        fetchOrganizations: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.organizations = {};
                this.fielddata.organizationId = '';
                return;
            }
            this.organizations = {};
            this.fielddata.organizationId = '';
            this.organizationLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_zohosubscriptions_organizations',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.organizations = response.data;
                } else {
                    that.organizations = {};
                }
                that.organizationLoading = false;
            }).fail(function () {
                that.organizations = {};
                that.organizationLoading = false;
            });
        },
        fetchPlans: function () {
            var that = this;
            if (!this.fielddata.credId || !this.fielddata.organizationId) {
                this.plans = {};
                return;
            }
            this.plans = {};
            this.planLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_zohosubscriptions_plans',
                credId: this.fielddata.credId,
                organizationId: this.fielddata.organizationId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.plans = response.data;
                } else {
                    that.plans = {};
                }
                that.planLoading = false;
            }).fail(function () {
                that.plans = {};
                that.planLoading = false;
            });
        }
    },
    template: '#zohosubscriptions-action-template'
});
