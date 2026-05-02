/**
 * Advanced Form Integration - "zohocrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohocrm").
 */

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
            adfoinHelpers.getFields(this, 'adfoin_get_zohocrm_module_fields', {
                task: 'subscribe',
                loadingKey: 'moduleLoading',
                includeCredId: true,
                clearBefore: true,
                extraParams: { module: this.fielddata.moduleId, task: this.action.task }
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
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_zohocrm_modules', {
                targetKey: 'modules',
                loadingKey: 'moduleLoading',
                requireCredId: false,
                includeCredId: true
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
