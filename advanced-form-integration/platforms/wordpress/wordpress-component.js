/**
 * Advanced Form Integration - "wordpress" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("wordpress").
 */

Vue.component('wordpress', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            postTypeLoading: false,
            selected: '',
            fields: [],
            title: '',
            slug: '',
            author: '',
            content: '',
            postMeta: '',
            username: '',
            email: '',
            firstName: '',
            lastName: '',
            website: '',
            password: '',
            role: '',
            userMeta: ''

        }
    },
    methods: {
        updateFieldValue: function (value) {
            if (this.selected || this.selected == 0) {
                if (this.fielddata[value] || "0" == this.fielddata[value]) {
                    this.fielddata[value] += ' {{' + this[value] + '}}';
                } else {
                    this.fielddata[value] = '{{' + this[value] + '}}';
                }
            }
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.postTypeId == 'undefined') {
            this.fielddata.postTypeId = '';
        }

        if (typeof this.fielddata.status == 'undefined') {
            this.fielddata.status = '';
        }

        if (typeof this.fielddata.role == 'undefined') {
            this.fielddata.role = '';
        }

        this.postTypeLoading = true;

        var postTypeRequestData = {
            'action': 'adfoin_get_wordpress_post_types',
            '_nonce': adfoin.nonce
        };

        jQuery.post(ajaxurl, postTypeRequestData, function (response) {
            that.fielddata.postTypes = response.data;
            that.postTypeLoading = false;
        });
    },
    watch: {},
    template: '#wordpress-action-template'
});
