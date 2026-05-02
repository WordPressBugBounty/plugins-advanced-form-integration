/**
 * Advanced Form Integration - "sendlane" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("sendlane").
 */

Vue.component('sendlane', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_sendlane_lists', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        var that = this;

        // Set default values
        if (typeof this.fielddata.list === 'undefined') {
            this.$set(this.fielddata, 'list', {});
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        // Load credentials list
        this.credentialLoading = true;
        jQuery.post(ajaxurl, {
            action: 'adfoin_get_sendlane_credentials_list',
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
    template: '#sendlane-action-template'
});
