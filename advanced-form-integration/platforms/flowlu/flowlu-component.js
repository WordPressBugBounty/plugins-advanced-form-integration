/**
 * Advanced Form Integration - "flowlu" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("flowlu").
 */

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
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_flowlu_credentials', {
                loadingKey: 'credentialLoading',
                clearOnFail: true
            });
        },
        getOwnerList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_flowlu_owner_list', {
                targetKey: 'ownerList',
                loadingKey: 'ownerLoading'
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
