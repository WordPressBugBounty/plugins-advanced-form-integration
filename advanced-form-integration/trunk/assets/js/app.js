/**
 * Advanced Form Integration - Vue App Initialization
 * Handles the main Vue instance and lazy loading of action components
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
    
    function initApp() {
        // Only initialize on pages with the integration form
        // Both new and edit pages use id="adfoin-new-integration"
        var appElement = document.getElementById('adfoin-new-integration');
        
        if (!appElement) {
            // Other handlers (toggle, delete, copy) are in core.js
            return;
        }
        
        var elementId = '#adfoin-new-integration';
        
        // Create the Vue app
        window.adfoinNewIntegration = new Vue({
            el: elementId,
            data: {
                trigger: {
                    integrationTitle: '',
                    formProviderId: '',
                    forms: '',
                    formId: '',
                    formName: '',
                    formFields: [],
                    extraFields: {}
                },
                formValidated: 0,
                actionValidated: 0,
                action: {
                    actionProviderId: '',
                    task: '',
                    cl: {
                        active: "no",
                        match: "any",
                        conditions: []
                    },
                    tasks: []
                },
                formLoading: false,
                fieldLoading: false,
                actionLoading: false,
                actionsComponentsLoading: false,
                functionLoading: false,
                refreshing: false,
                fieldData: {},
                componentKey: 0
            },
            methods: {
                changeFormProvider: function(event) {
                    var that = this;
                    this.formValidated = 1;
                    this.formLoading = true;
                    this.trigger.formId = '';
                    
                    if (this.trigger.formProviderId == '') {
                        this.trigger.forms = '';
                        this.formValidated = 0;
                        this.formLoading = false;
                        return;
                    }

                    var formProviderData = {
                        'action': 'adfoin_get_forms',
                        'nonce': adfoin.nonce,
                        'formProviderId': this.trigger.formProviderId
                    };

                    jQuery.post(ajaxurl, formProviderData, function(response) {
                        that.trigger.forms = response.data;
                        that.formValidated = 0;
                        that.formLoading = false;
                    });
                },
                
                updateFormProvider: function() {
                    var that = this;
                    this.formLoading = true;

                    var formProviderData = {
                        'action': 'adfoin_get_forms',
                        'nonce': adfoin.nonce,
                        'formProviderId': this.trigger.formProviderId
                    };

                    jQuery.post(ajaxurl, formProviderData, function(response) {
                        that.trigger.forms = response.data;
                        that.formLoading = false;
                    });
                },
                
                changedForm: function(event) {
                    var that = this;
                    this.fieldLoading = true;

                    if (!this.trigger.formFields || typeof this.trigger.formFields !== 'object') {
                        this.$set(this.trigger, 'formFields', {});
                    }

                    var formData = {
                        'action': 'adfoin_get_form_fields',
                        'formProviderId': this.trigger.formProviderId,
                        'nonce': adfoin.nonce,
                        'formId': this.trigger.formId
                    };

                    if (this.trigger.formProviderId === 'webhooksinbound2') {
                        if (this.trigger.formFields && this.trigger.formFields.webhook_id) {
                            formData.existingWebhookId = this.trigger.formFields.webhook_id;
                        }
                        if (typeof window.adfoinIntegrationId !== 'undefined') {
                            formData.integrationId = window.adfoinIntegrationId;
                        }
                    }

                    jQuery.post(ajaxurl, formData, function(response) {
                        that.trigger.formFields = response.data;
                        that.fieldLoading = false;
                        that.refreshing = false;
                    });
                },
                
                changeActionProvider: function(event) {
                    var that = this;
                    this.actionValidated = 1;
                    this.actionLoading = true;
                    this.action.task = '';
                    
                    if (this.action.actionProviderId == '') {
                        this.action.tasks = '';
                        this.actionValidated = 0;
                        this.actionLoading = false;
                        return;
                    }
                    
                    // Lazy load action components if not already loaded
                    if (!window.adfoinComponentLoader.areActionsLoaded()) {
                        this.actionsComponentsLoading = true;
                        window.adfoinComponentLoader.loadActions().then(function() {
                            that.actionsComponentsLoading = false;
                            // Increment key to force Vue to re-create the dynamic component
                            that.componentKey++;
                            that.fetchTasks();
                        }).catch(function(error) {
                            console.error('Failed to load action components:', error);
                            that.actionsComponentsLoading = false;
                            that.actionLoading = false;
                        });
                    } else {
                        this.fetchTasks();
                    }
                },
                
                fetchTasks: function() {
                    var that = this;
                    var actionProviderData = {
                        'action': 'adfoin_get_tasks',
                        'nonce': adfoin.nonce,
                        'actionProviderId': this.action.actionProviderId
                    };

                    jQuery.post(ajaxurl, actionProviderData, function(response) {
                        that.action.tasks = response.data;
                        that.actionValidated = 0;
                        that.actionLoading = false;
                    });
                },
                
                refreshForms: function() {
                    this.refreshing = true;
                    this.changedForm();
                }
            },
            computed: {
                calculatedTrigger: function() {
                    return JSON.stringify(this.trigger);
                },
                calculatedAction: function() {
                    return JSON.stringify(this.action);
                }
            },
            mounted: function() {
                var that = this;
                
                if (typeof integrationTitle != 'undefined') {
                    this.trigger.integrationTitle = integrationTitle;
                }

                if (typeof triggerData != 'undefined') {
                    this.trigger = triggerData;
                }

                if (typeof actionData != 'undefined') {
                    this.action = actionData;
                    
                    // If editing an existing integration with an action provider,
                    // we need to load the action components
                    if (this.action.actionProviderId) {
                        this.actionsComponentsLoading = true;
                        window.adfoinComponentLoader.loadActions().then(function() {
                            that.actionsComponentsLoading = false;
                            that.$forceUpdate();
                        });
                    }
                }

                if (typeof fieldData != 'undefined') {
                    this.fieldData = fieldData;
                }

                if (this.trigger.formProviderId) {
                    this.updateFormProvider();
                }
            },
            watch: {
                'trigger.formId': function(val) {
                    this.trigger.formName = this.trigger.forms[val];
                }
            }
        });
    }
    
    // Other handlers (toggle, delete, copy) are now in core.js
    // which is always loaded on all AFI pages
})();