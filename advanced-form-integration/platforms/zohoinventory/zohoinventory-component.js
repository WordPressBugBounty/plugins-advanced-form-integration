/**
 * Advanced Form Integration - "zohoinventory" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("zohoinventory").
 */

Vue.component('zohoinventory', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            organizationLoading: false,
            organizations: {},
            fields: [
                // Customer block — used by every task except upsert_item.
                { type: 'text',     value: 'contact_name', title: 'Contact Name', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false, description: 'Falls back to "First Last" then Company Name then Email if blank.' },
                { type: 'text',     value: 'first_name',   title: 'First Name',   task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'last_name',    title: 'Last Name',    task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'company_name', title: 'Company Name', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'email',        title: 'Email',        task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'phone',        title: 'Phone',        task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'website',      title: 'Website',      task: ['upsert_customer'], required: false },

                // Billing address
                { type: 'text', value: 'billing_address', title: 'Billing — Street Address', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'billing_street2', title: 'Billing — Address Line 2', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'billing_city',    title: 'Billing — City',           task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'billing_state',   title: 'Billing — State',          task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'billing_zip',     title: 'Billing — ZIP / Postcode', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'billing_country', title: 'Billing — Country',        task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'billing_phone',   title: 'Billing — Phone',          task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },

                // Shipping address
                { type: 'text', value: 'shipping_address', title: 'Shipping — Street Address', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'shipping_street2', title: 'Shipping — Address Line 2', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'shipping_city',    title: 'Shipping — City',           task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'shipping_state',   title: 'Shipping — State',          task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'shipping_zip',     title: 'Shipping — ZIP / Postcode', task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'shipping_country', title: 'Shipping — Country',        task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },
                { type: 'text', value: 'shipping_phone',   title: 'Shipping — Phone',          task: ['upsert_customer', 'create_salesorder', 'create_invoice'], required: false },

                // Document fields
                { type: 'text',     value: 'reference_number', title: 'Reference Number',    task: ['create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'date',             title: 'Date (YYYY-MM-DD)',   task: ['create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'due_date',         title: 'Due Date (YYYY-MM-DD)', task: ['create_invoice'], required: false },
                { type: 'text',     value: 'discount',         title: 'Discount',            task: ['create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'shipping_charge',  title: 'Shipping Charge',     task: ['create_salesorder', 'create_invoice'], required: false },
                { type: 'text',     value: 'adjustment',       title: 'Adjustment',          task: ['create_salesorder', 'create_invoice'], required: false },
                { type: 'textarea', value: 'notes',            title: 'Notes',               task: ['create_salesorder', 'create_invoice'], required: false },

                // Line items — only shown when source = manual
                { type: 'textarea', value: 'line_items_json',  title: 'Line Items JSON',
                  task: ['create_salesorder', 'create_invoice'],
                  required: false,
                  description: 'Example: [{"item_name":"Product","sku":"SKU-1","rate":10,"quantity":2}] — leave blank when "WooCommerce Order Items" is selected.'
                },

                // Package
                { type: 'text',     value: 'salesorder_id',    title: 'Sales Order ID', task: ['create_package'], required: true, description: 'Existing Sales Order to ship from.' },

                // Item upsert
                { type: 'text',     value: 'item_name',        title: 'Item Name',        task: ['upsert_item'], required: true },
                { type: 'text',     value: 'item_sku',         title: 'SKU',              task: ['upsert_item'], required: false },
                { type: 'text',     value: 'item_rate',        title: 'Rate',             task: ['upsert_item'], required: false },
                { type: 'textarea', value: 'item_description', title: 'Description',      task: ['upsert_item'], required: false },
                { type: 'text',     value: 'item_type',        title: 'Type (inventory/non-inventory/service)', task: ['upsert_item'], required: false }
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

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.organizationId === 'undefined') {
            this.$set(this.fielddata, 'organizationId', '');
        }
        if (typeof this.fielddata.line_items_source === 'undefined') {
            // Default to "woocommerce" when this integration was created
            // under a WooCommerce trigger, otherwise "manual".
            var triggerKey = (this.trigger && (this.trigger.key || this.trigger.trigger || this.trigger.value)) || '';
            this.$set(this.fielddata, 'line_items_source', triggerKey === 'woocommerce' ? 'woocommerce' : 'manual');
        }
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohoinventory_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        if (this.fielddata.credId) {
            this.fetchOrganizations();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fetchOrganizations();
            }
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
                action: 'adfoin_get_zohoinventory_organizations',
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
    template: '#zohoinventory-action-template'
});
