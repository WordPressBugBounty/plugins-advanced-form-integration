/**
 * Advanced Form Integration — "discord" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("discord").
 */

Vue.component('discord', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credLoading: false,
            serverLoading: false,
            channelLoading: false,
            credentialsList: [],
            fields: [
                { type: 'textarea', value: 'message', title: 'Message (max 2000 chars)', task: ['send_message'], required: true }
            ]
        };
    },
    methods: {
        fetchCredentialsList: function () {
            var that = this;
            this.credLoading = true;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_discord_credentials',
                _nonce: adfoin.nonce
            }, function (response) {
                if (response && response.success && Array.isArray(response.data)) {
                    that.credentialsList = response.data;
                    if (!that.fielddata.credId && that.credentialsList.length === 1) {
                        // The credId watcher fetches servers. Fetching here as well
                        // sends two requests at once, and Discord rate-limits the
                        // second one (1 request/second on users/@me/guilds).
                        that.fielddata.credId = that.credentialsList[0].id;
                    } else if (that.fielddata.credId) {
                        that.fetchServers();
                    }
                }
                that.credLoading = false;
            }).fail(function () {
                that.credLoading = false;
            });
        },
        fetchServers: function () {
            var that = this;
            if (!this.fielddata.credId) {
                this.fielddata.servers = {};
                return;
            }
            this.serverLoading = true;
            var credId = this.fielddata.credId;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_discord_servers',
                credId: credId,
                _nonce: adfoin.nonce
            }, function (response) {
                // Ignore a reply for an account that is no longer selected.
                if (credId !== that.fielddata.credId) {
                    return;
                }
                that.fielddata.servers = (response && response.success && response.data) ? response.data : {};
                that.serverLoading = false;
                if (that.fielddata.serverId) {
                    that.fetchChannels();
                }
            }).fail(function () {
                that.fielddata.servers = {};
                that.serverLoading = false;
            });
        },
        fetchChannels: function () {
            var that = this;
            if (!this.fielddata.credId || !this.fielddata.serverId) {
                this.fielddata.channels = {};
                return;
            }
            this.channelLoading = true;
            var serverId = this.fielddata.serverId;
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_discord_channels',
                credId: this.fielddata.credId,
                serverId: serverId,
                _nonce: adfoin.nonce
            }, function (response) {
                // Ignore a reply for a server that is no longer selected.
                if (serverId !== that.fielddata.serverId) {
                    return;
                }
                that.fielddata.channels = (response && response.success && response.data) ? response.data : {};
                that.channelLoading = false;
            }).fail(function () {
                that.fielddata.channels = {};
                that.channelLoading = false;
            });
        }
    },
    mounted: function () {
        var defaults = {
            credId:    '',
            serverId:  '',
            channelId: '',
            servers:   {},
            channels:  {}
        };
        var that = this;
        Object.keys(defaults).forEach(function (k) {
            if (typeof that.fielddata[k] === 'undefined') {
                that.$set(that.fielddata, k, defaults[k]);
            }
        });
        this.fetchCredentialsList();
    },
    watch: {
        'fielddata.credId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.serverId = '';
                this.fielddata.channelId = '';
                this.fielddata.channels = {};
                this.fetchServers();
            }
        },
        'fielddata.serverId': function (newVal, oldVal) {
            if (newVal !== oldVal) {
                this.fielddata.channelId = '';
                this.fetchChannels();
            }
        }
    },
    template: '#discord-action-template'
});
