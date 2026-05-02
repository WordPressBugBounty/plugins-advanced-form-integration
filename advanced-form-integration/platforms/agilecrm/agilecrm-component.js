/**
 * Advanced Form Integration - "agilecrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("agilecrm").
 */

Vue.component('agilecrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            pipelineLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'zip', title: 'Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_agilecrm_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        fetchPipelines: function () {
            var that = this;
            if (!this.fielddata.credId) return;
            
            this.pipelineLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_agilecrm_pipelines',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.pipelineLoading = false;
                if (response.success && response.data) {
                    // Add deal fields dynamically
                    response.data.forEach(function(field) {
                        var exists = that.fields.some(function(f) { return f.value === field.key; });
                        if (!exists) {
                            that.fields.push({
                                type: 'text',
                                value: field.key,
                                title: field.value,
                                task: ['add_contact'],
                                required: false,
                                description: field.description || ''
                            });
                        }
                    });
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        
        this.fetchCredentialsList();
        
        if (this.fielddata.credId) {
            this.fetchPipelines();
        }
    },
    template: '#agilecrm-action-template'
});
