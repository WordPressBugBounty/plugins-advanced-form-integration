/**
 * Advanced Form Integration - "salesmate" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("salesmate").
 */

Vue.component('salesmate', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldsLoading: false,
            tagsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['create_contact', 'create_deal'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['create_contact'], required: true },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['create_contact'], required: false },
                { type: 'text', value: 'title', title: 'Deal Title', task: ['create_deal'], required: true },
                { type: 'text', value: 'companyName', title: 'Company Name', task: ['create_company'], required: true },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_contact', 'create_company'], required: false }
            ]
        };
    },
    methods: {
        getFields: function () {
            var that = this;
            this.fieldsLoading = true;

            var fieldData = {
                action: 'adfoin_get_salesmate_fields',
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, fieldData, function (response) {
                if (response.success) {
                    that.fielddata.fields = response.data;
                }
                that.fieldsLoading = false;
            });
        },
        getTags: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_salesmate_tags', {
                targetKey: 'tags',
                loadingKey: 'tagsLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    created: function () {
        if (typeof this.fielddata.fields == 'undefined') {
            this.fielddata.fields = [];
        }

        if (typeof this.fielddata.tags == 'undefined') {
            this.fielddata.tags = [];
        }
    },
    mounted: function () {
        if (!this.fielddata.fields.length) {
            this.getFields();
        }

        if (!this.fielddata.tags.length) {
            this.getTags();
        }
    },
    template: '#salesmate-action-template'
});
