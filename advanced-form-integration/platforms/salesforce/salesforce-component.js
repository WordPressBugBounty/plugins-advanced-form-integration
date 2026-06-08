/**
 * Advanced Form Integration — "salesforce" action component.
 *
 * Loaded on demand by adfoinComponentLoader.loadPlatform("salesforce").
 *
 * Handles the consolidated Salesforce platform — the original add_lead /
 * add_contact tasks plus the cross-cutting tasks (create_task,
 * upload_file, upload_and_link, create_record, upsert_record,
 * create_article) that used to live in separate platforms.
 */

Vue.component('salesforce', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            fieldsLoading: false,
            campaignLoading: false,
            ownerLoading: false,
            sobjectsLoading: false,
            credentialsList: [],
            fields: []
        };
    },
    methods: {
        getCredentials: function () {
            var that = this;
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_salesforce_credentials', {
                loadingKey: 'credLoading',
                autoSelect: 'legacy',
                onLoaded: function () {
                    that.onAccountChange();
                }
            });
        },
        // Single entry point fired when account OR task changes. Each
        // sub-loader is gated to the tasks that actually need it.
        onAccountChange: function () {
            this.getFields();
            if (this.action.task === 'add_lead' || this.action.task === 'add_contact') {
                this.getOwners();
            }
            if (this.action.task === 'add_lead' || this.action.task === 'add_contact') {
                this.getCampaigns();
            }
            if (this.action.task === 'create_record' || this.action.task === 'upsert_record') {
                this.loadSObjects();
            }
        },
        getFields: function () {
            // Editable-field template requires each field to carry a
            // `task` array (it does `inArray(action.task, field.task)`),
            // so we route through adfoinHelpers.getFields which performs
            // that augmentation. Passing the CURRENT task pins every
            // returned field to whichever task is selected.
            adfoinHelpers.getFields(this, 'adfoin_get_salesforce_fields', {
                task:          [this.action.task],
                requireCredId: true,
                clearBefore:   true,
                extraParams:   { task: this.action.task }
            });
        },
        getCampaigns: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_salesforce_campaigns', {
                targetKey: 'campaigns',
                loadingKey: 'campaignLoading',
                requireSuccess: true
            });
        },
        getOwners: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_salesforce_owners', {
                targetKey: 'owners',
                loadingKey: 'ownerLoading',
                requireSuccess: true
            });
        },
        loadSObjects: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.sobjects = {};
                return;
            }
            this.sobjectsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_salesforce_sobjects',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.sobjects = (response && response.success && response.data) ? response.data : {};
                that.sobjectsLoading = false;
            }).fail(function () {
                that.fielddata.sobjects = {};
                that.sobjectsLoading = false;
            });
        },
        // Dynamic row management for create_record / upsert_record.
        addRow: function () {
            this.fielddata.rows.push({ key: '', value: '' });
        },
        removeRow: function (idx) {
            this.fielddata.rows.splice(idx, 1);
        },
        // Dynamic row management for create_article (custom Knowledge fields).
        addCustomField: function () {
            this.fielddata.customFields.push({ key: '', value: '' });
        },
        removeCustomField: function (idx) {
            this.fielddata.customFields.splice(idx, 1);
        }
    },
    mounted: function () {
        var defaults = {
            credId:           '',
            accountId:        '',
            campaignId:       '',
            ownerId:          '',
            // Generic SObject task
            sobject:          '',
            sobjects:         {},
            externalIdField:  '',
            externalIdValue:  '',
            rows:             [],
            // Knowledge article task
            customFields:     []
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });

        this.getCredentials();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.onAccountChange();
            }
        },
        'action.task': function () {
            if (this.fielddata.credId) {
                this.onAccountChange();
            }
        }
    },
    template: '#salesforce-action-template'
});
