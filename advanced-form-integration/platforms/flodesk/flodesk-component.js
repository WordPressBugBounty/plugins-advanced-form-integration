/**
 * Advanced Form Integration - "flodesk" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("flodesk").
 */

Vue.component('flodesk', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            segmentsLoading: false,
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['subscribe'], required: true },
                { type: 'text', value: 'firstName', title: 'First Name', task: ['subscribe'], required: false },
                { type: 'text', value: 'lastName', title: 'Last Name', task: ['subscribe'], required: false },
            ]

        }
    },
    methods: {
        getSegments: function () {
            var that = this;

            this.segmentsLoading = true;

            var segmentRequestData = {
                'action': 'adfoin_get_flodesk_segments',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, segmentRequestData, function (response) {
                that.fielddata.segments = response.data;
                that.segmentsLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.segmentId == 'undefined') {
            this.fielddata.segmentId = '';
        }

        if (typeof this.fielddata.doptin == 'undefined') {
            this.fielddata.doptin = false;
        }

        if (typeof this.fielddata.doptin != 'undefined') {
            if (this.fielddata.doptin == "false") {
                this.fielddata.doptin = false;
            }
        }

        if (this.fielddata.segmentId) {
            this.getSegments();
        }

    },
    template: '#flodesk-action-template'
});
