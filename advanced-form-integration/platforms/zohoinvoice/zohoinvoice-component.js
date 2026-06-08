/**
 * Advanced Form Integration - "zohoinvoice" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("zohoinvoice").
 */

Vue.component('zohoinvoice', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        var customerTasks = ['upsert_customer', 'create_invoice', 'create_estimate', 'create_recurring_invoice', 'create_customer_payment'];
        var docTasks      = ['create_invoice', 'create_estimate', 'create_recurring_invoice'];
        return {
            credentialsList: [],
            credentialLoading: false,
            organizationLoading: false,
            organizations: {},
            fields: [
                { type: 'text', value: 'contact_name', title: 'Contact Name', task: customerTasks, required: false, description: 'Falls back to "First Last" then Company Name then Email if blank.' },
                { type: 'text', value: 'first_name',   title: 'First Name',   task: customerTasks, required: false },
                { type: 'text', value: 'last_name',    title: 'Last Name',    task: customerTasks, required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: customerTasks, required: false },
                { type: 'text', value: 'email',        title: 'Email',        task: customerTasks, required: false },
                { type: 'text', value: 'phone',        title: 'Phone',        task: customerTasks, required: false },
                { type: 'text', value: 'website',      title: 'Website',      task: ['upsert_customer'], required: false },

                { type: 'text', value: 'billing_address', title: 'Billing — Street',  task: customerTasks, required: false },
                { type: 'text', value: 'billing_street2', title: 'Billing — Line 2',  task: customerTasks, required: false },
                { type: 'text', value: 'billing_city',    title: 'Billing — City',    task: customerTasks, required: false },
                { type: 'text', value: 'billing_state',   title: 'Billing — State',   task: customerTasks, required: false },
                { type: 'text', value: 'billing_zip',     title: 'Billing — Zip',     task: customerTasks, required: false },
                { type: 'text', value: 'billing_country', title: 'Billing — Country', task: customerTasks, required: false },
                { type: 'text', value: 'billing_phone',   title: 'Billing — Phone',   task: customerTasks, required: false },

                { type: 'text', value: 'shipping_address', title: 'Shipping — Street',  task: customerTasks, required: false },
                { type: 'text', value: 'shipping_street2', title: 'Shipping — Line 2',  task: customerTasks, required: false },
                { type: 'text', value: 'shipping_city',    title: 'Shipping — City',    task: customerTasks, required: false },
                { type: 'text', value: 'shipping_state',   title: 'Shipping — State',   task: customerTasks, required: false },
                { type: 'text', value: 'shipping_zip',     title: 'Shipping — Zip',     task: customerTasks, required: false },
                { type: 'text', value: 'shipping_country', title: 'Shipping — Country', task: customerTasks, required: false },
                { type: 'text', value: 'shipping_phone',   title: 'Shipping — Phone',   task: customerTasks, required: false },

                { type: 'text',     value: 'reference_number', title: 'Reference Number',    task: docTasks, required: false },
                { type: 'text',     value: 'date',             title: 'Date (YYYY-MM-DD)',   task: docTasks, required: false },
                { type: 'text',     value: 'due_date',         title: 'Due Date (YYYY-MM-DD)', task: ['create_invoice'], required: false },
                { type: 'text',     value: 'currency_id',      title: 'Currency ID',         task: docTasks, required: false },
                { type: 'textarea', value: 'notes',            title: 'Notes',               task: docTasks, required: false },

                { type: 'textarea', value: 'line_items_json',  title: 'Line Items JSON',
                  task: docTasks, required: false,
                  description: 'Example: [{"item_name":"Product","sku":"SKU-1","rate":10,"quantity":2}] — leave blank when "WooCommerce Order Items" is selected.'
                },

                { type: 'text', value: 'repeat_every',    title: 'Repeat Every (number)',   task: ['create_recurring_invoice'], required: false },
                { type: 'text', value: 'frequency',       title: 'Frequency (days/months/years)', task: ['create_recurring_invoice'], required: false },
                { type: 'text', value: 'recurrence_name', title: 'Recurrence Name',          task: ['create_recurring_invoice'], required: false },

                { type: 'text',     value: 'item_name',        title: 'Item Name',        task: ['upsert_item'], required: true },
                { type: 'text',     value: 'item_sku',         title: 'SKU',              task: ['upsert_item'], required: false },
                { type: 'text',     value: 'item_rate',        title: 'Rate',             task: ['upsert_item'], required: false },
                { type: 'textarea', value: 'item_description', title: 'Description',      task: ['upsert_item'], required: false },

                { type: 'text',     value: 'payment_mode',     title: 'Payment Mode',     task: ['create_customer_payment'], required: true },
                { type: 'text',     value: 'payment_amount',   title: 'Amount',           task: ['create_customer_payment'], required: true },
                { type: 'text',     value: 'payment_date',     title: 'Date (YYYY-MM-DD)',task: ['create_customer_payment'], required: false },
                { type: 'text',     value: 'payment_reference',title: 'Reference Number', task: ['create_customer_payment'], required: false },
                { type: 'text',     value: 'invoice_id',       title: 'Invoice ID (optional)', task: ['create_customer_payment'], required: false }
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
        if (typeof this.fielddata.line_items_source === 'undefined') {
            var triggerKey = (this.trigger && (this.trigger.key || this.trigger.trigger || this.trigger.value)) || '';
            this.$set(this.fielddata, 'line_items_source', triggerKey === 'woocommerce' ? 'woocommerce' : 'manual');
        }
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohoinvoice_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        if (this.fielddata.credId) { this.fetchOrganizations(); }
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
                action: 'adfoin_get_zohoinvoice_organizations',
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
        }
    },
    template: '#zohoinvoice-action-template'
});
