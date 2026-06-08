/**
 * Advanced Form Integration - "zohobooks" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohobooks").
 */

Vue.component('zohobooks', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            organizationLoading: false,
            organizations: {},
            fields: [
                { type: 'text', value: 'contact_name', title: 'Contact Name', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['upsert_customer'], required: false },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: false },
                { type: 'text', value: 'reference_number', title: 'Reference Number', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: false },
                { type: 'text', value: 'date', title: 'Date (YYYY-MM-DD)', task: ['create_estimate', 'create_invoice', 'create_salesorder'], required: false },
                { type: 'text', value: 'due_date', title: 'Due Date (YYYY-MM-DD)', task: ['create_estimate', 'create_invoice'], required: false },
                { type: 'text', value: 'currency_code', title: 'Currency Code', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: false },
                { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: true, description: 'Example: [{"item_name":"Product","rate":10,"quantity":1,"description":"Note"}]' },
                { type: 'text', value: 'item_name', title: 'Item Name', task: ['upsert_item'], required: true },
                { type: 'text', value: 'item_rate', title: 'Item Rate', task: ['upsert_item'], required: false },
                { type: 'textarea', value: 'item_description', title: 'Item Description', task: ['upsert_item'], required: false },
                { type: 'text', value: 'item_type', title: 'Item Type (goods/services)', task: ['upsert_item'], required: false },
                { type: 'text', value: 'payment_mode', title: 'Payment Mode', task: ['create_customer_payment'], required: true },
                { type: 'text', value: 'payment_amount', title: 'Payment Amount', task: ['create_customer_payment'], required: true },
                { type: 'text', value: 'payment_date', title: 'Payment Date (YYYY-MM-DD)', task: ['create_customer_payment'], required: false },
                { type: 'text', value: 'payment_reference', title: 'Payment Reference', task: ['create_customer_payment'], required: false },
                { type: 'text', value: 'invoice_id', title: 'Invoice ID (optional)', task: ['create_customer_payment'], required: false }
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
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohobooks_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
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

            var requestData = {
                action: 'adfoin_get_zohobooks_organizations',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
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
    template: '#zohobooks-action-template'
});
