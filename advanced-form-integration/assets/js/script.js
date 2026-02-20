/**
 * Advanced Form Integration - Action Components
 * Contains all action provider Vue components (email marketing, CRM, etc.)
 * Loaded only on new/edit integration pages
 */


Vue.component('mailchimp', {
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
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_mailchimp_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getList();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailchimp_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.getList();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.doubleoptin == 'undefined') {
            this.fielddata.doubleoptin = false;
        }

        if (typeof this.fielddata.doubleoptin != 'undefined') {
            if (this.fielddata.doubleoptin == "false") {
                this.fielddata.doubleoptin = false;
            }
        }

        this.getData();
    },
    template: '#mailchimp-action-template'
});

Vue.component('dynamics365marketing', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'firstname', title: 'First Name', task: ['create_marketing_contact'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['create_marketing_contact'], required: false },
                { type: 'text', value: 'emailaddress1', title: 'Email Address', task: ['create_marketing_contact'], required: true }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#dynamics365marketing-action-template'
});

Vue.component('salesforcemc', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['upsert_subscriber'], required: true },
                { type: 'text', value: 'subscriberKey', title: 'Subscriber Key', task: ['upsert_subscriber'], required: false, description: 'Defaults to the email address when left empty.' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['upsert_subscriber'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['upsert_subscriber'], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['upsert_subscriber'], required: false, description: 'Active, Unsubscribed, Held, etc. Defaults to Active.' }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#salesforcemc-action-template'
});

Vue.component('marketo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_lead'], required: true }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#marketo-action-template'
});

Vue.component('maropost', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false }
            ]
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.lists === 'undefined') {
            this.$set(this.fielddata, 'lists', {});
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.listLoading = true;

        jQuery.post(ajaxurl, {
            'action': 'adfoin_get_maropost_lists',
            '_nonce': adfoin.nonce
        }, function (response) {
            if (response.success) {
                that.fielddata.lists = response.data;
            } else {
                that.fielddata.lists = {};
            }
            that.listLoading = false;
        }).fail(function () {
            that.listLoading = false;
        });
    },
    template: '#maropost-action-template'
});

Vue.component('sapmarketingcloud', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'origin', title: 'Contact Origin', task: ['create_contact'], required: false, description: 'Defaults to WEB_FORM when left empty.' },
                { type: 'text', value: 'externalId', title: 'External Contact ID', task: ['create_contact'], required: false, description: 'Provide a unique identifier per origin; email is used when blank.' },
                { type: 'text', value: 'country', title: 'Country', task: ['create_contact'], required: false },
                { type: 'text', value: 'emailPermission', title: 'Email Opt-In', task: ['create_contact'], required: false, description: 'Map to true/false to control HasEmailOptIn.' }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.origin === 'undefined' || !this.fielddata.origin) {
            this.$set(this.fielddata, 'origin', 'WEB_FORM');
        }

        if (typeof this.fielddata.emailPermission === 'undefined') {
            this.$set(this.fielddata, 'emailPermission', 'true');
        }

        if (typeof this.fielddata.externalId === 'undefined') {
            this.$set(this.fielddata, 'externalId', '');
        }
    },
    template: '#sapmarketingcloud-action-template'
});

Vue.component('sapsalescloud', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'leadName', title: 'Lead Name', task: ['create_lead'], required: false, description: 'Defaults to contact or company name when empty.' },
                { type: 'text', value: 'company', title: 'Company', task: ['create_lead'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_lead'], required: false },
                { type: 'text', value: 'email', title: 'Email Address', task: ['create_lead'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['create_lead'], required: false },
                { type: 'text', value: 'originCode', title: 'Origin Code', task: ['create_lead'], required: false, description: 'Defaults to 001 (Web).' }
            ]
        }
    },
    mounted: function () {
        if (typeof this.fielddata.originCode === 'undefined' || !this.fielddata.originCode) {
            this.$set(this.fielddata, 'originCode', '001');
        }
    },
    template: '#sapsalescloud-action-template'
});

Vue.component('sendgrid', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.list === 'undefined') {
            this.$set(this.fielddata, 'list', {});
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_sendgrid_lists',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            if (response.success) {
                that.fielddata.list = response.data;
            } else {
                that.fielddata.list = {};
            }

            that.listLoading = false;
        }).fail(function () {
            that.listLoading = false;
        });
    },
    template: '#sendgrid-action-template'
});

Vue.component('mailersend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.list === 'undefined') {
            this.$set(this.fielddata, 'list', {});
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_mailersend_lists',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            if (response.success) {
                that.fielddata.list = response.data;
            } else {
                that.fielddata.list = {};
            }

            that.listLoading = false;
        }).fail(function () {
            that.listLoading = false;
        });
    },
    template: '#mailersend-action-template'
});

Vue.component('mailgun', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.list === 'undefined') {
            this.$set(this.fielddata, 'list', {});
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_mailgun_lists',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            if (response.success) {
                that.fielddata.list = response.data;
            } else {
                that.fielddata.list = {};
            }

            that.listLoading = false;
        }).fail(function () {
            that.listLoading = false;
        });
    },
    template: '#mailgun-action-template'
});

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
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_sendlane_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.list = {};
                that.listLoading = false;
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

Vue.component('ontraport', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false }
            ]
        }
    },
    template: '#ontraport-action-template'
});

Vue.component('dotdigital', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false }
            ]
        }
    },
    template: '#dotdigital-action-template'
});

Vue.component('sharpspring', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_lead'], required: false }
            ]
        }
    },
    template: '#sharpspring-action-template'
});

Vue.component('braze', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_user'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_user'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_user'], required: false }
            ]
        }
    },
    template: '#braze-action-template'
});

Vue.component('sendfox', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_sendfox_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
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

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_sendfox_credentials_list',
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
    template: '#sendfox-action-template'
});

Vue.component('apptivo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            entitiesLoading: false,
            fieldsLoading: false,
            entities: [],
            entityFields: []
        }
    },
    methods: {
        getData: function () {
            this.getEntities();
            if (this.fielddata.entityName) {
                this.getEntityFields();
            }
        },
        getEntities: function () {
            var that = this;
            var credId = this.fielddata.credId;

            if (!credId) {
                that.entities = [];
                return;
            }

            this.entitiesLoading = true;

            var requestData = {
                'action': 'adfoin_get_apptivo_entities',
                '_nonce': adfoin.nonce,
                'credId': credId
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                that.entitiesLoading = false;
                if (response.success) {
                    that.entities = response.data;
                } else {
                    that.entities = [];
                }
            }).fail(function () {
                that.entitiesLoading = false;
                that.entities = [];
            });
        },
        getEntityFields: function () {
            var that = this;
            var credId = this.fielddata.credId;
            var entityName = this.fielddata.entityName;

            if (!credId || !entityName) {
                that.entityFields = [];
                return;
            }

            this.fieldsLoading = true;

            var requestData = {
                'action': 'adfoin_get_apptivo_fields',
                '_nonce': adfoin.nonce,
                'credId': credId,
                'entityName': entityName
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                that.fieldsLoading = false;
                if (response.success) {
                    that.entityFields = response.data;
                } else {
                    that.entityFields = [];
                }
            }).fail(function () {
                that.fieldsLoading = false;
                that.entityFields = [];
            });
        }
    },
    watch: {
        'fielddata.entityName': function (newVal, oldVal) {
            if (newVal !== oldVal && newVal) {
                this.getEntityFields();
            } else if (!newVal) {
                this.entityFields = [];
            }
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.entityName === 'undefined') {
            this.$set(this.fielddata, 'entityName', '');
        }

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#apptivo-action-template'
});

Vue.component('sendx', {
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
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['subscribe'], required: false, description: 'YYYY-MM-DD' },
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // Set default values
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        // Load credentials list
        this.credentialLoading = true;
        jQuery.post(ajaxurl, {
            action: 'adfoin_get_sendx_credentials_list',
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
            }
            that.credentialLoading = false;
        }).fail(function () {
            that.credentialLoading = false;
        });
    },
    template: '#sendx-action-template'
});

Vue.component('woodpecker', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // Default credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }
    },
    template: '#woodpecker-action-template'
});

Vue.component('mautic', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'title', title: 'Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile Number', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['add_contact'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['add_contact'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_contact'], required: false },
                { type: 'text', value: 'position', title: 'Position', task: ['add_contact'], required: false },
                { type: 'text', value: 'address1', title: 'Address Line 1', task: ['add_contact'], required: false },
                { type: 'text', value: 'address2', title: 'Address Line 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'zipcode', title: 'ZIP', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['add_contact'], required: false },
                { type: 'text', value: 'instagram', title: 'Instagram', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['add_contact'], required: false },
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mautic_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#mautic-action-template'
});

Vue.component('smartrmail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.$forceUpdate();
                return;
            }
            this.listLoading = true;
            this.fielddata.lists = {};

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_smartrmail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    console.error("Error fetching SmartrMail lists:", response.data);
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching SmartrMail lists:", status, error);
                that.fielddata.lists = {};
                that.listLoading = false;
                that.$forceUpdate();
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.lists === 'undefined') {
            this.$set(this.fielddata, 'lists', {});
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.$set(this.fielddata, 'listId', '');
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.listId = '';
                if (newVal) {
                    this.getLists();
                } else {
                    this.fielddata.lists = {};
                    this.$forceUpdate();
                }
            }
        }
    },
    template: '#smartrmail-action-template'
});

Vue.component('livestorm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            eventLoading: false,
            sessionLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_people'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_people'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_people'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getEvents();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_livestorm_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getEvents: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.eventLoading = true;

            var eventRequestData = {
                'action': 'adfoin_get_livestorm_events',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, eventRequestData, function (response) {
                that.fielddata.events = response.data;
                that.eventLoading = false;
            });
        },
        getSessions: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.sessionLoading = true;

            var sessionRequestData = {
                'action': 'adfoin_get_livestorm_sessions',
                'credId': this.fielddata.credId,
                'eventId': this.fielddata.eventId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, sessionRequestData, function (response) {
                that.fielddata.sessions = response.data;
                that.sessionLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.eventId == 'undefined') {
            this.fielddata.eventId = '';
        }

        if (typeof this.fielddata.sessionId == 'undefined') {
            this.fielddata.sessionId = '';
        }

        this.getData();

        if (this.fielddata.eventId) {
            this.getSessions();
        }
    },
    template: '#livestorm-action-template'
});

Vue.component('demio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            eventLoading: false,
            sessionLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['reg_people'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['reg_people'], required: true },
                // {type: 'text', value: 'last_name', title: 'Last Name', task: ['reg_people'], required: false},
                // {type: 'text', value: 'company', title: 'Company', task: ['reg_people'], required: false},
                // {type: 'text', value: 'website', title: 'Website', task: ['reg_people'], required: false},
                // {type: 'text', value: 'phone_number', title: 'Phone Number', task: ['reg_people'], required: false},
                // {type: 'text', value: 'gdpr', title: 'GDPR', task: ['reg_people'], required: false},
                // {type: 'text', value: 'refUrl', title: 'Event Registration page URL', task: ['reg_people'], required: false},

            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getEvents();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credRequestData = {
                'action': 'adfoin_get_demio_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getEvents: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.eventLoading = true;

            var eventRequestData = {
                'action': 'adfoin_get_demio_events',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, eventRequestData, function (response) {
                that.fielddata.events = response.data;
                that.eventLoading = false;
            });
        },
        getSessions: function () {
            this.sessionLoading = true;
            var that = this;

            var sessionRequestData = {
                'action': 'adfoin_get_demio_sessions',
                'credId': this.fielddata.credId,
                'eventId': this.fielddata.eventId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, sessionRequestData, function (response) {
                that.fielddata.sessions = response.data;
                that.sessionLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.eventId == 'undefined') {
            this.fielddata.eventId = '';
        }

        if (typeof this.fielddata.sessionId == 'undefined') {
            this.fielddata.sessionId = '';
        }

        this.getData();

        if (this.fielddata.eventId) {
            this.getSessions();
        }

    },

    template: '#demio-action-template'
});

Vue.component('aweber', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            accountLoading: false,
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_aweber_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = 'legacy_123456';
                    }
                    if (that.fielddata.credId) {
                        that.getAccounts();
                    }
                }
                that.credLoading = false;
            });
        },
        getAccounts: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.accounts = {};
                return;
            }

            this.accountLoading = true;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_aweber_accounts',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            }, function (response) {
                that.fielddata.accounts = response.data;
                that.accountLoading = false;
            });
        },
        getLists: function () {
            var that = this;
            
            if (!this.fielddata.credId || !this.fielddata.accountId) {
                this.fielddata.lists = {};
                return;
            }

            this.listLoading = true;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_aweber_lists',
                '_nonce': adfoin.nonce,
                'accountId': this.fielddata.accountId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            }, function (response) {
                that.fielddata.lists = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.accounts == 'undefined') {
            this.fielddata.accounts = {};
        }
        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }
        if (typeof this.fielddata.lists == 'undefined') {
            this.fielddata.lists = {};
        }
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }
        
        this.getCredentials();
        
        if (this.fielddata.credId && this.fielddata.accountId) {
            this.getLists();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getAccounts();
            }
        },
        'fielddata.accountId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getLists();
            }
        }
    },
    template: '#aweber-action-template'
});

Vue.component('activecampaign', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            automationLoading: false,
            pipelineLoading: false,
            accountLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email [Contact]', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name [Contact]', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name [Contact]', task: ['subscribe'], required: false },
                { type: 'text', value: 'phoneNumber', title: 'Phone [Contact]', task: ['subscribe'], required: false },
                { type: 'text', value: 'note', title: 'Note', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getLists();
            this.getAutomations();
            this.getAccounts();
            this.getDealFields();
        },
        getCredentials: function() {
            var that = this;
            this.credentialLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_credentials',
                '_nonce': adfoin.nonce
            };
            
            jQuery.post(ajaxurl, requestData, function(response) {
                if(response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getLists: function() {
            var that = this;
            this.listLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getAutomations: function() {
            var that = this;
            this.automationLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_automations',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                that.fielddata.automations = response.data;
                that.automationLoading = false;
            });
        },
        getAccounts: function() {
            var that = this;
            this.accountLoading = true;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_accounts',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                that.fielddata.accounts = response.data;
                that.accountLoading = false;
            });
        },
        getDealFields: function() {
            var that = this;
            
            var requestData = {
                'action': 'adfoin_get_activecampaign_deal_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            
            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.automationId == 'undefined') {
            this.fielddata.automationId = '';
        }

        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        if (typeof this.fielddata.phoneNumber == 'undefined') {
            this.fielddata.phoneNumber = '';
        }

        if (typeof this.fielddata.update == 'undefined') {
            this.fielddata.update = false;
        }

        if (typeof this.fielddata.update != 'undefined') {
            if (this.fielddata.update == "false") {
                this.fielddata.update = false;
            }
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getData();
    },
    template: '#activecampaign-action-template'
});

Vue.component('keap', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'title', title: 'Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'suffix', title: 'Suffix', task: ['add_contact'], required: false },
                { type: 'text', value: 'contactType', title: 'Contact Type', task: ['add_contact'], description: 'Lead, Customer, Other', required: false },
                { type: 'text', value: 'company', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'optin', title: 'Opt-In', task: ['add_contact'], description: 'Has this person opted-in to receiving marketing communications from you? Insert "true" to send them email through Keap.', required: false },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'email2', title: 'Email 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'email3', title: 'Email 3', task: ['add_contact'], required: false },
                { type: 'text', value: 'mobilePhone', title: 'Mobile Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingStreet1', title: 'Billing Street1', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingStreet2', title: 'Billing Street2', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingCity', title: 'Billing City', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingState', title: 'Billing State', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingZip', title: 'Billing Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'billingCountryCode', title: 'Billing Country Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingStreet1', title: 'Shipping Street1', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingStreet2', title: 'Shipping Street2', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingCity', title: 'Shipping City', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingState', title: 'Shipping State', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingZip', title: 'Shipping Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'shippingCountryCode', title: 'Shipping Country Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['add_contact'], required: false },
                { type: 'text', value: 'anniversary', title: 'Anniversary', task: ['add_contact'], required: false },
                { type: 'text', value: 'spouseName', title: 'Spouse Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['add_contact'], required: false },
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        // var pipelineRequestData = {
        //     'action': 'adfoin_get_keap_pipelines',
        //     '_nonce': adfoin.nonce
        // };

        // jQuery.post( ajaxurl, pipelineRequestData, function( response ) {

        //     if( response.success ) {
        //         if( response.data ) {
        //             response.data.map(function(single) {
        //                 that.fields.push( { type: 'text', value: single.key, title: single.value, task: ['add_contact'], required: false, description: single.description } );
        //             });
        //         }
        //     }
        // });
    },
    template: '#keap-action-template'
});

Vue.component('pushover', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['push'], required: false },
                { type: 'text', value: 'message', title: 'Message', task: ['push'], required: false },
                { type: 'text', value: 'device', title: 'Device', task: ['push'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_pushover_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.title == 'undefined') {
            this.fielddata.title = '';
        }

        if (typeof this.fielddata.message == 'undefined') {
            this.fielddata.message = '';
        }

        if (typeof this.fielddata.device == 'undefined') {
            this.fielddata.device = '';
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#pushover-action-template'
});

Vue.component('twilio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'to', title: 'To', task: ['subscribe'], required: true },
                { type: 'textarea', value: 'body', title: 'Body', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getPhoneNumbers: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_twilio_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
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

        if (typeof this.fielddata.from == 'undefined') {
            this.fielddata.from = '';
        }

        if (typeof this.fielddata.to == 'undefined') {
            this.fielddata.to = '';
        }

        if (typeof this.fielddata.body == 'undefined') {
            this.fielddata.body = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_twilio_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load phone numbers if credential is selected
                if (that.fielddata.credId) {
                    that.getPhoneNumbers();
                }
            }
        });
    },
    template: '#twilio-action-template'
});

Vue.component('elasticemail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialData = {
                'action': 'adfoin_get_elasticemail_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_elasticemail_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#elasticemail-action-template'
});

Vue.component('pabbly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['subscribe'], required: false },
                { type: 'text', value: 'age', title: 'Age', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_pabbly_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        getList: function () {
            var that = this;
            if (!this.fielddata.credId) return;
            
            this.listLoading = true;
            var listRequestData = {
                'action': 'adfoin_get_pabbly_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.listLoading = false;
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        // Fetch credentials list
        this.fetchCredentialsList();

        // Load lists if credId is set
        if (this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#pabbly-action-template'
});

Vue.component('phplist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#phplist-action-template'
});

Vue.component('robly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'fname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lname', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_robly_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        getList: function () {
            var that = this;
            if (!this.fielddata.credId) return;
            
            this.listLoading = true;
            var listRequestData = {
                'action': 'adfoin_get_robly_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.listLoading = false;
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        // Fetch credentials list
        this.fetchCredentialsList();

        // Load lists if credId is set
        if (this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#robly-action-template'
});

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
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_selzy_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
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

Vue.component('mailerlite', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailerlite_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getLists();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailerlite_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getLists();
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailerlite-action-template'
});

Vue.component('mailerlite2', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active | unsubscribed | unconfirmed | bounced | junk' },
                { type: 'text', value: 'ip_address', title: 'IP Address', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailerlite2_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getData();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getData: function () {
            this.getLists();
            this.getCustomFields();
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailerlite2_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getCustomFields: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fields = [
                    { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active | unsubscribed | unconfirmed | bounced | junk' },
                    { type: 'text', value: 'ip_address', title: 'IP Address', task: ['subscribe'], required: false },
                ];
                return;
            }

            this.fieldsLoading = true;
            this.fields = [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active | unsubscribed | unconfirmed | bounced | junk' },
                { type: 'text', value: 'ip_address', title: 'IP Address', task: ['subscribe'], required: false },
            ];

            var customFieldData = {
                'action': 'adfoin_get_mailerlite2_custom_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, customFieldData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getData();
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailerlite2-action-template'
});

Vue.component('emailoctopus', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialData = {
                'action': 'adfoin_get_emailoctopus_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_emailoctopus_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        if (typeof this.fielddata.doubleoptin != 'undefined') {
            if (this.fielddata.doubleoptin == "false") {
                this.fielddata.doubleoptin = false;
            }
        }

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#emailoctopus-action-template'
});

Vue.component('jumplead', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false }
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }
    },
    template: '#jumplead-action-template'
});

Vue.component('klaviyo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'organization', title: 'Organization', task: ['subscribe'], required: false },
                { type: 'text', value: 'phoneNumber', title: 'Phone Number', task: ['subscribe'], required: false, description: 'Should be passed with proper country code. For example: "+91xxxxxxxxxx"' },
                { type: 'text', value: 'address1', title: 'Address 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'address2', title: 'Address 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'region', title: 'Region', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'latitude', title: 'Latitude', task: ['subscribe'], required: false },
                { type: 'text', value: 'longitude', title: 'Longitude', task: ['subscribe'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['subscribe'], required: false, description: 'e.g. Asia/Dhaka' },
                { type: 'text', value: 'ip', title: 'IP Address', task: ['subscribe'], required: false },
                { type: 'text', value: 'externalId', title: 'External ID', task: ['subscribe'], required: false },
                { type: 'text', value: 'source', title: 'Source', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getLists: function () {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_klaviyo_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_klaviyo_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                        that.getLists();
                    }
                } else {
                    that.credentialsList = [];
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        this.fetchCredentialsList();
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#klaviyo-action-template'
});

Vue.component('mailrelay', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'sms_phone', title: 'SMS Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['subscribe'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'locale', title: 'Locale', task: ['subscribe'], required: false, description: 'e.g. en' },
                { type: 'text', value: 'time_zone', title: 'Time Zone', task: ['subscribe'], required: false, description: 'e.g. Africa/Abidjan' },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active, inactive' },
            ]
        };
    },
    methods: {
        getGroups: function (credId = null) {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_mailrelay_groups',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getGroups(this.fielddata.credId);
        }
    },
    template: '#mailrelay-action-template'
});

Vue.component('mailtrain', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['add_to_list'], required: false, description: 'Example: Europe/Tallinn' }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.lists === 'undefined') {
                this.$set(this.fielddata, 'lists', {});
            }
            if (typeof this.fielddata.forceSubscribe === 'undefined') {
                this.$set(this.fielddata, 'forceSubscribe', '');
            }
            if (typeof this.fielddata.requireConfirmation === 'undefined') {
                this.$set(this.fielddata, 'requireConfirmation', '');
            }
            if (this.fielddata.forceSubscribe === false) {
                this.fielddata.forceSubscribe = '';
            }
            if (this.fielddata.forceSubscribe === true) {
                this.fielddata.forceSubscribe = 'true';
            }
            if (this.fielddata.requireConfirmation === false) {
                this.fielddata.requireConfirmation = '';
            }
            if (this.fielddata.requireConfirmation === true) {
                this.fielddata.requireConfirmation = 'true';
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'lists', {});
                this.fielddata.listId = '';
                return;
            }

            this.listLoading = true;

            var requestData = {
                'action': 'adfoin_get_mailtrain_lists',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.$set(that.fielddata, 'lists', response.data);
                }
                that.listLoading = false;
            }).fail(function () {
                that.listLoading = false;
            });
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mailtrain-action-template'
});

Vue.component('mailshake', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.campaignId === 'undefined') {
                this.$set(this.fielddata, 'campaignId', '');
            }
            if (typeof this.fielddata.leadCatcherId === 'undefined') {
                this.$set(this.fielddata, 'leadCatcherId', '');
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#mailshake-action-template'
});

Vue.component('reply', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'title', title: 'Job Title', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_to_list'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_to_list'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_to_list'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_to_list'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone_numbers', title: 'Optional Phone', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }

            if (typeof this.fielddata.sequenceId === 'undefined') {
                this.$set(this.fielddata, 'sequenceId', '');
            }

            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#reply-action-template'
});

Vue.component('expertsender', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.mode === 'undefined') {
                this.$set(this.fielddata, 'mode', 'AddAndUpdate');
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#expertsender-action-template'
});

Vue.component('unisender', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.doubleOptin === 'undefined') {
                this.$set(this.fielddata, 'doubleOptin', '0');
            }
            if (typeof this.fielddata.overwrite === 'undefined') {
                this.$set(this.fielddata, 'overwrite', '1');
            }
            if (typeof this.fielddata.tags === 'undefined') {
                this.$set(this.fielddata, 'tags', '');
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#unisender-action-template'
});

Vue.component('ongage', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_to_list'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_to_list'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_to_list'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_to_list'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_to_list'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.tags === 'undefined') {
                this.$set(this.fielddata, 'tags', '');
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#ongage-action-template'
});

Vue.component('sinch', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'msisdn', title: 'Single Number', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.groupId === 'undefined') {
                this.$set(this.fielddata, 'groupId', '');
            }
            if (typeof this.fielddata.addNumbers === 'undefined') {
                this.$set(this.fielddata, 'addNumbers', '');
            }
            if (typeof this.fielddata.removeNumbers === 'undefined') {
                this.$set(this.fielddata, 'removeNumbers', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#sinch-action-template'
});

Vue.component('webengage', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'uid', title: 'User ID (we_uid)', task: ['sync_user'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['sync_user'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['sync_user'], required: false },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['sync_user'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['sync_user'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['sync_user'], required: false },
                { type: 'text', value: 'birth_date', title: 'Birth Date (YYYY-MM-DD)', task: ['sync_user'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.tags === 'undefined') {
                this.$set(this.fielddata, 'tags', '');
            }
            if (typeof this.fielddata.customAttributes === 'undefined') {
                this.$set(this.fielddata, 'customAttributes', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#webengage-action-template'
});

Vue.component('blueshift', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'uid', title: 'Customer ID', task: ['sync_user'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['sync_user'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['sync_user'], required: false },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['sync_user'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['sync_user'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['sync_user'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['sync_user'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }

            if (typeof this.fielddata.tags === 'undefined') {
                this.$set(this.fielddata, 'tags', '');
            }

            if (typeof this.fielddata.customAttributes === 'undefined') {
                this.$set(this.fielddata, 'customAttributes', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#blueshift-action-template'
});

Vue.component('cordial', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['sync_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['sync_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['sync_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['sync_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['sync_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['sync_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['sync_contact'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listIds === 'undefined') {
                this.$set(this.fielddata, 'listIds', '');
            }
            if (typeof this.fielddata.customAttributes === 'undefined') {
                this.$set(this.fielddata, 'customAttributes', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#cordial-action-template'
});

Vue.component('listmonk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_to_list'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['add_to_list'], required: false }
            ]
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.lists === 'undefined') {
                this.$set(this.fielddata, 'lists', {});
            }
            if (typeof this.fielddata.status === 'undefined') {
                this.$set(this.fielddata, 'status', 'enabled');
            }
            if (typeof this.fielddata.preconfirm === 'undefined') {
                this.$set(this.fielddata, 'preconfirm', '');
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'lists', {});
                this.fielddata.listId = '';
                return;
            }

            this.listLoading = true;

            var requestData = {
                'action': 'adfoin_get_listmonk_lists',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.$set(that.fielddata, 'lists', response.data);
                }
                that.listLoading = false;
            }).fail(function () {
                that.listLoading = false;
            });
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#listmonk-action-template'
});

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

Vue.component('rapidmail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['subscribe'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdate', title: 'Birthdate', task: ['subscribe'], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: 'active, new' },
                { type: 'text', value: 'extra1', title: 'Extra 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra2', title: 'Extra 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra3', title: 'Extra 3', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra4', title: 'Extra 4', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra5', title: 'Extra 5', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra6', title: 'Extra 6', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra7', title: 'Extra 7', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra8', title: 'Extra 8', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra9', title: 'Extra 9', task: ['subscribe'], required: false },
                { type: 'text', value: 'extra10', title: 'Extra 10', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_rapidmail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#rapidmail-action-template'
});

Vue.component('emailchef', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.lists = [];
                return;
            }
            this.groupLoading = true;
            this.fielddata.lists = [];

            var groupRequestData = {
                'action': 'adfoin_get_emailchef_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }

                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId && (!this.fielddata.lists || this.fielddata.lists.length === 0)) {
            this.getLists();
        }
    },
    template: '#emailchef-action-template'
});

Vue.component('doppler', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'FIRSTNAME', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'LASTNAME', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'GENDER', title: 'Gender', task: ['subscribe'], required: false },
                { type: 'text', value: 'BIRTHDAY', title: 'Birthday', task: ['subscribe'], required: false },
                { type: 'text', value: 'CONSENT', title: 'Consent', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.groupLoading = true;
            this.fielddata.lists = [];

            var groupRequestData = {
                'action': 'adfoin_get_doppler_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId && (!this.fielddata.lists || this.fielddata.lists.length === 0)) {
            this.getLists();
        }
    },
    template: '#doppler-action-template'
});

