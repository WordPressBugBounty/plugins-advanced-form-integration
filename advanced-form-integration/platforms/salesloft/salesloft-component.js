/**
 * Advanced Form Integration - "salesloft" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("salesloft").
 */

Vue.component('salesloft', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email_address', title: 'Email Address', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'title', title: 'Job Title', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'mobile_phone', title: 'Mobile Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_to_list'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_to_list'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_to_list'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_to_list'], required: false },
                { type: 'text', value: 'linkedin_url', title: 'LinkedIn URL', task: ['add_to_list'], required: false },
                { type: 'text', value: 'twitter_handle', title: 'Twitter Handle', task: ['add_to_list'], required: false },
                { type: 'text', value: 'owner_id', title: 'Owner ID', task: ['add_to_list'], required: false },
                { type: 'text', value: 'person_stage_id', title: 'Person Stage ID', task: ['add_to_list'], required: false },
                { type: 'text', value: 'account_id', title: 'Account ID', task: ['add_to_list'], required: false },
                { type: 'text', value: 'tags', title: 'Tags', task: ['add_to_list'], required: false, description: 'Comma separated list or mapped text value' }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                customFields: ''
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#salesloft-action-template'
});
