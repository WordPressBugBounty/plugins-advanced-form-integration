/**
 * Advanced Form Integration - "googlesheets" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("googlesheets").
 */

/**
 * Surface a Google Sheets AJAX failure to the user. The backend returns the
 * real Google error in response.data; without this the dropdown just silently
 * stays empty and the user has no idea why ("keeps loading / nothing shows").
 */
if (typeof window.adfoinGooglesheetsError !== 'function') {
    window.adfoinGooglesheetsError = function (response, fallback) {
        var message = fallback || 'Google Sheets request failed.';
        if (response && typeof response.data === 'string' && response.data) {
            message = response.data;
        } else if (response && response.data && response.data.message) {
            message = response.data.message;
        }
        window.alert('Google Sheets: ' + message);
    };
}

Vue.component('googlesheets', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            listLoading: false,
            worksheetLoading: false,
            spreadsheetSearch: '',
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
                if (response && response.success) {
                    that.fielddata.spreadsheetList = response.data;
                } else {
                    that.fielddata.spreadsheetList = [];
                    adfoinGooglesheetsError(response, 'Could not load spreadsheets.');
                }
                that.listLoading = false;
            });
        },
        getWorksheets: function () {
            if (!this.fielddata.spreadsheetId) {
                return;
            }

            this.fielddata.worksheetList = [];
            this.fielddata.worksheetId = '';
            this.fielddata.worksheetName = '';
            this.fields = [];

            var that = this;
            this.worksheetLoading = true;

            var listData = {
                'action': 'adfoin_googlesheets_get_worksheets',
                '_nonce': adfoin.nonce,
                'spreadsheetId': this.fielddata.spreadsheetId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                if (response && response.success) {
                    that.fielddata.worksheetList = response.data;
                } else {
                    that.fielddata.worksheetList = [];
                    adfoinGooglesheetsError(response, 'Could not load worksheets.');
                }
                that.worksheetLoading = false;
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
                    'headerRow': this.fielddata.headerRow,
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
                    } else {
                        adfoinGooglesheetsError(response, 'Could not load column headers.');
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
            this.worksheetLoading = true;

            var listData = {
                'action': 'adfoin_googlesheets_get_worksheets',
                '_nonce': adfoin.nonce,
                'spreadsheetId': this.fielddata.spreadsheetId,
                'credId': this.fielddata.credId,
                'task': this.action.task
            };

            jQuery.post(ajaxurl, listData, function (response) {
                if (response && response.success) {
                    that.fielddata.worksheetList = response.data;
                } else {
                    that.fielddata.worksheetList = [];
                    adfoinGooglesheetsError(response, 'Could not load worksheets.');
                }
                that.worksheetLoading = false;
            });
        },
        filteredSpreadsheets: function () {
            var list = this.fielddata.spreadsheetList || {};
            var query = (this.spreadsheetSearch || '').toLowerCase();

            if (!query) {
                return list;
            }

            var filtered = {};

            for (var id in list) {
                if (Object.prototype.hasOwnProperty.call(list, id) &&
                    String(list[id]).toLowerCase().indexOf(query) !== -1) {
                    filtered[id] = list[id];
                }
            }

            return filtered;
        }
    },
    created: function () {

    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_googlesheets_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
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

        if (typeof this.fielddata.headerRow == 'undefined' || this.fielddata.headerRow === '') {
            this.fielddata.headerRow = 1;
        }

        if (typeof this.fielddata.valueInputOption == 'undefined' || this.fielddata.valueInputOption === '') {
            this.fielddata.valueInputOption = 'USER_ENTERED';
        }

        if (typeof this.fielddata.bottomAppend == 'undefined') {
            this.fielddata.bottomAppend = '';
        }

        if (typeof this.fielddata.createWorksheet == 'undefined') {
            this.fielddata.createWorksheet = '';
        }

        // Pre-load the spreadsheet list only when editing an existing integration
        // (a spreadsheet was already chosen). On a brand-new integration we wait
        // for the user to pick an account — the account <select>'s
        // @change="getSpreadsheets" handler triggers the fetch then.
        if (this.fielddata.spreadsheetId) {
            this.listLoading = true;

            var listRequestData = {
                'action': 'adfoin_get_spreadsheet_list',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, listRequestData, function (response) {
                if (response && response.success) {
                    that.fielddata.spreadsheetList = response.data;
                } else {
                    that.fielddata.spreadsheetList = [];
                    adfoinGooglesheetsError(response, 'Could not load spreadsheets.');
                }
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