Vue.component('emailit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: false },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        getAudiences: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.audiences = [];
                return;
            }
            this.groupLoading = true;
            this.fielddata.audiences = [];
            var requestData = {
                'action': 'adfoin_get_emailit_audiences',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };
            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.audiences = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.audienceId == 'undefined') {
            this.fielddata.audienceId = '';
        }
        if (!this.fielddata.audiences) {
            this.fielddata.audiences = [];
        }
        if (this.fielddata.credId) {
            this.getAudiences();
        }
    },
    template: '#emailit-action-template'
});

Vue.component('resend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false },

            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_resend_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#resend-action-template'
});

Vue.component('sender', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getGroups: function (credId = null) {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_sender_groups',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            });
        },
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_sender_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getData: function () {
            this.getGroups();
            this.getFields();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#sender-action-template'
});

Vue.component('loops', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getGroups: function (credId = null) {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_loops_groups',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            });
        },
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_loops_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getData: function () {
            this.getGroups();
            this.getFields();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#loops-action-template'
});

Vue.component('systemeio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_systemeio_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getData: function () {
            this.getFields();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#systemeio-action-template'
});

Vue.component('cleverreach', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: [],
            credentialsList: []
        };
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.groupId === 'undefined') {
                this.$set(this.fielddata, 'groupId', '');
            }
        },
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_cleverreach_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'subscribe') {
                this.fields = [];
                return;
            }

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_cleverreach_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['subscribe'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });

            // Also load groups when credentials change
            this.getGroups();
        },
        getGroups: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_cleverreach_groups',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            }).fail(function () {
                that.groupLoading = false;
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
    template: '#cleverreach-action-template'
});

Vue.component('mailup', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            groupLoading: false,
            fields: [],
            credentialsList: []
        };
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
            if (typeof this.fielddata.groupId === 'undefined') {
                this.$set(this.fielddata, 'groupId', '');
            }
        },
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_mailup_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'subscribe') {
                this.fields = [];
                return;
            }

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailup_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['subscribe'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });

            // Also load lists when credentials change
            this.getLists();
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailup_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            }).fail(function () {
                that.listLoading = false;
            });
        },
        getGroups: function () {
            var that = this;

            if (!this.fielddata.credId || !this.fielddata.listId) {
                return;
            }

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_mailup_groups',
                'credId': this.fielddata.credId,
                'listId': this.fielddata.listId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            }).fail(function () {
                that.groupLoading = false;
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
        },
        'fielddata.listId': function(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getGroups();
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
    template: '#mailup-action-template'
});

Vue.component('campaigner', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_campaigner_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists(this.fielddata.credId);
    },
    template: '#campaigner-action-template'
});

Vue.component('acelle', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'EMAIL', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'FIRST_NAME', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'LAST_NAME', title: 'Last Name', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_acelle_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists(this.fielddata.credId);
        }
    },
    template: '#acelle-action-template'
});

Vue.component('iterable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email Address', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }

            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }

            if (typeof this.fielddata.lists === 'undefined') {
                this.$set(this.fielddata, 'lists', {});
            }
        },
        getLists: function () {
            const that = this;

            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                return;
            }

            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_iterable_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    that.fielddata.lists = {};
                    that.fielddata.listId = '';
                }
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.lists = {};
                that.fielddata.listId = '';
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.getLists();
        }
    },
    template: '#iterable-action-template'
});

Vue.component('adobecampaign', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_adobecampaign_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_profile'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#adobecampaign-action-template'
});

Vue.component('postmark', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_postmark_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['send_email'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#postmark-action-template'
});

Vue.component('mailguntransactional', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailguntransactional_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['send_email'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#mailguntransactional-action-template'
});

Vue.component('mandrill', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mandrill_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['send_email'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#mandrill-action-template'
});

Vue.component('crmone', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_crmone_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_lead'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#crmone-action-template'
});

Vue.component('localiq', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_localiq_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['submit_lead'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#localiq-action-template'
});

Vue.component('kintone', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_kintone_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        var type = field.key === 'recordJson' ? 'textarea' : 'text';
                        return {
                            type: type,
                            value: field.key,
                            title: field.value,
                            task: ['create_record'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#kintone-action-template'
});

Vue.component('netsuite', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_netsuite_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        var type = field.key === 'recordJson' ? 'textarea' : 'text';
                        return {
                            type: type,
                            value: field.key,
                            title: field.value,
                            task: ['create_record'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#netsuite-action-template'
});

Vue.component('knack', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_knack_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        var type = field.key === 'recordJson' ? 'textarea' : 'text';
                        return {
                            type: type,
                            value: field.key,
                            title: field.value,
                            task: ['create_record'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#knack-action-template'
});

Vue.component('pipelinecrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_pipelinecrm_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_person'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#pipelinecrm-action-template'
});

Vue.component('planhat', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_planhat_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_company'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#planhat-action-template'
});

Vue.component('servicem8', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_servicem8_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_client'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#servicem8-action-template'
});

Vue.component('jobber', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_jobber_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_job'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#jobber-action-template'
});

Vue.component('servicetitan', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_servicetitan_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_job'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#servicetitan-action-template'
});

Vue.component('fieldnation', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_fieldnation_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_work_order'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#fieldnation-action-template'
});

Vue.component('attentive', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'create_subscriber') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_attentive_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_subscriber'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#attentive-action-template'
});

Vue.component('airmeet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.airmeetId === 'undefined') {
                this.$set(this.fielddata, 'airmeetId', '');
            }
            if (typeof this.fielddata.ticketClassId === 'undefined') {
                this.$set(this.fielddata, 'ticketClassId', '');
            }
            if (typeof this.fielddata.sendEmail === 'undefined') {
                this.$set(this.fielddata, 'sendEmail', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'register_attendee') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_airmeet_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['register_attendee'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#airmeet-action-template'
});

Vue.component('bigmarker', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.channelSlug === 'undefined') {
                this.$set(this.fielddata, 'channelSlug', '');
            }
            if (typeof this.fielddata.conferenceSlug === 'undefined') {
                this.$set(this.fielddata, 'conferenceSlug', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'register_attendee') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_bigmarker_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['register_attendee'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#bigmarker-action-template'
});

Vue.component('on24', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.eventId === 'undefined') {
                this.$set(this.fielddata, 'eventId', '');
            }
            if (typeof this.fielddata.sourceCode === 'undefined') {
                this.$set(this.fielddata, 'sourceCode', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'register_attendee') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_on24_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['register_attendee'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#on24-action-template'
});

Vue.component('zoomwebinar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.webinarId === 'undefined') {
                this.$set(this.fielddata, 'webinarId', '');
            }
            if (typeof this.fielddata.autoApprove === 'undefined') {
                this.$set(this.fielddata, 'autoApprove', 'auto');
            }
            if (typeof this.fielddata.language === 'undefined') {
                this.$set(this.fielddata, 'language', '');
            }
        },
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_zoomwebinar_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'register_attendee') {
                this.fields = [];
                return;
            }

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_zoomwebinar_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['register_attendee'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
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
    template: '#zoomwebinar-action-template'
});

Vue.component('slicktext', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'create_contact') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_slicktext_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#slicktext-action-template'
});

Vue.component('adobeconnect', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'create_user') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_adobeconnect_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_user'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#adobeconnect-action-template'
});

Vue.component('justcall', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'create_contact') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_justcall_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#justcall-action-template'
});

Vue.component('eztexting', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'create_contact') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_eztexting_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#eztexting-action-template'
});

Vue.component('gotowebinar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_gotowebinar_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'create_registrant') {
                this.fields = [];
                return;
            }

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_gotowebinar_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_registrant'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
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
    template: '#gotowebinar-action-template'
});

Vue.component('zoho_meeting', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'register_contact') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_zoho_meeting_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['register_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#zoho_meeting-action-template'
});

Vue.component('superoffice', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_superoffice_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#superoffice-action-template'
});

Vue.component('scoro', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_scoro_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        var type = field.key === 'contactJson' ? 'textarea' : 'text';
                        return {
                            type: type,
                            value: field.key,
                            title: field.value,
                            task: ['create_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#scoro-action-template'
});

Vue.component('softr', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_softr_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        var type = field.key === 'recordJson' ? 'textarea' : 'text';
                        return {
                            type: type,
                            value: field.key,
                            title: field.value,
                            task: ['create_record'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#softr-action-template'
});

Vue.component('successai', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_successai_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['add_prospect'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#successai-action-template'
});

Vue.component('salesmate', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
        },
        loadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_salesmate_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['create_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        }
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#salesmate-action-template'
});

Vue.component('saleshandy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            sequenceLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            this.getSequences();
            this.getFields();
        },
        getSequences: function () {
            const that = this;
            this.sequenceLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_saleshandy_sequences',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.sequenceLoading = false;
                if (response.success) {
                    that.fielddata.sequences = response.data;
                }
            });
        },
        getFields: function () {
            const that = this;
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_saleshandy_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {

                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_prospect'], required: false, description: single.description });
                        });
                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.sequenceId) this.fielddata.sequenceId = '';
        if (!this.fielddata.sequences) this.fielddata.sequences = {};

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#saleshandy-action-template'
});

Vue.component('smartlead', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            campaignLoading: false,
            fields: [
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_lead'], required: true, description: '' },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_lead'], required: true, description: '' },
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead'], required: true, description: '' },
                { type: 'text', value: 'phone_number', title: 'Phone Number', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'website', title: 'Website', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'location', title: 'Location', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'linkedin_profile', title: 'LinkedIn Profile', task: ['add_lead'], required: false, description: '' },
                { type: 'text', value: 'company_url', title: 'Company URL', task: ['add_lead'], required: false, description: '' }
            ]
        };
    },
    methods: {
        getCampaigns: function () {
            const that = this;
            this.campaignLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_smartlead_campaigns',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.campaignLoading = false;
                if (response.success) {
                    that.fielddata.campaigns = response.data;
                }
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.campaignId) this.fielddata.campaignId = '';
        if (!this.fielddata.campaigns) this.fielddata.campaigns = {};

        if (this.fielddata.credId) {
            this.getCampaigns();
        }
    },
    template: '#smartlead-action-template'
});

Vue.component('snovio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'fullName', title: 'Full Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phones', title: 'Phones (comma-separated)', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'locality', title: 'Locality (City, State)', task: ['add_contact'], required: false },
                { type: 'text', value: 'socialLinks[linkedIn]', title: 'LinkedIn Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'social[twitter]', title: 'Twitter Profile URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'position', title: 'Job Position', task: ['add_contact'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'companySite', title: 'Company Website', task: ['add_contact'], required: false },
            ]
        };
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_snovio_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = [];
                }
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists(this.fielddata.credId);
        }
    },
    template: '#snovio-action-template'
});

Vue.component('emma', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_contact'], required: false }
            ]
        };
    },
    methods: {
        getGroups: function () {
            const that = this;
            this.groupLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_emma_groups',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.groupLoading = false;
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.groupId) this.fielddata.groupId = '';
        if (!this.fielddata.groups) this.fielddata.groups = {};

        if (this.fielddata.credId) {
            this.getGroups();
        }
    },
    template: '#emma-action-template'
});

Vue.component('icontact', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'prefix', title: 'Prefix', task: ['subscribe'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'suffix', title: 'Suffix', task: ['subscribe'], required: false },
                { type: 'text', value: 'street', title: 'Street', task: ['subscribe'], required: false },
                { type: 'text', value: 'street2', title: 'Street 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'postalCode', title: 'Postal Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['subscribe'], required: false },
                { type: 'text', value: 'business', title: 'Business', task: ['subscribe'], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['subscribe'], required: false, description: '' }
            ]
        };
    },
    methods: {
        getLists: function () {
            const that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_icontact_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.listLoading = false;
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.listId) this.fielddata.listId = '';
        if (!this.fielddata.lists) this.fielddata.lists = {};

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#icontact-action-template'
});

Vue.component('laposta', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'ip', title: 'IP Address', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'firstname', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_subscriber'], required: false }
            ]
        };
    },
    methods: {
        getLists: function () {
            const that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_laposta_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.listLoading = false;
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.listId) this.fielddata.listId = '';
        if (!this.fielddata.lists) this.fielddata.lists = {};

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#laposta-action-template'
});

Vue.component('audienceful', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_person'], required: true },
                { type: 'text', value: 'tags', title: 'Tags (comma-separated)', task: ['add_person'], required: false },
                { type: 'text', value: 'notes', title: 'Notes', task: ['add_person'], required: false },
            ]
        };
    },
    methods: {
        getFields: function (task = null) {
            var that = this;
            this.fieldLoading = true;

            var requestData = {
                'action': 'adfoin_get_audienceful_fields',
                'credId': this.fielddata.credId,
                'task': task,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success && response.data) {
                    response.data.map(function (single) {
                        that.fields.push({
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['add_person'],
                            required: false,
                            description: single.description
                        });
                    });
                    that.fieldLoading = false;
                }
            });
        }
    },

    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';

        if (this.fielddata.credId) {
            this.getFields(this.action.task);
        }
    },
    template: '#audienceful-action-template'
});


Vue.component('acumbamail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'surname', title: 'Last Name', task: ['add_subscriber'], required: false }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acumbamail_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.listLoading = false;
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.listId) this.fielddata.listId = '';
        if (!this.fielddata.lists) this.fielddata.lists = {};

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#acumbamail-action-template'
});

Vue.component('acuity', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            appointmentTypeLoading: false,
            calendarLoading: false,
            appointmentTypes: {},
            calendars: {},
            credentialsList: [],
            fields: [
                { type: 'text', value: 'datetime', title: 'Appointment Date & Time', task: ['create_appointment'], required: true, description: 'ISO 8601 e.g. 2024-05-12T14:00:00-0500' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_appointment'], required: true },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_appointment'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['create_appointment'], required: true },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_appointment'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_appointment'], required: false, description: 'America/New_York etc.' },
                { type: 'text', value: 'certificate', title: 'Certificate / Coupon Code', task: ['create_appointment'], required: false },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_appointment'], required: false },
                { type: 'text', value: 'price', title: 'Price Override', task: ['create_appointment'], required: false },
                { type: 'textarea', value: 'fieldDefinitions', title: 'Form Fields (JSON)', task: ['create_appointment'], required: false, description: '[{\"id\":1,\"value\":\"Answer\"}]' },
                { type: 'text', value: 'addonIds', title: 'Addon IDs (comma separated)', task: ['create_appointment'], required: false },
                { type: 'text', value: 'labelId', title: 'Label ID', task: ['create_appointment'], required: false }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.appointmentTypeId === 'undefined') {
            this.$set(this.fielddata, 'appointmentTypeId', '');
        }

        if (typeof this.fielddata.calendarId === 'undefined') {
            this.$set(this.fielddata, 'calendarId', '');
        }

        if (typeof this.fielddata.adminMode === 'undefined') {
            this.$set(this.fielddata, 'adminMode', 'client');
        }

        if (typeof this.fielddata.noEmail === 'undefined') {
            this.$set(this.fielddata, 'noEmail', false);
        }
    },
    mounted: function () {
        this.fetchCredentialsList();
        this.fetchAppointmentTypes();
        this.fetchCalendars();
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acuity_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
            });
        },
        fetchAppointmentTypes: function () {
            var that = this;
            if (!this.fielddata.credId) {
                return;
            }
            this.appointmentTypeLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acuity_appointment_types',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.appointmentTypes = response.data;
                } else {
                    that.appointmentTypes = {};
                }
                that.appointmentTypeLoading = false;
            }).fail(function () {
                that.appointmentTypes = {};
                that.appointmentTypeLoading = false;
            });
        },
        fetchCalendars: function () {
            var that = this;
            if (!this.fielddata.credId) {
                return;
            }
            this.calendarLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_acuity_calendars',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.calendars = response.data;
                } else {
                    that.calendars = {};
                }
                that.calendarLoading = false;
            }).fail(function () {
                that.calendars = {};
                that.calendarLoading = false;
            });
        }
    },
    template: '#acuity-action-template'
});

Vue.component('addcal', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_event'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_event'], required: false },
                { type: 'text', value: 'location', title: 'Location', task: ['create_event'], required: false },
                { type: 'text', value: 'date_start', title: 'Start Date & Time', task: ['create_event'], required: true, description: 'ISO 8601, e.g. 2024-03-25T14:00:00-05:00' },
                { type: 'text', value: 'date_end', title: 'End Date & Time', task: ['create_event'], required: true, description: 'ISO 8601 format' },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_event'], required: false, description: 'America/New_York etc.' },
                { type: 'text', value: 'is_all_day', title: 'All Day?', task: ['create_event'], required: false, description: 'true/false' },
                { type: 'text', value: 'recurrence_rule', title: 'Recurrence Rule', task: ['create_event'], required: false, description: 'RRULE string' },
                { type: 'text', value: 'has_rsvp', title: 'Enable RSVP', task: ['create_event'], required: false, description: 'true/false' },
                { type: 'text', value: 'rsvp_limit', title: 'RSVP Limit', task: ['create_event'], required: false },
                { type: 'text', value: 'busy_type', title: 'Busy Type', task: ['create_event'], required: false, description: 'busy or free' },
                { type: 'text', value: 'reminder_before', title: 'Reminder Minutes', task: ['create_event'], required: false },
                { type: 'text', value: 'short_link', title: 'Custom Short Link', task: ['create_event'], required: false },
                { type: 'text', value: 'team_uid', title: 'Team UID', task: ['create_event'], required: false },
                { type: 'text', value: 'calendar_uid', title: 'Calendar UID', task: ['create_event'], required: false },
                { type: 'text', value: 'calendar_name', title: 'Calendar Name', task: ['create_event'], required: false, description: 'Auto-create/use by name' },
                { type: 'text', value: 'image_url', title: 'Image URL', task: ['create_event'], required: false },
                { type: 'text', value: 'location_url', title: 'Location URL', task: ['create_event'], required: false },
                { type: 'text', value: 'internal_name', title: 'Internal Name', task: ['create_event'], required: false },
                { type: 'text', value: 'is_draft', title: 'Save as Draft', task: ['create_event'], required: false, description: 'true/false' }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.withHtml === 'undefined') {
            this.$set(this.fielddata, 'withHtml', false);
        }
    },
    template: '#addcal-action-template'
});

Vue.component('enormail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_subscriber'], required: false, description: 'M or F' },
                { type: 'text', value: 'lastname', title: 'Last Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'postal', title: 'Postal / Region', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'telephone', title: 'Telephone', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['add_subscriber'], required: false, description: 'YYYY-MM-DD' }
            ]
        }
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_enormail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.lists = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#enormail-action-template'
});

Vue.component('sarbacane', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_subscriber'], required: false }
            ]
        }
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_sarbacane_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.lists = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#sarbacane-action-template'
});

Vue.component('mailcoach', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_mailcoach_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mailcoach-action-template'
});

Vue.component('cakemail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: [
                { type: 'text', value: 'doubleOptIn', title: 'Double Opt-In', task: ['add_subscriber'], required: false, description: 'true or false' },
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.$forceUpdate();
                return;
            }
            this.listLoading = true;
            this.fielddata.lists = {};
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_cakemail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    console.error("Error fetching Cakemail lists:", response.data);
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching Cakemail lists:", status, error);
                that.fielddata.lists = {};
                that.listLoading = false;
                that.$forceUpdate();
            });
        },
        getCustomFields: function () {
            var that = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_cakemail_custom_fields',
                'credId': this.fielddata.credId,
                'listId': this.fielddata.listId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(function (field) {
                        that.fields.push({
                            type: 'text',
                            value: field.key,
                            title: field.label || field.key,
                            task: ['add_subscriber'],
                            required: false,
                            description: field.description || ''
                        });
                    });

                    that.fields.push(
                        {
                            type: 'text',
                            value: 'tags',
                            title: 'Tags',
                            task: ['add_subscriber'],
                            required: false,
                            description: 'Comma separated tags'
                        },
                        {
                            type: 'text',
                            value: 'interests',
                            title: 'Interests',
                            task: ['add_subscriber'],
                            required: false,
                            description: 'Comma separated interests'
                        }
                    );
                    that.fieldsLoading = false;
                }
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching Cakemail custom fields:", status, error);
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.lists === 'undefined') {
            this.$set(this.fielddata, 'lists', {});
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.$set(this.fielddata, 'listId', '');
        }
        if (this.fielddata.credId) {
            this.getLists();
        }
        if (this.fielddata.credId && this.fielddata.listId) {
            this.getCustomFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.listId = '';
                if (newVal) {
                    this.getLists();
                } else {
                    this.fielddata.lists = {};
                    this.$forceUpdate();
                }
            }
        },
        'fielddata.listId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.getCustomFields();
            }
        }
    },
    template: '#cakemail-action-template'
});

Vue.component('campayn', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name (fname)', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name (lname)', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.$forceUpdate();
                return;
            }
            this.listLoading = true;
            this.fielddata.lists = {};

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_campayn_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    console.error("Error fetching Campayn lists:", response.data);
                    that.fielddata.lists = {};
                }
                that.listLoading = false;
                that.$forceUpdate();
            }).fail(function (xhr, status, error) {
                console.error("AJAX error fetching Campayn lists:", status, error);
                that.fielddata.lists = {};
                that.listLoading = false;
                that.$forceUpdate();
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.lists === 'undefined') {
            this.$set(this.fielddata, 'lists', {});
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.$set(this.fielddata, 'listId', '');
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#campayn-action-template'
});

Vue.component('courrielleur', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_courrielleur_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.lists === 'undefined') {
            this.fielddata.lists = {};
        }
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#courrielleur-action-template'
});

Vue.component('mailmodo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['subscribe'], required: false },
                { type: 'text', value: 'age', title: 'Age', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'address1', title: 'Address Line 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'address2', title: 'Address Line 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'postal_code', title: 'Postal Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'designation', title: 'Designation', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'industry', title: 'Industry', task: ['subscribe'], required: false },
                { type: 'text', value: 'description', title: 'Description', task: ['subscribe'], required: false },
                { type: 'text', value: 'anniversary_date', title: 'Anniversary Date', task: ['subscribe'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_mailmodo_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.$set(this.fielddata, 'listId', '');
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mailmodo-action-template'
});

Vue.component('lacrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            userLoading: false,
            fields: [
                { type: 'text', value: 'company__Company Name', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Email', title: 'Company Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Phone', title: 'Company Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address__Street', title: 'Company Street', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_City', title: 'Company City', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_State', title: 'Company State', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_Zip', title: 'Company Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_Country', title: 'Company Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Background Info', title: 'Company Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Website', title: 'Company Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'Name', title: 'Contact Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'Email', title: 'Contact Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'Phone', title: 'Contact Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'Job Title', title: 'Job Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'address__Street', title: 'Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_City', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_State', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_Zip', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_Country', title: 'Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'Background Info', title: 'Background Info', task: ['add_contact'], required: false },
                { type: 'text', value: 'Website', title: 'Website', task: ['add_contact'], required: false },
            ]
        }
    },
    methods: {
        getUsers: function () {
            var that = this;
            this.userLoading = true;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_lacrm_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.users = response.data;
                }
                that.userLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.userId === 'undefined') {
            this.fielddata.userId = '';
        }

        if (this.fielddata.credId) {
            this.getUsers();
        }
    },
    template: '#lacrm-action-template'
});

Vue.component('keila', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'external_id', title: 'External ID', task: ['add_contact'], required: false }
            ]
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#keila-action-template'
});

Vue.component('flodesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            segmentsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getSegments: function () {
            var that = this;

            this.segmentsLoading = true;

            var segmentRequestData = {
                'action': 'adfoin_get_flodesk_segments',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, segmentRequestData, function (response) {
                that.fielddata.segments = response.data;
                that.segmentsLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.segmentId == 'undefined') {
            this.fielddata.segmentId = '';
        }

        if (typeof this.fielddata.doptin == 'undefined') {
            this.fielddata.doptin = false;
        }

        if (typeof this.fielddata.doptin != 'undefined') {
            if (this.fielddata.doptin == "false") {
                this.fielddata.doptin = false;
            }
        }

        if (this.fielddata.segmentId) {
            this.getSegments();
        }

    },
    template: '#flodesk-action-template'
});


Vue.component('mumara', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false },
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_mumara_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mumara-action-template'
});

Vue.component('academylms', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            courseLoading: false,
            lessonLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Student Email', task: ['enroll', 'unenroll'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['enroll'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['enroll'], required: false },
                { type: 'text', value: 'username', title: 'Username', task: ['enroll'], required: true },
                { type: 'text', value: 'password', title: 'Password', task: ['enroll'], required: true },
            ]

        }
    },
    methods: {
        getCourses: function (credId = null) {
            var that = this;

            this.courseLoading = true;

            var courseRequestData = {
                'action': 'adfoin_get_academylms_courses',
                // 'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, courseRequestData, function (response) {
                that.fielddata.courses = response.data;
                that.courseLoading = false;
            });
        },
        getLessons: function (courseId = null) {
            var that = this;

            this.lessonLoading = true;

            var lessonRequestData = {
                'action': 'adfoin_get_academylms_lessons',
                'courseId': courseId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, lessonRequestData, function (response) {
                that.fielddata.lesson = response.data;
                that.lessonLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.courseId == 'undefined') {
            this.fielddata.courseId = '';
        }

        if (typeof this.fielddata.lessonId == 'undefined') {
            this.fielddata.lessonId = '';
        }

        this.getCourses();

        if (this.fielddata.courseId != '') {
            this.getLessons(this.fielddata.courseId);
        }
    },
    template: '#academylms-action-template'
});

Vue.component('fluentcrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldLoading: false,
            fields: [
            ]

        }
    },
    methods: {
        getLists: function (credId = null) {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_fluentcrm_lists',
                // 'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.lists = response.data;
                that.listLoading = false;
            });
        },
        getFields: function (task = null) {
            var that = this;

            this.fieldLoading = true;

            var tagRequestData = {
                'action': 'adfoin_get_fluentcrm_fields',
                'task': task,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, tagRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['addContact', 'removeContact', 'addTag', 'removeTag'], required: false, description: single.description });
                        });

                        that.fieldLoading = false;
                    }
                }
            });
        }
    },
    watch: {
        'action.task': function (val) {
            this.getFields(this.action.task);
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists();

        if (this.action.task) {
            this.getFields(this.action.task);
        }
    },
    template: '#fluentcrm-action-template'
});

Vue.component('copernica', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            dbLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getDatabases: function () {
            var that = this;
            this.dbLoading = true;

            var dbRequestData = {
                'action': 'adfoin_get_copernica_databases',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, dbRequestData, function (response) {
                if (response.success && response.data) {
                    that.fielddata.databases = response.data;
                }
                that.dbLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            this.fieldLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_copernica_fields',
                'credId': this.fielddata.credId,
                'databaseId': this.fielddata.databaseId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success && response.data) {
                    that.fields = [];
                    response.data.map(function (single) {
                        that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_subscriber'], required: false, description: single.description });
                    });
                }
                that.fieldLoading = false;
            });
        }
    },
    watch: {
        'fielddata.databaseId': function (val) {
            if (val) {
                this.getFields();
            }
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.databaseId === 'undefined') {
            this.fielddata.databaseId = '';
        }

        if (!this.fielddata.databases) {
            this.fielddata.databases = {};
        }

        if (this.fielddata.credId) {
            this.getDatabases();
        }

        if (this.fielddata.databaseId) {
            this.getFields();
        }
    },
    template: '#copernica-action-template'
});

Vue.component('bombbomb', {
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
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
        },
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_bombbomb_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'add_contact') {
                this.fields = [];
                return;
            }

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_bombbomb_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['add_contact'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });

            // Also load lists when credentials change
            this.getLists();
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var requestData = {
                action: 'adfoin_get_bombbomb_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
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
    template: '#bombbomb-action-template'
});

Vue.component('apollo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            userLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getFields();
            this.getUsers();
        },
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_apollo_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_contact'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getUsers: function () {
            var that = this;
            this.userLoading = true;
            var userRequestData = {
                'action': 'adfoin_get_apollo_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, userRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.users = response.data;
                        that.userLoading = false;
                    }
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.userId === 'undefined') {
            this.fielddata.userId = '';
        }
        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#apollo-action-template'
});

Vue.component('suitedash', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getFields();
        },
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_suitedash_fields',
                'credId': this.fielddata.credId,
                'task': this.action.task,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fields = [];
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: [that.action.task], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#suitedash-action-template'
});

Vue.component('customerio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'userId', title: 'User ID', task: ['add_people'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['add_people'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['add_people'], required: false },
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () { },
    template: '#customerio-action-template'
});

Vue.component('kartra', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName2', title: 'Last Name 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'phoneCountryCode', title: 'Phone Country Code', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'ip', title: 'IP', task: ['subscribe'], required: false },
                { type: 'text', value: 'address', title: 'Address 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'facebook', title: 'Facebook', task: ['subscribe'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['subscribe'], required: false },
                { type: 'text', value: 'linkedin', title: 'LinkedIn', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_kartra_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            that.fielddata.list = response.data;
            that.listLoading = false;
        });
    },
    template: '#kartra-action-template'
});

