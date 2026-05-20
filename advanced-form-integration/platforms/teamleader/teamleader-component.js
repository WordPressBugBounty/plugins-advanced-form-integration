/**
 * Advanced Form Integration - "teamleader" action component (Teamleader Focus).
 * Loaded on demand by adfoinComponentLoader.loadPlatform("teamleader").
 *
 * Teamleader exposes two tasks (create_contact, create_company) with
 * different field sets. We watch action.task and re-fetch the field list
 * whenever it changes so the UI swaps cleanly between contact and company
 * mappings.
 */

Vue.component('teamleader', {
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
                action: 'adfoin_get_teamleader_credentials',
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
            // Field set depends on the selected task; fall back to
            // create_contact when the task isn't set yet.
            var task = (this.action && this.action.task) ? this.action.task : 'create_contact';
            var taskTag = (task === 'create_company') ? 'create_company' : 'create_contact';

            this.fieldsLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_teamleader_fields',
                _nonce: adfoin.nonce,
                task: task
            }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return {
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: [taskTag],
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
        // Reload the appropriate field set whenever the user changes the
        // task. Without this, switching between Create Contact and Create
        // Company would leave stale fields in the table.
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
    template: '#teamleader-action-template'
});
