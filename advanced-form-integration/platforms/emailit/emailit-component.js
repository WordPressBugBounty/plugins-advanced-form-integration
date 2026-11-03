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
            audienceError: '',
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['subscribe'], required: false }
            ]
        };
    },
    methods: {
        getAudiences: function () {
            var that = this;
            this.audienceError = '';

            if (!this.fielddata.credId) {
                this.$set(this.fielddata, 'audiences', []);
                return;
            }

            this.groupLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_emailit_audiences',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                var audiences = response && response.success ? response.data : [];
                var saved = that.fielddata.audienceId;

                if (!response || !response.success) {
                    that.audienceError = (response && typeof response.data === 'string') ? response.data : 'Could not load Emailit audiences.';
                }

                // Integrations saved before Emailit's v2 API hold an audience token; keep it selected.
                if (saved && saved.indexOf('aud_') !== 0 && !audiences.some(function (a) { return a.id === saved; })) {
                    audiences = audiences.concat([{ id: saved, name: 'Saved audience' }]);
                }

                that.$set(that.fielddata, 'audiences', audiences);
            }).fail(function () {
                that.audienceError = 'Could not load Emailit audiences.';
            }).always(function () {
                that.groupLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = { credId: '', audienceId: '', audiences: [] };
        var that = this;

        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });

        if (this.fielddata.credId) {
            this.getAudiences();
        }
    },
    template: '#emailit-action-template'
});