Vue.component('moosend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobile', title: 'Phone', task: ['subscribe'], required: false, 'description': 'Phone number should be passed with proper country code. For example: "+91xxxxxxxxxx"' }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_moosend_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = 'legacy_123456';
                    }
                    if (that.fielddata.credId) {
                        that.getList();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_moosend_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        this.getData();
    },
    template: '#moosend-action-template'
});

Vue.component('mailercloud', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: []

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailercloud_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getData();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getData: function () {
            this.getLists();
            this.getFields();
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailercloud_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getFields: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            var fieldRequestData = {
                'action': 'adfoin_get_mailercloud_contact_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {

                that.fields = [];
                if (response.success && response.data) {
                    response.data.map(function (single) {
                        that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                    });
                }
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.fields = [];
            this.getData();
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailercloud-action-template'
});

Vue.component('encharge', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: []

        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function() {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_encharge_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getFields: function() {
            var that = this;
            
            if (!this.fielddata.credId) {
                return;
            }

            this.fieldLoading = true;
            this.fields = [];

            var fieldRequestData = {
                'action': 'adfoin_get_encharge_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });
                    }
                }
                that.fieldLoading = false;
            });
        }
    },
    created: function () { },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getData();
    },
    template: '#encharge-action-template'
});

Vue.component('sendy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_sendy_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential or first available for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        // Look for legacy credential first
                        var legacyCred = that.credentialsList.find(function(cred) {
                            return cred.id === 'legacy_123456' || cred.title.includes('Legacy');
                        });
                        that.fielddata.credId = legacyCred ? legacyCred.id : that.credentialsList[0].id;
                    }
                }
                that.credentialLoading = false;
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#sendy-action-template'
});

Vue.component('convertkit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            formsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getLists();
            this.getForms();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credRequestData = {
                'action': 'adfoin_get_convertkit_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_convertkit_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getForms: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.formsLoading = true;

            var formsRequestData = {
                'action': 'adfoin_get_convertkit_forms',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, formsRequestData, function (response) {
                that.fielddata.forms = response.data;
                that.formsLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.formId == 'undefined') {
            this.fielddata.formId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        this.getData();
    },
    template: '#convertkit-action-template'
});

Vue.component('kit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            formsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
            this.getForms();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_kit_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_kit_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getForms: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.formsLoading = true;

            var formsRequestData = {
                'action': 'adfoin_get_kit_forms',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, formsRequestData, function (response) {
                that.fielddata.forms = response.data;
                that.formsLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.formId == 'undefined') {
            this.fielddata.formId = '';
        }

        this.getData();
    },
    template: '#kit-action-template'
});

Vue.component('beehiiv', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'utm_source', title: 'UTM Source', task: ['subscribe'], required: false },
                { type: 'text', value: 'utm_campaign', title: 'UTM Campaign', task: ['subscribe'], required: false },
                { type: 'text', value: 'utm_medium', title: 'UTM Medium', task: ['subscribe'], required: false },
                { type: 'text', value: 'referring_site', title: 'Referring Site', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credData = {
                'action': 'adfoin_get_beehiiv_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_beehiiv_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();

        // Load list for existing integrations (backward compatibility)
        if (this.fielddata.listId && !this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#beehiiv-action-template'
});

Vue.component('wealthbox', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            ownerLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'prefix', title: 'Prefix', task: ['add_contact'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'suffix', title: 'Suffix', task: ['add_contact'], required: false },
                { type: 'text', value: 'nickname', title: 'Nick Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'twitterName', title: 'Twitter Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'linkedinUrl', title: 'LinkedIn URL', task: ['add_contact'], required: false },
                { type: 'text', value: 'contactSource', title: 'Contact Source', task: ['add_contact'], required: false, description: 'Referral | Conference | Direct Mail | Cold Call | Other' },
                { type: 'text', value: 'contactType', title: 'Contact Type', task: ['add_contact'], required: false, description: 'Client | Past Client | Prospect | Vendor | Organization' },
                { type: 'text', value: 'status', title: 'Status', task: ['add_contact'], required: false, description: 'Active | Inactive' },
                { type: 'text', value: 'maritalStatus', title: 'Marital Status', task: ['add_contact'], required: false, description: 'Married | Single | Divorced | Widowed | Life Partner | Seperated | Unknown' },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['add_contact',], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'backgroundInfo', title: 'Background Information', task: ['add_contact'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_contact'], required: false, description: 'Female | Male | Non-binary | Unknown' },
                { type: 'text', value: 'householdTitle', title: 'Household Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'householdName', title: 'Household Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'personalEmail', title: 'Pesonal Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'workEmail', title: 'Work Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'mobile', title: 'Mobile', task: ['add_contact'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'birthDate', title: 'Birth Date', task: ['add_contact'], required: false },
                { type: 'text', value: 'addressLine1', title: 'Address line 1', task: ['add_contact'], required: false },
                { type: 'text', value: 'addressLine2', title: 'Address line 2', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'zipCode', title: 'ZIP Code', task: ['add_contact'], required: false },
                { type: 'text', value: 'kind', title: 'Address Type', task: ['add_contact'], required: false, description: 'e.g. Work | Home' },
                { type: 'text', value: 'webAddress', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'webType', title: 'Web Address Type', task: ['add_contact'], required: false }
            ]
        }
    },
    methods: {
        getOwnerList: function () {
            var that = this;
            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_wealthbox_owner_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        }
    },
    created: function () { },
    mounted: function () {
        var that = this;

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_wealthbox_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load owner list if credential is selected
                if (that.fielddata.credId) {
                    that.getOwnerList();
                }
            }
        });
    },
    template: '#wealthbox-action-template'
});

Vue.component('onehash', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead', 'add_customer', 'add_contact'], required: true },
                { type: 'text', value: 'fullName', title: 'Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'customerName', title: 'Customer Name', task: ['add_customer'], required: false },
                { type: 'text', value: 'customerType', title: 'Customer Type', task: ['add_customer'], required: false },
                { type: 'text', value: 'customerGroup', title: 'Customer Group', task: ['add_customer'], required: false },
                { type: 'text', value: 'territory', title: 'Territory', task: ['add_customer'], required: false },
                { type: 'text', value: 'leadName', title: 'Lead Name', task: ['add_customer'], required: false },
                { type: 'text', value: 'opportunityName', title: 'Opportunity Name', task: ['add_customer'], required: false },
                { type: 'text', value: 'company', title: 'Company Name', task: ['add_lead',], required: false },
                { type: 'text', value: 'status', title: 'Status', task: ['add_lead'], required: false, description: 'Active | Inactive' },
                { type: 'text', value: 'salutation', title: 'Salutation', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'designation', title: 'Designation', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'source', title: 'Source', task: ['add_lead'], required: false },
                { type: 'text', value: 'campaignName', title: 'Campaign Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'contactBy', title: 'Contact By', task: ['add_lead'], required: false },
                { type: 'text', value: 'contactDate', title: 'Contact Date', task: ['add_lead'], required: false },
                { type: 'text', value: 'endsOn', title: 'Ends On', task: ['add_lead'], required: false },
                { type: 'text', value: 'addressType', title: 'Address Type', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'addressTitle', title: 'Address Title', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'addressLine1', title: 'Address line 1', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'addressLine2', title: 'Address line 2', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'county', title: 'County', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'pincode', title: 'Postal Code', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'phonNO', title: 'Phone', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'mobileNo', title: 'Mobile No.', task: ['add_lead', 'add_customer', 'add_contact'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['add_lead', 'add_contact'], required: false },
                { type: 'text', value: 'doctype', title: 'Doctype', task: ['add_lead', 'add_customer'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_onehash_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential or first available for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        // Look for legacy credential first
                        var legacyCred = that.credentialsList.find(function(cred) {
                            return cred.id === 'legacy_123456' || cred.title.includes('Legacy');
                        });
                        that.fielddata.credId = legacyCred ? legacyCred.id : that.credentialsList[0].id;
                    }
                }
                that.credentialLoading = false;
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#onehash-action-template'
});

Vue.component('nimble', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_nimble_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = 'legacy_123456';
                    }
                }
                that.credentialLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        this.getData();
    },
    template: '#nimble-action-template'
});

Vue.component('companyhub', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credRequestData = {
                'action': 'adfoin_get_companyhub_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        this.getData();
    },
    template: '#companyhub-action-template'
});

Vue.component('autopilot', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'twitter', title: 'Twitter', task: ['subscribe'], required: false },
                { type: 'text', value: 'salutation', title: 'Salutation', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
                { type: 'text', value: 'numberOfEmployees', title: 'Number Of Employees', task: ['subscribe'], required: false },
                { type: 'text', value: 'title', title: 'Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'industry', title: 'Industry', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobilePhone', title: 'MobilePhone', task: ['subscribe'], required: false },
                { type: 'text', value: 'fax', title: 'Fax', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingStreet', title: 'MailingStreet', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingCity', title: 'MailingCity', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingState', title: 'MailingState', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingPostalCode', title: 'MailingPostalCode', task: ['subscribe'], required: false },
                { type: 'text', value: 'mailingCountry', title: 'MailingCountry', task: ['subscribe'], required: false },
                { type: 'text', value: 'leadSource', title: 'LeadSource', task: ['subscribe'], required: false },
                { type: 'text', value: 'linkedIn', title: 'LinkedIn', task: ['subscribe'], required: false }

            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        this.listLoading = true;

        var listRequestData = {
            'action': 'adfoin_get_autopilot_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, listRequestData, function (response) {
            that.fielddata.list = response.data;
            that.listLoading = false;
        });
    },
    template: '#autopilot-action-template'
});

Vue.component('benchmark', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'middleName', title: 'Middle Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credData = {
                'action': 'adfoin_get_benchmark_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_benchmark_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();

        // Load list for existing integrations (backward compatibility)
        if (this.fielddata.listId && !this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#benchmark-action-template'
});

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
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_sendpulse_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.list = {};
                that.listLoading = false;
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

Vue.component('getresponse', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_getresponse_credentials',
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getList: function () {
            if (!this.fielddata.credId) return;

            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_getresponse_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();
    },
    template: '#getresponse-action-template'
});

Vue.component('mailpoet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_mailpoet_subscriber_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldLoading = false;
                    }
                }
            });
        },
        getLists: function () {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailpoet_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists();
        this.getFields();
    },
    template: '#mailpoet-action-template'
});

Vue.component('slicewp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'amount', title: 'Commission Amount', task: ['add_commission'], required: true },
                { type: 'text', value: 'reference', title: 'Reference', task: ['add_commission'], required: true },
                { type: 'date', value: 'commission_date', title: 'Commission Date', task: ['add_commission'], required: true },
                { type: 'select', value: 'status', title: 'Commission Status', task: ['add_commission'], required: true, options: ['unpaid', 'paid', 'rejected'] },
                { type: 'select', value: 'type', title: 'Commission Type', task: ['add_commission'], required: true, options: ['sale', 'lead', 'click'] },
            ]
        };
    },
    methods: {
        checkPluginStatus: function () {
            jQuery.post(ajaxurl, {
                action: 'adfoin_slicewp_check_plugin',
                _nonce: adfoin.nonce
            }, function (response) {
                if (!response.success) {
                    console.error('SliceWP plugin is not active or authorization failed.');
                }
            });
        }
    },
    mounted: function () {
        this.checkPluginStatus();
    },
    template: '#slicewp-action-template'
});

Vue.component('telegram', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            chatLoading: false,
            chatList: []
        };
    },
    methods: {
        fetchChats: function () {
            var that = this;

            this.chatLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_telegram_updates',
                bot_api_key: this.fielddata.bot_api_key,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.chatList = response.data;
                } else {
                    console.error('Failed to fetch chat list:', response.data);
                }
                that.chatLoading = false;
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.chat_id) {
            this.fetchChats();
        }
    },
    template: '#telegram-action-template'
});

Vue.component('whatsapp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            loading: false,
            fields: []
        };
    },
    methods: {
        getTemplates: function () {
            var that = this;
            this.loading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_whatsapp_templates',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fields = response.data.map(template => ({
                        type: 'text',
                        value: template.name,
                        title: template.name,
                        required: false
                    }));
                }
                that.loading = false;
            });
        }
    },
    mounted: function () {
        if (this.action.task === 'send_message') {
            this.getTemplates();
        }
    },
    template: '#whatsapp-action-template'
});

Vue.component('civicrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_civicrm_contact_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({
                                type: 'text',
                                value: single.key,
                                title: single.value,
                                task: ['add_contact'],
                                required: false,
                                description: single.description
                            });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getGroups: function () {
            var that = this;

            this.groupLoading = true;

            var groupRequestData = {
                'action': 'adfoin_get_civicrm_groups',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groupList = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        this.getGroups();
        this.getFields();
    },
    template: '#civicrm-action-template'
});

Vue.component('groundhogg', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            tagLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_groundhogg_contact_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    response.data.map(function (single) {
                        that.fields.push({
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['add_contact'],
                            required: false,
                            description: single.description
                        });
                    });

                    that.fieldsLoading = false;
                }
            });
        },
        getTags: function () {
            var that = this;

            this.tagLoading = true;

            var tagRequestData = {
                'action': 'adfoin_get_groundhogg_tags',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, tagRequestData, function (response) {
                if (response.success) {
                    that.fielddata.tagList = response.data;
                }
                that.tagLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.tagId == 'undefined') {
            this.fielddata.tagId = '';
        }

        this.getTags();
        this.getFields();
    },
    template: '#groundhogg-action-template'
});

Vue.component('engagebay', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: true },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'role', title: 'Role', task: ['subscribe'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['subscribe'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'sate', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'Zip', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },
                { type: 'text', value: 'company', title: 'Company', task: ['subscribe'], required: false },
            ]
        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function() {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_engagebay_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function() {
            var that = this;
            
            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_engagebay_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () { },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();
    },
    template: '#engagebay-action-template'
});

Vue.component('easysendy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialData = {
                'action': 'adfoin_get_easysendy_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_easysendy_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#easysendy-action-template'
});

Vue.component('salesrocks', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_salesrocks_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
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

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_salesrocks_credentials_list',
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
    template: '#salesrocks-action-template'
});

Vue.component('salesmate', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            tagsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['create_contact', 'create_deal'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: true },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'title', title: 'Deal Title', task: ['create_deal'], required: true },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['create_company'], required: true },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_contact', 'create_company'], required: false }
            ]
        };
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var fieldData = {
                action: 'adfoin_get_salesmate_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldData, function (response) {
                if (response.success) {
                    that.fielddata.fields = response.data;
                }
                that.fieldsLoading = false;
            });
        },
        getTags: function () {
            var that = this;
            this.tagsLoading = true;

            var tagData = {
                action: 'adfoin_get_salesmate_tags',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, tagData, function (response) {
                if (response.success) {
                    that.fielddata.tags = response.data;
                }
                that.tagsLoading = false;
            });
        }
    },
    created: function () {
        if (typeof this.fielddata.fields == 'undefined') {
            this.fielddata.fields = [];
        }

        if (typeof this.fielddata.tags == 'undefined') {
            this.fielddata.tags = [];
        }
    },
    mounted: function () {
        if (!this.fielddata.fields.length) {
            this.getFields();
        }

        if (!this.fielddata.tags.length) {
            this.getTags();
        }
    },
    template: '#salesmate-action-template'
});

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
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }

            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#salesloft-action-template'
});

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
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.companyName === 'undefined') {
                this.$set(this.fielddata, 'companyName', '');
            }
            if (typeof this.fielddata.customFields === 'undefined') {
                this.$set(this.fielddata, 'customFields', '');
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
    },
    template: '#outreach-action-template'
});

Vue.component('sendgrid', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone_number', title: 'Phone Number', task: ['add_contact'], required: false },
                { type: 'select', value: 'list_id', title: 'List', task: ['add_contact'], required: true }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listsLoading = true;

            var listRequestData = {
                action: 'adfoin_get_sendgrid_lists',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data.map(function (list) {
                        return { id: list.id, name: list.name };
                    });
                } else {
                    console.error('Error fetching lists:', response.data);
                }
                that.listsLoading = false;
            });
        }
    },
    created: function () {
        if (typeof this.fielddata.lists === 'undefined') {
            this.fielddata.lists = [];
        }
    },
    mounted: function () {
        if (!this.fielddata.lists.length) {
            this.getLists();
        }
    },
    template: '#sendgrid-action-template'
});

Vue.component('mailwizz', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailwizz_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        getList: function () {
            var that = this;
            if (!this.fielddata.credId) return;
            
            this.listLoading = true;
            var listRequestData = {
                'action': 'adfoin_get_mailwizz_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.listLoading = false;
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        // Fetch credentials list
        this.fetchCredentialsList();

        // Load lists if credId is set
        if (this.fielddata.credId) {
            this.getList();
        }
    },
    template: '#mailwizz-action-template'
});

Vue.component('maileon', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: []
        }
    },
    methods: {
        fetchCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_maileon_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getFields();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldLoading = true;
            this.fields = [];

            var fieldsRequestData = {
                'action': 'adfoin_get_maileon_fields',
                '_nonce': adfoin.nonce,
                'task': this.action.task,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, fieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldLoading = false;
                    }
                }
            }).always(function () {
                that.fieldLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fields = [];
            this.getFields();
        }
    },
    mounted: function () {
        ['permission', 'doi', 'doiplus', 'update'].forEach(key => {
            if (typeof this.fielddata[key] === 'undefined') {
                this.fielddata[key] = '';
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        this.fetchCredentials();
    },
    template: '#maileon-action-template'
});

Vue.component('trello', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            boardLoading: false,
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['add_card'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['add_card'], required: false },
                { type: 'text', value: 'pos', title: 'Position', task: ['add_card'], required: false, description: 'The position of the new card. top, bottom, or a positive float' }
            ]

        }
    },
    methods: {
        getBoards: function () {
            var that = this;
            this.boardLoading = true;

            var boardRequestData = {
                'action': 'adfoin_get_trello_boards',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, boardRequestData, function (response) {
                that.fielddata.boards = response.data;
                that.boardLoading = false;
            });
        },
        getLists: function () {
            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_get_trello_lists',
                '_nonce': adfoin.nonce,
                'boardId': this.fielddata.boardId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var lists = response.data;
                that.fielddata.lists = lists;
                that.listLoading = false;
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

        if (typeof this.fielddata.boardId == 'undefined') {
            this.fielddata.boardId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_trello_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load boards if credential is selected
                if (that.fielddata.credId) {
                    that.getBoards();
                }
            }
        });

        // Load lists if board is already selected (for existing integrations)
        if (this.fielddata.boardId && this.fielddata.credId) {
            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_get_trello_lists',
                '_nonce': adfoin.nonce,
                'boardId': this.fielddata.boardId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var lists = response.data;
                that.fielddata.lists = lists;
                that.listLoading = false;
            });
        }
    },
    template: '#trello-action-template'
});

Vue.component('mailjet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['subscribe'], required: false, description: 'Create a contact property titled "name" in Mailjet' },
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailjet_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getLists();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailjet_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getLists();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailjet-action-template'
});

Vue.component('mailify', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'phone', title: 'Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mailify_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getLists();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailify_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        handleAccountChange: function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            this.getLists();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        if (typeof this.fielddata.phone == 'undefined') {
            this.fielddata.phone = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getCredentials();
    },
    template: '#mailify-action-template'
});

Vue.component('lemlist', {
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
                { type: 'text', value: 'picture', title: 'Profile Picture URL', task: ['subscribe'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['subscribe'], required: false },
                { type: 'text', value: 'linkedinUrl', title: 'LinkedIn Profile URL', task: ['subscribe'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'companyDomain', title: 'Company Domain', task: ['subscribe'], required: false },
                { type: 'text', value: 'icebreaker', title: 'Icebreaker', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_lemlist_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_lemlist_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();
    },
    template: '#lemlist-action-template'
});

Vue.component('directiq', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getLists();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credRequestData = {
                'action': 'adfoin_get_directiq_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_directiq_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        this.getData();
    },
    template: '#directiq-action-template'
});

Vue.component('revue', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_revue_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        var that = this;

        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.doptin == 'undefined') {
            this.fielddata.doptin = false;
        }

        if (typeof this.fielddata.doptin != 'undefined') {
            if (this.fielddata.doptin == "false") {
                this.fielddata.doptin = false;
            }
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#revue-action-template'
});

Vue.component('slack', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'textarea', value: 'message', title: 'Message', task: ['sendmsg'], required: false }
            ]

        }
    },
    methods: {
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.url == 'undefined') {
            this.fielddata.url = '';
        }

        if (typeof this.fielddata.message == 'undefined') {
            this.fielddata.message = '';
        }
    },
    template: '#slack-action-template'
});

Vue.component('liondesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    mounted: function() {
        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    methods: {
        getCredentials: function() {
            var self = this;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_liondesk_credentials',
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                if (response.success) {
                    self.credentialsList = response.data;
                }
            });
        },
        loadFields: function() {
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            var self = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_liondesk_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                self.fieldsLoading = false;
                if (response.success) {
                    self.fields = response.data.map(function(field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: ['add_contact'],
                            required: field.required || false
                        };
                    });
                }
            });
        }
    },
    template: '#liondesk-action-template'
});

Vue.component('curated', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credRequestData = {
                'action': 'adfoin_get_curated_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        this.getData();
    },
    template: '#curated-action-template'
});

Vue.component('brevo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'sms', title: 'SMS', task: ['subscribe'], required: false, description: 'Mobile Number should be passed with proper country code. For example: "+91xxxxxxxxxx" or "0091xxxxxxxxxx"' }
            ],
            credentialsList: []
        }
    },
    methods: {
        getLists: function () {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_brevo_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_brevo_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        this.fetchCredentialsList();

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#brevo-action-template'
});

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
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_sendinblue_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = {};
                }
                that.listLoading = false;
            }).fail(function () {
                that.fielddata.list = {};
                that.listLoading = false;
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

Vue.component('zapier', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {}
    },
    mounted: function () {

        if (typeof this.fielddata.webhookUrl == 'undefined') {
            this.fielddata.webhookUrl = '';
        }
    },
    template: '#zapier-action-template'
});

Vue.component('webhook', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {}
    },
    mounted: function () {

        if (typeof this.fielddata.webhookUrl == 'undefined') {
            this.fielddata.webhookUrl = '';
        }
    },
    template: '#webhook-action-template'
});

Vue.component('drip', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            accountLoading: false,
            campaignLoading: false,
            workflowLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['create_subscriber'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'address1', title: 'Address 1', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'address2', title: 'Address 2', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['create_subscriber'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['create_subscriber'], required: false },
            ]

        }
    },
    methods: {
        getData: function () {
            this.getAccounts();
            if (this.fielddata.accountId) {
                this.getList();
            }
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialData = {
                'action': 'adfoin_get_drip_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getAccounts: function () {
            var that = this;
            this.accountLoading = true;

            var accountRequestData = {
                'action': 'adfoin_get_drip_accounts',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, accountRequestData, function (response) {
                that.fielddata.accounts = response.data;
                that.accountLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.campaignLoading = true;
            this.workflowLoading = true;

            var listData = {
                'action': 'adfoin_get_drip_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'accountId': this.fielddata.accountId
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var list = response.data;
                that.fielddata.list = list;
                that.campaignLoading = false;

                var workflowData = {
                    'action': 'adfoin_get_drip_workflows',
                    '_nonce': adfoin.nonce,
                    'credId': that.fielddata.credId,
                    'accountId': that.fielddata.accountId
                };

                jQuery.post(ajaxurl, workflowData, function (response) {
                    var workflows = response.data;
                    that.fielddata.workflows = workflows;
                    that.workflowLoading = false;
                });
            });


        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }

        if (typeof this.fielddata.campaignId == 'undefined') {
            this.fielddata.campaignId = '';
        }

        if (typeof this.fielddata.workflowId == 'undefined') {
            this.fielddata.workflowId = '';
        }

        this.getCredentials();

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#drip-action-template'
});

Vue.component('asana', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            workspaceLoading: false,
            projectLoading: false,
            sectionLoading: false,
            userLoading: false,
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['create_task'], required: true },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_task'], required: false },
                { type: 'text', value: 'dueOn', title: 'Due On', task: ['create_task'], required: false, description: 'Use YYYY-MM-DD format' },
                { type: 'text', value: 'dueOnX', title: 'Due After X Days', task: ['create_task'], required: false, description: 'Accepts numeric value. If filled, due date will be calculated and set' },
            ]

        }
    },
    methods: {
        getWorkspaces: function () {
            var that = this;
            this.workspaceLoading = true;

            var workspaceRequestData = {
                'action': 'adfoin_get_asana_workspaces',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, workspaceRequestData, function (response) {
                that.fielddata.workspaces = response.data;
                that.workspaceLoading = false;
            });
        },
        getProjects: function () {
            var that = this;
            this.projectLoading = true;
            this.userLoading = true;

            var projectData = {
                'action': 'adfoin_get_asana_projects',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'workspaceId': this.fielddata.workspaceId
            };

            jQuery.post(ajaxurl, projectData, function (response) {
                var projects = response.data;
                that.fielddata.projects = projects;
                that.projectLoading = false;
            });

            var userData = {
                'action': 'adfoin_get_asana_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'workspaceId': this.fielddata.workspaceId
            };

            jQuery.post(ajaxurl, userData, function (response) {
                var users = response.data;
                that.fielddata.users = users;
                that.userLoading = false;
            });
        },
        getSections: function () {
            var that = this;
            this.sectionLoading = true;

            var sectionData = {
                'action': 'adfoin_get_asana_sections',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'projectId': this.fielddata.projectId
            };

            jQuery.post(ajaxurl, sectionData, function (response) {
                var sections = response.data;
                that.fielddata.sections = sections;
                that.sectionLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        ['credId', 'workspaceId', 'projectId', 'sectionId', 'userId'].forEach(key => {
            if (typeof this.fielddata[key] === 'undefined') {
                this.fielddata[key] = '';
            }
        });

        // this.getWorkspaces();

        if (this.fielddata.credId) {
            this.getWorkspaces();
        }

        if (this.fielddata.workspaceId) {
            this.getProjects();
        }

        if (this.fielddata.workspaceId && this.fielddata.projectId) {
            this.getSections();
        }
    },
    template: '#asana-action-template'
});

Vue.component('anydo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            categoriesLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Task Title', task: ['add_task'], required: true },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['add_task'], required: false, description: 'YYYY-MM-DD or any parsable date' },
                { type: 'text', value: 'dueShortcut', title: 'Due Shortcut', task: ['add_task'], required: false, description: 'Supports tomorrow (t), upcoming (u), someday (s)' },
                { type: 'text', value: 'categoryIdCustom', title: 'Category ID Override', task: ['add_task'], required: false, description: 'Use a mapped category id to override the dropdown' }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.categories = {};
                this.fielddata.categoryId = '';
                return;
            }

            this.getCategories();
        },
        getCategories: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.categoriesLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_anydo_categories',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.categories = response.data;
                } else {
                    that.fielddata.categories = {};
                }
                that.categoriesLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.categoryId === 'undefined') {
            this.fielddata.categoryId = '';
        }

        if (typeof this.fielddata.categories === 'undefined') {
            this.fielddata.categories = {};
        }

        if (this.fielddata.credId) {
            this.getCategories();
        }
    },
    template: '#anydo-action-template'
});

Vue.component('mstodo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'bodyContent', title: 'Body Content', task: ['create_task'], required: false },
                { type: 'text', value: 'bodyContentType', title: 'Body Content Type', task: ['create_task'], required: false, description: 'text or html (defaults to text)' },
                { type: 'text', value: 'dueDateTime', title: 'Due DateTime', task: ['create_task'], required: false, description: 'Example: 2024-05-01T17:00:00' },
                { type: 'text', value: 'dueTimeZone', title: 'Due Time Zone', task: ['create_task'], required: false, description: 'IANA/Windows TZ, defaults to UTC' },
                { type: 'text', value: 'reminderDateTime', title: 'Reminder DateTime', task: ['create_task'], required: false },
                { type: 'text', value: 'reminderTimeZone', title: 'Reminder Time Zone', task: ['create_task'], required: false, description: 'Defaults to UTC' },
                { type: 'text', value: 'importance', title: 'Importance', task: ['create_task'], required: false, description: 'low, normal, or high' },
                { type: 'text', value: 'categories', title: 'Categories (CSV)', task: ['create_task'], required: false }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.lists = {};
                this.fielddata.listId = '';
                return;
            }

            this.getLists();
        },
        getLists: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.listsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_mstodo_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                } else {
                    that.fielddata.lists = {};
                }
                that.listsLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.lists === 'undefined') {
            this.fielddata.lists = {};
        }

        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#mstodo-action-template'
});

