/**
 * Advanced Form Integration - Trigger Components
 * Contains all form/trigger related Vue components
 */

// Webhook triggers
Vue.component('webhook-row', {
    props: ["trigger", "action", "fielddata"],
    template: '#webhook-row-template',
    data: function () {
        return {
            loading: false,
            webhookData: null
        }
    },
    methods: {
        copy: function (e) {
            var copyText = document.getElementById("inbound-webhook-url");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            e.target.innerText = "Copied!";
        },
        receiveData: function () {
            this.loading = true;
            this.webhookData = null;
            this.pollForWebhookData();
        },
        pollForWebhookData: function () {
            var vm = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_check_inbound_webhook_data'
            }).done(function (response) {
                if (response.success && response.data) {
                    if (response.data) {
                        var webhook_url = adfoinNewIntegration.trigger.formFields.webhook_url;
                        adfoinNewIntegration.trigger.formFields = response.data;
                        adfoinNewIntegration.trigger.formFields.webhook_url = webhook_url;
                    }
                    vm.loading = false;
                } else {
                    setTimeout(vm.pollForWebhookData, 2000);
                }
            }).fail(function () {
                vm.loading = false;
                console.error("Error fetching webhook data.");
            });
        }
    }
});

Vue.component('webhook2-row', {
    props: ["trigger", "action", "fielddata"],
    template: '#webhook2-row-template',
    data: function () {
        return {
            polling: false,
            rotating: false,
            copied: false,
            showModal: false,
            modalLoading: false,
            errorMessage: '',
            lastEventId: 0,
            pollAttempts: 0,
            lastPayload: null,
            latestPayload: {}
        };
    },
    computed: {
        integrationId: function () {
            return typeof window.adfoinIntegrationId !== 'undefined' ? parseInt(window.adfoinIntegrationId, 10) : 0;
        }
    },
    mounted: function () {
        if (!this.trigger.formFields || typeof this.trigger.formFields !== 'object') {
            this.$set(this.trigger, 'formFields', {});
        }
        if (typeof this.trigger.formFields.webhook_url === 'undefined') {
            this.$set(this.trigger.formFields, 'webhook_url', '');
        }
        if (typeof this.trigger.formFields.webhook_id === 'undefined') {
            this.$set(this.trigger.formFields, 'webhook_id', '');
        }
    },
    methods: {
        currentWebhookId: function () {
            return (this.trigger.formFields && this.trigger.formFields.webhook_id) ? this.trigger.formFields.webhook_id : '';
        },
        copyUrl: function () {
            if (!this.trigger.formFields || !this.trigger.formFields.webhook_url) return;
            var url = this.trigger.formFields.webhook_url;
            var vm = this;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    vm.copied = true;
                    setTimeout(function () { vm.copied = false; }, 1500);
                });
            } else {
                var input = document.createElement('input');
                input.setAttribute('type', 'text');
                input.setAttribute('value', url);
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                vm.copied = true;
                setTimeout(function () { vm.copied = false; }, 1500);
            }
        },
        rotateUrl: function () {
            var vm = this;
            if (this.rotating) return;
            this.rotating = true;
            this.errorMessage = "";
            jQuery.post(ajaxurl, {
                action: 'adfoin_rotate_webhooksinbound2_url',
                integrationId: this.integrationId,
                webhookId: this.currentWebhookId(),
                _nonce: adfoin.nonce
            }).done(function (response) {
                if (response.success && response.data) {
                    vm.$set(vm.trigger.formFields, 'webhook_url', response.data.webhook_url);
                    vm.$set(vm.trigger.formFields, 'webhook_id', response.data.webhook_id);
                    vm.lastEventId = 0;
                    vm.lastPayload = null;
                } else if (response.data && response.data.message) {
                    vm.errorMessage = response.data.message;
                }
            }).always(function () { vm.rotating = false; });
        },
        receiveData: function () {
            if (!this.currentWebhookId()) {
                this.errorMessage = "Webhook URL unavailable.";
                return;
            }
            this.polling = true;
            this.pollAttempts = 0;
            this.errorMessage = "";
            this.modalLoading = true;
            this.latestPayload = {};
            this.showModal = true;
            this.pollForData();
        },
        pollForData: function () {
            if (!this.polling) return;
            var vm = this;
            jQuery.post(ajaxurl, {
                action: 'adfoin_check_inbound_webhook2_data',
                _nonce: adfoin.nonce,
                webhookId: this.currentWebhookId(),
                integrationId: this.integrationId,
                afterEventId: this.lastEventId
            }).done(function (response) {
                if (response.success && response.data) {
                    vm.handlePayload(response.data);
                } else {
                    vm.pollAttempts++;
                    if (vm.pollAttempts >= 30) {
                        vm.polling = false;
                        vm.modalLoading = false;
                        vm.errorMessage = "No data received yet.";
                    } else {
                        setTimeout(function () { vm.pollForData(); }, 2000);
                    }
                }
            }).fail(function () {
                vm.pollAttempts++;
                if (vm.pollAttempts >= 30) {
                    vm.polling = false;
                    vm.modalLoading = false;
                    vm.errorMessage = "Unable to poll for webhook data.";
                } else {
                    setTimeout(function () { vm.pollForData(); }, 2000);
                }
            });
        },
        handlePayload: function (payload) {
            this.polling = false;
            this.modalLoading = false;
            this.errorMessage = '';
            this.lastEventId = payload.id;
            this.lastPayload = payload;
            this.latestPayload = payload;
            this.pollAttempts = 0;
        },
        closeModal: function () {
            this.showModal = false;
            this.polling = false;
            this.modalLoading = false;
        },
        showLatest: function () {
            if (!this.lastPayload) {
                this.loadLatestPayload();
                return;
            }
            this.latestPayload = this.lastPayload;
            this.modalLoading = false;
            this.showModal = true;
        },
        loadLatestPayload: function () {
            var vm = this;
            if (!this.currentWebhookId()) return;
            this.modalLoading = true;
            this.showModal = true;
            this.errorMessage = '';
            jQuery.post(ajaxurl, {
                action: 'adfoin_get_webhooksinbound2_payload',
                _nonce: adfoin.nonce,
                webhookId: this.currentWebhookId(),
                integrationId: this.integrationId
            }).done(function (response) {
                if (response.success && response.data) {
                    vm.handlePayload(response.data);
                } else if (response.data && response.data.message) {
                    vm.errorMessage = response.data.message;
                }
            }).fail(function () {
                vm.errorMessage = "Unable to load payload.";
            }).always(function () { vm.modalLoading = false; });
        },
        copyKey: function (key) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(key);
            } else {
                var input = document.createElement('input');
                input.setAttribute('type', 'text');
                input.setAttribute('value', key);
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
            }
        }
    }
});

