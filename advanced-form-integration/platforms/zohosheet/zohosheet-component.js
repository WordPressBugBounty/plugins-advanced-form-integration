/**
 * Advanced Form Integration - "zohosheet" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohosheet").
 */

Vue.component('zohosheet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
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
            adfoinHelpers.getFields(this, 'adfoin_get_zohosheet_fields', {
                task: 'add_row',
                loadingKey: 'fieldLoading',
                includeCredId: true,
                extraParams: {
                    workbookId: this.fielddata.workbookId,
                    worksheetId: this.fielddata.worksheetId,
                    task: this.action.task
                }
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohosheet_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
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
