/**
 * Advanced Form Integration - "capsulecrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("capsulecrm").
 */

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
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_capsulecrm_credentials', {
                loadingKey: 'credentialLoading'
            });
        },
        getOwnerList: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_capsulecrm_owner_list', {
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
