/**
 * Advanced Form Integration - "bigmarker" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("bigmarker").
 */

Vue.component('bigmarker', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [],
            fieldsLoading: false,
            conferencesLoading: false,
            conferences: []
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, {
                credId: '',
                conferenceId: '',
                channelSlug: '',
                conferenceSlug: ''
            });
        },
        getConferences: function () {
            var that = this;

            if (!this.fielddata.credId || this.conferencesLoading || this.conferences.length > 0) {
                return;
            }

            this.conferencesLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_bigmarker_conferences',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success && response.data) {
                    that.conferences = response.data;
                }
                that.conferencesLoading = false;
            }).fail(function () {
                that.conferencesLoading = false;
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
                action: 'adfoin_get_bigmarker_fields',
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
            this.conferences = [];
            this.$set(this.fielddata, 'conferenceId', '');
            this.$set(this.fielddata, 'channelSlug', '');
            this.$set(this.fielddata, 'conferenceSlug', '');
        },
        'fielddata.conferenceId': function (newVal) {
            var selected = this.conferences.find(function (conf) {
                return conf.id === newVal;
            });
            if (selected) {
                this.$set(this.fielddata, 'channelSlug', selected.channel_slug);
                this.$set(this.fielddata, 'conferenceSlug', selected.conference_slug);
            }
        }
    },
    mounted: function () {
        this.ensureDefaults();
        this.loadFields();
        this.getConferences();
    },
    template: '#bigmarker-action-template'
});
