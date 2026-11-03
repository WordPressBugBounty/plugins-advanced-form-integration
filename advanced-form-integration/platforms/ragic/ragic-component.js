/**
 * Advanced Form Integration - "ragic" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("ragic").
 */

(function () {
    var extraFields = [];

    for (var i = 1; i <= 20; i++) {
        extraFields.push({
            type: 'text',
            value: 'field' + i,
            title: 'Extra Field ' + i,
            task: ['subscribe'],
            required: false,
            description: 'Optional. Ragic field ID and value, e.g. 1000001={{email}}. Separate pairs with ||.'
        });
    }

    Vue.component('ragic', {
        props: ["trigger", "action", "fielddata"],
        data: function () {
            return {
                credentialsList: [],
                sheets: [],
                sheetLoading: false,
                fieldsLoading: false,
                fieldsMessage: '',
                sheetFields: [],
                sheetFieldsLoaded: false,
                sheetFieldsSheet: '',
                sheetRequest: 0,
                fieldsRequest: 0,
                locationFields: [
                    { type: 'text', value: 'account_name', title: 'Account Name', task: ['subscribe'], required: false },
                    { type: 'text', value: 'tab', title: 'Tab', task: ['subscribe'], required: false },
                    { type: 'text', value: 'sheet_id', title: 'Sheet ID', task: ['subscribe'], required: false }
                ]
            };
        },
        computed: {
            visibleFields: function () {
                var that = this;
                var list = [];
                var shownEmptyExtra = false;

                // Account, tab and sheet are typed by hand only when no sheet is picked.
                if (!this.fielddata.sheetKey) {
                    list = list.concat(this.locationFields);
                }

                list = list.concat(this.sheetFields);

                // Once the sheet's fields are listed, show only the extra fields in use
                // plus one empty one; otherwise keep all twenty, as before.
                extraFields.forEach(function (field) {
                    if (!that.sheetFieldsLoaded || that.fielddata[field.value]) {
                        list.push(field);
                    } else if (!shownEmptyExtra) {
                        shownEmptyExtra = true;
                        list.push(field);
                    }
                });

                return list;
            }
        },
        methods: {
            fetchCredentialsList: function () {
                var that = this;
                jQuery.post(ajaxurl, {
                    action: 'adfoin_get_ragic_credentials_list',
                    _nonce: adfoin.nonce
                }, function (response) {
                    if (response.success) {
                        that.credentialsList = response.data;

                        if (!that.fielddata.credId && that.credentialsList.length === 1) {
                            that.fielddata.credId = that.credentialsList[0].id;
                        }

                        // An empty credId uses the default account, so sheets can still load.
                        if (that.credentialsList.length) {
                            that.getSheets();
                        }
                    }
                });
            },
            onAccountChange: function () {
                this.fielddata.sheetKey = '';
                this.clearSheetFields();
                this.getSheets();
            },
            onSheetChange: function () {
                var parts = this.splitSheetKey(this.fielddata.sheetKey);

                this.clearSheetFields();

                if (!parts) {
                    return;
                }

                this.fielddata.account_name = parts.account;
                this.fielddata.tab = parts.tab;
                this.fielddata.sheet_id = parts.sheet;
                this.getFields();
            },
            splitSheetKey: function (key) {
                var parts = (key || '').split('|');

                if (parts.length !== 3) {
                    return null;
                }

                return { account: parts[0], tab: parts[1].replace(/^\/+|\/+$/g, ''), sheet: parts[2] };
            },
            clearSheetFields: function () {
                this.sheetFields = [];
                this.sheetFieldsLoaded = false;
                this.sheetFieldsSheet = '';
                this.fieldsMessage = '';
            },
            getSheets: function () {
                var that = this;
                var request = ++this.sheetRequest;

                this.sheetLoading = true;

                jQuery.post(ajaxurl, {
                    action: 'adfoin_get_ragic_sheets',
                    credId: this.fielddata.credId,
                    _nonce: adfoin.nonce
                }, function (response) {
                    if (request !== that.sheetRequest) {
                        return;
                    }

                    that.sheetLoading = false;
                    that.sheets = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                    that.matchSavedSheet();
                }).fail(function () {
                    if (request === that.sheetRequest) {
                        that.sheetLoading = false;
                    }
                });
            },
            // Integrations saved with hand-typed account, tab and sheet ID get the
            // matching sheet selected, so their fields can be listed too.
            matchSavedSheet: function () {
                var that = this;

                if (!this.fielddata.sheetKey) {
                    var tab = String(this.fielddata.tab || '').replace(/^\/+|\/+$/g, '');

                    this.sheets.some(function (sheet) {
                        var parts = that.splitSheetKey(sheet.key);

                        if (parts && parts.account === that.fielddata.account_name && parts.tab === tab && parts.sheet === String(that.fielddata.sheet_id)) {
                            that.fielddata.sheetKey = sheet.key;
                            return true;
                        }

                        return false;
                    });
                }

                if (this.fielddata.sheetKey && this.sheetFieldsSheet !== this.fielddata.sheetKey) {
                    this.getFields();
                }
            },
            getFields: function () {
                var that = this;
                var sheetKey = this.fielddata.sheetKey;
                var parts = this.splitSheetKey(sheetKey);
                var request = ++this.fieldsRequest;

                if (!parts) {
                    return;
                }

                this.fieldsLoading = true;
                this.fieldsMessage = '';

                jQuery.post(ajaxurl, {
                    action: 'adfoin_get_ragic_fields',
                    credId: this.fielddata.credId,
                    account: parts.account,
                    tab: parts.tab,
                    sheetId: parts.sheet,
                    _nonce: adfoin.nonce
                }, function (response) {
                    // Ignore a reply for a sheet that is no longer selected.
                    if (request !== that.fieldsRequest || sheetKey !== that.fielddata.sheetKey) {
                        return;
                    }

                    that.fieldsLoading = false;
                    that.sheetFieldsSheet = sheetKey;

                    if (!response || !response.success || !Array.isArray(response.data) || !response.data.length) {
                        that.fieldsMessage = (response && response.data && response.data.reason === 'empty')
                            ? 'This sheet has no records yet, so its fields can\'t be listed. Add one record in Ragic and reselect the sheet, or use the extra fields below.'
                            : 'Couldn\'t list this sheet\'s fields. Use the extra fields below.';
                        return;
                    }

                    var keep = response.data.map(function (single) { return single.key; });

                    // Forget values for fields this sheet doesn't have.
                    Object.keys(that.fielddata).forEach(function (key) {
                        if (key.indexOf('ragic__') === 0 && keep.indexOf(key) === -1) {
                            that.$delete(that.fielddata, key);
                        }
                    });

                    that.sheetFields = response.data.map(function (single) {
                        if (typeof that.fielddata[single.key] === 'undefined') {
                            that.$set(that.fielddata, single.key, '');
                        }

                        return { type: 'text', value: single.key, title: single.value, task: ['subscribe'], required: !!single.required };
                    });
                    that.sheetFieldsLoaded = true;
                }).fail(function () {
                    if (request === that.fieldsRequest) {
                        that.fieldsLoading = false;
                        that.fieldsMessage = 'Couldn\'t list this sheet\'s fields. Use the extra fields below.';
                    }
                });
            }
        },
        mounted: function () {
            var that = this;

            // 'legacy_123456' was a placeholder that matched no account; treat it as none.
            if (typeof this.fielddata.credId == 'undefined' || this.fielddata.credId === 'legacy_123456') {
                this.$set(this.fielddata, 'credId', '');
            }

            ['sheetKey', 'account_name', 'tab', 'sheet_id'].forEach(function (key) {
                if (typeof that.fielddata[key] == 'undefined') {
                    that.$set(that.fielddata, key, '');
                }
            });

            this.fetchCredentialsList();
        },
        template: '#ragic-action-template'
    });
})();
