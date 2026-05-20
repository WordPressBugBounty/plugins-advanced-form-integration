/**
 * Advanced Form Integration — "notion" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("notion").
 *
 * Wires up the #notion-action-template:
 *   - credentialsList  — fetched once on mount via /wp-ajax (account picker)
 *   - fielddata.databases — fetched per account via Notion `POST /v1/search`
 *   - fields — fetched per database via Notion `GET /v1/databases/{id}`
 *
 * Field keys follow the platform's `<property_type>__<property_name>`
 * convention — the send_data path splits on `__` to know how to format
 * each value for Notion's strict per-type property shape.
 */

Vue.component('notion', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialLoading: false,
            databaseLoading: false,
            fieldLoading: false,
            credentialsList: [],
            fields: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credentialLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_notion_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getDatabases();
                        if (that.fielddata.databaseId) {
                            that.getFields();
                        }
                    }
                }
                that.credentialLoading = false;
            });
        },
        getDatabases: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.databases = {};
                this.fielddata.databaseId = '';
                this.fields = [];
                return;
            }
            this.databaseLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_notion_databases',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fielddata.databases = (response && response.success && response.data) ? response.data : {};
                that.databaseLoading = false;
            }).fail(function () {
                that.fielddata.databases = {};
                that.databaseLoading = false;
            });
        },
        getFields: function () {
            var that = this;
            if (!this.fielddata.credId || !this.fielddata.databaseId) {
                this.fields = [];
                return;
            }
            this.fieldLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_notion_fields',
                credId: this.fielddata.credId,
                databaseId: this.fielddata.databaseId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldLoading = false;
                if (response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return {
                            // Keep the `type__name` key intact — the PHP
                            // send_data splits on `__` to know how to
                            // format each value into Notion's typed
                            // property shape (title vs rich_text vs date
                            // vs select etc.).
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['add_item'],
                            required: false,
                            description: single.description || ''
                        };
                    });
                } else {
                    that.fields = [];
                }
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId:     '',
            databaseId: '',
            databases:  {}
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
    },
    watch: {
        // Reload fields if the editor reuses an existing config where
        // databaseId is preloaded — the mounted hook already fires the
        // initial fetch, but this catches edit-then-switch-database.
        'fielddata.databaseId': function (newVal, oldVal) {
            if (newVal && newVal !== oldVal && this.fielddata.credId) {
                this.getFields();
            }
        }
    },
    template: '#notion-action-template'
});
