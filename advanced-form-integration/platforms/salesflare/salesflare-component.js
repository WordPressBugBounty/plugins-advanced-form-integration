/**
 * Advanced Form Integration - "salesflare" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("salesflare").
 */

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
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_salesflare_owner_list', {
                targetKey: 'ownerList',
                loadingKey: 'ownerLoading',
                requireCredId: false,
                includeCredId: true
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
