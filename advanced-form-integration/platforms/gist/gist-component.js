/**
 * Advanced Form Integration - "gist" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("gist").
 */

Vue.component('gist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'type', title: 'Type', task: ['create_contact'], required: true, description: 'lead, user' },
                { type: 'text', value: 'full_name', title: 'Full Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'name', title: 'Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['create_contact'], required: true },
                { type: 'text', value: 'phone_number', title: 'Phone Number', task: ['create_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_contact'], required: false },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'salutation', title: 'Salutation', task: ['create_contact'], required: false },
                { type: 'text', value: 'job_title', title: 'Job Title', task: ['create_contact'], required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'website_url', title: 'Website URL', task: ['create_contact'], required: false },
                { type: 'text', value: 'mobile_phone_number', title: 'Mobile Phone Number', task: ['create_contact'], required: false },
                { type: 'text', value: 'fax_number', title: 'Fax Number', task: ['create_contact'], required: false },
                { type: 'text', value: 'preferred_language', title: 'Preferred Language', task: ['create_contact'], required: false },
                { type: 'text', value: 'industry', title: 'Industry', task: ['create_contact'], required: false },
                { type: 'text', value: 'date_of_birth', title: 'Date of Birth', task: ['create_contact'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['create_contact'], required: false },
                { type: 'text', value: 'company_size', title: 'Company Size', task: ['create_contact'], required: false },
                { type: 'text', value: 'landing_url', title: 'Landing URL', task: ['create_contact'], required: false },
                { type: 'text', value: 'street_address', title: 'Street Address', task: ['create_contact'], required: false },
                { type: 'text', value: 'city_name', title: 'City Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'region_name', title: 'Region Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'country_name', title: 'Country Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'country_code', title: 'Country Code', task: ['create_contact'], required: false },
                { type: 'text', value: 'continent_name', title: 'Continent Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'continent_code', title: 'Continent Code', task: ['create_contact'], required: false },
                { type: 'text', value: 'latitude', title: 'Latitude', task: ['create_contact'], required: false },
                { type: 'text', value: 'longitude', title: 'Longitude', task: ['create_contact'], required: false },
                { type: 'text', value: 'postal_code', title: 'Postal Code', task: ['create_contact'], required: false },
                { type: 'text', value: 'time_zone', title: 'Time Zone', task: ['create_contact'], required: false }
            ]
        };
    },
    methods: {},
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getContacts(this.fielddata.credId);
        }
    },
    template: '#gist-action-template'
});
