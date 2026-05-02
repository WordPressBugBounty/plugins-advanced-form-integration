/**
 * Advanced Form Integration - "clinchpad" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("clinchpad").
 */

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
            adfoinHelpers.fetchCredentials(this, 'adfoin_get_clinchpad_credentials', {
                loadingKey: 'credentialLoading'
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
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_clinchpad_pipeline', {
                targetKey: 'pipelineList',
                loadingKey: 'pipelineLoading',
                requireCredId: false,
                includeCredId: true
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