// Simple trigger components - use factory pattern
(function() {
    var simpleTriggers = [
        'elementorpro', 'calderaforms', 'everestforms', 'fluentforms',
        'formcraft', 'formcraftb', 'formidable', 'forminator',
        'gravityforms', 'buddypress', 'cartflows', 'happyforms',
        'liveforms', 'ninjaforms', 'quform', 'quillforms',
        'smartforms', 'sureforms', 'weforms', 'wpforms',
        'bricks', 'wsform', 'jetformbuilder'
    ];
    
    simpleTriggers.forEach(function(name) {
        Vue.component(name, {
            props: ["trigger", "action", "fielddata"],
            template: '#' + name + '-template'
        });
    });
})();

// LifterlMS with extra logic
Vue.component('lifterlms', {
    props: ["trigger", "action", "fielddata"],
    template: '#lifterlms-template',
    mounted: function () {
        if (typeof this.trigger.extraFields.courseId == 'undefined') {
            this.trigger.extraFields.courseId = '';
        }
    }
});

// Conditional Logic components
Vue.component('cl-main', {
    props: ["trigger", "action", "fielddata"],
    template: '#cl-main-template',
    data: function () { return {} },
    methods: {
        clAddCondition: function (event) {
            var conditionL = adfoinNewIntegration.action.cl.conditions.length;
            adfoinNewIntegration.action.cl.conditions.push({ id: conditionL + 1, field: "", operator: "equal_to", value: "" });
        }
    }
});

Vue.component('conditional-logic', {
    props: ["trigger", "action", "fielddata", "condition"],
    template: '#conditional-logic-template',
    data: function () { return { selected2: '' } },
    methods: {
        clRemoveCondition: function (condition) {
            const conditionIndex = adfoinNewIntegration.action.cl.conditions.indexOf(condition);
            adfoinNewIntegration.action.cl.conditions.splice(conditionIndex, 1);
        },
        updateFieldValue: function (e) {
            if (this.selected2 || this.selected2 == 0) {
                if (this.condition.field || "0" == this.condition.field) {
                    this.condition.field += ' {{' + this.selected2 + '}}';
                } else {
                    this.condition.field = '{{' + this.selected2 + '}}';
                }
            }
        }
    }
});

// Editable field component
Vue.component('editable-field', {
    props: ["trigger", "action", "fielddata", "field"],
    template: '#editable-field-template',
    data: function () { return { selected: '' } },
    methods: {
        updateFieldValue: function (e) {
            if (this.selected || this.selected == 0) {
                if (this.fielddata[this.field.value] || "0" == this.fielddata[this.field.value]) {
                    this.fielddata[this.field.value] += ' {{' + this.selected + '}}';
                } else {
                    this.fielddata[this.field.value] = '{{' + this.selected + '}}';
                }
            }
        },
        inArray: function (needle, haystack) {
            var length = haystack.length;
            for (var i = 0; i < length; i++) {
                if (haystack[i] == needle) return true;
            }
            return false;
        }
    }
});
