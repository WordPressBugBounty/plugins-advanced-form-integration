/**
 * Advanced Form Integration - "outreach" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("outreach").
 */

Vue.component('outreach', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email_address', title: 'Email Address', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'job_title', title: 'Job Title', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'work_phone', title: 'Work Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'mobile_phone', title: 'Mobile Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'website', title: 'Website URL', task: ['add_to_list'], required: false },
                { type: 'text', value: 'linkedin_url', title: 'LinkedIn URL', task: ['add_to_list'], required: false },
                { type: 'text', value: 'twitter_handle', title: 'Twitter Handle', task: ['add_to_list'], required: false },
                { type: 'text', value: 'owner_id', title: 'Owner ID', task: ['add_to_list'], required: false },
                { type: 'text', value: 'stage_id', title: 'Stage ID', task: ['add_to_list'], required: false },
                { type: 'text', value: 'account_id', title: 'Account ID', task: ['add_to_list'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_to_list'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_to_list'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_to_list'], required: false },
                { type: 'text', value: 'tags', title: 'Tags', task: ['add_to_list'], required: false, description: 'Comma separated list' }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                companyName: '',
                customFields: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#outreach-action-template'
});
