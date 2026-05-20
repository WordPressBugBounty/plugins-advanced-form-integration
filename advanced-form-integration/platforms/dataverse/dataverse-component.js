/**
 * Advanced Form Integration — "dataverse" action component.
 * Reuses the Dynamics 365 CRM credential list. Fetches the connected
 * environment's entity-set list once per account and caches it in a
 * datalist for the entity-name input.
 */

Vue.component('dataverse', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            entitiesLoading: false,
            entities: {}
        };
    },
    created: function () {
        var that = this;
        var defaults = {
            credId:    '',
            entitySet: '',
            rows:      [],
            lookups:   []
        };
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
    },
    mounted: function () {
        if (this.fielddata.credId) {
            this.loadEntities();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.entities = {};
                this.loadEntities();
            }
        }
    },
    methods: {
        onAccountChange: function () {
            this.entities = {};
            this.loadEntities();
        },
        loadEntities: function () {
            var that = this;
            if (!this.fielddata.credId) { return; }
            this.entitiesLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_dataverse_entities',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                that.entities = (response && response.success && response.data) ? response.data : {};
                that.entitiesLoading = false;
            }).fail(function () {
                that.entities = {};
                that.entitiesLoading = false;
            });
        },
        addRow: function () {
            this.fielddata.rows.push({ key: '', value: '' });
        },
        removeRow: function (idx) {
            this.fielddata.rows.splice(idx, 1);
        },
        addLookup: function () {
            this.fielddata.lookups.push({ field: '', entitySet: '', value: '' });
        },
        removeLookup: function (idx) {
            this.fielddata.lookups.splice(idx, 1);
        }
    },
    template: '#dataverse-action-template'
});
