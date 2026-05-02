/**
 * Advanced Form Integration - "sendpulse" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendpulse").
 */

Vue.component('sendpulse', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_sendpulse_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        }
    },
    created: function () {

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
        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }
        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        // Load credentials list
        this.credentialLoading = true;
        jQuery.post(ajaxurl, {
            action: 'adfoin_get_sendpulse_credentials_list',
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
    template: '#sendpulse-action-template'
});
