/**
 * Advanced Form Integration - "selzy" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("selzy").
 */

Vue.component('selzy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_selzy_list', {
                targetKey: 'list',
                loadingKey: 'listLoading',
                requireCredId: false,
                includeCredId: true
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.doubleOptin == 'undefined') {
            this.fielddata.doubleOptin = false;
        }

        if (typeof this.fielddata.doubleOptin != 'undefined') {
            if (this.fielddata.doubleOptin == "false") {
                this.fielddata.doubleOptin = false;
            }
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_selzy_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load lists if credential is selected
                if (that.fielddata.credId) {
                    that.getList();
                }
            }
        });
    },
    template: '#selzy-action-template'
});
