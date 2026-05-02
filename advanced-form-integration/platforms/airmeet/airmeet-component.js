/**
 * Advanced Form Integration - "airmeet" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("airmeet").
 */

Vue.component('airmeet', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            airmeetsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                airmeetId: '',
                airmeets: [],
                ticketClassId: '',
                sendEmail: ''
            });
        },
        getAirmeets: function () {
            var that = this;

            if (!this.fielddata.credId || this.airmeetsLoading || this.fielddata.airmeets.length > 0) {
                return;
            }

            this.airmeetsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_airmeet_list',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.$set(that.fielddata, 'airmeets', response.data);
                }
                that.airmeetsLoading = false;
            }).fail(function () {
                that.airmeetsLoading = false;
            });
        },
        loadFields: function () {
            var that = this;

            if (!this.action || this.action.task !== 'register_attendee') {
                this.fields = [];
                return;
            }

            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_airmeet_fields',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.fields = response.data.map(function (field) {
                        return {
                            type: field.type ? field.type : 'text',
                            value: field.key,
                            title: field.value,
                            task: ['register_attendee'],
                            required: !!field.required,
                            description: field.description || ''
                        };
                    });
                }
                that.fieldsLoading = false;
            }).fail(function () {
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        },
        'fielddata.credId': function () {
            this.$set(this.fielddata, 'airmeets', []);
            this.$set(this.fielddata, 'airmeetId', '');
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#airmeet-action-template'
});
