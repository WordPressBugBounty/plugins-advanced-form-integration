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
                componentKey: 0,
                // Alphabetically sorted picker option lists, populated from
                // window globals set by the integration views. Used by the
                // <afi-searchable-select> components in Step 2 (form provider)
                // and Step 3 (action provider).
                adfoinFormProviders: (typeof window !== 'undefined' && Array.isArray(window.adfoinFormProviders)) ? window.adfoinFormProviders : [],
                adfoinActionProviders: (typeof window !== 'undefined' && Array.isArray(window.adfoinActionProviders)) ? window.adfoinActionProviders : [],
                // True once the user has typed in the integration title input.
                // Programmatic v-model writes don't fire native input events,
                // so this flag only gets set on genuine user keystrokes.
                // We also flip it true on mount when editing an existing
                // record so the saved title is never overwritten.
                titleManuallyEdited: false,

                // Validation state. Inline errors only render once the user
                // has either tried to submit the form or has focused-then-
                // blurred a field while it was still empty.
                submitAttempted: false,
                touched: {
                    integrationTitle: false,
                    formProvider: false,
                    formId: false,
                    actionProvider: false,
                    task: false
                },

                // AJAX save state.
                saving: false,
                toasts: [],
                _toastSeq: 0,

                // Dirty tracking via baseline snapshot diff.
                //
                // _baselineSnapshot is a JSON string of the form state we
                // consider "saved" — it auto-refreshes whenever anything
                // mutates trigger / action / fieldData *until* the user
                // actually interacts. After interaction, the baseline is
                // locked and the `dirty` computed reports any divergence.
                //
                // This means async post-mount writes (form list AJAX,
                // platform-component credential fetches, browser autofill,
                // password manager probes, etc.) become part of the
                // baseline rather than triggering a false dirty state.
                //
                // _bypassUnloadGuard short-circuits the prompt when the
                // user is intentionally submitting / navigating.
                _baselineSnapshot: null,
                _userInteracted: false,
                _bypassUnloadGuard: false
            },
            methods: {
                changeFormProvider: function(event) {
                    var that = this;
                    this._userInteracted = true;
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
                    this._userInteracted = true;
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
                    this._userInteracted = true;
                    this.actionValidated = 1;
                    this.actionLoading = true;
                    this.action.task = '';

                    if (this.action.actionProviderId == '') {
                        this.action.tasks = '';
                        this.actionValidated = 0;
                        this.actionLoading = false;
                        return;
                    }

                    var providerId = this.action.actionProviderId;

                    // Lazy-load just this platform's component file if it hasn't
                    // been loaded yet. Each platform now lives in its own file at
                    // platforms/<name>/<name>-component.js (mapped by
                    // adfoin.platformScripts).
                    if (!window.adfoinComponentLoader.isPlatformLoaded(providerId)) {
                        this.actionsComponentsLoading = true;
                        window.adfoinComponentLoader.loadPlatform(providerId).then(function() {
                            that.actionsComponentsLoading = false;
                            // Increment key to force Vue to re-create the dynamic component
                            that.componentKey++;
                            that.fetchTasks();
                        }).catch(function(error) {
                            // No on-demand component file is registered for this
                            // provider (e.g. a Pro-only platform that renders its
                            // fields purely server-side and has no
                            // <provider>-component.js). Log it, then still fetch
                            // the tasks list — the server-side action provider
                            // is registered (it appeared in the picker) and the
                            // task dropdown does not depend on the component.
                            console.error('Failed to load platform component for', providerId, error);
                            that.actionsComponentsLoading = false;
                            that.fetchTasks();
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
                },

                // Resolve a value to its display label inside a
                // [{value,label}] list (case-insensitive on value).
                lookupLabel: function(list, value) {
                    if (!list || value === '' || value === null || typeof value === 'undefined') return '';
                    for (var i = 0; i < list.length; i++) {
                        if (String(list[i].value) === String(value)) {
                            return list[i].label;
                        }
                    }
                    return '';
                },

                // Fired by the integration_title input on every native input
                // event. v-model assignments done programmatically (e.g. by
                // the suggestedTitle watcher) do NOT dispatch native input
                // events, so this only flips when the user types.
                onTitleInput: function() {
                    this.titleManuallyEdited = true;
                    this.touched.integrationTitle = true;
                    this._userInteracted = true;
                },

                // Mark a single required field as "touched" so its inline
                // error becomes visible. Called from @blur handlers.
                markTouched: function(key) {
                    if (Object.prototype.hasOwnProperty.call(this.touched, key)) {
                        this.touched[key] = true;
                    }
                },

                // Wired to @change on dropdowns whose selection is a real
                // user edit but doesn't already have a dedicated handler
                // (the task picker, for example). Custom Vue change events
                // don't fire native DOM events, so the form-level listener
                // doesn't see them.
                markUserInteracted: function() {
                    this._userInteracted = true;
                },

                // Snapshot the current trigger / action / fieldData as the
                // "saved" baseline. Called repeatedly during init (each
                // time async data lands) until the user interacts, then
                // again after a successful save.
                captureBaseline: function() {
                    try {
                        this._baselineSnapshot = JSON.stringify({
                            trigger:   this.trigger,
                            action:    this.action,
                            fieldData: this.fieldData
                        });
                    } catch (e) {
                        this._baselineSnapshot = '';
                    }
                },

                // Whether to render the inline error for a given key.
                showError: function(key) {
                    if (!this.errors[key]) return false;
                    return this.submitAttempted || !!this.touched[key];
                },

                // Form submit handler. Always intercepts the native submit
                // so we can:
                //   1. Light up inline errors when the form is incomplete.
                //   2. Send the save as an AJAX call instead of a full
                //      page reload, then react to the JSON response.
                // Falls back to the native admin-post.php handler if the
                // AJAX request itself fails at the network level.
                onSubmitForm: function(e) {
                    if (e && typeof e.preventDefault === 'function') {
                        e.preventDefault();
                    }
                    this.submitAttempted = true;

                    if (!this.canSave) {
                        var that = this;
                        this.$nextTick(function() {
                            var first = that.$el.querySelector('.has-error, .afi-input.has-error, [aria-invalid="true"]');
                            if (first && typeof first.focus === 'function') {
                                first.focus();
                            }
                        });
                        return false;
                    }

                    if (this.saving) return false;
                    this.saveIntegration();
                    return false;
                },

                // Build the payload from current Vue state and POST to the
                // AJAX save endpoint. On success: show a toast, reset dirty,
                // and (for new integrations) seamlessly upgrade the page
                // into edit-mode by updating window.adfoinIntegrationId and
                // patching the form's hidden inputs.
                //
                // We serialize the entire <form> so the AJAX payload mirrors
                // exactly what a native submit to admin-post.php would send
                // — including the bracketed `fieldData[xxx]` inputs each
                // per-platform Vue component renders. PHP then parses
                // $_POST['fieldData'] as a real associative array, which is
                // what the storage and render code on edit-load expects.
                saveIntegration: function() {
                    var that = this;
                    var form = document.getElementById('new-integration') || document.getElementById('edit-integration');
                    if (!form) return;

                    var typeField   = form.querySelector('input[name="type"]');
                    var editIdField = form.querySelector('input[name="edit_id"]');

                    // Wait one tick so any pending Vue DOM updates (e.g. the
                    // :value bindings on triggerData / actionData hidden
                    // inputs) have been flushed before we serialize.
                    this.$nextTick(function() {
                        var serialized = jQuery(form).serialize();

                        // Swap the form's `action` value (admin-post handler
                        // that redirects) for the AJAX action that returns
                        // JSON. The capturing group keeps the leading `&`
                        // intact when `action=` isn't first in the string.
                        serialized = serialized.replace(/(^|&)action=[^&]*/, '$1action=adfoin_save_integration_ajax');

                        that.saving = true;

                        jQuery.post(ajaxurl, serialized)
                            .done(function(response) {
                            if (response && response.success) {
                                var data = response.data || {};
                                // Refresh baseline + reopen the
                                // baseline-refresh window so async post-
                                // save writes (e.g. platform component
                                // refetching credentials after the row
                                // saves) don't immediately re-dirty the
                                // form. Real typing / dropdown picks will
                                // flip _userInteracted back on as soon as
                                // they happen and re-lock the baseline.
                                that.captureBaseline();
                                that._userInteracted = false;

                                // Promote new -> edit mode in place.
                                if (data.is_new && data.integration_id) {
                                    window.adfoinIntegrationId = data.integration_id;
                                    if (typeField) typeField.value = 'update_integration';
                                    if (!editIdField) {
                                        editIdField = document.createElement('input');
                                        editIdField.type = 'hidden';
                                        editIdField.name = 'edit_id';
                                        form.appendChild(editIdField);
                                    }
                                    editIdField.value = data.integration_id;

                                    // Rewrite the URL so a manual refresh
                                    // keeps the user in the edit context.
                                    if (data.edit_url && window.history && typeof window.history.replaceState === 'function') {
                                        try {
                                            window.history.replaceState({}, '', data.edit_url);
                                        } catch (err) { /* ignore */ }
                                    }
                                }

                                that.showToast(data.message || 'Integration saved.', 'success');
                            } else {
                                var msg = (response && response.data && response.data.message)
                                    ? response.data.message
                                    : 'Could not save the integration.';
                                that.showToast('Saving failed: ' + msg, 'error');
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Network error while saving.';
                            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                                msg = xhr.responseJSON.data.message;
                            } else if (xhr && xhr.statusText) {
                                msg = xhr.statusText;
                            }
                            that.showToast('Saving failed: ' + msg, 'error');
                        })
                        .always(function() {
                            that.saving = false;
                        });
                    });
                },

                // Toast helpers. Push a transient message onto the stack;
                // it auto-dismisses after `timeout` ms (default 4000) with
                // a fade-out animation.
                showToast: function(message, type, timeout) {
                    if (typeof type === 'undefined') type = 'info';
                    if (typeof timeout === 'undefined') timeout = 4000;
                    var id = ++this._toastSeq;
                    this.toasts.push({ id: id, message: String(message), type: type, leaving: false });
                    var that = this;
                    if (timeout > 0) {
                        setTimeout(function() { that.dismissToast(id); }, timeout);
                    }
                    return id;
                },

                dismissToast: function(id) {
                    var that = this;
                    var toast = null;
                    for (var i = 0; i < this.toasts.length; i++) {
                        if (this.toasts[i].id === id) { toast = this.toasts[i]; break; }
                    }
                    if (!toast || toast.leaving) return;
                    toast.leaving = true;
                    setTimeout(function() {
                        for (var j = 0; j < that.toasts.length; j++) {
                            if (that.toasts[j].id === id) { that.toasts.splice(j, 1); return; }
                        }
                    }, 200);
                },

                // beforeunload listener. Returning a string in modern
                // browsers triggers the native confirm dialog. We only
                // engage when the form is dirty AND the user isn't in the
                // middle of an intentional submit/navigation.
                onBeforeUnload: function(e) {
                    if (this._bypassUnloadGuard) return undefined;
                    if (!this.dirty) return undefined;
                    var msg = 'You have unsaved changes. Leave this page?';
                    if (e) e.returnValue = msg;
                    return msg;
                }
            },
            computed: {
                calculatedTrigger: function() {
                    return JSON.stringify(this.trigger);
                },
                calculatedAction: function() {
                    return JSON.stringify(this.action);
                },
                // Sorted [{value, label}] list for the form-name picker.
                // trigger.forms is an object map keyed by form id, populated
                // via AJAX when a form provider is selected. Empty string
                // before that, so guard for non-object values.
                formOptions: function() {
                    var forms = this.trigger.forms;
                    if (!forms || typeof forms !== 'object') return [];
                    var list = [];
                    for (var key in forms) {
                        if (Object.prototype.hasOwnProperty.call(forms, key)) {
                            list.push({
                                value: String(key),
                                label: String(forms[key])
                            });
                        }
                    }
                    list.sort(function(a, b) {
                        var al = a.label.toLowerCase();
                        var bl = b.label.toLowerCase();
                        if (al < bl) return -1;
                        if (al > bl) return 1;
                        return 0;
                    });
                    return list;
                },
                // Sorted [{value, label}] list for the action task picker.
                // action.tasks is an object map keyed by task id, populated
                // when an action provider is selected. May be empty string.
                taskOptions: function() {
                    var tasks = this.action.tasks;
                    if (!tasks || typeof tasks !== 'object') return [];
                    var list = [];
                    for (var key in tasks) {
                        if (Object.prototype.hasOwnProperty.call(tasks, key)) {
                            list.push({
                                value: String(key),
                                label: String(tasks[key])
                            });
                        }
                    }
                    list.sort(function(a, b) {
                        var al = a.label.toLowerCase();
                        var bl = b.label.toLowerCase();
                        if (al < bl) return -1;
                        if (al > bl) return 1;
                        return 0;
                    });
                    return list;
                },

                // Map of per-field error message strings. Empty string
                // means "no error". Keys correspond to entries in
                // this.touched.
                errors: function() {
                    var titleStr = this.trigger.integrationTitle ? String(this.trigger.integrationTitle).trim() : '';
                    return {
                        integrationTitle: titleStr ? '' : 'Please enter an integration title.',
                        formProvider:     this.trigger.formProviderId ? '' : 'Please select a form provider.',
                        formId:           this.trigger.formId          ? '' : 'Please select a form.',
                        actionProvider:   this.action.actionProviderId ? '' : 'Please select a platform.',
                        task:             this.action.task             ? '' : 'Please select a task.'
                    };
                },

                // True when the current form state differs from the
                // saved baseline. Reads every nested reactive property
                // through JSON.stringify, so Vue tracks them as deps and
                // recomputes whenever anything inside trigger / action /
                // fieldData changes.
                dirty: function() {
                    if (!this._baselineSnapshot) return false;
                    try {
                        var current = JSON.stringify({
                            trigger:   this.trigger,
                            action:    this.action,
                            fieldData: this.fieldData
                        });
                        return current !== this._baselineSnapshot;
                    } catch (e) {
                        return false;
                    }
                },

                // True only when every required field is filled and no
                // background AJAX is still in flight.
                canSave: function() {
                    var e = this.errors;
                    var anyError = e.integrationTitle || e.formProvider || e.formId || e.actionProvider || e.task;
                    if (anyError) return false;
                    if (this.formLoading || this.fieldLoading || this.actionLoading || this.actionsComponentsLoading) return false;
                    return true;
                },

                // Human-friendly title built from the current selections,
                // e.g. "Contact Form 7: Newsletter → HubSpot (Create Contact)".
                // Returns an empty string while nothing is picked, so the
                // initial "Integration #N" placeholder isn't clobbered.
                suggestedTitle: function() {
                    var triggerLabel = this.lookupLabel(this.adfoinFormProviders, this.trigger.formProviderId);
                    var formName     = this.trigger.formName ? String(this.trigger.formName).trim() : '';
                    var actionLabel  = this.lookupLabel(this.adfoinActionProviders, this.action.actionProviderId);
                    var taskLabel    = '';

                    if (this.action.task && this.action.tasks && typeof this.action.tasks === 'object') {
                        if (Object.prototype.hasOwnProperty.call(this.action.tasks, this.action.task)) {
                            taskLabel = String(this.action.tasks[this.action.task]);
                        }
                    }

                    var parts = [];

                    if (triggerLabel) {
                        parts.push(formName ? (triggerLabel + ': ' + formName) : triggerLabel);
                    } else if (formName) {
                        parts.push(formName);
                    }

                    if (actionLabel) {
                        parts.push(taskLabel ? (actionLabel + ' (' + taskLabel + ')') : actionLabel);
                    }

                    return parts.join(' → ');
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

                    // If editing an existing integration, load just that
                    // provider's component on mount.
                    if (this.action.actionProviderId) {
                        this.actionsComponentsLoading = true;
                        window.adfoinComponentLoader.loadPlatform(this.action.actionProviderId).then(function() {
                            that.actionsComponentsLoading = false;
                            that.$forceUpdate();
                        }).catch(function(error) {
                            console.error('Failed to load platform component for', that.action.actionProviderId, error);
                            that.actionsComponentsLoading = false;
                        });
                    }
                }

                if (typeof fieldData != 'undefined') {
                    this.fieldData = fieldData;
                }

                if (this.trigger.formProviderId) {
                    this.updateFormProvider();
                }

                // When editing a saved integration, treat its title as
                // user-authored — never overwrite it with a freshly suggested
                // one. New integrations start unedited so the suggestion
                // engine can replace the "Integration #N" default once the
                // user makes any selection.
                if (typeof window !== 'undefined' && window.adfoinIntegrationId && parseInt(window.adfoinIntegrationId, 10) > 0) {
                    this.titleManuallyEdited = true;
                }

                // Capture an initial baseline immediately. The deep
                // watchers will keep refreshing it as async post-mount
                // writes land (form list, platform credentials, the
                // formId→formName watcher, etc.) until the user's first
                // real interaction locks it.
                this.captureBaseline();

                // Wire the unsaved-changes guard. Stored as a bound
                // reference so beforeUnmount can detach the same function.
                this._boundBeforeUnload = this.onBeforeUnload.bind(this);
                window.addEventListener('beforeunload', this._boundBeforeUnload);

                // Detect real user interaction with the form so async AJAX
                // writes during init don't trigger a false unsaved-changes
                // warning. Native input/change events fire on text inputs,
                // textareas, native selects, and v-model'd controls — they
                // do NOT fire from programmatic value writes (Vue updating
                // a hidden input's :value, the suggestedTitle watcher, the
                // platform component populating credential lists). Custom
                // Vue events from <afi-searchable-select> aren't native
                // events either; the dropdown handlers (changeFormProvider
                // etc.) flip the flag explicitly.
                var formEl = document.getElementById('new-integration') || document.getElementById('edit-integration');
                if (formEl) {
                    var markInteracted = function() { that._userInteracted = true; };
                    formEl.addEventListener('input', markInteracted, true);
                    formEl.addEventListener('change', markInteracted, true);
                    this._formEl = formEl;

                    formEl.addEventListener('submit', function() {
                        that._bypassUnloadGuard = true;
                    });
                }
            },
            beforeUnmount: function() {
                if (this._boundBeforeUnload) {
                    window.removeEventListener('beforeunload', this._boundBeforeUnload);
                }
            },
            watch: {
                'trigger.formId': function(val) {
                    this.trigger.formName = this.trigger.forms[val];
                },
                // Auto-fill the integration title from the user's selections
                // until they edit it themselves. We never overwrite with an
                // empty string so the initial "Integration #N" placeholder
                // stays put while nothing is picked.
                suggestedTitle: function(newVal) {
                    if (this.titleManuallyEdited) return;
                    if (!newVal) return;
                    this.trigger.integrationTitle = newVal;
                },
                // Baseline-refresh watchers. While the user hasn't
                // interacted yet, every mutation to trigger / action /
                // fieldData becomes part of the baseline. The moment a
                // real user edit fires (input/change/keydown event on the
                // form, or one of the dropdown handlers) _userInteracted
                // flips true and the baseline locks — `dirty` is then a
                // pure JSON diff against that locked snapshot.
                trigger: {
                    deep: true,
                    handler: function() {
                        if (!this._userInteracted) this.captureBaseline();
                    }
                },
                action: {
                    deep: true,
                    handler: function() {
                        if (!this._userInteracted) this.captureBaseline();
                    }
                },
                fieldData: {
                    deep: true,
                    handler: function() {
                        if (!this._userInteracted) this.captureBaseline();
                    }
                }
            }
        });
    }
    
    // Other handlers (toggle, delete, copy) are now in core.js
    // which is always loaded on all AFI pages
})();