Vue.component('todoist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            projectLoading: false,
            sectionLoading: false,
            labelLoading: false,
            fields: [
                { type: 'text', value: 'content', title: 'Task Content', task: ['create_task'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_task'], required: false },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['create_task'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'dueString', title: 'Due String', task: ['create_task'], required: false, description: 'Examples: tomorrow 9am, next Monday' },
                { type: 'text', value: 'priority', title: 'Priority (1-4)', task: ['create_task'], required: false },
                { type: 'text', value: 'labels', title: 'Dynamic Labels (CSV)', task: ['create_task'], required: false, description: 'Comma-separated list pulled from form data' }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.projects = {};
                this.fielddata.sections = {};
                this.fielddata.labelsList = [];
                this.fielddata.labelNames = [];
                this.fielddata.projectId = '';
                this.fielddata.sectionId = '';
                return;
            }

            this.getProjects();
            this.getLabels();
        },
        getProjects: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.projectLoading = true;

            var requestData = {
                'action': 'adfoin_get_todoist_projects',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.projects = response.data;
                } else {
                    that.fielddata.projects = {};
                }
                that.projectLoading = false;
            });
        },
        getSections: function () {
            var that = this;

            if (!this.fielddata.credId || !this.fielddata.projectId) {
                this.fielddata.sections = {};
                this.fielddata.sectionId = '';
                return;
            }

            this.fielddata.sectionId = '';
            this.sectionLoading = true;

            var requestData = {
                'action': 'adfoin_get_todoist_sections',
                'credId': this.fielddata.credId,
                'projectId': this.fielddata.projectId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.sections = response.data;
                } else {
                    that.fielddata.sections = {};
                }
                that.sectionLoading = false;
            });
        },
        getLabels: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.labelsList = [];
                return;
            }

            this.labelLoading = true;

            var requestData = {
                'action': 'adfoin_get_todoist_labels',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.labelsList = response.data;
                } else {
                    that.fielddata.labelsList = [];
                }
                that.labelLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.projectId === 'undefined') {
            this.fielddata.projectId = '';
        }

        if (typeof this.fielddata.sectionId === 'undefined') {
            this.fielddata.sectionId = '';
        }

        if (typeof this.fielddata.projects === 'undefined') {
            this.fielddata.projects = {};
        }

        if (typeof this.fielddata.sections === 'undefined') {
            this.fielddata.sections = {};
        }

        if (typeof this.fielddata.labelsList === 'undefined') {
            this.fielddata.labelsList = [];
        }

        if (typeof this.fielddata.labelNames === 'undefined') {
            this.fielddata.labelNames = [];
        }

        if (this.fielddata.credId) {
            this.getProjects();
            this.getLabels();
        }

        if (this.fielddata.credId && this.fielddata.projectId) {
            this.getSections();
        }
    },
    template: '#todoist-action-template'
});

Vue.component('wrike', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            folderLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_task'], required: false },
                { type: 'text', value: 'startDate', title: 'Start Date', task: ['create_task'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['create_task'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'importance', title: 'Importance', task: ['create_task'], required: false, description: 'low, normal, high' },
                { type: 'text', value: 'status', title: 'Status', task: ['create_task'], required: false, description: 'ACTIVE, COMPLETED, DEFERRED, CANCELLED' },
                { type: 'text', value: 'responsibles', title: 'Responsibles', task: ['create_task'], required: false, description: 'Comma separated Wrike user IDs' }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.folders = {};
                this.fielddata.folderId = '';
                return;
            }

            this.getFolders();
        },
        getFolders: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.folderLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_wrike_folders',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.folders = response.data;
                } else {
                    that.fielddata.folders = {};
                }
                that.folderLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.folderId === 'undefined') {
            this.fielddata.folderId = '';
        }

        if (typeof this.fielddata.folders === 'undefined') {
            this.fielddata.folders = {};
        }

        if (this.fielddata.credId) {
            this.getFolders();
        }
    },
    template: '#wrike-action-template'
});

Vue.component('quickbase', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            appsLoading: false,
            tablesLoading: false
        };
    },
    methods: {
        getTables: function () {
            var that = this;
            this.appsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_quickbase_tables',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.apps = response.data;
                    that.appsLoading = false;
                }
            });
        },
        getFields: function () {
            const that = this;
            this.tablesLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_quickbase_fields',
                credId: this.fielddata.credId,
                appId: this.fielddata.appId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.tablesLoading = false;
                if (response.success) {
                    if (response.data) {
                        that.fields = [];
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') this.fielddata.credId = '';
        if (typeof this.fielddata.appId === 'undefined') this.fielddata.appId = '';
        if (this.fielddata.credId) this.getTables();
        if (this.fielddata.credId && this.fielddata.appId) this.getFields();
    },
    template: '#quickbase-action-template'
});

Vue.component('highlevel', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_highlevel_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_contact'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    template: '#highlevel-action-template'
});

Vue.component('monday', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            boardLoading: false,
            fieldsLoading: false,
            groupLoading: false,
            itemsLoading: false,
            fields: []
        };
    },
    methods: {
        getBoards: function () {
            var that = this;
            this.boardLoading = true;

            var boardRequestData = {
                action: 'adfoin_get_monday_boards',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, boardRequestData, function (response) {
                if (response.success) {
                    that.fielddata.boards = response.data;
                }
                that.boardLoading = false;
            });
        },
        getGroups: function () {
            var that = this;
            this.groupLoading = true;

            var groupRequestData = {
                action: 'adfoin_get_monday_groups',
                _nonce: adfoin.nonce,
                credId: this.fielddata.credId,
                boardId: this.fielddata.boardId
            };

            jQuery.post(ajaxurl, groupRequestData, function (response) {
                if (response.success) {
                    that.fielddata.groups = response.data;
                }
                that.groupLoading = false;
            });
        },
        getColumns: function () {
            var that = this;
            this.itemsLoading = true;

            var requestData = {
                action: 'adfoin_get_monday_columns',
                credId: this.fielddata.credId,
                boardId: this.fielddata.boardId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_item'], required: false, description: single.description });
                        });

                        that.itemsLoading = false;
                    }
                }
            });
        },
        getFields: function () {
            this.getColumns();
            this.getGroups();
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.boardId == 'undefined') {
            this.fielddata.boardId = '';
        }

        if (typeof this.fielddata.groupId == 'undefined') {
            this.fielddata.groupId = '';
        }

        if (this.fielddata.credId) {
            this.getBoards();
        }

        if (this.fielddata.boardId) {
            this.getFields();
        }
    },
    template: '#monday-action-template'
});

Vue.component('kirimemail', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_subscriber', 'remove_subscriber'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_subscriber'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_subscriber'], required: false }
            ]
        };
    },
    methods: {
        getLists: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_kirimemail_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {
        // Initialize data fields
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.listId) this.fielddata.listId = '';
    },
    mounted: function () {
        // Automatically fetch lists if a credential ID is set
        if (this.fielddata.credId) {
            this.getLists();
        }
    },
    template: '#kirimemail-action-template'
});

Vue.component('mailmint', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_mailmint_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getLists: function () {
            var that = this;

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_mailmint_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getLists();
        this.getFields();
    },
    template: '#mailmint-action-template'
});

Vue.component('instantly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            campaignLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_lead'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['add_lead'], required: false },
                { type: 'text', value: 'personalization', title: 'Personalization', task: ['add_lead'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_lead'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_lead'], required: false },
            ]
        };
    },
    methods: {
        getCampaigns: function (credId = null) {
            var that = this;

            this.campaignLoading = true;

            var campaignRequestData = {
                'action': 'adfoin_get_instantly_campaigns',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, campaignRequestData, function (response) {
                if (response.success) {
                    that.fielddata.campaigns = response.data;
                } else {
                    that.fielddata.campaigns = [];
                }
                that.campaignLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.campaignId == 'undefined') {
            this.fielddata.campaignId = '';
        }

        if (this.fielddata.credId) {
            this.getCampaigns(this.fielddata.credId);
        }
    },
    template: '#instantly-action-template'
});

Vue.component('salesforce', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            fieldsLoading: false,
            campaignLoading: false,
            ownerLoading: false,
            credentialsList: [],
            fields: []
        };
    },
    methods: {
        getCredentials: function () {
            var that = this;
            this.credLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_salesforce_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = 'legacy_123456';
                    }
                    if (that.fielddata.credId) {
                        that.getFields();
                        that.getOwners();
                        if (that.action.task === 'add_lead') {
                            that.getCampaigns();
                        }
                    }
                }
                that.credLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_salesforce_fields',
                'task': this.action.task,
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fields = [];
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_lead', 'add_contact'], required: false, description: single.description });
                        });
                    }
                }
                that.fieldsLoading = false;
            });
        },
        getCampaigns: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.campaigns = {};
                return;
            }

            this.campaignLoading = true;

            var campaignRequestData = {
                'action': 'adfoin_get_salesforce_campaigns',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, campaignRequestData, function (response) {
                if (response.success) {
                    that.fielddata.campaigns = response.data;
                }
                that.campaignLoading = false;
            });
        },
        getOwners: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.owners = {};
                return;
            }

            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_salesforce_owners',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                if (response.success) {
                    that.fielddata.owners = response.data;
                }
                that.ownerLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.accountId === 'undefined') {
            this.fielddata.accountId = '';
        }
        if (typeof this.fielddata.campaignId === 'undefined') {
            this.fielddata.campaignId = '';
        }
        if (typeof this.fielddata.ownerId === 'undefined') {
            this.fielddata.ownerId = '';
        }

        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
                this.getOwners();
                if (this.action.task === 'add_lead') {
                    this.getCampaigns();
                }
            }
        },
        'action.task': function (newTask) {
            if (this.fielddata.credId) {
                this.getFields();
                this.getOwners();
                if (newTask === 'add_lead') {
                    this.getCampaigns();
                }
            }
        }
    },
    template: '#salesforce-action-template',
});

Vue.component('nutshell', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fieldsLoading: false,
            ownerLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_nutshell_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = 'legacy_123456';
                    }
                    if (that.fielddata.credId) {
                        that.getFields();
                        that.getOwners();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_nutshell_fields',
                'task': this.action.task,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fields = []; // Clear existing fields
                        response.data.map(function (single) {
                            that.fields.push({
                                type: 'text',
                                value: single.key,
                                title: single.value,
                                task: ['add_contact'],
                                required: false,
                                description: single.description
                            });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getOwners: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.owners = {};
                return;
            }

            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_nutshell_owners',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                if (response.success) {
                    that.fielddata.owners = response.data;
                }
                that.ownerLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.ownerId === 'undefined') {
            this.fielddata.ownerId = '';
        }

        this.getData();
    },
    watch: {
        'action.task': function (newTask) {
            if (this.fielddata.credId) {
                this.getFields();
                this.getOwners();
            }
        }
    },
    template: '#nutshell-action-template'
});

Vue.component('mailster', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldsLoading: false,
            fields: [],
        };
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_mailster_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getLists: function () {
            var that = this;
            this.listLoading = true;

            const data = {
                action: 'adfoin_get_mailster_lists',
                _nonce: adfoin.nonce,
            };

            jQuery.post(ajaxurl, data, function (response) {
                if (response.success) {
                    that.fielddata.lists = response.data;
                }
                that.listLoading = false;
            });
        },
    },
    mounted: function () {
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.status === 'undefined') {
            this.fielddata.status = '0';
        }

        this.getLists();
        this.getFields();
    },
    template: '#mailster-action-template',
});

Vue.component('newsletter', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: [],
        };
    },
    methods: {
        getFields: function () {
            var that = this;

            this.fieldsLoading = true;

            var fieldRequestData = {
                'action': 'adfoin_get_newsletter_fields',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        this.getFields();
    },
    template: '#newsletter-action-template',
});

Vue.component('clickup', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            workspaceLoading: false,
            spaceLoading: false,
            folderLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['create_task'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_task'], required: false },
                { type: 'text', value: 'startDate', title: 'Start Date', task: ['create_task'], required: false },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['create_task'], required: false },
                { type: 'text', value: 'dueOnX', title: 'Due After X Days', task: ['create_task'], required: false, description: 'Accepts numeric value. If filled, due date will be calculated and set' },
                { type: 'text', value: 'priorityId', title: 'Priority ID', task: ['create_task'], required: false, description: 'Urgent: 1, Hight: 2. Normal: 3, Low: 4' },
                { type: 'text', value: 'assignees', title: 'Assignee Emails', task: ['create_task'], required: false, description: 'Enter assignee email. Use comma for multiple emails.' },
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getWorkspaces();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_clickup_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getWorkspaces: function () {
            var that = this;
            this.workspaceLoading = true;

            var workspaceRequestData = {
                'action': 'adfoin_get_clickup_workspaces',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, workspaceRequestData, function (response) {
                that.fielddata.workspaces = response.data;
                that.workspaceLoading = false;
            });
        },
        getSpaces: function () {
            var that = this;
            this.spaceLoading = true;

            var spaceData = {
                'action': 'adfoin_get_clickup_spaces',
                '_nonce': adfoin.nonce,
                'workspaceId': this.fielddata.workspaceId,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, spaceData, function (response) {
                var spaces = response.data;
                that.fielddata.spaces = spaces;
                that.spaceLoading = false;
            });
        },
        getFolders: function () {
            var that = this;
            this.folderLoading = true;

            var folderData = {
                'action': 'adfoin_get_clickup_folders',
                '_nonce': adfoin.nonce,
                'spaceId': this.fielddata.spaceId,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, folderData, function (response) {
                var folders = response.data;
                that.fielddata.folders = folders;
                that.folderLoading = false;
            });

            if (!this.fielddata.folderId) {
                this.getLists();
            }
        },
        getLists: function () {
            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_get_clickup_lists',
                '_nonce': adfoin.nonce,
                'spaceId': this.fielddata.spaceId,
                'folderId': this.fielddata.folderId,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var lists = response.data;
                that.fielddata.lists = lists;
                that.listLoading = false;
            });
        },
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.workspaceId == 'undefined') {
            this.fielddata.workspaceId = '';
        }

        if (typeof this.fielddata.spaceId == 'undefined') {
            this.fielddata.spaceId = '';
        }

        if (typeof this.fielddata.folderId == 'undefined') {
            this.fielddata.folderId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        this.getData();

        if (this.fielddata.workspaceId) {
            this.getSpaces();
        }

        if (this.fielddata.workspaceId && this.fielddata.spaceId) {
            this.getFolders();
        }

        if (this.fielddata.workspaceId && this.fielddata.spaceId && this.fielddata.folderId) {
            this.getLists();
        }
    },
    template: '#clickup-action-template'
});

Vue.component('everwebinar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            webinarLoading: false,
            scheduleLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['register_webinar'], required: true, description: 'Required' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['register_webinar'], required: true, description: 'Required' },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['register_webinar'], required: false },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['register_webinar'], required: false },
                { type: 'text', value: 'phoneCountryCode', title: 'Phone Country Code', task: ['register_webinar'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['register_webinar'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['register_webinar'], required: false },
                { type: 'text', value: 'date', title: 'Date', task: ['register_webinar'], required: false }
            ]

        }
    },
    methods: {
        getData: function() {
            this.getCredentials();
            this.getWebinar();
        },
        getCredentials: function() {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_everwebinar_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getWebinar: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                return;
            }

            this.webinarLoading = true;

            var webinarRequestData = {
                'action': 'adfoin_get_everwebinar_webinars',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };
            jQuery.post(ajaxurl, webinarRequestData, function (response) {
                that.fielddata.webinars = response.data;
                that.webinarLoading = false;
            });
        },
        getSchedule: function () {
            var that = this;
            this.scheduleLoading = true;

            var scheduleData = {
                'action': 'adfoin_get_everwebinar_schedules',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'webinarId': this.fielddata.webinarId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, scheduleData, function (response) {
                var schedules = response.data;
                that.fielddata.schedules = schedules;
                that.scheduleLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.webinarId == 'undefined') {
            this.fielddata.webinarId = '';
        }

        if (typeof this.fielddata.scheduleId == 'undefined') {
            this.fielddata.scheduleId = '';
        }

        this.getData();

        if (this.fielddata.webinarId) {
            this.getSchedule();
        }
    },
    template: '#everwebinar-action-template'
});

Vue.component('webinarjam', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            webinarLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['register_webinar'], required: true, description: 'Required' },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['register_webinar'], required: true, description: 'Required' },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['register_webinar'], required: false },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['register_webinar'], required: false },
                { type: 'text', value: 'phoneCountryCode', title: 'Phone Country Code', task: ['register_webinar'], required: false },
                { type: 'text', value: 'phone', title: 'Phone Number', task: ['register_webinar'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['register_webinar'], required: false },
                { type: 'text', value: 'date', title: 'Date', task: ['register_webinar'], required: false }
            ]

        }
    },
    methods: {
        getWebinars: function () {
            var that = this;
            this.webinarLoading = true;

            var webinarRequestData = {
                'action': 'adfoin_get_webinarjam_webinars',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };
            jQuery.post(ajaxurl, webinarRequestData, function (response) {
                that.fielddata.webinars = response.data;
                that.webinarLoading = false;
            });
        },
        getSchedule: function () {
            var that = this;
            this.webinarLoading = true;

            var scheduleData = {
                'action': 'adfoin_get_webinarjam_schedules',
                '_nonce': adfoin.nonce,
                'webinarId': this.fielddata.webinarId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, scheduleData, function (response) {
                var schedules = response.data;
                that.fielddata.schedules = schedules;
                that.webinarLoading = false;
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

        if (typeof this.fielddata.webinarId == 'undefined') {
            this.fielddata.webinarId = '';
        }

        if (typeof this.fielddata.scheduleId == 'undefined') {
            this.fielddata.scheduleId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.firstName == 'undefined') {
            this.fielddata.firstName = '';
        }

        if (typeof this.fielddata.lastName == 'undefined') {
            this.fielddata.lastName = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_webinarjam_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load webinars if credential is selected
                if (that.fielddata.credId) {
                    that.getWebinars();
                }
            }
        });
    },
    template: '#webinarjam-action-template'
});

Vue.component('constantcontact', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe', 'unsubscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'jobTitle', title: 'Job Title', task: ['subscribe'], required: false },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'workPhone', title: 'Work Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'homePhone', title: 'Home Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'mobilePhone', title: 'Cell Phone', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdayMonth', title: 'Birthday Month', task: ['subscribe'], required: false },
                { type: 'text', value: 'birthdayDay', title: 'Birthday Day', task: ['subscribe'], required: false },
                { type: 'text', value: 'anniversary', title: 'Anniversary', task: ['subscribe'], required: false },
                { type: 'text', value: 'addressType', title: 'Address Type', task: ['subscribe'], required: false, description: 'home, work, other' },
                { type: 'text', value: 'address1', title: 'Address Line 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['subscribe'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['subscribe'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['subscribe'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['subscribe'], required: false },

            ]

        }
    },
    methods: {
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_constantcontact_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        getConstantContactList: function() {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.list = [];
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_constantcontact_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.list = response.data;
                } else {
                    that.fielddata.list = [];
                }
                that.listLoading = false;
            });
        }
    },
    watch: {
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getConstantContactList();
            }
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.permission == 'undefined') {
            this.fielddata.permission = 'explicit';
        }

        if (typeof this.fielddata.createSource == 'undefined') {
            this.fielddata.createSource = 'Account';
        }

        // Get credentials first
        this.getCredentials();

        // Load lists if credId is already set
        if (this.fielddata.credId) {
            this.getConstantContactList();
        }
    },
    template: '#constantcontact-action-template'
});

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
            if (typeof this.fielddata.credId === 'undefined') {
                this.$set(this.fielddata, 'credId', '');
            }
            if (typeof this.fielddata.listId === 'undefined') {
                this.$set(this.fielddata, 'listId', '');
            }
        },
        getCredentials: function() {
            var that = this;
            var credRequestData = {
                'action': 'adfoin_get_verticalresponse_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'subscribe') {
                this.fields = [];
                return;
            }

            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_verticalresponse_fields',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['subscribe'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });

            // Also load lists when credentials change
            this.loadLists();
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

Vue.component('zohocrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            userLoading: false,
            moduleLoading: false,
            fieldsLoading: false,
            fields: []

        }
    },
    methods: {

        getFields: function () {
            var that = this;
            this.moduleLoading = true;
            this.fields = [];

            var fieldsRequestData = {
                'action': 'adfoin_get_zohocrm_module_fields',
                '_nonce': adfoin.nonce,
                'module': this.fielddata.moduleId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, fieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.moduleLoading = false;
                    }
                }
            });
        },
        getUsers: function () {
            var that = this;

            this.userLoading = true;

            var userRequestData = {
                'action': 'adfoin_get_zohocrm_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, userRequestData, function (response) {
                that.fielddata.users = response.data;
                that.userLoading = false;
            });
        },
        getModules: function () {
            var that = this;
            this.moduleLoading = true;

            var moduleRequestData = {
                'action': 'adfoin_get_zohocrm_modules',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, moduleRequestData, function (response) {
                that.fielddata.modules = response.data;
                that.moduleLoading = false;
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

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '123456';
        }

        if (this.fielddata.credId) {
            this.getUsers();
        }

        if (this.fielddata.credId && this.fielddata.userId) {
            this.getModules();
        }

        if (this.fielddata.credId && this.fielddata.userId && this.fielddata.moduleId) {
            this.getFields();
        }

    },
    watch: {},
    template: '#zohocrm-action-template'
});

Vue.component('attio', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            objectLoading: false,
            fieldsLoading: false,
            fields: []

        }
    },
    methods: {

        getFields: function () {
            var that = this;
            this.fieldsLoading = true;
            this.fields = [];

            var fieldsRequestData = {
                'action': 'adfoin_get_attio_object_fields',
                '_nonce': adfoin.nonce,
                'objectId': this.fielddata.objectId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, fieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        },
        getObjects: function () {
            var that = this;
            this.objectLoading = true;

            var objectRequestData = {
                'action': 'adfoin_get_attio_objects',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, objectRequestData, function (response) {
                that.fielddata.objects = response.data;
                that.objectLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.objectId == 'undefined') {
            this.fielddata.objectId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.update == 'undefined') {
            this.fielddata.update = false;
        }

        if (typeof this.fielddata.update != 'undefined') {
            if (this.fielddata.update == "false") {
                this.fielddata.update = false;
            }
        }

        if (this.fielddata.credId) {
            this.getObjects();
        }

        if (this.fielddata.credId && this.fielddata.objectId) {
            this.getFields();
        }

    },
    watch: {},
    template: '#attio-action-template'
});

Vue.component('zohodesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            organizationLoading: false,
            departmentLoading: false,
            ownerLoading: false,
            fields: []

        }
    },
    methods: {

        getFields: function () {
            var that = this;
            this.departmentLoading = true;
            this.fields = [];

            var fieldsRequestData = {
                'action': 'adfoin_get_zohodesk_fields',
                '_nonce': adfoin.nonce,
                'orgId': this.fielddata.orgId,
                'departmentId': this.fielddata.departmentId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, fieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.departmentLoading = false;
                    }
                }
            });
        },
        getOrganizations: function () {
            var that = this;

            this.organizationLoading = true;

            var orgRequestData = {
                'action': 'adfoin_get_zohodesk_organizations',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, orgRequestData, function (response) {
                that.fielddata.organizations = response.data;
                that.organizationLoading = false;
            });

            this.getOwners();
        },
        getDepartments: function () {
            var that = this;
            this.departmentLoading = true;

            var departmentRequestData = {
                'action': 'adfoin_get_zohodesk_departments',
                'credId': this.fielddata.credId,
                'orgId': this.fielddata.orgId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, departmentRequestData, function (response) {
                that.fielddata.departments = response.data;
                that.departmentLoading = false;
            });
        },
        getOwners: function () {
            var that = this;

            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_zohodesk_owners',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.owners = response.data;
                that.ownerLoading = false;
            });
        },
    },
    created: function () {
        if (this.fielddata.credId && this.fielddata.orgId) {
            this.getDepartments();
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.orgId == 'undefined') {
            this.fielddata.orgId = '';
        }

        if (typeof this.fielddata.departmentId == 'undefined') {
            this.fielddata.departmentId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.ownerId == 'undefined') {
            this.fielddata.ownerId = '';
        }

        if (this.fielddata.credId) {
            this.getOrganizations();
        }

        if (this.fielddata.credId && this.fielddata.orgId && this.fielddata.departmentId) {
            this.getFields();
        }
    },
    watch: {},
    template: '#zohodesk-action-template'
});

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
                }
                that.moduleLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            this.moduleLoading = true;
            this.fields = [];

            var fieldsRequestData = {
                'action': 'adfoin_get_bigin_module_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'module': this.fielddata.moduleId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, fieldsRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });

                        that.moduleLoading = false;
                    }
                }
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

Vue.component('zohocampaigns', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_zohocampaigns_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (this.fielddata.credId) {
            this.getList();
        }
    },
    watch: {
        'fielddata.credId': function () {
            this.fielddata.listId = '';
            this.fielddata.list = {};
            if (this.fielddata.credId) {
                this.getList();
            }
        }
    },
    template: '#zohocampaigns-action-template'
});

Vue.component('zohoma', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getList: function () {
            var that = this;
            this.listLoading = true;
            this.fields = [];

            var listRequestData = {
                'action': 'adfoin_get_zohoma_lists',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.lists = response.data;
            }).always(function () {
                that.listLoading = false;
            });

            this.getFields();
        },
        getFields: function () {
            var that = this;
            this.fieldLoading = true;
            this.fields = [];

            var fieldRequestData = {
                'action': 'adfoin_get_zohoma_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldRequestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: false, description: single.description });
                        });
                    }
                }
            }).always(function () {
                that.fieldLoading = false;
            });
        }
    },
    mounted: function () {

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (this.fielddata.listId) {
            this.getList();
        }
    },
    template: '#zohoma-action-template'
});

Vue.component('wordpress', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            postTypeLoading: false,
            selected: '',
            fields: [],
            title: '',
            slug: '',
            author: '',
            content: '',
            postMeta: '',
            username: '',
            email: '',
            firstName: '',
            lastName: '',
            website: '',
            password: '',
            role: '',
            userMeta: ''

        }
    },
    methods: {
        updateFieldValue: function (value) {
            if (this.selected || this.selected == 0) {
                if (this.fielddata[value] || "0" == this.fielddata[value]) {
                    this.fielddata[value] += ' {{' + this[value] + '}}';
                } else {
                    this.fielddata[value] = '{{' + this[value] + '}}';
                }
            }
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.postTypeId == 'undefined') {
            this.fielddata.postTypeId = '';
        }

        if (typeof this.fielddata.status == 'undefined') {
            this.fielddata.status = '';
        }

        if (typeof this.fielddata.role == 'undefined') {
            this.fielddata.role = '';
        }

        this.postTypeLoading = true;

        var postTypeRequestData = {
            'action': 'adfoin_get_wordpress_post_types',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, postTypeRequestData, function (response) {
            that.fielddata.postTypes = response.data;
            that.postTypeLoading = false;
        });
    },
    watch: {},
    template: '#wordpress-action-template'
});

Vue.component('bbpress', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {};
    },
    methods: {
        ensureDefaults: function () {
            var defaults = [
                'title',
                'content',
                'slug',
                'parent',
                'author',
                'forumStatus',
                'visibility',
                'forumType',
                'forum',
                'postStatus',
                'stickType',
                'tags',
                'topic',
                'replyTo'
            ];

            for (var i = 0; i < defaults.length; i++) {
                var key = defaults[i];
                if (typeof this.fielddata[key] === 'undefined') {
                    this.$set(this.fielddata, key, '');
                }
            }
        }
    },
    created: function () {
        this.ensureDefaults();
    },
    mounted: function () {
        this.ensureDefaults();
    },
    watch: {
        'action.task': function () {
            this.ensureDefaults();
        }
    },
    template: '#bbpress-action-template'
});

