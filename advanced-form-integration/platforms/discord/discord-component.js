/**
 * Advanced Form Integration - "discord" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("discord").
 */

Vue.component('discord', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            serverLoading: false,
            channelLoading: false,
            fields: [
                { type: 'textarea', value: 'message', title: 'Message', task: ['send_message'], required: true }
            ]
        };
    },
    methods: {
        getServers: function () {
            var that = this;
            this.serverLoading = true;

            var serverData = {
                'action': 'adfoin_get_discord_servers',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId
            };

            jQuery.post(ajaxurl, serverData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.servers = response.data;
                    }
                }
                that.serverLoading = false;
            });
        },
        getChannels: function () {
            var that = this;
            this.channelLoading = true;

            var channelData = {
                'action': 'adfoin_get_discord_channels',
                '_nonce': adfoin.nonce,
                'credId': this.fielddata.credId,
                'serverId': this.fielddata.serverId
            };

            jQuery.post(ajaxurl, channelData, function (response) {
                if (response.success) {
                    if (response.data) {
                        that.fielddata.channels = response.data;
                    }
                }
                that.channelLoading = false;
            });
        }
    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.serverId == 'undefined') {
            this.fielddata.serverId = '';
        }

        if (typeof this.fielddata.channelId == 'undefined') {
            this.fielddata.channelId = '';
        }

        if (this.fielddata.credId) {
            this.getServers();
        }

        if (this.fielddata.serverId) {
            this.getChannels();
        }
    },
    template: '#discord-action-template'
});
