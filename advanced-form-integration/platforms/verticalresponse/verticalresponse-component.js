/**
 * Advanced Form Integration - "verticalresponse" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("verticalresponse").
 */

Vue.component('verticalresponse', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                listId: ''
            });
        },
        getCredentials: function() {
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_verticalresponse_credentials');
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_verticalresponse_fields', {
                task: 'subscribe',
                taskGate: 'subscribe',
                requireCredId: true,
                clearOnEmpty: true,
                onStart: function () { this.loadLists(); }
            });
        },
        loadLists: function() {
            var that = this;
            
            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_verticalresponse_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                }
                that.listLoading = false;
            }).fail(function () {
                that.listLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        },
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.getCredentials();
        
        // Load fields if credId is already set
        if (this.fielddata.credId) {
            this.loadFields();
        }
    },
    template: '#verticalresponse-action-template'
});
