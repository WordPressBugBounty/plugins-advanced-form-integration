Vue.component('segment', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return { credentialsList: [], credLoading: false, fieldsLoading: false, fields: [] };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, { action: 'adfoin_get_segment_credentials', _nonce: adfoin.nonce }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                }
                that.credLoading = false;
            }).fail(function () { that.credLoading = false; });
        },
        fetchFields: function () {
            var that = this;
            this.fieldsLoading = true;
            jQuery.post(ajaxurl, { action: 'adfoin_get_segment_fields', _nonce: adfoin.nonce }, function (response) {
                that.fieldsLoading = false;
                if (response && response.success && Array.isArray(response.data)) {
                    that.fields = response.data.map(function (single) {
                        return { type: 'text', value: single.key, title: single.value, task: ['track_event'], required: !!single.required, description: single.description || '' };
                    });
                } else {
                    that.fields = [];
                }
            }).fail(function () { that.fields = []; that.fieldsLoading = false; });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') this.fielddata.credId = '';
        this.fetchCredentialsList();
        this.fetchFields();
    },
    template: '#segment-action-template'
});
