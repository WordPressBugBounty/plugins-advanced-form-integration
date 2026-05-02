/**
 * Advanced Form Integration - "givewp" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("givewp").
 */

Vue.component('givewp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_donor: [
                    { type: 'text', value: 'email', title: 'Donor Email', task: ['create_donor'], required: true, description: 'Required primary email for the donor.' },
                    { type: 'text', value: 'name', title: 'Donor Name', task: ['create_donor'], description: 'Optional donor display name. Defaults to the supplied first/last name or email.' },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_donor'], description: 'Optional WordPress user ID to link to the donor.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_donor'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_donor'] },
                    { type: 'text', value: 'company', title: 'Company', task: ['create_donor'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_donor'] },
                    { type: 'text', value: 'title_prefix', title: 'Title Prefix', task: ['create_donor'], description: 'Optional title (Mr, Ms, Dr, etc.).' },
                    { type: 'text', value: 'address_line1', title: 'Address Line 1', task: ['create_donor'] },
                    { type: 'text', value: 'address_line2', title: 'Address Line 2', task: ['create_donor'] },
                    { type: 'text', value: 'address_city', title: 'City', task: ['create_donor'] },
                    { type: 'text', value: 'address_state', title: 'State/Province', task: ['create_donor'] },
                    { type: 'text', value: 'address_zip', title: 'Postal Code', task: ['create_donor'] },
                    { type: 'text', value: 'address_country', title: 'Country Code', task: ['create_donor'], description: 'Optional 2-letter country code.' },
                    { type: 'text', value: 'donor_note', title: 'Donor Note', task: ['create_donor'], description: 'Optional note saved on the donor profile.' },
                    { type: 'text', value: 'purchase_value', title: 'Total Purchase Value', task: ['create_donor'], description: 'Optional numeric total donation value.' },
                    { type: 'text', value: 'purchase_count', title: 'Purchase Count', task: ['create_donor'], description: 'Optional total donation count.' },
                    { type: 'text', value: 'payment_ids', title: 'Payment IDs', task: ['create_donor'], description: 'Optional comma-separated GiveWP donation IDs.' },
                    { type: 'text', value: 'token', title: 'Verification Token', task: ['create_donor'] },
                    { type: 'text', value: 'verify_key', title: 'Verify Key', task: ['create_donor'] },
                    { type: 'text', value: 'verify_throttle', title: 'Verify Throttle', task: ['create_donor'] }
                ],
                update_donor: [
                    { type: 'text', value: 'donor_id', title: 'Donor ID', task: ['update_donor'], description: 'Provide donor ID or donor email.', required: false },
                    { type: 'text', value: 'email', title: 'Donor Email', task: ['update_donor'], description: 'Email to update or locate donor.' },
                    { type: 'text', value: 'name', title: 'Donor Name', task: ['update_donor'] },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['update_donor'] },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['update_donor'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['update_donor'] },
                    { type: 'text', value: 'company', title: 'Company', task: ['update_donor'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['update_donor'] },
                    { type: 'text', value: 'title_prefix', title: 'Title Prefix', task: ['update_donor'] },
                    { type: 'text', value: 'address_line1', title: 'Address Line 1', task: ['update_donor'] },
                    { type: 'text', value: 'address_line2', title: 'Address Line 2', task: ['update_donor'] },
                    { type: 'text', value: 'address_city', title: 'City', task: ['update_donor'] },
                    { type: 'text', value: 'address_state', title: 'State/Province', task: ['update_donor'] },
                    { type: 'text', value: 'address_zip', title: 'Postal Code', task: ['update_donor'] },
                    { type: 'text', value: 'address_country', title: 'Country Code', task: ['update_donor'] },
                    { type: 'text', value: 'donor_note', title: 'Donor Note', task: ['update_donor'] },
                    { type: 'text', value: 'purchase_value', title: 'Total Purchase Value', task: ['update_donor'] },
                    { type: 'text', value: 'purchase_count', title: 'Purchase Count', task: ['update_donor'] },
                    { type: 'text', value: 'payment_ids', title: 'Payment IDs', task: ['update_donor'] },
                    { type: 'text', value: 'token', title: 'Verification Token', task: ['update_donor'] },
                    { type: 'text', value: 'verify_key', title: 'Verify Key', task: ['update_donor'] },
                    { type: 'text', value: 'verify_throttle', title: 'Verify Throttle', task: ['update_donor'] }
                ],
                create_donation: [
                    { type: 'text', value: 'form_id', title: 'Form ID', task: ['create_donation'], required: true, description: 'Required GiveWP form ID.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['create_donation'], required: true, description: 'Required donation amount.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_donation'], description: 'Optional currency code; defaults to form/site currency.' },
                    { type: 'text', value: 'donor_id', title: 'Donor ID', task: ['create_donation'], description: 'Optional existing donor ID.' },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_donation'], description: 'Optional WordPress user ID linked to the donation.' },
                    { type: 'text', value: 'email', title: 'Donor Email', task: ['create_donation'], description: 'Required when donor ID is not supplied.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_donation'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_donation'] },
                    { type: 'text', value: 'title_prefix', title: 'Title Prefix', task: ['create_donation'] },
                    { type: 'text', value: 'company', title: 'Company', task: ['create_donation'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_donation'] },
                    { type: 'text', value: 'address_line1', title: 'Address Line 1', task: ['create_donation'] },
                    { type: 'text', value: 'address_line2', title: 'Address Line 2', task: ['create_donation'] },
                    { type: 'text', value: 'address_city', title: 'City', task: ['create_donation'] },
                    { type: 'text', value: 'address_state', title: 'State/Province', task: ['create_donation'] },
                    { type: 'text', value: 'address_zip', title: 'Postal Code', task: ['create_donation'] },
                    { type: 'text', value: 'address_country', title: 'Country Code', task: ['create_donation'] },
                    { type: 'text', value: 'price_id', title: 'Price ID', task: ['create_donation'], description: 'Optional GiveWP price ID/level.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_donation'], description: 'Optional status key (pending, publish, refunded, etc.).' },
                    { type: 'text', value: 'gateway', title: 'Gateway', task: ['create_donation'], description: 'Optional gateway slug (manual, stripe, etc.).' },
                    { type: 'text', value: 'mode', title: 'Mode', task: ['create_donation'], description: 'Optional live/test mode override.' },
                    { type: 'text', value: 'purchase_key', title: 'Purchase Key', task: ['create_donation'], description: 'Optional custom purchase key.' },
                    { type: 'text', value: 'donation_title', title: 'Donation Title', task: ['create_donation'], description: 'Optional donation title override.' },
                    { type: 'text', value: 'donation_note', title: 'Donation Note', task: ['create_donation'], description: 'Optional note added after creation.' },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_donation'], description: 'Optional JSON object of payment meta fields.' },
                    { type: 'text', value: 'campaign_id', title: 'Campaign ID', task: ['create_donation'], description: 'Optional campaign ID to associate.' },
                    { type: 'text', value: 'date', title: 'Donation Date', task: ['create_donation'], description: 'Optional MySQL datetime (Y-m-d H:i:s).' }
                ],
                update_donation_status: [
                    { type: 'text', value: 'donation_id', title: 'Donation ID', task: ['update_donation_status'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_donation_status'], required: true, description: 'Valid GiveWP status key.' }
                ],
                add_donation_note: [
                    { type: 'text', value: 'donation_id', title: 'Donation ID', task: ['add_donation_note'], required: true },
                    { type: 'text', value: 'note', title: 'Note', task: ['add_donation_note'], required: true, description: 'Note content stored on the donation.' }
                ]
            }
        };
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#givewp-action-template'
});
