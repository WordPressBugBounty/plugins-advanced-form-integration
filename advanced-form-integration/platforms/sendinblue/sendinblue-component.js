/**
 * Advanced Form Integration - "sendinblue" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendinblue").
 */

Vue.component('sendinblue', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'sms', title: 'SMS', task: ['subscribe'], required: false, description: 'Mobile Number should be passed with proper country code. For example: "+91xxxxxxxxxx" or "0091xxxxxxxxxx"' }
            ]

        }
    },
    methods: {
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_sendinblue_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        var that = this;

        // Set default values
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        // Load credentials list
        this.credentialLoading = true;
        jQuery.post(ajaxurl, {
            action: 'adfoin_get_sendinblue_credentials_list',
            _nonce: adfoin.nonce
        }, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select credential for existing integrations
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    // Check for legacy credential first
                    var legacyCred = that.credentialsList.find(function(cred) {
                        return cred.title && cred.title.includes('Legacy');
                    });
                    
                    if (legacyCred) {
                        that.fielddata.credId = legacyCred.id;
                    } else {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                
                // Load lists if credential is selected
                if (that.fielddata.credId) {
                    that.getList();
                }
            }
            that.credentialLoading = false;
        }).fail(function () {
            that.credentialLoading = false;
        });
    },
    template: '#sendinblue-action-template'
});
