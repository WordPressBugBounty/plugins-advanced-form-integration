/**
 * Advanced Form Integration - "bigin" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("bigin").
 */

Vue.component('bigin', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            userLoading: false,
            moduleLoading: false,
            fieldsLoading: false,
            credLoading: false,
            credentialsList: [],
            fields: []
        }
    },
    methods: {
        getUsers: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.users = {};
                return;
            }

            this.userLoading = true;

            var userRequestData = {
                'action': 'adfoin_get_bigin_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, userRequestData, function (response) {
                if (response.success) {
                    // Use Vue.set to ensure reactivity
                    that.$set(that.fielddata, 'users', response.data);
                } else {
                    that.$set(that.fielddata, 'users', {});
                    console.log('Error fetching users:', response.data);
                }
                that.userLoading = false;
            });

            // Also get modules when account changes
            this.getModules();
        },
        getModules: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.modules = {};
                return;
            }

            this.moduleLoading = true;

            var moduleRequestData = {
                'action': 'adfoin_get_bigin_modules',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, moduleRequestData, function (response) {
                if (response.success) {
                    // Use Vue.set to ensure reactivity
                    that.$set(that.fielddata, 'modules', response.data);
                } else {
                    that.$set(that.fielddata, 'modules', {});
                    console.log('Error fetching modules:', response.data);
                    // Surface the reason (e.g. wrong Data Center / INVALID_TOKEN)
                    // so a "Connected but no data" setup isn't a silent dead end.
                    if (response.data) {
                        window.alert('Bigin: ' + (typeof response.data === 'string' ? response.data : (response.data.message || 'Could not load modules.')));
                    }
                }
                that.moduleLoading = false;
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_bigin_module_fields', {
                task: 'subscribe',
                loadingKey: 'moduleLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: { module: this.fielddata.moduleId, task: this.action.task }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.userId == 'undefined') {
            this.fielddata.userId = '';
        }

        if (typeof this.fielddata.moduleId == 'undefined') {
            this.fielddata.moduleId = '';
        }

        if (typeof this.fielddata.users == 'undefined') {
            this.fielddata.users = {};
        }

        if (typeof this.fielddata.modules == 'undefined') {
            this.fielddata.modules = {};
        }

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        // Load credentials
        this.credLoading = true;

        var credRequestData = {
            'action': 'adfoin_get_bigin_credentials',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credRequestData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    // Check for legacy credential first
                    var legacyCred = that.credentialsList.find(function(cred) {
                        return cred.id && cred.id.indexOf('legacy_') === 0;
                    });
                    
                    if (legacyCred) {
                        that.fielddata.credId = legacyCred.id;
                    } else {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                
                // Load users and modules if credential is selected
                if (that.fielddata.credId) {
                    that.getUsers();
                }
            }
            that.credLoading = false;
        });

        if (this.fielddata.moduleId) {
            this.getFields();
        }
    },
    watch: {},
    template: '#bigin-action-template'
});