Vue.component('eventsmanager', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_event: [
                    { type: 'text', value: 'eventName', title: 'Event Name', task: ['create_event'], required: true, description: 'Required event title.' },
                    { type: 'text', value: 'eventDescription', title: 'Event Description', task: ['create_event'], description: 'Optional description (HTML allowed).' },
                    { type: 'text', value: 'eventExcerpt', title: 'Event Excerpt', task: ['create_event'] },
                    { type: 'text', value: 'slug', title: 'Event Slug', task: ['create_event'], description: 'Optional custom slug.' },
                    { type: 'text', value: 'startDate', title: 'Start Date', task: ['create_event'], required: true, description: 'Required start date (YYYY-MM-DD).' },
                    { type: 'text', value: 'startTime', title: 'Start Time', task: ['create_event'], description: 'Start time (HH:MM, 24-hour or AM/PM).' },
                    { type: 'text', value: 'endDate', title: 'End Date', task: ['create_event'], description: 'Defaults to the start date when empty.' },
                    { type: 'text', value: 'endTime', title: 'End Time', task: ['create_event'], description: 'Defaults to start time or all-day end.' },
                    { type: 'text', value: 'allDay', title: 'All Day (yes/no)', task: ['create_event'], description: 'Enter yes to mark as all-day.' },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_event'], description: 'Optional. Example: Europe/London.' },
                    { type: 'text', value: 'locationId', title: 'Location ID', task: ['create_event'], description: 'Existing Events Manager location ID.' },
                    { type: 'text', value: 'ownerId', title: 'Owner User ID', task: ['create_event'], description: 'WordPress user ID for event owner.' },
                    { type: 'text', value: 'forceStatus', title: 'Force Post Status', task: ['create_event'], description: 'publish, draft, pending, or private.' },
                    { type: 'text', value: 'eventStatus', title: 'Event Status', task: ['create_event'], description: 'Numeric status (1 = approved, 0 = pending).' },
                    { type: 'text', value: 'eventPrivate', title: 'Private Event (yes/no)', task: ['create_event'] },
                    { type: 'text', value: 'rsvpEnabled', title: 'Enable RSVPs (yes/no)', task: ['create_event'] },
                    { type: 'text', value: 'rsvpDate', title: 'RSVP Deadline Date', task: ['create_event'], description: 'YYYY-MM-DD format.' },
                    { type: 'text', value: 'rsvpTime', title: 'RSVP Deadline Time', task: ['create_event'], description: 'HH:MM format.' },
                    { type: 'text', value: 'totalSpaces', title: 'Total Spaces', task: ['create_event'] },
                    { type: 'text', value: 'rsvpSpaces', title: 'RSVP Spaces Per Booking', task: ['create_event'] }
                ]
            }
        };
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#eventsmanager-action-template'
});

Vue.component('fluentbooking', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_booking: [
                    { type: 'text', value: 'eventId', title: 'Calendar Event ID', task: ['create_booking'], required: true, description: 'Numeric ID of the Fluent Booking calendar slot.' },
                    { type: 'text', value: 'personTimeZone', title: 'Attendee Timezone', task: ['create_booking'], required: true, description: 'IANA timezone e.g. America/New_York.' },
                    { type: 'text', value: 'startTimeUtc', title: 'Start Time (UTC)', task: ['create_booking'], description: 'Optional. UTC date/time (YYYY-MM-DD HH:MM:SS).' },
                    { type: 'text', value: 'startTime', title: 'Start Time (Local)', task: ['create_booking'], description: 'Optional local date/time. Requires attendee timezone.' },
                    { type: 'text', value: 'endTimeUtc', title: 'End Time (UTC)', task: ['create_booking'], description: 'Optional. Overrides automatic calculation.' },
                    { type: 'text', value: 'endTime', title: 'End Time (Local)', task: ['create_booking'], description: 'Optional local end time. Requires attendee timezone.' },
                    { type: 'text', value: 'durationMinutes', title: 'Duration (Minutes)', task: ['create_booking'], description: 'Override slot duration. Used if end time omitted.' },
                    { type: 'text', value: 'email', title: 'Attendee Email', task: ['create_booking'], required: true },
                    { type: 'text', value: 'name', title: 'Attendee Name', task: ['create_booking'], description: 'Full name. Split into first/last if needed.' },
                    { type: 'text', value: 'firstName', title: 'First Name', task: ['create_booking'] },
                    { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_booking'] },
                    { type: 'text', value: 'status', title: 'Booking Status', task: ['create_booking'], description: 'Default scheduled. Examples: scheduled, pending, cancelled.' },
                    { type: 'text', value: 'message', title: 'Message', task: ['create_booking'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_booking'] },
                    { type: 'text', value: 'country', title: 'Country', task: ['create_booking'] },
                    { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['create_booking'] },
                    { type: 'text', value: 'hostUserId', title: 'Host User ID', task: ['create_booking'], description: 'Optional WordPress user ID to assign as host.' },
                    { type: 'text', value: 'personUserId', title: 'Attendee User ID', task: ['create_booking'] },
                    { type: 'text', value: 'personContactId', title: 'Attendee Contact ID', task: ['create_booking'] },
                    { type: 'text', value: 'eventType', title: 'Event Type', task: ['create_booking'], description: 'Override event type (defaults to slot type).' },
                    { type: 'text', value: 'paymentMethod', title: 'Payment Method', task: ['create_booking'] },
                    { type: 'text', value: 'paymentStatus', title: 'Payment Status', task: ['create_booking'] },
                    { type: 'text', value: 'source', title: 'Source', task: ['create_booking'], description: 'Label for booking source.' },
                    { type: 'text', value: 'sourceUrl', title: 'Source URL', task: ['create_booking'] },
                    { type: 'text', value: 'utmSource', title: 'UTM Source', task: ['create_booking'] },
                    { type: 'text', value: 'utmMedium', title: 'UTM Medium', task: ['create_booking'] },
                    { type: 'text', value: 'utmCampaign', title: 'UTM Campaign', task: ['create_booking'] },
                    { type: 'text', value: 'utmTerm', title: 'UTM Term', task: ['create_booking'] },
                    { type: 'text', value: 'utmContent', title: 'UTM Content', task: ['create_booking'] },
                    { type: 'text', value: 'browser', title: 'Browser', task: ['create_booking'] },
                    { type: 'text', value: 'device', title: 'Device', task: ['create_booking'] },
                    { type: 'text', value: 'locationType', title: 'Location Type', task: ['create_booking'], description: 'Match a configured location key (e.g. online_meeting).' },
                    { type: 'text', value: 'locationDescription', title: 'Location Description', task: ['create_booking'] },
                    { type: 'text', value: 'locationUrl', title: 'Location URL', task: ['create_booking'], description: 'Online meeting link when applicable.' },
                    { type: 'text', value: 'additionalGuests', title: 'Additional Guests', task: ['create_booking'], description: 'Comma or newline separated guest emails.' },
                    { type: 'text', value: 'additionalGuestsJson', title: 'Additional Guests JSON', task: ['create_booking'], description: 'JSON array of {"name":"","email":""} items.' },
                    { type: 'text', value: 'customFieldsJson', title: 'Custom Fields JSON', task: ['create_booking'], description: 'JSON object where keys match custom booking field names.' }
                ]
            }
        };
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#fluentbooking-action-template'
});

Vue.component('googletasks', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listsLoading: false,
            credLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Title', task: ['create_task'], required: true },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_task'], required: false },
                { type: 'text', value: 'due', title: 'Due DateTime', task: ['create_task'], required: false, description: 'Example: 2024-05-01T10:00:00' },
                { type: 'text', value: 'status', title: 'Status', task: ['create_task'], required: false, description: 'needsAction or completed' },
                { type: 'text', value: 'parent', title: 'Parent Task ID', task: ['create_task'], required: false },
                { type: 'text', value: 'position', title: 'Position', task: ['create_task'], required: false }
            ]
        }
    },
    methods: {
        getTaskLists: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.taskLists = {};
                return;
            }

            this.listsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_googletasks_lists',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.taskLists = response.data;
                } else {
                    that.fielddata.taskLists = {};
                    console.log('Error fetching task lists:', response.data);
                }
                that.listsLoading = false;
            });
        },
        fetchLists: function () {
            // Legacy method for backward compatibility
            this.getTaskLists();
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId === 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.taskLists === 'undefined') {
            this.fielddata.taskLists = {};
        }

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        // Load credentials
        this.credLoading = true;

        var credRequestData = {
            'action': 'adfoin_get_googletasks_credentials',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credRequestData, function (response) {
            if (response.success) {
                that.fielddata.credId = response.data;
                
                // Auto-select first credential if none selected
                if (!that.fielddata.credId || that.fielddata.credId === '') {
                    var firstKey = Object.keys(response.data)[0];
                    if (firstKey) {
                        that.fielddata.credId = firstKey;
                    }
                }
                
                // Load task lists if credential is selected
                if (that.fielddata.credId) {
                    that.getTaskLists();
                }
            }
            that.credLoading = false;
        });
    },
    template: '#googletasks-action-template'
});

Vue.component('googlecalendar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            credLoading: false,
            selected: '',
            fields: [],
            title: '',
            description: '',
            start: '',
            end: '',
            timezone: '',
            location: '',
            attendees: ''
        }
    },
    methods: {
        updateFieldValue: function (value) {
            if (this.selected || this.selected == 0) {
                if (this.fielddata[value] || "0" == this.fielddata[value]) {
                    this.fielddata[value] += ' {{' + this[value] + '}}';
                } else {
                    this.fielddata[value] = '{{' + this[value] + '}}';
                }
            }
        },
        getCalendars: function () {
            var that = this;
            
            if (!this.fielddata.credId) {
                this.fielddata.calendarList = {};
                return;
            }

            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_googlecalendar_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response.success) {
                    that.fielddata.calendarList = response.data;
                } else {
                    that.fielddata.calendarList = {};
                    console.log('Error fetching calendars:', response.data);
                }
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.calendarId == 'undefined') {
            this.fielddata.calendarId = '';
        }

        if (typeof this.fielddata.allDayEvent == 'undefined') {
            this.fielddata.allDayEvent = false;
        }

        if (typeof this.fielddata.allDayEvent != 'undefined') {
            if (this.fielddata.allDayEvent == "false") {
                this.fielddata.allDayEvent = false;
            }
        }

        // Initialize credId for backward compatibility
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = 'legacy_123456';
        }

        // Load credentials
        this.credLoading = true;

        var credRequestData = {
            'action': 'adfoin_get_googlecalendar_credentials',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credRequestData, function (response) {
            if (response.success) {
                that.fielddata.credId = response.data;
                
                // Auto-select first credential if none selected
                if (!that.fielddata.credId || that.fielddata.credId === '') {
                    var firstKey = Object.keys(response.data)[0];
                    if (firstKey) {
                        that.fielddata.credId = firstKey;
                    }
                }
                
                // Load calendars if credential is selected
                if (that.fielddata.credId) {
                    that.getCalendars();
                }
            }
            that.credLoading = false;
        });
    },
    watch: {},
    template: '#googlecalendar-action-template'
});

Vue.component('googlesheets', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            worksheetLoading: false,
            fields: []

        }
    },
    methods: {
        getSpreadsheets: function () {
            if (!this.fielddata.credId) {
                return;
            }

            this.fielddata.spreadsheetList = [];
            this.fielddata.spreadsheetId = '';
            this.fielddata.worksheetList = [];
            this.fielddata.worksheetId = '';
            this.fields = [];

            var that = this;
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_spreadsheet_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.spreadsheetList = response.data;
                that.listLoading = false;
            });
        },
        getWorksheets: function () {
            if (!this.fielddata.spreadsheetId) {
                return;
            }

            this.fielddata.worksheetList = [];
            this.fielddata.worksheetId = '';
            this.fields = [];

            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_googlesheets_get_worksheets',
                '_nonce': adfoin.nonce,
                'spreadsheetId': this.fielddata.spreadsheetId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                that.fielddata.worksheetList = response.data;
                that.listLoading = false;
            });
        },
        getHeaders: function () {
            if (this.fielddata.worksheetId == 0 || this.fielddata.worksheetId) {

                this.fields = [];
                var that = this;
                this.worksheetLoading = true;
                this.fielddata.worksheetName = this.fielddata.worksheetList[parseInt(this.fielddata.worksheetId)];

                var requestData = {
                    'action': 'adfoin_googlesheets_get_headers',
                    '_nonce': adfoin.nonce,
                    'spreadsheetId': this.fielddata.spreadsheetId,
                    'worksheetName': this.fielddata.worksheetName,
                    'credId': this.fielddata.credId,
                    'task': this.action.task
                };

                jQuery.post(ajaxurl, requestData, function (response) {
                    if (response.success) {
                        if (response.data) {
                            for (var key in response.data) {
                                that.fielddata[key] = '';
                                that.fields.push({ type: 'text', value: key, title: response.data[key], task: ['add_row'], required: false });
                            }
                        }
                    }

                    that.worksheetLoading = false;
                });
            }
        },
        refreshWorksheets: function () {
            if (!this.fielddata.spreadsheetId) {
                return;
            }

            this.fielddata.worksheetList = [];

            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_googlesheets_get_worksheets',
                '_nonce': adfoin.nonce,
                'spreadsheetId': this.fielddata.spreadsheetId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                that.fielddata.worksheetList = response.data;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        // For backward compatibility with existing integrations that don't have credId
        // Default to legacy_123456 which is the migrated legacy credential
        if (typeof this.fielddata.credId == 'undefined' || this.fielddata.credId === '') {
            this.fielddata.credId = 'legacy_123456';
        }

        if (typeof this.fielddata.spreadsheetId == 'undefined') {
            this.fielddata.spreadsheetId = '';
        }

        if (typeof this.fielddata.worksheetId == 'undefined') {
            this.fielddata.worksheetId = '';
        }

        if (typeof this.fielddata.worksheetName == 'undefined') {
            this.fielddata.worksheetName = '';
        }

        // Always load spreadsheets if we have a credId
        if (this.fielddata.credId) {
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_spreadsheet_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                that.fielddata.spreadsheetList = response.data;
                that.listLoading = false;
            });
        }

        if (this.fielddata.credId && this.fielddata.spreadsheetId && this.fielddata.worksheetName) {
            var that = this;
            this.worksheetLoading = true;

            var requestData = {
                'action': 'adfoin_googlesheets_get_headers',
                '_nonce': adfoin.nonce,
                'spreadsheetId': this.fielddata.spreadsheetId,
                'worksheetName': this.fielddata.worksheetName,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        for (var key in response.data) {
                            that.fields.push({ type: 'text', value: key, title: response.data[key], task: ['add_row'], required: false });
                        }
                    }
                }

                that.worksheetLoading = false;
            });
        }

        if (this.fielddata.worksheetList) {
            this.fielddata.worksheetList = JSON.parse(this.fielddata.worksheetList.replace(/\\/g, ''));
        }
    },
    watch: {},
    template: '#googlesheets-action-template'
});

Vue.component('googledrive', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            folderLoading: false,
            fields: [
                { type: 'text', value: 'fileField', title: 'File Field', task: ['upload_file'], required: true }
            ]
        };
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_googledrive_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = 'legacy_123456';
                    }
                    if (that.fielddata.credId) {
                        that.getFolders();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getFolders: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.folderList = {};
                return;
            }

            this.folderLoading = true;

            var folderData = {
                'action': 'adfoin_get_googledrive_folders',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, folderData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.folderList = response.data;
                    }
                }
                that.folderLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (!this.fielddata.folderId) {
            this.fielddata.folderId = '';
        }

        this.getData();
    },
    template: '#googledrive-action-template'
});

Vue.component('smartsheet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_smartsheet_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential or first available for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        // Look for legacy credential first
                        var legacyCred = that.credentialsList.find(function(cred) {
                            return cred.id === 'legacy_123456' || cred.title.includes('Legacy');
                        });
                        that.fielddata.credId = legacyCred ? legacyCred.id : that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getList();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_get_smartsheet_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listData, function (response) {
                that.fielddata.list = response.data;
                that.listLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            this.fieldLoading = true;
            this.fields = [];

            var listData = {
                'action': 'adfoin_get_smartsheet_fields',
                '_nonce': adfoin.nonce,
                'listId': this.fielddata.listId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                if (response.success) {
                    if (response.data) {
                        for (var key in response.data) {
                            that.fields.push({ type: 'text', value: key, title: response.data[key], task: ['add_row'], required: false });
                        }
                    }
                }

                that.fieldLoading = false;
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.listId && this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.getList();
            }
        }
    },
    template: '#smartsheet-action-template'
});

Vue.component('airtable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            baseLoading: false,
            tableLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getBases();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credData = {
                'action': 'adfoin_get_airtable_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getBases: function () {
            var that = this;
            this.baseLoading = true;

            var baseRequestData = {
                'action': 'adfoin_get_airable_bases',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, baseRequestData, function (response) {
                if (response.success) {
                    that.fielddata.bases = response.data;
                }
                that.baseLoading = false;
            });
        },
        getTables: function () {
            var that = this;
            this.tableLoading = true;

            var tableData = {
                'action': 'adfoin_get_airtable_tables',
                '_nonce': adfoin.nonce,
                'baseId': this.fielddata.baseId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, tableData, function (response) {
                if (response.success) {
                    if (response.data) {
                        var tables = response.data;
                        that.fielddata.tables = tables;
                        that.tableLoading = false;
                    }
                }
            });
        },
        getFields: function () {
            var that = this;
            this.fieldLoading = true;

            var fieldData = {
                'action': 'adfoin_get_airtable_fields',
                '_nonce': adfoin.nonce,
                'baseId': this.fielddata.baseId,
                'tableId': this.fielddata.tableId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, fieldData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fields = [];
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_row'], required: false, description: single.description });
                        });
                    }
                }

                that.fieldLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.baseId == 'undefined') {
            this.fielddata.baseId = '';
        }

        if (typeof this.fielddata.tableId == 'undefined') {
            this.fielddata.tableId = '';
        }

        this.getData();

        // Load bases for existing integrations (backward compatibility)
        if (this.fielddata.baseId && !this.fielddata.credId) {
            this.getBases();
        }

        if (this.fielddata.baseId) {
            this.getTables();

            if (this.fielddata.tableId) {
                this.getFields();
            }
        }
    },
    template: '#airtable-action-template'
});

Vue.component('dropbox', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            folderLoading: false,
            fields: [
                { type: 'text', value: 'fileField', title: 'File', task: ['upload_file'], required: true }
            ]
        };
    },
    methods: {
        getFolders: function () {
            var that = this;
            this.folderLoading = true;

            var folderData = {
                'action': 'adfoin_get_dropbox_folders',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, folderData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.folders = response.data;
                    }
                }
                that.folderLoading = false;
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.credId) this.fielddata.credId = '';
        if (!this.fielddata.folderId) this.fielddata.folderId = '';

        if (this.fielddata.credId) {
            this.getFolders();
        }
    },
    template: '#dropbox-action-template'
});


Vue.component('discord', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            serverLoading: false,
            channelLoading: false,
            fields: [
                { type: 'textarea', value: 'message', title: 'Message', task: ['send_message'], required: true }
            ]
        };
    },
    methods: {
        getServers: function () {
            var that = this;
            this.serverLoading = true;

            var serverData = {
                'action': 'adfoin_get_discord_servers',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, serverData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.servers = response.data;
                    }
                }
                that.serverLoading = false;
            });
        },
        getChannels: function () {
            var that = this;
            this.channelLoading = true;

            var channelData = {
                'action': 'adfoin_get_discord_channels',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'serverId': this.fielddata.serverId
            };

            jQuery.post(ajaxurl, channelData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.channels = response.data;
                    }
                }
                that.channelLoading = false;
            });
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.serverId == 'undefined') {
            this.fielddata.serverId = '';
        }

        if (typeof this.fielddata.channelId == 'undefined') {
            this.fielddata.channelId = '';
        }

        if (this.fielddata.credId) {
            this.getServers();
        }

        if (this.fielddata.serverId) {
            this.getChannels();
        }
    },
    template: '#discord-action-template'
});

Vue.component('fluentsupport', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            mailboxLoading: false,
            agentLoading: false,
            fields: [
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_ticket'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_ticket'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['create_ticket'], required: false },
                { type: 'text', value: 'subject', title: 'Subject', task: ['create_ticket'], required: false },
                { type: 'textarea', value: 'message', title: 'Message', task: ['create_ticket'], required: false },
            ]
        };
    },
    methods: {
        /**
         * Fetch Fluent Support mailboxes via AJAX
         */
        fetchMailboxes: function () {
            var that = this;
            this.mailboxLoading = true;

            var requestData = {
                action: 'adfoin_get_fluentsupport_mailboxes',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.mailboxes = response.data;
                } else {
                    alert(response.data.message || 'Error fetching mailboxes.');
                }
                that.mailboxLoading = false;
            });
        },

        /**
         * Fetch Fluent Support agents via AJAX
         */
        fetchAgents: function () {
            var that = this;
            this.agentLoading = true;

            var requestData = {
                action: 'adfoin_get_fluentsupport_agents',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.agents = response.data;
                } else {
                    alert(response.data.message || 'Error fetching agents.');
                }
                that.agentLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.mailboxId == 'undefined') {
            this.fielddata.mailboxId = '';
        }

        if (typeof this.fielddata.agentId == 'undefined') {
            this.fielddata.agentId = '';
        }

        this.fetchMailboxes();
        this.fetchAgents();
    },
    template: '#fluentsupport-action-template'
});


Vue.component('freshdesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            ticketFieldsLoading: false,
            fields: []
        };
    },
    methods: {
        fetchTicketFields: function () {
            var that = this;
            this.ticketFieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_freshdesk_ticket_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_ticket'], required: false, description: single.description });
                        });

                        that.ticketFieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.fetchTicketFields();
        }
    },
    template: '#freshdesk-action-template'
});

Vue.component('gistcrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            customFieldsLoading: false,
            customFields: []
        };
    },
    methods: {
        fetchCustomFields: function () {
            var that = this;
            this.customFieldsLoading = true;

            var requestData = {
                action: 'adfoin_get_gist_custom_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.customFields = response.data;
                } else {
                    alert(response.data.message || 'Error fetching custom fields.');
                }
                that.customFieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.fetchCustomFields();
    },
    template: '#gistcrm-action-template'
});

Vue.component('zohosheet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            workbookLoading: false,
            worksheetLoading: false,
            fieldLoading: false,
            fields: []
        }
    },
    methods: {
        getWorkbooks: function () {
            var that = this;
            this.workbookLoading = true;

            var workbookRequestData = {
                'action': 'adfoin_get_zohosheet_workbooks',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, workbookRequestData, function (response) {
                that.fielddata.workbooks = response.data;
                that.workbookLoading = false;
            });
        },
        getWorksheets: function () {
            var that = this;
            this.worksheetLoading = true;

            var worksheetData = {
                'action': 'adfoin_get_zohosheet_worksheets',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'workbookId': this.fielddata.workbookId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, worksheetData, function (response) {
                if (response.success) {
                    if (response.data) {
                        var worksheets = response.data;
                        that.fielddata.worksheets = worksheets;
                        that.worksheetLoading = false;
                    }
                }
            });
        },
        getFields: function () {
            var that = this;
            this.fieldLoading = true;

            var fieldData = {
                'action': 'adfoin_get_zohosheet_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'workbookId': this.fielddata.workbookId,
                'worksheetId': this.fielddata.worksheetId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, fieldData, function (response) {
                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_row'], required: false, description: single.description });
                        });
                    }
                }

                that.fieldLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.workbookId == 'undefined') {
            this.fielddata.workbookId = '';
        }

        if (typeof this.fielddata.worksheetId == 'undefined') {
            this.fielddata.worksheetId = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getWorkbooks();
        }

        if (this.fielddata.credId && this.fielddata.workbookId) {
            this.getWorksheets();

            if (this.fielddata.worksheetId) {
                this.getFields();
            }
        }
    },
    template: '#zohosheet-action-template'
});

Vue.component('pipedrive', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_pipedrive_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }

            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.duplicate == 'undefined') {
            this.fielddata.duplicate = false;
        }

        if (typeof this.fielddata.duplicate != 'undefined') {
            if (this.fielddata.duplicate == "false") {
                this.fielddata.duplicate = false;
            }
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {},
    template: '#pipedrive-action-template'
});

Vue.component('zendesksell', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            if (this.action.task === 'add_lead') {
                console.log('addLead');
                this.getLeadFields();
            }
        },
        getLeadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var requestData = {
                'action': 'adfoin_get_zendesksell_lead_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success && response.data) {
                    response.data.map(function (single) {
                        that.fields.push({
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['add_lead'],
                            required: false,
                            description: single.description
                        });
                    });
                }

                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getData();
    },
    watch: {},
    template: '#zendesksell-action-template'
});

Vue.component('zendesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'ticket_subject', title: 'Ticket Subject', task: ['create_ticket'], required: true },
                { type: 'textarea', value: 'ticket_comment', title: 'Ticket Description', task: ['create_ticket'], required: true },
                { type: 'text', value: 'requester_email', title: 'Requester Email', task: ['create_ticket'], required: false },
                { type: 'text', value: 'requester_name', title: 'Requester Name', task: ['create_ticket'], required: false },
                { type: 'text', value: 'ticket_priority', title: 'Priority', task: ['create_ticket'], required: false, description: 'Allowed: low, normal, high, urgent' },
                { type: 'text', value: 'ticket_status', title: 'Status', task: ['create_ticket'], required: false, description: 'Allowed: new, open, pending, hold, solved, closed' }
            ]
        };
    },
    created: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#zendesk-action-template'
});

Vue.component('zohobooks', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            organizationLoading: false,
            organizations: {},
            fields: [
                { type: 'text', value: 'contact_name', title: 'Contact Name', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_contact', 'upsert_customer', 'create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder', 'create_customer_payment'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['upsert_customer'], required: false },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: false },
                { type: 'text', value: 'reference_number', title: 'Reference Number', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: false },
                { type: 'text', value: 'date', title: 'Date (YYYY-MM-DD)', task: ['create_estimate', 'create_invoice', 'create_salesorder'], required: false },
                { type: 'text', value: 'due_date', title: 'Due Date (YYYY-MM-DD)', task: ['create_estimate', 'create_invoice'], required: false },
                { type: 'text', value: 'currency_code', title: 'Currency Code', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: false },
                { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON', task: ['create_estimate', 'create_invoice', 'create_recurring_invoice', 'create_salesorder'], required: true, description: 'Example: [{"item_name":"Product","rate":10,"quantity":1,"description":"Note"}]' },
                { type: 'text', value: 'item_name', title: 'Item Name', task: ['upsert_item'], required: true },
                { type: 'text', value: 'item_rate', title: 'Item Rate', task: ['upsert_item'], required: false },
                { type: 'textarea', value: 'item_description', title: 'Item Description', task: ['upsert_item'], required: false },
                { type: 'text', value: 'item_type', title: 'Item Type (goods/services)', task: ['upsert_item'], required: false },
                { type: 'text', value: 'payment_mode', title: 'Payment Mode', task: ['create_customer_payment'], required: true },
                { type: 'text', value: 'payment_amount', title: 'Payment Amount', task: ['create_customer_payment'], required: true },
                { type: 'text', value: 'payment_date', title: 'Payment Date (YYYY-MM-DD)', task: ['create_customer_payment'], required: false },
                { type: 'text', value: 'payment_reference', title: 'Payment Reference', task: ['create_customer_payment'], required: false },
                { type: 'text', value: 'invoice_id', title: 'Invoice ID (optional)', task: ['create_customer_payment'], required: false }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.organizationId === 'undefined') {
            this.$set(this.fielddata, 'organizationId', '');
        }
    },
    mounted: function () {
        if (this.fielddata.credId) {
            this.fetchOrganizations();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fetchOrganizations();
            }
        }
    },
    methods: {
        fetchOrganizations: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.organizations = {};
                this.fielddata.organizationId = '';
                return;
            }

            this.organizations = {};
            this.fielddata.organizationId = '';
            this.organizationLoading = true;

            var requestData = {
                action: 'adfoin_get_zohobooks_organizations',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success && response.data) {
                    that.organizations = response.data;
                } else {
                    that.organizations = {};
                }
                that.organizationLoading = false;
            }).fail(function () {
                that.organizations = {};
                that.organizationLoading = false;
            });
        }
    },
    template: '#zohobooks-action-template'
});

Vue.component('zohorecruit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            organizationLoading: false,
            organizations: {},
            fields: [
                { type: 'text', value: 'first_name', title: 'First Name', task: ['create_candidate'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_candidate'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['create_candidate'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_candidate'], required: false }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.organizationId === 'undefined') {
            this.$set(this.fielddata, 'organizationId', '');
        }
    },
    mounted: function () {
        if (this.fielddata.credId) {
            this.fetchOrganizations();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fetchOrganizations();
            }
        }
    },
    methods: {
        fetchOrganizations: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.organizations = {};
                this.fielddata.organizationId = '';
                return;
            }

            this.organizations = {};
            this.fielddata.organizationId = '';
            this.organizationLoading = true;

            var requestData = {
                action: 'adfoin_get_zohorecruit_organizations',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success && response.data) {
                    that.organizations = response.data;
                } else {
                    that.organizations = {};
                }
                that.organizationLoading = false;
            }).fail(function () {
                that.organizations = {};
                that.organizationLoading = false;
            });
        }
    },
    template: '#zohorecruit-action-template'
});

