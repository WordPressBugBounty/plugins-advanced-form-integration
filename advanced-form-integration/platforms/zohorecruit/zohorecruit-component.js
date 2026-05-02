/**
 * Advanced Form Integration - "zohorecruit" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohorecruit").
 */

Vue.component('zohorecruit', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            organizationLoading: false,
            organizations: {},
            fields: [
                { type: 'text', value: 'first_name', title: 'First Name', task: ['create_candidate'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['create_candidate'], required: true },
                { type: 'text', value: 'email', title: 'Email', task: ['create_candidate'], required: false },
                { type: 'text', value: 'phone', title: 'Phone', task: ['create_candidate'], required: false }
            ]
        };
    },
    created: function () {
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.organizationId === 'undefined') {
            this.$set(this.fielddata, 'organizationId', '');
        }
    },
    mounted: function () {
        if (this.fielddata.credId) {
            this.fetchOrganizations();
        }
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fetchOrganizations();
            }
        }
    },
    methods: {
        fetchOrganizations: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.organizations = {};
                this.fielddata.organizationId = '';
                return;
            }

            this.organizations = {};
            this.fielddata.organizationId = '';
            this.organizationLoading = true;

            var requestData = {
                action: 'adfoin_get_zohorecruit_organizations',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success && response.data) {
                    that.organizations = response.data;
                } else {
                    that.organizations = {};
                }
                that.organizationLoading = false;
            }).fail(function () {
                that.organizations = {};
                that.organizationLoading = false;
            });
        }
    },
    template: '#zohorecruit-action-template'
});
