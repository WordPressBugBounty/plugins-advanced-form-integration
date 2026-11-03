/**
 * Advanced Form Integration - "lacrm" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("lacrm").
 */

Vue.component('lacrm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            userLoading: false,
            userError: '',
            fields: [
                { type: 'text', value: 'company__Company Name', title: 'Company Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Email', title: 'Company Email', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Phone', title: 'Company Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address__Street', title: 'Company Street', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_City', title: 'Company City', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_State', title: 'Company State', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_Zip', title: 'Company Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__address_Country', title: 'Company Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Background Info', title: 'Company Background Info', task: ['add_contact'], required: false },
                { type: 'text', value: 'company__Website', title: 'Company Website', task: ['add_contact'], required: false },
                { type: 'text', value: 'Name', title: 'Contact Name', task: ['add_contact'], required: false, description: 'Full name. If empty, new contacts are named after their email.' },
                { type: 'text', value: 'Email', title: 'Contact Email', task: ['add_contact'], required: false, description: 'Used to find an existing contact. Without it, only the company is saved.' },
                { type: 'text', value: 'Phone', title: 'Contact Phone', task: ['add_contact'], required: false },
                { type: 'text', value: 'Job Title', title: 'Job Title', task: ['add_contact'], required: false },
                { type: 'text', value: 'address__Street', title: 'Address', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_City', title: 'City', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_State', title: 'State', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_Zip', title: 'Zip', task: ['add_contact'], required: false },
                { type: 'text', value: 'address_Country', title: 'Country', task: ['add_contact'], required: false },
                { type: 'text', value: 'Background Info', title: 'Background Info', task: ['add_contact'], required: false },
                { type: 'text', value: 'Website', title: 'Website', task: ['add_contact'], required: false },
            ]
        }
    },
    methods: {
        getUsers: function () {
            var that = this;
            this.userError = '';
            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'users', {});
                return;
            }
            this.userLoading = true;
            jQuery.post(ajaxurl, {
                'action': 'adfoin_get_lacrm_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            }, function (response) {
                if (response && response.success) {
                    that.$set(that.fielddata, 'users', response.data);
                } else {
                    that.$set(that.fielddata, 'users', {});
                    that.userError = (response && typeof response.data === 'string') ? response.data : 'Could not load users.';
                }
            }).fail(function () {
                that.userError = 'Could not load users.';
            }).always(function () {
                that.userLoading = false;
            });
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.userId === 'undefined') {
            this.$set(this.fielddata, 'userId', '');
        }

        if (this.fielddata.credId) {
            this.getUsers();
        }
    },
    template: '#lacrm-action-template'
});
