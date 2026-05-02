/**
 * Advanced Form Integration - "apptivo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("apptivo").
 */

Vue.component('apptivo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            entitiesLoading: false,
            fieldsLoading: false,
            entities: [],
            entityFields: []
        }
    },
    methods: {
        getData: function () {
            this.getEntities();
            if (this.fielddata.entityName) {
                this.getEntityFields();
            }
        },
        getEntities: function () {
            var that = this;
            var credId = this.fielddata.credId;

            if (!credId) {
                that.entities = [];
                return;
            }

            this.entitiesLoading = true;

            var requestData = {
                'action': 'adfoin_get_apptivo_entities',
                '_nonce': adfoin.nonce,
                'credId': credId
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                that.entitiesLoading = false;
                if (response.success) {
                    that.entities = response.data;
                } else {
                    that.entities = [];
                }
            }).fail(function () {
                that.entitiesLoading = false;
                that.entities = [];
            });
        },
        getEntityFields: function () {
            var that = this;
            var credId = this.fielddata.credId;
            var entityName = this.fielddata.entityName;

            if (!credId || !entityName) {
                that.entityFields = [];
                return;
            }

            this.fieldsLoading = true;

            var requestData = {
                'action': 'adfoin_get_apptivo_fields',
                '_nonce': adfoin.nonce,
                'credId': credId,
                'entityName': entityName
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                that.fieldsLoading = false;
                if (response.success) {
                    that.entityFields = response.data;
                } else {
                    that.entityFields = [];
                }
            }).fail(function () {
                that.fieldsLoading = false;
                that.entityFields = [];
            });
        }
    },
    watch: {
        'fielddata.entityName': function (newVal, oldVal) {
            if (newVal !== oldVal && newVal) {
                this.getEntityFields();
            } else if (!newVal) {
                this.entityFields = [];
            }
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        if (typeof this.fielddata.entityName === 'undefined') {
            this.$set(this.fielddata, 'entityName', '');
        }

        if (this.fielddata.credId) {
            this.getData();
        }
    },
    template: '#apptivo-action-template'
});
