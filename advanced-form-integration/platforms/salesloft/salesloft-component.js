/**
 * Advanced Form Integration — "salesloft" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("salesloft").
 */

Vue.component('salesloft', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email_address', title: 'Email Address', task: ['add_to_list'], required: true },
                { type: 'text', value: 'first_name',    title: 'First Name',    task: ['add_to_list'] },
                { type: 'text', value: 'last_name',     title: 'Last Name',     task: ['add_to_list'] },
                { type: 'text', value: 'title',         title: 'Job Title',     task: ['add_to_list'] },
                { type: 'text', value: 'company',       title: 'Company',       task: ['add_to_list'] },
                { type: 'text', value: 'phone',         title: 'Phone',         task: ['add_to_list'] },
                { type: 'text', value: 'mobile_phone',  title: 'Mobile Phone',  task: ['add_to_list'] },
                { type: 'text', value: 'city',          title: 'City',          task: ['add_to_list'] },
                { type: 'text', value: 'state',         title: 'State',         task: ['add_to_list'] },
                { type: 'text', value: 'country',       title: 'Country',       task: ['add_to_list'] },
                { type: 'text', value: 'tags',          title: 'Tags',          task: ['add_to_list'], description: 'Comma-separated list' }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_salesloft_credentials',
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
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
        this.fetchCredentialsList();
    },
    template: '#salesloft-action-template'
});