Vue.component('zohopeople', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'EmployeeID', title: 'Employee ID', task: ['create_employee'], required: false },
                { type: 'text', value: 'FirstName', title: 'First Name', task: ['create_employee'], required: true },
                { type: 'text', value: 'LastName', title: 'Last Name', task: ['create_employee'], required: true },
                { type: 'text', value: 'EmailID', title: 'Email', task: ['create_employee'], required: false },
                { type: 'text', value: 'Work_phone', title: 'Mobile', task: ['create_employee'], required: false }
            ]
        };
    },
    created: function () {
        var that = this;

        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.formLinkName === 'undefined' || !this.fielddata.formLinkName) {
            this.$set(this.fielddata, 'formLinkName', 'P_EmployeeView');
        }
    },
    template: '#zohopeople-action-template'
});

Vue.component('capsulecrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            ownerLoading: false,
            fieldsLoading: false,
            fields: []

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getOwnerList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_capsulecrm_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getOwnerList: function () {
            var that = this;
            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_capsulecrm_owner_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        },
        getFields: function () {
            this.fields = [];
            var that = this;
            this.fieldsLoading = true;
            var selectedObjects = [];
            if (this.fielddata.organisation__chosen) { selectedObjects.push('organisation') }
            if (this.fielddata.person__chosen) { selectedObjects.push('person') }
            if (this.fielddata.opportunity__chosen) { selectedObjects.push('opportunity') }
            if (this.fielddata.case__chosen) { selectedObjects.push('case') }
            if (this.fielddata.task__chosen) { selectedObjects.push('task') }

            var allFieldsRequestData = {
                'action': 'adfoin_get_capsulecrm_all_fields',
                '_nonce': adfoin.nonce,
                'selectedObjects': selectedObjects,
                'task': this.action.task,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_party'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();

        if (typeof this.fielddata.organisation__chosen == 'undefined') {
            this.fielddata.organisation__chosen = false;
        }

        if (typeof this.fielddata.organisation__chosen != 'undefined') {
            this.fielddata.organisation__chosen = (this.fielddata.organisation__chosen === "true");
        }

        if (typeof this.fielddata.person__chosen == 'undefined') {
            this.fielddata.person__chosen = false;
        }

        if (typeof this.fielddata.person__chosen != 'undefined') {
            this.fielddata.person__chosen = (this.fielddata.person__chosen === "true");
        }

        if (typeof this.fielddata.opportunity__chosen == 'undefined') {
            this.fielddata.opportunity__chosen = false;
        }

        if (typeof this.fielddata.opportunity__chosen != 'undefined') {
            this.fielddata.opportunity__chosen = (this.fielddata.opportunity__chosen === "true");
        }

        if (typeof this.fielddata.case__chosen == 'undefined') {
            this.fielddata.case__chosen = false;
        }

        if (typeof this.fielddata.case__chosen != 'undefined') {
            this.fielddata.case__chosen = (this.fielddata.case__chosen === "true");
        }

        if (typeof this.fielddata.task__chosen == 'undefined') {
            this.fielddata.task__chosen = false;
        }

        if (typeof this.fielddata.task__chosen != 'undefined') {
            this.fielddata.task__chosen = (this.fielddata.task__chosen === "true");
        }

        if (this.fielddata.organisation__chosen || this.fielddata.person__chosen || this.fielddata.opportunity__chosen || this.fielddata.case__chosen || this.fielddata.task__chosen) {
            this.getFields();
        }


    },
    watch: {},
    template: '#capsulecrm-action-template'
});

Vue.component('flowlu', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            ownerLoading: false,
            fieldsLoading: false,
            fields: []

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getOwnerList();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_flowlu_credentials',
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getOwnerList: function () {
            if (!this.fielddata.credId) return;

            var that = this;
            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_flowlu_owner_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        },
        getFields: function () {
            if (!this.fielddata.credId) return;

            this.fields = [];
            var that = this;
            this.fieldsLoading = true;
            var selectedObjects = [];
            if (this.fielddata.organization__chosen) { selectedObjects.push('organization') }
            if (this.fielddata.contact__chosen) { selectedObjects.push('contact') }
            if (this.fielddata.opportunity__chosen) { selectedObjects.push('opportunity') }

            var allFieldsRequestData = {
                'action': 'adfoin_get_flowlu_all_fields',
                '_nonce': adfoin.nonce,
                'selectedObjects': selectedObjects,
                'task': this.action.task,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_record'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        if (typeof this.fielddata.organization__chosen == 'undefined') {
            this.fielddata.organization__chosen = false;
        }

        if (typeof this.fielddata.organization__chosen != 'undefined') {
            this.fielddata.organization__chosen = (this.fielddata.organization__chosen === "true");
        }

        if (typeof this.fielddata.contact__chosen == 'undefined') {
            this.fielddata.contact__chosen = false;
        }

        if (typeof this.fielddata.contact__chosen != 'undefined') {
            this.fielddata.contact__chosen = (this.fielddata.contact__chosen === "true");
        }

        if (typeof this.fielddata.opportunity__chosen == 'undefined') {
            this.fielddata.opportunity__chosen = false;
        }

        if (typeof this.fielddata.opportunity__chosen != 'undefined') {
            this.fielddata.opportunity__chosen = (this.fielddata.opportunity__chosen === "true");
        }

        this.getData();

        if (this.fielddata.organization__chosen || this.fielddata.contact__chosen || this.fielddata.opportunity__chosen) {
            this.getFields();
        }
    },
    watch: {},
    template: '#flowlu-action-template'
});


Vue.component('ragic', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            fields: [
                { type: 'text', value: 'account_name', title: 'Account Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'tab', title: 'Tab', task: ['subscribe'], required: false },
                { type: 'text', value: 'sheet_id', title: 'Sheet ID', task: ['subscribe'], required: false },
                { type: 'text', value: 'field1', title: 'Field 1', task: ['subscribe'], required: false },
                { type: 'text', value: 'field2', title: 'Field 2', task: ['subscribe'], required: false },
                { type: 'text', value: 'field3', title: 'Field 3', task: ['subscribe'], required: false },
                { type: 'text', value: 'field4', title: 'Field 4', task: ['subscribe'], required: false },
                { type: 'text', value: 'field5', title: 'Field 5', task: ['subscribe'], required: false },
                { type: 'text', value: 'field6', title: 'Field 6', task: ['subscribe'], required: false },
                { type: 'text', value: 'field7', title: 'Field 7', task: ['subscribe'], required: false },
                { type: 'text', value: 'field8', title: 'Field 8', task: ['subscribe'], required: false },
                { type: 'text', value: 'field9', title: 'Field 9', task: ['subscribe'], required: false },
                { type: 'text', value: 'field10', title: 'Field 10', task: ['subscribe'], required: false },
                { type: 'text', value: 'field11', title: 'Field 11', task: ['subscribe'], required: false },
                { type: 'text', value: 'field12', title: 'Field 12', task: ['subscribe'], required: false },
                { type: 'text', value: 'field13', title: 'Field 13', task: ['subscribe'], required: false },
                { type: 'text', value: 'field14', title: 'Field 14', task: ['subscribe'], required: false },
                { type: 'text', value: 'field15', title: 'Field 15', task: ['subscribe'], required: false },
                { type: 'text', value: 'field16', title: 'Field 16', task: ['subscribe'], required: false },
                { type: 'text', value: 'field17', title: 'Field 17', task: ['subscribe'], required: false },
                { type: 'text', value: 'field18', title: 'Field 18', task: ['subscribe'], required: false },
                { type: 'text', value: 'field19', title: 'Field 19', task: ['subscribe'], required: false },
                { type: 'text', value: 'field20', title: 'Field 20', task: ['subscribe'], required: false },
            ]
        }
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_ragic_credentials_list',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
            });
        }
    },
    mounted: function () {
        // Initialize credId - default to legacy for existing integrations
        if (typeof this.fielddata.credId == 'undefined') {
            this.$set(this.fielddata, 'credId', 'legacy_123456');
        }

        // Fetch credentials list
        this.fetchCredentialsList();
    },
    template: '#ragic-action-template'
});

Vue.component('salesflare', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            ownerLoading: false,
            fieldsLoading: false,
            credentialsList: [],
            fields: []

        }
    },
    methods: {
        getOwnerList: function () {
            var that = this;
            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_salesflare_owner_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        },
        getFields: function () {
            this.fields = [];
            var that = this;
            this.fieldsLoading = true;
            var selectedObjects = [];
            if (this.fielddata.account__chosen) { selectedObjects.push('account') }
            if (this.fielddata.contact__chosen) { selectedObjects.push('contact') }
            if (this.fielddata.opportunity__chosen) { selectedObjects.push('opportunity') }
            if (this.fielddata.task__chosen) { selectedObjects.push('task') }

            var allFieldsRequestData = {
                'action': 'adfoin_get_salesflare_all_fields',
                '_nonce': adfoin.nonce,
                'selectedObjects': selectedObjects,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_data'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
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

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_salesflare_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load owner list if credential is selected
                if (that.fielddata.credId) {
                    that.getOwnerList();
                }
            }
        });

        if (typeof this.fielddata.account__chosen == 'undefined') {
            this.fielddata.account__chosen = false;
        }

        if (typeof this.fielddata.account__chosen != 'undefined') {
            this.fielddata.account__chosen = (this.fielddata.account__chosen === "true");
        }

        if (typeof this.fielddata.contact__chosen == 'undefined') {
            this.fielddata.contact__chosen = false;
        }

        if (typeof this.fielddata.contact__chosen != 'undefined') {
            this.fielddata.person__chosen = (this.fielddata.contact__chosen === "true");
        }

        if (typeof this.fielddata.opportunity__chosen == 'undefined') {
            this.fielddata.opportunity__chosen = false;
        }

        if (typeof this.fielddata.opportunity__chosen != 'undefined') {
            this.fielddata.opportunity__chosen = (this.fielddata.opportunity__chosen === "true");
        }

        if (typeof this.fielddata.task__chosen == 'undefined') {
            this.fielddata.task__chosen = false;
        }

        if (typeof this.fielddata.task__chosen != 'undefined') {
            this.fielddata.task__chosen = (this.fielddata.task__chosen === "true");
        }

        if (this.fielddata.account__chosen || this.fielddata.contact__chosen || this.fielddata.opportunity__chosen || this.fielddata.task__chosen) {
            this.getFields();
        }


    },
    watch: {},
    template: '#salesflare-action-template'
});

Vue.component('vtiger', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            ownerLoading: false,
            fieldsLoading: false,
            credentialsList: [],
            fields: []

        }
    },
    methods: {
        getOwnerList: function () {
            var that = this;
            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_vtiger_owner_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        },
        getFields: function () {
            this.fields = [];
            var that = this;
            this.fieldsLoading = true;
            var selectedObjects = [];
            if (this.fielddata.organization__chosen) { selectedObjects.push('organization') }
            if (this.fielddata.contact__chosen) { selectedObjects.push('contact') }
            if (this.fielddata.deal__chosen) { selectedObjects.push('deal') }
            // if(this.fielddata.case__chosen) {selectedObjects.push('case')}
            // if(this.fielddata.task__chosen) {selectedObjects.push('task')}

            var allFieldsRequestData = {
                'action': 'adfoin_get_vtiger_all_fields',
                '_nonce': adfoin.nonce,
                'selectedObjects': selectedObjects,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_fields'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
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

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        // Load credentials list
        var credentialsData = {
            'action': 'adfoin_get_vtiger_credentials_list',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, credentialsData, function (response) {
            if (response.success) {
                that.credentialsList = response.data;
                
                // Auto-select first credential if none selected and credentials exist
                if (!that.fielddata.credId && that.credentialsList.length > 0) {
                    that.fielddata.credId = that.credentialsList[0].id;
                }
                
                // Load owner list if credential is selected
                if (that.fielddata.credId) {
                    that.getOwnerList();
                }
            }
        });

        if (typeof this.fielddata.organization__chosen == 'undefined') {
            this.fielddata.organization__chosen = false;
        }

        if (typeof this.fielddata.organization__chosen != 'undefined') {
            this.fielddata.organization__chosen = (this.fielddata.organization__chosen === "true");
        }

        if (typeof this.fielddata.contact__chosen == 'undefined') {
            this.fielddata.contact__chosen = false;
        }

        if (typeof this.fielddata.contact__chosen != 'undefined') {
            this.fielddata.contact__chosen = (this.fielddata.contact__chosen === "true");
        }

        if (typeof this.fielddata.deal__chosen == 'undefined') {
            this.fielddata.deal__chosen = false;
        }

        if (typeof this.fielddata.deal__chosen != 'undefined') {
            this.fielddata.deal__chosen = (this.fielddata.deal__chosen === "true");
        }

        // if (typeof this.fielddata.case__chosen == 'undefined') {
        //     this.fielddata.case__chosen = false;
        // }

        // if (typeof this.fielddata.case__chosen != 'undefined') {
        //     this.fielddata.case__chosen = (this.fielddata.case__chosen === "true");
        // }

        // if (typeof this.fielddata.task__chosen == 'undefined') {
        //     this.fielddata.task__chosen = false;
        // }

        // if (typeof this.fielddata.task__chosen != 'undefined') {
        //     this.fielddata.task__chosen = (this.fielddata.task__chosen === "true");
        // }

        if (this.fielddata.organization__chosen || this.fielddata.contact__chosen || this.fielddata.deal__chosen || this.fielddata.case__chosen || this.fielddata.task__chosen) {
            this.getFields();
        }


    },
    watch: {},
    template: '#vtiger-action-template'
});

Vue.component('hubspot', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            contactLoading: false,
            fields: []

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_hubspot_credentials',
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getFields: function () {
            if (!this.fielddata.credId) return;

            this.fields = [];
            var that = this;

            var contactRequestData = {
                'action': 'adfoin_get_hubspot_contact_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, contactRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_contact'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        this.getData();
    },
    watch: {},
    template: '#hubspot-action-template'
});

Vue.component('autopilotnew', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_autopilotnew_credentials_list',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    // Default to legacy credential or first available for existing integrations
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        // Look for legacy credential first
                        var legacyCred = that.credentialsList.find(function(cred) {
                            return cred.id === 'legacy_123456' || cred.title.includes('Legacy');
                        });
                        that.fielddata.credId = legacyCred ? legacyCred.id : that.credentialsList[0].id;
                    }
                }
                that.credentialLoading = false;
            });
        }
    },
    created: function () {
        this.getData();
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#autopilotnew-action-template'
});

Vue.component('omnisend', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'city', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'state', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'zip', title: 'ZIP', task: ['add_contact'], required: false },
                { type: 'text', value: 'country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'birthday', title: 'Birthday', task: ['add_contact'], required: false, description: 'required format YYYY-MM-DD' },
                { type: 'text', value: 'gender', title: 'Gender', task: ['add_contact'], required: false, description: 'e.g. Male, Female' }
            ]

        }
    },
    methods: {
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#omnisend-action-template'
});

Vue.component('mailbluster', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'fullName', title: 'Full Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'timezone', title: 'Timezone', task: ['add_contact'], required: false },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['add_contact'], required: false },
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_mailbluster_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.doptin == 'undefined') {
            this.fielddata.doptin = false;
        }

        if (typeof this.fielddata.doptin != 'undefined') {
            if (this.fielddata.doptin == "false") {
                this.fielddata.doptin = false;
            }
        }

        this.getData();
    },
    template: '#mailbluster-action-template'
});

Vue.component('close', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            ownerLoading: false,
            fields: []

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_close_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getFields: function () {
            var that = this;

            var allRequestData = {
                'action': 'adfoin_get_close_all_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, allRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_lead'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();
    },
    template: '#close-action-template'
});

Vue.component('insightly', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            ownerLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getOwnerList();
            this.getFields();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_insightly_credentials',
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getOwnerList: function () {
            if (!this.fielddata.credId) return;

            var that = this;
            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_insightly_owner_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        },
        getFields: function () {
            if (!this.fielddata.credId) return;

            this.fields = [];
            var that = this;

            var allRequestData = {
                'action': 'adfoin_get_insightly_all_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, allRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_contact'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();
    },
    watch: {},
    template: '#insightly-action-template'
});

Vue.component('copper', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            ownerLoading: false,
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getOwners();
            this.getFields();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credRequestData = {
                'action': 'adfoin_get_copper_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getOwners: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.ownerLoading = true;

            var ownerRequestData = {
                'action': 'adfoin_get_copper_owner_list',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, ownerRequestData, function (response) {
                that.fielddata.ownerList = response.data;
                that.ownerLoading = false;
            });
        },
        getFields: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.fieldsLoading = true;
            this.fields = [];

            var allRequestData = {
                'action': 'adfoin_get_copper_all_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, allRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_contact'], required: false, description: single.description });
                        });
                    }
                }
                that.fieldsLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        this.getData();
    },
    watch: {},
    template: '#copper-action-template'
});

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

Vue.component('freshsales', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            fields: []
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getFields();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_freshsales_credentials',
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                } else {
                    that.credentialsList = [];
                }
                that.credentialLoading = false;
            }).fail(function () {
                that.credentialLoading = false;
            });
        },
        getFields: function () {
            if (!this.fielddata.credId) return;

            this.fields = [];
            var that = this;

            var accountRequestData = {
                'action': 'adfoin_get_freshsales_account_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, accountRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });
                    }
                }
            });

            var contactRequestData = {
                'action': 'adfoin_get_freshsales_contact_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, contactRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });
                    }
                }
            });

            var dealRequestData = {
                'action': 'adfoin_get_freshsales_deal_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, dealRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['add_ocdna'], required: false, description: single.description });
                        });
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        this.getData();
    },
    template: '#freshsales-action-template'
});

Vue.component('campaignmonitor', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            accountLoading: false,
            listLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['create_subscriber'], required: true },
                { type: 'text', value: 'name', title: 'Name', task: ['create_subscriber'], required: false }
            ]
        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getAccounts();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credData = {
                'action': 'adfoin_get_campaignmonitor_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getAccounts: function () {
            var that = this;
            this.accountLoading = true;

            var accountRequestData = {
                'action': 'adfoin_get_campaignmonitor_accounts',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, accountRequestData, function (response) {
                if (response.success) {
                    that.fielddata.accounts = response.data;
                }
                that.accountLoading = false;
            });
        },
        getList: function () {
            var that = this;
            this.listLoading = true;

            var listData = {
                'action': 'adfoin_get_campaignmonitor_list',
                '_nonce': adfoin.nonce,
                'accountId': this.fielddata.accountId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                var list = response.data;
                that.fielddata.list = list;
                that.listLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.accountId == 'undefined') {
            this.fielddata.accountId = '';
        }

        if (typeof this.fielddata.listId == 'undefined') {
            this.fielddata.listId = '';
        }

        if (typeof this.fielddata.email == 'undefined') {
            this.fielddata.email = '';
        }

        if (typeof this.fielddata.name == 'undefined') {
            this.fielddata.name = '';
        }

        this.getData();

        // Load accounts for existing integrations (backward compatibility)
        if (this.fielddata.accountId && !this.fielddata.credId) {
            this.getAccounts();
        }

        if (this.fielddata.accountId) {
            this.getList();
        }
    },
    template: '#campaignmonitor-action-template'
});

Vue.component('clinchpad', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            userLoading: false,
            pipelineLoading: false,
            stageLoading: false,
            fields: [
                { type: 'text', value: 'lead', title: 'Lead Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'value', title: 'Lead Value', task: ['add_contact'], required: false },
                { type: 'text', value: 'note', title: 'Note', task: ['add_contact'], required: false },
                { type: 'text', value: 'name', title: 'Contact Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'designation', title: 'Designation', task: ['add_contact'], required: false },
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'address', title: 'Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'organization', title: 'Organization', task: ['add_contact'], required: false },
                { type: 'text', value: 'org_email', title: 'Organization Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'org_phone', title: 'Organization Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'website', title: 'Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'org_address', title: 'Organization Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'product_name', title: 'Product Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'product_price', title: 'Product Price', task: ['add_contact'], required: false }
            ]

        }
    },
    methods: {
        getData: function () {
            this.getCredentials();
            this.getUser();
            this.getPipeline();
        },
        getCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            var credentialRequestData = {
                'action': 'adfoin_get_clinchpad_credentials',
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, credentialRequestData, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                }
                that.credentialLoading = false;
            });
        },
        getUser: function () {
            var that = this;
            this.userLoading = true;

            var userRequestData = {
                'action': 'adfoin_get_clinchpad_user',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, userRequestData, function (response) {
                that.fielddata.userList = response.data;
                that.userLoading = false;
            });
        },
        getPipeline: function () {
            var that = this;
            this.pipelineLoading = true;

            var pipelineRequestData = {
                'action': 'adfoin_get_clinchpad_pipeline',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, pipelineRequestData, function (response) {
                that.fielddata.pipelineList = response.data;
                that.pipelineLoading = false;
            });
        },
        getStage: function () {
            var that = this;
            this.stageLoading = true;

            var stageData = {
                'action': 'adfoin_get_clinchpad_stage',
                '_nonce': adfoin.nonce,
                'pipelineId': this.fielddata.pipelineId,
                'task': this.action.task,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, stageData, function (response) {
                var stages = response.data;
                that.fielddata.stages = stages;
                that.stageLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.userId == 'undefined') {
            this.fielddata.userId = '';
        }

        if (typeof this.fielddata.stageId == 'undefined') {
            this.fielddata.stageId = '';
        }

        if (typeof this.fielddata.pipelineId == 'undefined') {
            this.fielddata.pipelineId = '';
        }

        this.getData();

        if (this.fielddata.pipelineId) {
            this.getStage();
        }
    },
    template: '#clinchpad-action-template'
});

Vue.component('intercom', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;
            this.fields = [];

            var allFieldsRequestData = {
                'action': 'adfoin_get_intercom_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_contact'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#intercom-action-template'
});

Vue.component('affiliatewp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                add_affiliate: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['add_affiliate'], description: 'Existing WordPress user ID.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['add_affiliate'], description: 'If user ID is not provided a new user will be created from this email.' },
                    { type: 'text', value: 'user_name', title: 'Username', task: ['add_affiliate'], description: 'Optional username to associate with the affiliate.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['add_affiliate'], description: 'Accepted values: active, pending, rejected.' },
                    { type: 'text', value: 'rate', title: 'Rate', task: ['add_affiliate'], description: 'Affiliate specific rate (leave empty to use default).' },
                    { type: 'text', value: 'rate_type', title: 'Rate Type', task: ['add_affiliate'], description: 'Accepted values: percentage, flat.' },
                    { type: 'text', value: 'flat_rate_basis', title: 'Flat Rate Basis', task: ['add_affiliate'], description: 'Optional product type used for flat rates.' },
                    { type: 'text', value: 'payment_email', title: 'Payment Email', task: ['add_affiliate'] },
                    { type: 'text', value: 'website_url', title: 'Website URL', task: ['add_affiliate'] },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['add_affiliate'], description: 'Optional internal notes.' },
                    { type: 'text', value: 'date_registered', title: 'Date Registered', task: ['add_affiliate'], description: 'Format: YYYY-MM-DD HH:MM:SS' },
                    { type: 'text', value: 'dynamic_coupon', title: 'Dynamic Coupon', task: ['add_affiliate'], description: 'Use yes/true/1 to enable dynamic coupon creation.' },
                    { type: 'text', value: 'registration_method', title: 'Registration Method', task: ['add_affiliate'] },
                    { type: 'text', value: 'registration_url', title: 'Registration URL', task: ['add_affiliate'] }
                ],
                add_referral: [
                    { type: 'text', value: 'affiliate_id', title: 'Affiliate ID', task: ['add_referral'], description: 'Direct affiliate ID. Optional if user ID / username provided.' },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['add_referral'], description: 'Used to locate the affiliate when ID is unknown.' },
                    { type: 'text', value: 'user_name', title: 'Username', task: ['add_referral'], description: 'Affiliate username fallback lookup.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['add_referral'] },
                    { type: 'text', value: 'order_total', title: 'Order Total', task: ['add_referral'] },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['add_referral'], description: 'Order or transaction reference.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['add_referral'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['add_referral'], description: 'Accepted values: pending, unpaid, paid, rejected.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['add_referral'], description: 'Currency code (e.g. USD).' },
                    { type: 'text', value: 'campaign', title: 'Campaign', task: ['add_referral'] },
                    { type: 'text', value: 'context', title: 'Context', task: ['add_referral'] },
                    { type: 'text', value: 'custom', title: 'Custom Data', task: ['add_referral'], description: 'Plain text or JSON string.' },
                    { type: 'text', value: 'products', title: 'Products', task: ['add_referral'], description: 'Optionally pass JSON encoded product data.' },
                    { type: 'text', value: 'parent_id', title: 'Parent Referral ID', task: ['add_referral'] },
                    { type: 'text', value: 'visit_id', title: 'Visit ID', task: ['add_referral'] },
                    { type: 'text', value: 'type', title: 'Referral Type', task: ['add_referral'] },
                    { type: 'text', value: 'date', title: 'Referral Date', task: ['add_referral'], description: 'Format: YYYY-MM-DD HH:MM:SS' },
                    { type: 'text', value: 'flag', title: 'Flag', task: ['add_referral'], description: 'Optional internal flag.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#affiliatewp-action-template'
});

Vue.component('appointmenthourbooking', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_booking: [
                    { type: 'text', value: 'calendar_id', title: 'Calendar ID', task: ['create_booking'], description: 'Numeric ID of the calendar/form.' },
                    { type: 'text', value: 'service_field', title: 'Service Field Name', task: ['create_booking'], description: 'Appointment field name (default fieldname1).' },
                    { type: 'text', value: 'service_index', title: 'Service Index', task: ['create_booking'], description: 'Index of the service option (starting at 0).' },
                    { type: 'text', value: 'service_name', title: 'Service Name', task: ['create_booking'] },
                    { type: 'text', value: 'service_duration', title: 'Service Duration (minutes)', task: ['create_booking'] },
                    { type: 'text', value: 'service_price', title: 'Service Price', task: ['create_booking'] },
                    { type: 'text', value: 'service_id', title: 'Service ID', task: ['create_booking'], description: 'Optional service identifier.' },
                    { type: 'text', value: 'date', title: 'Booking Date', task: ['create_booking'], description: 'Expected format YYYY-MM-DD.' },
                    { type: 'text', value: 'start_time', title: 'Start Time', task: ['create_booking'], description: 'Expected format HH:MM (24-hour).' },
                    { type: 'text', value: 'end_time', title: 'End Time', task: ['create_booking'], description: 'Expected format HH:MM (24-hour).' },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['create_booking'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_booking'], description: 'Approved by default. Examples: Pending, Cancelled.' },
                    { type: 'text', value: 'customer_email', title: 'Customer Email', task: ['create_booking'] },
                    { type: 'text', value: 'customer_name', title: 'Customer Name', task: ['create_booking'] },
                    { type: 'text', value: 'customer_phone', title: 'Customer Phone', task: ['create_booking'] },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['create_booking'] }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#appointmenthourbooking-action-template'
});

Vue.component('charitable', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_donation: [
                    { type: 'text', value: 'campaign_id', title: 'Campaign ID', task: ['create_donation'], description: 'Required. ID of the campaign receiving the donation.' },
                    { type: 'text', value: 'campaign_name', title: 'Campaign Name', task: ['create_donation'], description: 'Optional override campaign name.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['create_donation'], description: 'Required. Donation amount.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_donation'], description: 'Optional currency code (defaults to site currency).' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_donation'], description: 'Donation status, e.g. charitable-completed.' },
                    { type: 'text', value: 'gateway', title: 'Gateway', task: ['create_donation'], description: 'Payment gateway slug (defaults to manual).' },
                    { type: 'text', value: 'donation_key', title: 'Donation Key', task: ['create_donation'] },
                    { type: 'text', value: 'donation_note', title: 'Donation Note', task: ['create_donation'] },
                    { type: 'text', value: 'log_note', title: 'Log Note', task: ['create_donation'] },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_donation'] },
                    { type: 'text', value: 'donor_id', title: 'Donor ID', task: ['create_donation'] },
                    { type: 'text', value: 'donor_email', title: 'Donor Email', task: ['create_donation'] },
                    { type: 'text', value: 'donor_first_name', title: 'Donor First Name', task: ['create_donation'] },
                    { type: 'text', value: 'donor_last_name', title: 'Donor Last Name', task: ['create_donation'] },
                    { type: 'text', value: 'donor_company', title: 'Donor Company', task: ['create_donation'] },
                    { type: 'text', value: 'donor_address', title: 'Donor Address', task: ['create_donation'] },
                    { type: 'text', value: 'donor_address_2', title: 'Donor Address Line 2', task: ['create_donation'] },
                    { type: 'text', value: 'donor_city', title: 'Donor City', task: ['create_donation'] },
                    { type: 'text', value: 'donor_state', title: 'Donor State', task: ['create_donation'] },
                    { type: 'text', value: 'donor_postcode', title: 'Donor Postcode', task: ['create_donation'] },
                    { type: 'text', value: 'donor_country', title: 'Donor Country', task: ['create_donation'] },
                    { type: 'text', value: 'donor_phone', title: 'Donor Phone', task: ['create_donation'] },
                    { type: 'text', value: 'contact_consent', title: 'Contact Consent', task: ['create_donation'], description: 'Yes/No to mark donor contact consent.' },
                    { type: 'text', value: 'anonymous', title: 'Anonymous Donation', task: ['create_donation'], description: 'Yes/No to mark donation anonymous.' },
                    { type: 'text', value: 'donation_plan', title: 'Donation Plan ID', task: ['create_donation'] },
                    { type: 'text', value: 'date_gmt', title: 'Donation Date (GMT)', task: ['create_donation'], description: 'Optional date-time in Y-m-d H:i:s format.' },
                    { type: 'text', value: 'transaction_id', title: 'Gateway Transaction ID', task: ['create_donation'] },
                    { type: 'text', value: 'payment_id', title: 'Gateway Payment ID', task: ['create_donation'] },
                    { type: 'text', value: 'transaction_url', title: 'Transaction URL', task: ['create_donation'] },
                    { type: 'text', value: 'receipt_url', title: 'Receipt URL', task: ['create_donation'] },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_donation'], description: 'Optional JSON string for additional meta, e.g. {"source":"API"}.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#charitable-action-template'
});

