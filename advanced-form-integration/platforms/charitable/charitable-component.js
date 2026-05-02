/**
 * Advanced Form Integration - "charitable" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("charitable").
 */

Vue.component('charitable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_donation: [
                    { type: 'text', value: 'campaign_id', title: 'Campaign ID', task: ['create_donation'], description: 'Required. ID of the campaign receiving the donation.' },
                    { type: 'text', value: 'campaign_name', title: 'Campaign Name', task: ['create_donation'], description: 'Optional override campaign name.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['create_donation'], description: 'Required. Donation amount.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_donation'], description: 'Optional currency code (defaults to site currency).' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_donation'], description: 'Donation status, e.g. charitable-completed.' },
                    { type: 'text', value: 'gateway', title: 'Gateway', task: ['create_donation'], description: 'Payment gateway slug (defaults to manual).' },
                    { type: 'text', value: 'donation_key', title: 'Donation Key', task: ['create_donation'] },
                    { type: 'text', value: 'donation_note', title: 'Donation Note', task: ['create_donation'] },
                    { type: 'text', value: 'log_note', title: 'Log Note', task: ['create_donation'] },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_donation'] },
                    { type: 'text', value: 'donor_id', title: 'Donor ID', task: ['create_donation'] },
                    { type: 'text', value: 'donor_email', title: 'Donor Email', task: ['create_donation'] },
                    { type: 'text', value: 'donor_first_name', title: 'Donor First Name', task: ['create_donation'] },
                    { type: 'text', value: 'donor_last_name', title: 'Donor Last Name', task: ['create_donation'] },
                    { type: 'text', value: 'donor_company', title: 'Donor Company', task: ['create_donation'] },
                    { type: 'text', value: 'donor_address', title: 'Donor Address', task: ['create_donation'] },
                    { type: 'text', value: 'donor_address_2', title: 'Donor Address Line 2', task: ['create_donation'] },
                    { type: 'text', value: 'donor_city', title: 'Donor City', task: ['create_donation'] },
                    { type: 'text', value: 'donor_state', title: 'Donor State', task: ['create_donation'] },
                    { type: 'text', value: 'donor_postcode', title: 'Donor Postcode', task: ['create_donation'] },
                    { type: 'text', value: 'donor_country', title: 'Donor Country', task: ['create_donation'] },
                    { type: 'text', value: 'donor_phone', title: 'Donor Phone', task: ['create_donation'] },
                    { type: 'text', value: 'contact_consent', title: 'Contact Consent', task: ['create_donation'], description: 'Yes/No to mark donor contact consent.' },
                    { type: 'text', value: 'anonymous', title: 'Anonymous Donation', task: ['create_donation'], description: 'Yes/No to mark donation anonymous.' },
                    { type: 'text', value: 'donation_plan', title: 'Donation Plan ID', task: ['create_donation'] },
                    { type: 'text', value: 'date_gmt', title: 'Donation Date (GMT)', task: ['create_donation'], description: 'Optional date-time in Y-m-d H:i:s format.' },
                    { type: 'text', value: 'transaction_id', title: 'Gateway Transaction ID', task: ['create_donation'] },
                    { type: 'text', value: 'payment_id', title: 'Gateway Payment ID', task: ['create_donation'] },
                    { type: 'text', value: 'transaction_url', title: 'Transaction URL', task: ['create_donation'] },
                    { type: 'text', value: 'receipt_url', title: 'Receipt URL', task: ['create_donation'] },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_donation'], description: 'Optional JSON string for additional meta, e.g. {"source":"API"}.' }
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
    template: '#charitable-action-template'
});
