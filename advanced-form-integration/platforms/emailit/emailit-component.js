/**
 * Advanced Form Integration - "emailit" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("emailit").
 */

Vue.component('emailit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            groupLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: false },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        getAudiences: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.audiences = [];
                return;
            }
            this.groupLoading = true;
            this.fielddata.audiences = [];
            var requestData = {
                'action': 'adfoin_get_emailit_audiences',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };
            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.audiences = response.data;
                }
                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
        if (typeof this.fielddata.audienceId == 'undefined') {
            this.fielddata.audienceId = '';
        }
        if (!this.fielddata.audiences) {
            this.fielddata.audiences = [];
        }
        if (this.fielddata.credId) {
            this.getAudiences();
        }
    },
    template: '#emailit-action-template'
});