Vue.component('fluentaffiliate', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_affiliate: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_affiliate'], description: 'Existing WordPress user ID.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['create_affiliate'], description: 'Used to locate/create the user.' },
                    { type: 'text', value: 'user_login', title: 'Username', task: ['create_affiliate'], description: 'Optional username when creating a new user.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_affiliate'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_affiliate'] },
                    { type: 'text', value: 'display_name', title: 'Display Name', task: ['create_affiliate'] },
                    { type: 'text', value: 'user_url', title: 'Website URL', task: ['create_affiliate'] },
                    { type: 'text', value: 'role', title: 'User Role', task: ['create_affiliate'], description: 'Role for newly created user (e.g. subscriber).' },
                    { type: 'text', value: 'status', title: 'Affiliate Status', task: ['create_affiliate'], description: 'pending, active, or inactive.' },
                    { type: 'text', value: 'rate_type', title: 'Rate Type', task: ['create_affiliate'], description: 'default, group, flat, or percentage.' },
                    { type: 'text', value: 'rate', title: 'Rate', task: ['create_affiliate'], description: 'Required when rate type is flat or percentage.' },
                    { type: 'text', value: 'group_id', title: 'Group ID', task: ['create_affiliate'], description: 'Required when rate type is group.' },
                    { type: 'text', value: 'payment_email', title: 'Payment Email', task: ['create_affiliate'] },
                    { type: 'text', value: 'note', title: 'Note', task: ['create_affiliate'] },
                    { type: 'text', value: 'custom_param', title: 'Custom Param', task: ['create_affiliate'] },
                    { type: 'text', value: 'settings_disable_new_ref_email', title: 'Disable New Referral Email', task: ['create_affiliate'], description: 'Yes/No to disable referral notifications.' }
                ],
                create_referral: [
                    { type: 'text', value: 'affiliate_id', title: 'Affiliate ID', task: ['create_referral'], description: 'Required affiliate ID.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['create_referral'], description: 'Required referral amount.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_referral'], description: 'unpaid, pending, or rejected.' },
                    { type: 'text', value: 'type', title: 'Type', task: ['create_referral'], description: 'sale or opt_in.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_referral'] },
                    { type: 'text', value: 'provider', title: 'Provider', task: ['create_referral'], description: 'Defaults to manual.' },
                    { type: 'text', value: 'provider_id', title: 'Provider ID', task: ['create_referral'] },
                    { type: 'text', value: 'provider_sub_id', title: 'Provider Sub ID', task: ['create_referral'] },
                    { type: 'text', value: 'order_total', title: 'Order Total', task: ['create_referral'] },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_referral'] },
                    { type: 'text', value: 'utm_campaign', title: 'UTM Campaign', task: ['create_referral'] },
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_referral'] },
                    { type: 'text', value: 'visit_id', title: 'Visit ID', task: ['create_referral'] },
                    { type: 'text', value: 'parent_id', title: 'Parent Referral ID', task: ['create_referral'] },
                    { type: 'text', value: 'products', title: 'Products (JSON)', task: ['create_referral'], description: 'Optional JSON encoded products array.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_referral'], description: 'Optional JSON encoded settings.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#fluentaffiliate-action-template'
});

Vue.component('fluentboards', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_board: [
                    { type: 'text', value: 'title', title: 'Title', task: ['create_board'], description: 'Required board title.' },
                    { type: 'text', value: 'type', title: 'Type', task: ['create_board'], description: 'Optional board type (to-do or roadmap).' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_board'] },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_board'] },
                    { type: 'text', value: 'background', title: 'Background', task: ['create_board'], description: 'Optional background identifier/URL.' },
                    { type: 'text', value: 'created_by', title: 'Created By (User ID)', task: ['create_board'], description: 'User ID to assign as board creator.' }
                ],
                create_task: [
                    { type: 'text', value: 'board_id', title: 'Board ID', task: ['create_task'], description: 'Required board ID.' },
                    { type: 'text', value: 'stage_id', title: 'Stage ID', task: ['create_task'], description: 'Required stage ID (or use Stage Name field).' },
                    { type: 'text', value: 'stage_name', title: 'Stage Name', task: ['create_task'], description: 'Optional stage name fallback.' },
                    { type: 'text', value: 'title', title: 'Title', task: ['create_task'], description: 'Required task title.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_task'] },
                    { type: 'text', value: 'priority', title: 'Priority', task: ['create_task'], description: 'low, medium, high.' },
                    { type: 'text', value: 'crm_contact_id', title: 'CRM Contact ID', task: ['create_task'] },
                    { type: 'text', value: 'due_at', title: 'Due At', task: ['create_task'], description: 'Date/time string.' },
                    { type: 'text', value: 'started_at', title: 'Started At', task: ['create_task'] },
                    { type: 'text', value: 'type', title: 'Task Type', task: ['create_task'] },
                    { type: 'text', value: 'scope', title: 'Scope', task: ['create_task'] },
                    { type: 'text', value: 'source', title: 'Source', task: ['create_task'] },
                    { type: 'text', value: 'reminder_type', title: 'Reminder Type', task: ['create_task'] },
                    { type: 'text', value: 'remind_at', title: 'Remind At', task: ['create_task'] },
                    { type: 'text', value: 'is_template', title: 'Is Template', task: ['create_task'], description: 'yes to mark as template.' },
                    { type: 'text', value: 'assignee_ids', title: 'Assignee IDs', task: ['create_task'], description: 'Comma separated user IDs.' },
                    { type: 'text', value: 'label_ids', title: 'Label IDs', task: ['create_task'], description: 'Comma separated label IDs.' },
                    { type: 'text', value: 'watcher_ids', title: 'Watcher IDs', task: ['create_task'], description: 'Comma separated watcher user IDs.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_task'], description: 'Optional JSON settings payload.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#fluentboards-action-template'
});

Vue.component('fluentcommunity', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_space: [
                    { type: 'text', value: 'title', title: 'Title', task: ['create_space'], required: true, description: 'Required space title.' },
                    { type: 'text', value: 'privacy', title: 'Privacy', task: ['create_space'], required: true, description: 'Privacy level must be public, private, or secret.' },
                    { type: 'text', value: 'slug', title: 'Slug', task: ['create_space'], description: 'Optional slug; defaults from the title when empty.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_space'] },
                    { type: 'text', value: 'parent_id', title: 'Parent Group ID', task: ['create_space'], description: 'Optional parent space group ID.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_space'], description: 'Optional JSON settings following Fluent Community schema.' }
                ],
                invite_member: [
                    { type: 'text', value: 'space_id', title: 'Space ID', task: ['invite_member'], required: true, description: 'Numeric ID of the target space.' },
                    { type: 'text', value: 'user_id', title: 'Inviter User ID', task: ['invite_member'], required: true, description: 'Existing user ID that sends the invitation.' },
                    { type: 'text', value: 'invitee_email', title: 'Invitee Email', task: ['invite_member'], required: true, description: 'Email address of the member to invite.' },
                    { type: 'text', value: 'invitee_name', title: 'Invitee Name', task: ['invite_member'], description: 'Optional display name for the invitee.' }
                ],
                create_space_group: [
                    { type: 'text', value: 'title', title: 'Group Title', task: ['create_space_group'], required: true, description: 'Required group title.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_space_group'] },
                    { type: 'text', value: 'parent_id', title: 'Parent Group ID', task: ['create_space_group'], description: 'Optional parent group ID for nesting.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#fluentcommunity-action-template'
});

Vue.component('gamipress', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                award_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['award_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['award_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['award_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'points', title: 'Points', task: ['award_points'], required: true, description: 'Required numeric value to award.' },
                    { type: 'text', value: 'points_type', title: 'Points Type Slug', task: ['award_points'], required: true, description: 'Required GamiPress points type slug.' },
                    { type: 'text', value: 'reason', title: 'Reason', task: ['award_points'], description: 'Optional award reason stored in the log.' },
                    { type: 'text', value: 'log_type', title: 'Log Type', task: ['award_points'], description: 'Optional log type identifier.' },
                    { type: 'text', value: 'admin_id', title: 'Admin User ID', task: ['award_points'], description: 'Optional admin/user performing the award.' },
                    { type: 'text', value: 'achievement_id', title: 'Related Achievement ID', task: ['award_points'], description: 'Optional achievement ID to associate with the award.' }
                ],
                deduct_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['deduct_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['deduct_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['deduct_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'points', title: 'Points', task: ['deduct_points'], required: true, description: 'Required positive numeric value to deduct.' },
                    { type: 'text', value: 'points_type', title: 'Points Type Slug', task: ['deduct_points'], required: true, description: 'Required GamiPress points type slug.' },
                    { type: 'text', value: 'reason', title: 'Reason', task: ['deduct_points'], description: 'Optional deduction reason stored in the log.' },
                    { type: 'text', value: 'log_type', title: 'Log Type', task: ['deduct_points'], description: 'Optional log type identifier.' },
                    { type: 'text', value: 'admin_id', title: 'Admin User ID', task: ['deduct_points'], description: 'Optional admin/user performing the deduction.' },
                    { type: 'text', value: 'achievement_id', title: 'Related Achievement ID', task: ['deduct_points'], description: 'Optional achievement ID to associate with the deduction.' }
                ],
                award_achievement: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['award_achievement'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['award_achievement'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['award_achievement'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'achievement_id', title: 'Achievement ID', task: ['award_achievement'], required: true, description: 'Required achievement, step, or rank post ID.' },
                    { type: 'text', value: 'admin_id', title: 'Admin User ID', task: ['award_achievement'], description: 'Optional admin/user performing the award.' },
                    { type: 'text', value: 'trigger', title: 'Trigger', task: ['award_achievement'], description: 'Optional trigger string sent to hooks.' },
                    { type: 'text', value: 'site_id', title: 'Site ID', task: ['award_achievement'], description: 'Optional site/blog ID for multisite use.' },
                    { type: 'text', value: 'args_json', title: 'Args (JSON)', task: ['award_achievement'], description: 'Optional JSON object of extra arguments.' }
                ],
                revoke_achievement: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['revoke_achievement'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['revoke_achievement'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['revoke_achievement'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'achievement_id', title: 'Achievement ID', task: ['revoke_achievement'], required: true, description: 'Required achievement, step, or rank post ID to revoke.' },
                    { type: 'text', value: 'earning_id', title: 'Earning ID', task: ['revoke_achievement'], description: 'Optional specific earning ID to revoke.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#gamipress-action-template'
});

Vue.component('ninjatables', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_row: [
                    { type: 'text', value: 'table_id', title: 'Table ID', task: ['create_row'], required: true },
                    { type: 'text', value: 'row_json', title: 'Row (JSON)', task: ['create_row'], required: true, description: 'Example: {"column_key":"Value"}' },
                    { type: 'text', value: 'created_at', title: 'Created At', task: ['create_row'], description: 'Optional datetime (Y-m-d H:i:s).' },
                    { type: 'text', value: 'insert_after_id', title: 'Insert After Row ID', task: ['create_row'], description: 'Optional row ID to insert after.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_row'], description: 'Optional row settings JSON.' }
                ],
                update_row: [
                    { type: 'text', value: 'table_id', title: 'Table ID', task: ['update_row'], required: true },
                    { type: 'text', value: 'row_id', title: 'Row ID', task: ['update_row'], required: true },
                    { type: 'text', value: 'row_json', title: 'Row (JSON)', task: ['update_row'], required: true, description: 'Example: {"column_key":"Value"}' },
                    { type: 'text', value: 'created_at', title: 'Created At', task: ['update_row'], description: 'Optional datetime (Y-m-d H:i:s).' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['update_row'], description: 'Optional row settings JSON.' }
                ],
                delete_row: [
                    { type: 'text', value: 'table_id', title: 'Table ID', task: ['delete_row'], required: true },
                    { type: 'text', value: 'row_id', title: 'Row ID', task: ['delete_row'], required: true }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#ninjatables-action-template'
});

Vue.component('theeventscalendar', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_event: [
                    { type: 'text', value: 'title', title: 'Title', task: ['create_event'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_event'], description: 'publish, draft, pending, private, or future.' },
                    { type: 'text', value: 'content', title: 'Content', task: ['create_event'] },
                    { type: 'text', value: 'excerpt', title: 'Excerpt', task: ['create_event'] },
                    { type: 'text', value: 'author_id', title: 'Author ID', task: ['create_event'], description: 'Optional WordPress user ID.' },
                    { type: 'text', value: 'start_date', title: 'Start Date (Y-m-d)', task: ['create_event'], required: true },
                    { type: 'text', value: 'start_time', title: 'Start Time (H:i)', task: ['create_event'] },
                    { type: 'text', value: 'end_date', title: 'End Date (Y-m-d)', task: ['create_event'], required: true },
                    { type: 'text', value: 'end_time', title: 'End Time (H:i)', task: ['create_event'] },
                    { type: 'text', value: 'all_day', title: 'All Day', task: ['create_event'], description: 'true/false.' },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_event'], description: 'Optional IANA timezone.' },
                    { type: 'text', value: 'venue_id', title: 'Venue ID', task: ['create_event'] },
                    { type: 'text', value: 'organizer_id', title: 'Organizer ID', task: ['create_event'] },
                    { type: 'text', value: 'cost', title: 'Cost', task: ['create_event'] },
                    { type: 'text', value: 'featured', title: 'Featured', task: ['create_event'], description: 'true/false.' },
                    { type: 'text', value: 'website_url', title: 'Website URL', task: ['create_event'] },
                    { type: 'text', value: 'hide_from_list', title: 'Hide From List', task: ['create_event'], description: 'true/false to hide from calendars.' },
                    { type: 'text', value: 'category_ids', title: 'Category IDs', task: ['create_event'], description: 'Comma separated category IDs.' },
                    { type: 'text', value: 'category_slugs', title: 'Category Slugs', task: ['create_event'], description: 'Comma separated slugs.' },
                    { type: 'text', value: 'tag_slugs', title: 'Tag Slugs', task: ['create_event'], description: 'Comma separated tag slugs.' },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_event'], description: 'Optional JSON object of additional meta.' }
                ],
                update_event: [
                    { type: 'text', value: 'event_id', title: 'Event ID', task: ['update_event'], required: true },
                    { type: 'text', value: 'title', title: 'Title', task: ['update_event'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_event'] },
                    { type: 'text', value: 'content', title: 'Content', task: ['update_event'] },
                    { type: 'text', value: 'excerpt', title: 'Excerpt', task: ['update_event'] },
                    { type: 'text', value: 'author_id', title: 'Author ID', task: ['update_event'] },
                    { type: 'text', value: 'start_date', title: 'Start Date (Y-m-d)', task: ['update_event'] },
                    { type: 'text', value: 'start_time', title: 'Start Time (H:i)', task: ['update_event'] },
                    { type: 'text', value: 'end_date', title: 'End Date (Y-m-d)', task: ['update_event'] },
                    { type: 'text', value: 'end_time', title: 'End Time (H:i)', task: ['update_event'] },
                    { type: 'text', value: 'all_day', title: 'All Day', task: ['update_event'] },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['update_event'] },
                    { type: 'text', value: 'venue_id', title: 'Venue ID', task: ['update_event'] },
                    { type: 'text', value: 'organizer_id', title: 'Organizer ID', task: ['update_event'] },
                    { type: 'text', value: 'cost', title: 'Cost', task: ['update_event'] },
                    { type: 'text', value: 'featured', title: 'Featured', task: ['update_event'] },
                    { type: 'text', value: 'website_url', title: 'Website URL', task: ['update_event'] },
                    { type: 'text', value: 'hide_from_list', title: 'Hide From List', task: ['update_event'] },
                    { type: 'text', value: 'category_ids', title: 'Category IDs', task: ['update_event'] },
                    { type: 'text', value: 'category_slugs', title: 'Category Slugs', task: ['update_event'] },
                    { type: 'text', value: 'tag_slugs', title: 'Tag Slugs', task: ['update_event'] },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['update_event'] }
                ],
                delete_event: [
                    { type: 'text', value: 'event_id', title: 'Event ID', task: ['delete_event'], required: true }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#theeventscalendar-action-template'
});

Vue.component('webbabookinglite', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_booking: [
                    { type: 'text', value: 'service_id', title: 'Service ID', task: ['create_booking'], required: true },
                    { type: 'text', value: 'timestamp', title: 'Timestamp (seconds)', task: ['create_booking'], required: true, description: 'Unix timestamp in seconds.' },
                    { type: 'text', value: 'duration', title: 'Duration (minutes)', task: ['create_booking'], description: 'Defaults to service duration.' },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['create_booking'], description: 'Defaults to 1.' },
                    { type: 'text', value: 'name', title: 'Customer Name', task: ['create_booking'], required: true },
                    { type: 'text', value: 'email', title: 'Customer Email', task: ['create_booking'], required: true },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_booking'] },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_booking'] },
                    { type: 'text', value: 'service_category', title: 'Service Category ID', task: ['create_booking'] },
                    { type: 'text', value: 'time_offset', title: 'Time Offset (minutes)', task: ['create_booking'] },
                    { type: 'text', value: 'locale', title: 'Locale', task: ['create_booking'] },
                    { type: 'text', value: 'attachment', title: 'Attachment URL', task: ['create_booking'] },
                    { type: 'text', value: 'extra_json', title: 'Custom Fields (JSON)', task: ['create_booking'], description: 'Array of custom field tuples.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_booking'], description: 'pending, approved, cancelled, or rejected.' }
                ],
                update_booking: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['update_booking'], required: true },
                    { type: 'text', value: 'service_id', title: 'Service ID', task: ['update_booking'] },
                    { type: 'text', value: 'timestamp', title: 'Timestamp (seconds)', task: ['update_booking'] },
                    { type: 'text', value: 'duration', title: 'Duration (minutes)', task: ['update_booking'] },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['update_booking'] },
                    { type: 'text', value: 'name', title: 'Customer Name', task: ['update_booking'] },
                    { type: 'text', value: 'email', title: 'Customer Email', task: ['update_booking'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['update_booking'] },
                    { type: 'text', value: 'description', title: 'Description', task: ['update_booking'] },
                    { type: 'text', value: 'service_category', title: 'Service Category ID', task: ['update_booking'] },
                    { type: 'text', value: 'time_offset', title: 'Time Offset (minutes)', task: ['update_booking'] },
                    { type: 'text', value: 'locale', title: 'Locale', task: ['update_booking'] },
                    { type: 'text', value: 'attachment', title: 'Attachment URL', task: ['update_booking'] },
                    { type: 'text', value: 'extra_json', title: 'Custom Fields (JSON)', task: ['update_booking'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_booking'] }
                ],
                delete_booking: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['delete_booking'], required: true },
                    { type: 'text', value: 'delete_mode', title: 'Delete Mode', task: ['delete_booking'], description: 'auto, admin, customer, or permanent.' },
                    { type: 'text', value: 'force_delete', title: 'Force Delete', task: ['delete_booking'], description: 'true/false to bypass soft delete.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#webbabookinglite-action-template'
});

Vue.component('mycred', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                award_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['award_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['award_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['award_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['award_points'], required: true, description: 'Required positive amount to credit.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['award_points'], description: 'Optional myCred point type key.' },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['award_points'], description: 'Optional reference slug; defaults to adfoin_award.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['award_points'], description: 'Optional log message stored with the transaction.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['award_points'], description: 'Optional numeric/string reference ID.' },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['award_points'], description: 'Optional JSON object stored with the log entry.' }
                ],
                deduct_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['deduct_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['deduct_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['deduct_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['deduct_points'], required: true, description: 'Required positive amount to debit.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['deduct_points'], description: 'Optional myCred point type key.' },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['deduct_points'], description: 'Optional reference slug; defaults to adfoin_deduct.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['deduct_points'], description: 'Optional log message stored with the transaction.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['deduct_points'] },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['deduct_points'] }
                ],
                set_balance: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['set_balance'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['set_balance'] },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['set_balance'] },
                    { type: 'text', value: 'target_balance', title: 'Target Balance', task: ['set_balance'], required: true, description: 'Required target balance amount.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['set_balance'] },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['set_balance'], description: 'Optional reference slug for the logged adjustment.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['set_balance'], description: 'Optional message stored with the adjustment log.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['set_balance'] },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['set_balance'] }
                ],
                add_log_entry: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['add_log_entry'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['add_log_entry'] },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['add_log_entry'] },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['add_log_entry'], required: true, description: 'Required non-zero amount recorded in the log.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['add_log_entry'] },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['add_log_entry'], description: 'Optional reference slug; defaults to adfoin_log.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['add_log_entry'], description: 'Optional log message.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['add_log_entry'] },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['add_log_entry'], description: 'Optional JSON object stored with the log entry.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#mycred-action-template'
});

Vue.component('latepoint', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_customer: [
                    { type: 'text', value: 'email', title: 'Email', task: ['create_customer'], required: true, description: 'Required and used to locate existing customers.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_customer'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_customer'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_customer'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_customer'], description: 'Optional status key (defaults to pending_verification).' },
                    { type: 'text', value: 'is_guest', title: 'Is Guest', task: ['create_customer'], description: 'true/false to mark the profile as guest.' },
                    { type: 'text', value: 'wordpress_user_id', title: 'WordPress User ID', task: ['create_customer'], description: 'Optional linked WP user ID.' },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['create_customer'], description: 'Public notes visible to customer.' },
                    { type: 'text', value: 'admin_notes', title: 'Admin Notes', task: ['create_customer'], description: 'Private notes visible to admins only.' },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['create_customer'], description: 'Optional timezone name (e.g. America/New_York).' },
                    { type: 'text', value: 'password', title: 'Password', task: ['create_customer'], description: 'Optional password; hashes and updates the account if supplied.' },
                    { type: 'text', value: 'timeline_note', title: 'Timeline Note', task: ['create_customer'], description: 'Optional note appended to the customer timeline.' },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_customer'], description: 'Optional JSON object of meta key/value pairs.' }
                ],
                update_customer: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['update_customer'], description: 'Provide customer ID or email to locate the record.' },
                    { type: 'text', value: 'email', title: 'Email', task: ['update_customer'], description: 'Email address to locate or update the customer.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['update_customer'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['update_customer'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['update_customer'] },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_customer'] },
                    { type: 'text', value: 'is_guest', title: 'Is Guest', task: ['update_customer'], description: 'true/false to toggle guest flag.' },
                    { type: 'text', value: 'wordpress_user_id', title: 'WordPress User ID', task: ['update_customer'] },
                    { type: 'text', value: 'notes', title: 'Notes', task: ['update_customer'] },
                    { type: 'text', value: 'admin_notes', title: 'Admin Notes', task: ['update_customer'] },
                    { type: 'text', value: 'timezone', title: 'Timezone', task: ['update_customer'] },
                    { type: 'text', value: 'password', title: 'Password', task: ['update_customer'], description: 'Optional new password.' },
                    { type: 'text', value: 'timeline_note', title: 'Timeline Note', task: ['update_customer'] },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['update_customer'] }
                ],
                update_booking_status: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['update_booking_status'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_booking_status'], required: true, description: 'Valid LatePoint status key (approved, pending, cancelled, no_show, completed, or custom).' },
                    { type: 'text', value: 'note', title: 'Note', task: ['update_booking_status'], description: 'Optional activity note logged after the status change.' },
                    { type: 'text', value: 'initiated_by', title: 'Initiated By', task: ['update_booking_status'], description: 'Optional initiator type (wp_user, agent, customer, etc.).' },
                    { type: 'text', value: 'initiated_by_id', title: 'Initiator ID', task: ['update_booking_status'], description: 'Optional ID associated with the initiator.' }
                ],
                add_booking_note: [
                    { type: 'text', value: 'booking_id', title: 'Booking ID', task: ['add_booking_note'], required: true },
                    { type: 'text', value: 'note', title: 'Note', task: ['add_booking_note'], required: true, description: 'Activity description stored on the booking timeline.' },
                    { type: 'text', value: 'code', title: 'Activity Code', task: ['add_booking_note'], description: 'Optional activity code (defaults to booking_note).' },
                    { type: 'text', value: 'initiated_by', title: 'Initiated By', task: ['add_booking_note'] },
                    { type: 'text', value: 'initiated_by_id', title: 'Initiator ID', task: ['add_booking_note'] }
                ],
                add_customer_note: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['add_customer_note'], description: 'Provide customer ID or email.' },
                    { type: 'text', value: 'email', title: 'Email', task: ['add_customer_note'] },
                    { type: 'text', value: 'note', title: 'Note', task: ['add_customer_note'], required: true }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#latepoint-action-template'
});

