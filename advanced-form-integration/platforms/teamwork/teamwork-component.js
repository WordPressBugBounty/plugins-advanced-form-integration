/**
 * Advanced Form Integration — "teamwork" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("teamwork").
 *
 * Field list differs per task (create_task vs create_company) so we
 * re-fetch the field schema whenever action.task changes.
 */

Vue.component('teamwork', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_teamwork_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchFields: function () {
            var that = this;

            if (!this.action || (this.action.task !== 'create_task' && this.action.task !== 'create_company')) {
                this.fields = [];
                return;
            }

            var task = this.action.task;
            this.fieldsLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_teamwork_fields',
                task: task,
                _nonce: adfoin.nonce
            }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return {
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: [task],
                            required: !!single.required
                        };
                    });
                } else {
                    that.fields = [];
                }
            }).fail(function () {
                that.fields = [];
                that.fieldsLoading = false;
            });
        }
    },
    watch: {
        'action.task': function () {
            this.fetchFields();
        }
    },
    mounted: function () {
        var defaults = {
            credId: ''
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
        this.fetchFields();
    },
    template: '#teamwork-action-template'
});
