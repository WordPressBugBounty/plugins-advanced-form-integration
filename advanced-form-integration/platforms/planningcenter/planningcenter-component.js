Vue.component('planningcenter', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return { fieldsLoading: false, workflowsLoading: false, groupsLoading: false, fields: [] };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_planningcenter_fields', { task: 'create_person', includeCredId: true, clearBefore: true });
        },
        getWorkflows: function () {
            var that = this;
            this.workflowsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_planningcenter_workflows',
                _nonce: adfoin.nonce,
                credId: this.fielddata.credId
            }, function (response) {
                that.fielddata.workflows = response.data;
                that.workflowsLoading = false;
            });
        },
        getGroups: function () {
            var that = this;
            this.groupsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_planningcenter_groups',
                _nonce: adfoin.nonce,
                credId: this.fielddata.credId
            }, function (response) {
                that.fielddata.groups = response.data;
                that.groupsLoading = false;
            });
        },
        getData: function () {
            this.getFields();
            this.getWorkflows();
            this.getGroups();
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        if (typeof this.fielddata.workflowId == 'undefined') this.fielddata.workflowId = '';
        if (typeof this.fielddata.groupId == 'undefined') this.fielddata.groupId = '';
        if (this.fielddata.credId) this.getData();
    },
    watch: { 'fielddata.credId': function (n, o) { if (n !== o) this.getData(); } },
    template: '#planningcenter-action-template'
});
