/**
 * Advanced Form Integration - "googlesheets" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("googlesheets").
 */

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
