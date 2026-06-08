/**
 * Advanced Form Integration - "zohodesk" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohodesk").
 */

Vue.component('zohodesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            organizationLoading: false,
            departmentLoading: false,
            ownerLoading: false,
            fields: []

        }
    },
    methods: {

        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_zohodesk_fields', {
                task: 'subscribe',
                loadingKey: 'departmentLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: {
                    orgId: this.fielddata.orgId,
                    departmentId: this.fielddata.departmentId,
                    task: this.action.task
                }
            });
        },
        getOrganizations: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_zohodesk_organizations', {
                targetKey: 'organizations',
                loadingKey: 'organizationLoading',
                requireCredId: false,
                includeCredId: true
            });
            this.getOwners();
        },
        getDepartments: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_zohodesk_departments', {
                targetKey: 'departments',
                loadingKey: 'departmentLoading',
                requireCredId: false,
                includeCredId: true,
                extraParams: { orgId: this.fielddata.orgId }
            });
        },
        getOwners: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_zohodesk_owners', {
                targetKey: 'owners',
                loadingKey: 'ownerLoading',
                requireCredId: false,
                includeCredId: true
            });
        },
    },
    created: function () {
        if (this.fielddata.credId && this.fielddata.orgId) {
            this.getDepartments();
        }
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohodesk_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
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
