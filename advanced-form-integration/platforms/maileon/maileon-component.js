/**
 * Advanced Form Integration - "maileon" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("maileon").
 */

Vue.component('maileon', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            listLoading: false,
            fieldLoading: false,
            credentialLoading: false,
            credentialsList: [],
            fields: []
        }
    },
    methods: {
        fetchCredentials: function () {
            var that = this;
            this.credentialLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_maileon_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.credentialsList = response.data;
                    if (that.credentialsList.length > 0 && !that.fielddata.credId) {
                        that.fielddata.credId = that.credentialsList[0].id;
                    }
                    if (that.fielddata.credId) {
                        that.getFields();
                    }
                }
                that.credentialLoading = false;
            });
        },
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_maileon_fields', {
                task: 'subscribe',
                loadingKey: 'fieldLoading',
                requireCredId: true,
                clearBefore: true,
                extraParams: { task: this.action.task }
            });
        },
        handleAccountChange: function () {
            this.fields = [];
            this.getFields();
        }
    },
    mounted: function () {
        ['permission', 'doi', 'doiplus', 'update'].forEach(key => {
            if (typeof this.fielddata[key] === 'undefined') {
                this.fielddata[key] = '';
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        this.fetchCredentials();
    },
    template: '#maileon-action-template'
});