Vue.component('givewp', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_donor: [
                    { type: 'text', value: 'email', title: 'Donor Email', task: ['create_donor'], required: true, description: 'Required primary email for the donor.' },
                    { type: 'text', value: 'name', title: 'Donor Name', task: ['create_donor'], description: 'Optional donor display name. Defaults to the supplied first/last name or email.' },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_donor'], description: 'Optional WordPress user ID to link to the donor.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_donor'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_donor'] },
                    { type: 'text', value: 'company', title: 'Company', task: ['create_donor'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_donor'] },
                    { type: 'text', value: 'title_prefix', title: 'Title Prefix', task: ['create_donor'], description: 'Optional title (Mr, Ms, Dr, etc.).' },
                    { type: 'text', value: 'address_line1', title: 'Address Line 1', task: ['create_donor'] },
                    { type: 'text', value: 'address_line2', title: 'Address Line 2', task: ['create_donor'] },
                    { type: 'text', value: 'address_city', title: 'City', task: ['create_donor'] },
                    { type: 'text', value: 'address_state', title: 'State/Province', task: ['create_donor'] },
                    { type: 'text', value: 'address_zip', title: 'Postal Code', task: ['create_donor'] },
                    { type: 'text', value: 'address_country', title: 'Country Code', task: ['create_donor'], description: 'Optional 2-letter country code.' },
                    { type: 'text', value: 'donor_note', title: 'Donor Note', task: ['create_donor'], description: 'Optional note saved on the donor profile.' },
                    { type: 'text', value: 'purchase_value', title: 'Total Purchase Value', task: ['create_donor'], description: 'Optional numeric total donation value.' },
                    { type: 'text', value: 'purchase_count', title: 'Purchase Count', task: ['create_donor'], description: 'Optional total donation count.' },
                    { type: 'text', value: 'payment_ids', title: 'Payment IDs', task: ['create_donor'], description: 'Optional comma-separated GiveWP donation IDs.' },
                    { type: 'text', value: 'token', title: 'Verification Token', task: ['create_donor'] },
                    { type: 'text', value: 'verify_key', title: 'Verify Key', task: ['create_donor'] },
                    { type: 'text', value: 'verify_throttle', title: 'Verify Throttle', task: ['create_donor'] }
                ],
                update_donor: [
                    { type: 'text', value: 'donor_id', title: 'Donor ID', task: ['update_donor'], description: 'Provide donor ID or donor email.', required: false },
                    { type: 'text', value: 'email', title: 'Donor Email', task: ['update_donor'], description: 'Email to update or locate donor.' },
                    { type: 'text', value: 'name', title: 'Donor Name', task: ['update_donor'] },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['update_donor'] },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['update_donor'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['update_donor'] },
                    { type: 'text', value: 'company', title: 'Company', task: ['update_donor'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['update_donor'] },
                    { type: 'text', value: 'title_prefix', title: 'Title Prefix', task: ['update_donor'] },
                    { type: 'text', value: 'address_line1', title: 'Address Line 1', task: ['update_donor'] },
                    { type: 'text', value: 'address_line2', title: 'Address Line 2', task: ['update_donor'] },
                    { type: 'text', value: 'address_city', title: 'City', task: ['update_donor'] },
                    { type: 'text', value: 'address_state', title: 'State/Province', task: ['update_donor'] },
                    { type: 'text', value: 'address_zip', title: 'Postal Code', task: ['update_donor'] },
                    { type: 'text', value: 'address_country', title: 'Country Code', task: ['update_donor'] },
                    { type: 'text', value: 'donor_note', title: 'Donor Note', task: ['update_donor'] },
                    { type: 'text', value: 'purchase_value', title: 'Total Purchase Value', task: ['update_donor'] },
                    { type: 'text', value: 'purchase_count', title: 'Purchase Count', task: ['update_donor'] },
                    { type: 'text', value: 'payment_ids', title: 'Payment IDs', task: ['update_donor'] },
                    { type: 'text', value: 'token', title: 'Verification Token', task: ['update_donor'] },
                    { type: 'text', value: 'verify_key', title: 'Verify Key', task: ['update_donor'] },
                    { type: 'text', value: 'verify_throttle', title: 'Verify Throttle', task: ['update_donor'] }
                ],
                create_donation: [
                    { type: 'text', value: 'form_id', title: 'Form ID', task: ['create_donation'], required: true, description: 'Required GiveWP form ID.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['create_donation'], required: true, description: 'Required donation amount.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_donation'], description: 'Optional currency code; defaults to form/site currency.' },
                    { type: 'text', value: 'donor_id', title: 'Donor ID', task: ['create_donation'], description: 'Optional existing donor ID.' },
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['create_donation'], description: 'Optional WordPress user ID linked to the donation.' },
                    { type: 'text', value: 'email', title: 'Donor Email', task: ['create_donation'], description: 'Required when donor ID is not supplied.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_donation'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_donation'] },
                    { type: 'text', value: 'title_prefix', title: 'Title Prefix', task: ['create_donation'] },
                    { type: 'text', value: 'company', title: 'Company', task: ['create_donation'] },
                    { type: 'text', value: 'phone', title: 'Phone', task: ['create_donation'] },
                    { type: 'text', value: 'address_line1', title: 'Address Line 1', task: ['create_donation'] },
                    { type: 'text', value: 'address_line2', title: 'Address Line 2', task: ['create_donation'] },
                    { type: 'text', value: 'address_city', title: 'City', task: ['create_donation'] },
                    { type: 'text', value: 'address_state', title: 'State/Province', task: ['create_donation'] },
                    { type: 'text', value: 'address_zip', title: 'Postal Code', task: ['create_donation'] },
                    { type: 'text', value: 'address_country', title: 'Country Code', task: ['create_donation'] },
                    { type: 'text', value: 'price_id', title: 'Price ID', task: ['create_donation'], description: 'Optional GiveWP price ID/level.' },
                    { type: 'text', value: 'status', title: 'Status', task: ['create_donation'], description: 'Optional status key (pending, publish, refunded, etc.).' },
                    { type: 'text', value: 'gateway', title: 'Gateway', task: ['create_donation'], description: 'Optional gateway slug (manual, stripe, etc.).' },
                    { type: 'text', value: 'mode', title: 'Mode', task: ['create_donation'], description: 'Optional live/test mode override.' },
                    { type: 'text', value: 'purchase_key', title: 'Purchase Key', task: ['create_donation'], description: 'Optional custom purchase key.' },
                    { type: 'text', value: 'donation_title', title: 'Donation Title', task: ['create_donation'], description: 'Optional donation title override.' },
                    { type: 'text', value: 'donation_note', title: 'Donation Note', task: ['create_donation'], description: 'Optional note added after creation.' },
                    { type: 'text', value: 'meta_json', title: 'Meta (JSON)', task: ['create_donation'], description: 'Optional JSON object of payment meta fields.' },
                    { type: 'text', value: 'campaign_id', title: 'Campaign ID', task: ['create_donation'], description: 'Optional campaign ID to associate.' },
                    { type: 'text', value: 'date', title: 'Donation Date', task: ['create_donation'], description: 'Optional MySQL datetime (Y-m-d H:i:s).' }
                ],
                update_donation_status: [
                    { type: 'text', value: 'donation_id', title: 'Donation ID', task: ['update_donation_status'], required: true },
                    { type: 'text', value: 'status', title: 'Status', task: ['update_donation_status'], required: true, description: 'Valid GiveWP status key.' }
                ],
                add_donation_note: [
                    { type: 'text', value: 'donation_id', title: 'Donation ID', task: ['add_donation_note'], required: true },
                    { type: 'text', value: 'note', title: 'Note', task: ['add_donation_note'], required: true, description: 'Note content stored on the donation.' }
                ]
            }
        };
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#givewp-action-template'
});

Vue.component('buddyboss', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;

            if (this.action.task !== 'create_member') {
                this.fields = [];
                this.fieldsLoading = false;
                return;
            }

            this.fieldsLoading = true;
            this.fields = [];

            var requestData = {
                action: 'adfoin_get_buddyboss_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                that.fieldsLoading = false;
                if (response.success && response.data) {
                    response.data.forEach(function (single) {
                        that.fields.push({
                            type: single.type || 'text',
                            value: single.key,
                            title: single.value,
                            task: ['create_member'],
                            required: !!single.required,
                            description: single.description || ''
                        });
                    });
                }
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {
        this.getFields();
    },
    watch: {
        'action.task': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#buddyboss-action-template'
});

Vue.component('followupboss', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;
            this.fields = [];

            var allFieldsRequestData = {
                'action': 'adfoin_get_followupboss_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_contact'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#followupboss-action-template'
});

Vue.component('dynamics365', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        }
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;
            this.fields = [];

            var allFieldsRequestData = {
                'action': 'adfoin_get_dynamics365_fields',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
            };

            jQuery.post(ajaxurl, allFieldsRequestData, function (response) {

                if (response.success) {
                    if (response.data) {
                        response.data.map(function (single) {
                            that.fields.push({ type: 'text', value: single.key, title: single.value, task: ['create_contact'], required: false, description: single.description });
                        });

                        that.fieldsLoading = false;
                    }
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (this.fielddata.credId) {
            this.getFields();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.getFields();
            }
        }
    },
    template: '#dynamics365-action-template'
});

Vue.component('gravityforms', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            forms: {},
            formsLoading: false,
            formFieldList: [],
            fieldsLoading: false,
            fieldMap: {},
            selected: {}
        }
    },
    computed: {
        fields: function () {
            return [
                { type: 'text', value: 'entryId', title: 'Entry ID', task: ['update_entry', 'add_note'], required: true, description: 'Entry ID to locate the Gravity Forms submission.' },
                { type: 'textarea', value: 'entryNote', title: 'Entry Note', task: ['create_entry', 'update_entry', 'add_note'], description: 'Optional note saved to the entry after processing.' },
                { type: 'text', value: 'entryStatus', title: 'Entry Status', task: ['create_entry', 'update_entry'], description: 'Accepts active, spam, or trash.' },
                { type: 'text', value: 'sourceUrl', title: 'Source URL', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'userAgent', title: 'User Agent', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['create_entry'], description: 'Defaults to the detected visitor IP when left blank.' },
                { type: 'text', value: 'createdBy', title: 'Created By (User ID)', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'currency', title: 'Currency', task: ['create_entry', 'update_entry'], description: '3-letter currency code. Defaults to the site currency.' },
                { type: 'text', value: 'paymentStatus', title: 'Payment Status', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'paymentMethod', title: 'Payment Method', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'paymentAmount', title: 'Payment Amount', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'paymentDate', title: 'Payment Date (Y-m-d H:i:s)', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'transactionId', title: 'Transaction ID', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'transactionType', title: 'Transaction Type (0|1)', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'isFulfilled', title: 'Is Fulfilled (0|1)', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'isStarred', title: 'Is Starred (0|1)', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'isRead', title: 'Is Read (0|1)', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'postId', title: 'Post ID', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'sourceId', title: 'Source ID', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'dateCreated', title: 'Date Created (Y-m-d H:i:s)', task: ['create_entry'] },
                { type: 'text', value: 'dateUpdated', title: 'Date Updated (Y-m-d H:i:s)', task: ['update_entry'] }
            ];
        },
        showFieldMapping: function () {
            return (this.action.task === 'create_entry' || this.action.task === 'update_entry') && this.fielddata.formId;
        },
        serializedFieldMap: function () {
            var map = this.fieldMap || {};
            var payload = JSON.stringify(map);
            this.$set(this.fielddata, 'fieldMap', payload);
            return payload;
        }
    },
    methods: {
        loadForms: function (force) {
            if (!force && Object.keys(this.forms).length) {
                return;
            }

            var vm = this;
            vm.formsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_forms',
                nonce: adfoin.nonce,
                formProviderId: 'gravityforms'
            }).done(function (response) {
                if (response.success && response.data) {
                    vm.forms = response.data;
                }
            }).fail(function () {
                vm.forms = {};
            }).always(function () {
                vm.formsLoading = false;
            });
        },
        fetchFields: function () {
            if (!this.fielddata.formId) {
                this.formFieldList = [];
                return;
            }

            var vm = this;
            vm.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_form_fields',
                nonce: adfoin.nonce,
                formProviderId: 'gravityforms',
                formId: this.fielddata.formId
            }).done(function (response) {
                if (response.success && response.data) {
                    var items = [];
                    Object.keys(response.data).forEach(function (id) {
                        items.push({
                            id: id,
                            label: response.data[id]
                        });
                    });
                    vm.formFieldList = items;
                    vm.restoreFieldMap();
                } else {
                    vm.formFieldList = [];
                }
            }).fail(function () {
                vm.formFieldList = [];
            }).always(function () {
                vm.fieldsLoading = false;
            });
        },
        restoreFieldMap: function () {
            var stored = this.fielddata.fieldMap;
            var parsed = {};

            if (stored && typeof stored === 'object') {
                parsed = stored;
            } else if (stored) {
                try {
                    parsed = JSON.parse(stored);
                } catch (e) {
                    parsed = {};
                }
            }

            this.fieldMap = parsed || {};
        },
        applySelected: function (fieldId) {
            var selectedKey = this.selected[fieldId];
            if (typeof selectedKey === 'undefined' || selectedKey === '') {
                return;
            }

            var token = '{{' + selectedKey + '}}';

            if (this.fieldMap[fieldId]) {
                if (this.fieldMap[fieldId].indexOf(token) === -1) {
                    this.fieldMap[fieldId] += (this.fieldMap[fieldId].length ? ' ' : '') + token;
                }
            } else {
                this.$set(this.fieldMap, fieldId, token);
            }
        },
        clearField: function (fieldId) {
            if (this.fieldMap[fieldId]) {
                this.$delete(this.fieldMap, fieldId);
            }

            if (this.selected[fieldId]) {
                this.$set(this.selected, fieldId, '');
            }
        }
    },
    watch: {
        'fielddata.formId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.fetchFields();
            }

            if (!newVal) {
                this.formFieldList = [];
                this.fieldMap = {};
            }
        },
        'action.task': function (newVal) {
            if (newVal === 'create_entry' || newVal === 'update_entry') {
                this.loadForms();

                if (this.fielddata.formId) {
                    this.fetchFields();
                }
            }
        }
    },
    created: function () {
        if (typeof this.fielddata.fieldMap === 'undefined') {
            this.$set(this.fielddata, 'fieldMap', '');
        }

        this.restoreFieldMap();
    },
    mounted: function () {
        if (this.action.task === 'create_entry' || this.action.task === 'update_entry') {
            this.loadForms(true);

            if (this.fielddata.formId) {
                this.fetchFields();
            }
        }
    },
    template: '#gravityforms-action-template'
});

Vue.component('woocommerce', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            addressKeys: [
                { key: 'first_name', title: 'First Name' },
                { key: 'last_name', title: 'Last Name' },
                { key: 'company', title: 'Company' },
                { key: 'address_1', title: 'Address Line 1' },
                { key: 'address_2', title: 'Address Line 2' },
                { key: 'city', title: 'City' },
                { key: 'state', title: 'State' },
                { key: 'postcode', title: 'Postal Code' },
                { key: 'country', title: 'Country' },
                { key: 'email', title: 'Email' },
                { key: 'phone', title: 'Phone' }
            ],
            fieldLists: {
                create_customer: [
                    { type: 'text', value: 'email', title: 'Email', task: ['create_customer'], required: true },
                    { type: 'text', value: 'username', title: 'Username', task: ['create_customer'], description: 'Optional username. Defaults to email when blank.' },
                    { type: 'text', value: 'password', title: 'Password', task: ['create_customer'], description: 'Leave blank to auto-generate.' },
                    { type: 'text', value: 'first_name', title: 'First Name', task: ['create_customer'] },
                    { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_customer'] },
                    { type: 'text', value: 'display_name', title: 'Display Name', task: ['create_customer'] },
                    { type: 'textarea', value: 'customer_note', title: 'Customer Note', task: ['create_customer'] },
                    { type: 'text', value: 'role', title: 'Role', task: ['create_customer'], description: 'Optional WordPress role to assign.' }
                ],
                create_order: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_order'], description: 'User/customer ID. Leave blank for guest orders.' },
                    { type: 'text', value: 'customer_email', title: 'Customer Email', task: ['create_order'] },
                    { type: 'text', value: 'customer_phone', title: 'Customer Phone', task: ['create_order'] },
                    { type: 'textarea', value: 'customer_note', title: 'Customer Note', task: ['create_order'] },
                    { type: 'text', value: 'status', title: 'Order Status', task: ['create_order'], description: 'pending, processing, completed, etc.' },
                    { type: 'text', value: 'payment_method', title: 'Payment Method ID', task: ['create_order'] },
                    { type: 'text', value: 'payment_method_title', title: 'Payment Method Title', task: ['create_order'] },
                    { type: 'text', value: 'transaction_id', title: 'Transaction ID', task: ['create_order'] },
                    { type: 'text', value: 'shipping_total', title: 'Shipping Total', task: ['create_order'] },
                    { type: 'text', value: 'discount_total', title: 'Discount Total', task: ['create_order'] },
                    { type: 'text', value: 'discount_tax', title: 'Discount Tax', task: ['create_order'] },
                    { type: 'text', value: 'shipping_tax', title: 'Shipping Tax', task: ['create_order'] },
                    { type: 'text', value: 'cart_tax', title: 'Cart Tax', task: ['create_order'] },
                    { type: 'text', value: 'total', title: 'Order Total', task: ['create_order'], description: 'Overrides calculated total when supplied.' },
                    { type: 'text', value: 'set_paid', title: 'Mark Paid', task: ['create_order'], description: 'Use 1/true to mark payment complete.' },
                    { type: 'textarea', value: 'order_note', title: 'Order Note', task: ['create_order'], description: 'Internal order note.' },
                    { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON', task: ['create_order'], description: 'JSON array of line items. Example: [{"product_id":123,"quantity":1,"totals":{"total":29.99,"subtotal":29.99}}]' },
                    { type: 'textarea', value: 'shipping_lines_json', title: 'Shipping Lines JSON', task: ['create_order'], description: 'JSON array matching WooCommerce REST shipping schema.' },
                    { type: 'textarea', value: 'fee_lines_json', title: 'Fee Lines JSON', task: ['create_order'] },
                    { type: 'textarea', value: 'coupon_lines_json', title: 'Coupon Lines JSON', task: ['create_order'] }
                ],
                create_subscription: [
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_subscription'], required: true },
                    { type: 'text', value: 'status', title: 'Subscription Status', task: ['create_subscription'], description: 'pending, active, on-hold, etc.' },
                    { type: 'text', value: 'currency', title: 'Currency', task: ['create_subscription'], description: 'Defaults to store currency.' },
                    { type: 'text', value: 'billing_period', title: 'Billing Period', task: ['create_subscription'], description: 'day, week, month, year.' },
                    { type: 'text', value: 'billing_interval', title: 'Billing Interval', task: ['create_subscription'], description: 'Numeric interval, defaults to 1.' },
                    { type: 'text', value: 'start_date', title: 'Start Date', task: ['create_subscription'], description: 'Any strtotime-compatible string.' },
                    { type: 'text', value: 'trial_end', title: 'Trial End Date', task: ['create_subscription'] },
                    { type: 'text', value: 'next_payment', title: 'Next Payment Date', task: ['create_subscription'] },
                    { type: 'text', value: 'end_date', title: 'End Date', task: ['create_subscription'] },
                    { type: 'text', value: 'payment_method', title: 'Payment Method ID', task: ['create_subscription'] },
                    { type: 'text', value: 'requires_manual_renewal', title: 'Requires Manual Renewal', task: ['create_subscription'], description: 'Use 1/true to require manual renewal.' },
                    { type: 'text', value: 'total', title: 'Subscription Total', task: ['create_subscription'] },
                    { type: 'text', value: 'discount_total', title: 'Discount Total', task: ['create_subscription'] },
                    { type: 'text', value: 'discount_tax', title: 'Discount Tax', task: ['create_subscription'] },
                    { type: 'text', value: 'shipping_total', title: 'Shipping Total', task: ['create_subscription'] },
                    { type: 'text', value: 'shipping_tax', title: 'Shipping Tax', task: ['create_subscription'] },
                    { type: 'text', value: 'cart_tax', title: 'Cart Tax', task: ['create_subscription'] },
                    { type: 'textarea', value: 'subscription_note', title: 'Subscription Note', task: ['create_subscription'] },
                    { type: 'textarea', value: 'line_items_json', title: 'Line Items JSON', task: ['create_subscription'], description: 'JSON array of subscription products.' },
                    { type: 'textarea', value: 'shipping_lines_json', title: 'Shipping Lines JSON', task: ['create_subscription'] },
                    { type: 'textarea', value: 'fee_lines_json', title: 'Fee Lines JSON', task: ['create_subscription'] },
                    { type: 'textarea', value: 'coupon_lines_json', title: 'Coupon Lines JSON', task: ['create_subscription'] }
                ],
                create_booking: [
                    { type: 'text', value: 'product_id', title: 'Bookable Product ID', task: ['create_booking'], required: true },
                    { type: 'text', value: 'resource_id', title: 'Resource ID', task: ['create_booking'], description: 'Optional resource for the booking.' },
                    { type: 'text', value: 'person_ids_json', title: 'Person IDs JSON', task: ['create_booking'], description: 'JSON array of person IDs/quantities as needed.' },
                    { type: 'text', value: 'start_date', title: 'Start Date (Y-m-d H:i)', task: ['create_booking'], required: true },
                    { type: 'text', value: 'end_date', title: 'End Date (Y-m-d H:i)', task: ['create_booking'], required: true },
                    { type: 'text', value: 'all_day', title: 'All Day (1/0)', task: ['create_booking'] },
                    { type: 'text', value: 'quantity', title: 'Quantity', task: ['create_booking'], description: 'Number of slots/persons.' },
                    { type: 'text', value: 'customer_id', title: 'Customer ID', task: ['create_booking'], description: 'Existing user ID. Leave blank for guest bookings.' },
                    { type: 'text', value: 'customer_email', title: 'Customer Email', task: ['create_booking'] },
                    { type: 'text', value: 'customer_name', title: 'Customer Name', task: ['create_booking'] },
                    { type: 'text', value: 'customer_phone', title: 'Customer Phone', task: ['create_booking'] },
                    { type: 'text', value: 'order_status', title: 'Order Status', task: ['create_booking'], description: 'pending, confirmed, cancelled, etc.' },
                    { type: 'textarea', value: 'order_note', title: 'Order Note', task: ['create_booking'] },
                    { type: 'text', value: 'pricing_base_cost', title: 'Base Cost Override', task: ['create_booking'] },
                    { type: 'text', value: 'pricing_block_cost', title: 'Block Cost Override', task: ['create_booking'] },
                    { type: 'text', value: 'pricing_display_cost', title: 'Display Cost Override', task: ['create_booking'] },
                    { type: 'textarea', value: 'booking_note', title: 'Booking Note', task: ['create_booking'] },
                    { type: 'textarea', value: 'meta_json', title: 'Booking Meta (JSON)', task: ['create_booking'], description: 'Optional meta key/value pairs.' },
                    { type: 'textarea', value: 'order_meta_json', title: 'Order Meta (JSON)', task: ['create_booking'], description: 'Optional order meta key/value pairs.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            var task = this.action.task;
            var list = [];

            if (!task) {
                return list;
            }

            if (this.fieldLists[task]) {
                list = list.concat(this.fieldLists[task]);
            }

            if (task === 'create_customer') {
                list = list.concat(this.buildAddressFields('billing', ['create_customer'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_customer'], 'Shipping '));
            }

            if (task === 'create_order') {
                list = list.concat(this.buildAddressFields('billing', ['create_order'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_order'], 'Shipping '));
            }

            if (task === 'create_subscription') {
                list = list.concat(this.buildAddressFields('billing', ['create_subscription'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_subscription'], 'Shipping '));
            }

            if (task === 'create_booking') {
                list = list.concat(this.buildAddressFields('billing', ['create_booking'], 'Billing '));
                list = list.concat(this.buildAddressFields('shipping', ['create_booking'], 'Shipping '));
            }

            return list;
        }
    },
    methods: {
        buildAddressFields: function (prefix, tasks, labelPrefix) {
            var fields = [];
            labelPrefix = labelPrefix || '';

            this.addressKeys.forEach(function (item) {
                fields.push({
                    type: 'text',
                    value: prefix + '_' + item.key,
                    title: labelPrefix + item.title,
                    task: tasks
                });
            });

            return fields;
        }
    },
    template: '#woocommerce-action-template'
});

Vue.component('wpforms', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            forms: {},
            formsLoading: false,
            formFieldList: [],
            fieldsLoading: false,
            fieldMap: {},
            selected: {}
        }
    },
    computed: {
        fields: function () {
            return [
                { type: 'text', value: 'entryId', title: 'Entry ID', task: ['update_entry', 'add_note'], required: true, description: 'Existing entry ID to update or annotate.' },
                { type: 'textarea', value: 'entryNote', title: 'Entry Note', task: ['create_entry', 'update_entry', 'add_note'], description: 'Optional note stored with the entry.' },
                { type: 'text', value: 'entryStatus', title: 'Entry Status', task: ['create_entry', 'update_entry'], description: 'Accepted values include empty, spam, trash, archived, or pending.' },
                { type: 'text', value: 'entryType', title: 'Entry Type', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'postId', title: 'Post ID', task: ['create_entry', 'update_entry'] },
                { type: 'text', value: 'userId', title: 'User ID', task: ['create_entry', 'update_entry'], description: 'Defaults to the current user when blank.' },
                { type: 'text', value: 'viewed', title: 'Viewed Flag', task: ['create_entry', 'update_entry'], description: '0 or 1.' },
                { type: 'text', value: 'starred', title: 'Starred Flag', task: ['create_entry', 'update_entry'], description: '0 or 1.' },
                { type: 'text', value: 'notesCount', title: 'Notes Count', task: ['create_entry', 'update_entry'], description: 'Optional integer override.' },
                { type: 'textarea', value: 'metaJson', title: 'Meta (JSON/String)', task: ['create_entry', 'update_entry'], description: 'Stored in the entry meta column.' },
                { type: 'text', value: 'dateCreated', title: 'Date Created (Y-m-d H:i:s)', task: ['create_entry'] },
                { type: 'text', value: 'dateModified', title: 'Date Modified (Y-m-d H:i:s)', task: ['update_entry'] },
                { type: 'text', value: 'ipAddress', title: 'IP Address', task: ['create_entry', 'update_entry'], description: 'Defaults to detected visitor IP when blank.' },
                { type: 'text', value: 'userAgent', title: 'User Agent', task: ['create_entry', 'update_entry'], description: 'Defaults to the current request user agent.' },
                { type: 'text', value: 'userUuid', title: 'User UUID', task: ['create_entry', 'update_entry'] }
            ];
        },
        showFieldMapping: function () {
            return (this.action.task === 'create_entry' || this.action.task === 'update_entry') && this.fielddata.formId;
        },
        serializedFieldMap: function () {
            var map = this.fieldMap || {};
            var payload = JSON.stringify(map);
            this.$set(this.fielddata, 'fieldMap', payload);
            return payload;
        }
    },
    methods: {
        loadForms: function (force) {
            if (!force && Object.keys(this.forms).length) {
                return;
            }

            var vm = this;
            vm.formsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_forms',
                nonce: adfoin.nonce,
                formProviderId: 'wpforms'
            }).done(function (response) {
                if (response.success && response.data) {
                    vm.forms = response.data;
                }
            }).fail(function () {
                vm.forms = {};
            }).always(function () {
                vm.formsLoading = false;
            });
        },
        fetchFields: function () {
            if (!this.fielddata.formId) {
                this.formFieldList = [];
                return;
            }

            var vm = this;
            vm.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_form_fields',
                nonce: adfoin.nonce,
                formProviderId: 'wpforms',
                formId: this.fielddata.formId
            }).done(function (response) {
                if (response.success && response.data) {
                    var items = [];
                    Object.keys(response.data).forEach(function (id) {
                        items.push({
                            id: id,
                            label: response.data[id]
                        });
                    });
                    vm.formFieldList = items;
                    vm.restoreFieldMap();
                } else {
                    vm.formFieldList = [];
                }
            }).fail(function () {
                vm.formFieldList = [];
            }).always(function () {
                vm.fieldsLoading = false;
            });
        },
        restoreFieldMap: function () {
            var stored = this.fielddata.fieldMap;
            var parsed = {};

            if (stored && typeof stored === 'object') {
                parsed = stored;
            } else if (stored) {
                try {
                    parsed = JSON.parse(stored);
                } catch (e) {
                    parsed = {};
                }
            }

            this.fieldMap = parsed || {};
        },
        applySelected: function (fieldId) {
            var selectedKey = this.selected[fieldId];
            if (typeof selectedKey === 'undefined' || selectedKey === '') {
                return;
            }

            var token = '{{' + selectedKey + '}}';

            if (this.fieldMap[fieldId]) {
                if (this.fieldMap[fieldId].indexOf(token) === -1) {
                    this.fieldMap[fieldId] += (this.fieldMap[fieldId].length ? ' ' : '') + token;
                }
            } else {
                this.$set(this.fieldMap, fieldId, token);
            }
        },
        clearField: function (fieldId) {
            if (this.fieldMap[fieldId]) {
                this.$delete(this.fieldMap, fieldId);
            }

            if (this.selected[fieldId]) {
                this.$set(this.selected, fieldId, '');
            }
        }
    },
    watch: {
        'fielddata.formId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.fetchFields();
            }

            if (!newVal) {
                this.formFieldList = [];
                this.fieldMap = {};
            }
        },
        'action.task': function (newVal) {
            if (newVal === 'create_entry' || newVal === 'update_entry') {
                this.loadForms();

                if (this.fielddata.formId) {
                    this.fetchFields();
                }
            }
        }
    },
    created: function () {
        if (typeof this.fielddata.fieldMap === 'undefined') {
            this.$set(this.fielddata, 'fieldMap', '');
        }

        this.restoreFieldMap();
    },
    mounted: function () {
        if (this.action.task === 'create_entry' || this.action.task === 'update_entry') {
            this.loadForms(true);

            if (this.fielddata.formId) {
                this.fetchFields();
            }
        }
    },
    template: '#wpforms-action-template'
});
Vue.component('moneybird', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    mounted: function() {
        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    methods: {
        getCredentials: function() {
            var self = this;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_moneybird_credentials',
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                if (response.success) {
                    self.credentialsList = response.data;
                }
            });
        },
        loadFields: function() {
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            var self = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_moneybird_fields',
                'credId': this.fielddata.credId,
                'task': this.action.task,
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                self.fieldsLoading = false;
                if (response.success) {
                    self.fields = response.data.map(function(field) {
                        return {
                            type: field.type || 'text',
                            value: field.key,
                            title: field.value,
                            task: [self.action.task],
                            required: field.required || false
                        };
                    });
                }
            });
        }
    },
    template: '#moneybird-action-template'
});

Vue.component('salesforcefieldservice', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            credentialsList: []
        }
    },
    mounted: function() {
        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function(newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.loadFields();
            }
        }
    },
    methods: {
        getCredentials: function() {
            var self = this;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_salesforcefieldservice_credentials',
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                if (response.success) {
                    self.credentialsList = response.data;
                }
            });
        },
        loadFields: function() {
            if (!this.fielddata.credId) {
                this.fields = [];
                return;
            }

            var self = this;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_salesforcefieldservice_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin_admin.nonce
            }, function(response) {
                self.fieldsLoading = false;
                if (response.success) {
                    self.fields = response.data.map(function(field) {
                        return {
                            type: 'text',
                            value: field.key,
                            title: field.value,
                            task: [self.action.task],
                            required: field.required || false
                        };
                    });
                }
            });
        }
    },
    template: '#salesforcefieldservice-action-template'
});
