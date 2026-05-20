/**
 * Advanced Form Integration — "outreach" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("outreach").
 */

Vue.component('outreach', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            credentialsList: [],
            fields: [
                { type: 'text', value: 'email',       title: 'Email',        task: ['add_to_list'], required: true },
                { type: 'text', value: 'firstName',   title: 'First Name',   task: ['add_to_list'] },
                { type: 'text', value: 'lastName',    title: 'Last Name',    task: ['add_to_list'] },
                { type: 'text', value: 'jobTitle',    title: 'Job Title',    task: ['add_to_list'] },
                { type: 'text', value: 'company',     title: 'Company',      task: ['add_to_list'] },
                { type: 'text', value: 'workPhone',   title: 'Work Phone',   task: ['add_to_list'] },
                { type: 'text', value: 'mobilePhone', title: 'Mobile Phone', task: ['add_to_list'] },
                { type: 'text', value: 'city',        title: 'City',         task: ['add_to_list'] },
                { type: 'text', value: 'state',       title: 'State',        task: ['add_to_list'] },
                { type: 'text', value: 'country',     title: 'Country',      task: ['add_to_list'] }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_outreach_credentials',
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
    template: '#outreach-action-template'
});
