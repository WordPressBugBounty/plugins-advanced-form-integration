/**
 * Advanced Form Integration - "zendesksell" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zendesksell").
 */

Vue.component('zendesksell', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getData: function () {
            if (this.action.task === 'add_lead') {
                console.log('addLead');
                this.getLeadFields();
            }
        },
        getLeadFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var requestData = {
                'action': 'adfoin_get_zendesksell_lead_fields',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success && response.data) {
                    response.data.map(function (single) {
                        that.fields.push({
                            type: 'text',
                            value: single.key,
                            title: single.value,
                            task: ['add_lead'],
                            required: false,
                            description: single.description
                        });
                    });
                }

                that.fieldsLoading = false;
            });
        }
    },
    mounted: function () {

        if (typeof this.fielddata.owner == 'undefined') {
            this.fielddata.owner = '';
        }

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        this.getData();
    },
    watch: {},
    template: '#zendesksell-action-template'
});
