/**
 * Advanced Form Integration - "telegram" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("telegram").
 */

Vue.component('telegram', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            chatLoading: false,
            chatList: []
        };
    },
    methods: {
        fetchChats: function () {
            var that = this;

            this.chatLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_telegram_updates',
                bot_api_key: this.fielddata.bot_api_key,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.chatList = response.data;
                } else {
                    console.error('Failed to fetch chat list:', response.data);
                }
                that.chatLoading = false;
            });
        }
    },
    mounted: function () {
        if (!this.fielddata.chat_id) {
            this.fetchChats();
        }
    },
    template: '#telegram-action-template'
});
