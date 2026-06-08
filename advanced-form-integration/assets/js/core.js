/**
 * Advanced Form Integration - Core JavaScript
 *
 * Contains:
 *   1. Shared `adfoinHelpers` used by every per-platform Vue component.
 *      (Moved here from the old monolithic assets/js/script.js so they are
 *       always available before any platform component loads.)
 *   2. The lazy-loading infrastructure (adfoinComponentLoader) that fetches
 *      a single platform's component file on demand. Each platform now lives
 *      at platforms/<name>/<name>-component.js and the URL map is provided
 *      by PHP via `adfoin.platformScripts`.
 *   3. Misc utilities and the `api-key-management` Vue component.
 */

window.adfoinHelpers = window.adfoinHelpers || {};

/**
 * Global safeguard for the Account dropdown.
 *
 * For backward compatibility, the per-platform components default
 * `fielddata.credId` to the migrated single-account id ('legacy_123456').
 * On installs that never had that legacy account, the id matches no <option>,
 * so the Account <select> renders blank instead of its "Select Account..."
 * placeholder. Once the real account options are present, drop the phantom
 * legacy id so the placeholder shows. Migrated users (who genuinely have a
 * legacy_123456 option) are left untouched, so existing integrations are
 * unaffected.
 */
if (window.Vue && typeof window.Vue.mixin === 'function') {
    window.Vue.mixin({
        updated: function () {
            var fd = this.fielddata;
            if (!fd || fd.credId !== 'legacy_123456') {
                return;
            }
            if (!this.$el || typeof this.$el.querySelector !== 'function') {
                return;
            }
            var sel = this.$el.querySelector('select[name="fieldData[credId]"]');
            // Wait until real account options exist (more than the lone
            // placeholder) so we never reset during the AJAX loading window.
            if (!sel || !sel.options || sel.options.length <= 1) {
                return;
            }
            if (!sel.querySelector('option[value="legacy_123456"]')) {
                fd.credId = '';
            }
        }
    });
}

/**
 * Generic credentials fetcher used by every provider component.
 * Replaces the repetitive `getCredentials` boilerplate in every Vue component.
 *
 * @param {Object} vm      The Vue component instance (pass `this`).
 * @param {string} action  The WP AJAX action name (e.g. 'adfoin_get_mailchimp_credentials').
 * @param {Object} [options]
 *   loadingKey  {string}   Property on `vm` to toggle while loading (e.g. 'credentialLoading'). Optional.
 *   listKey     {string}   Property on `vm` to receive the response data. Defaults to 'credentialsList'.
 *   nonce       {string}   Override nonce. Defaults to `adfoin.nonce`.
 *   autoSelect  {string}   'first' | 'legacy' | 'legacy_or_first' to auto-pick credId when none is set.
 *   clearOnFail {boolean}  When true, clears the list on failure and binds a `.fail()` handler.
 *   onSuccess   {Function} Called as `onSuccess.call(vm, response.data)` after the list is set.
 *   onLoaded    {Function} Called as `onLoaded.call(vm, vm.fielddata.credId)` only if a credId is present.
 */
window.adfoinHelpers.fetchCredentials = function (vm, action, options) {
    options = options || {};
    var loadingKey  = options.loadingKey;
    var listKey     = options.listKey || 'credentialsList';
    var nonce       = options.nonce !== undefined ? options.nonce : adfoin.nonce;
    var autoSelect  = options.autoSelect;
    var clearOnFail = options.clearOnFail === true;
    var onSuccess   = options.onSuccess;
    var onLoaded    = options.onLoaded;

    if (loadingKey) vm[loadingKey] = true;

    var jqxhr = jQuery.post(ajaxurl, {
        action: action,
        _nonce: nonce
    }, function (response) {
        if (response.success) {
            vm[listKey] = response.data;

            if (autoSelect && vm[listKey] && vm[listKey].length > 0 && !vm.fielddata.credId) {
                if (autoSelect === 'first') {
                    vm.fielddata.credId = vm[listKey][0].id;
                } else if (autoSelect === 'legacy') {
                    // Only default to the migrated legacy account when it
                    // actually exists; otherwise leave credId empty so the
                    // "Select Account..." placeholder is shown.
                    var legacyCred = vm[listKey].find(function (cred) {
                        return cred.id === 'legacy_123456';
                    });
                    if (legacyCred) {
                        vm.fielddata.credId = 'legacy_123456';
                    }
                } else if (autoSelect === 'legacy_or_first') {
                    var legacy = vm[listKey].find(function (cred) {
                        return cred.id === 'legacy_123456' || (cred.title && cred.title.includes('Legacy'));
                    });
                    vm.fielddata.credId = legacy ? legacy.id : vm[listKey][0].id;
                }
            }

            if (typeof onSuccess === 'function') onSuccess.call(vm, response.data);

            if (typeof onLoaded === 'function' && vm.fielddata.credId) {
                onLoaded.call(vm, vm.fielddata.credId);
            }
        } else if (clearOnFail) {
            vm[listKey] = [];
        }

        if (loadingKey) vm[loadingKey] = false;
    });

    if (clearOnFail) {
        jqxhr.fail(function () {
            if (loadingKey) vm[loadingKey] = false;
        });
    }

    return jqxhr;
};

/**
 * Initialize fielddata properties with defaults when they are undefined.
 */
window.adfoinHelpers.ensureFielddataDefaults = function (vm, defaults) {
    Object.keys(defaults).forEach(function (key) {
        if (typeof vm.fielddata[key] === 'undefined') {
            vm.$set(vm.fielddata, key, defaults[key]);
        }
    });
};

/**
 * Generic field loader that fetches a list of fields via AJAX and maps them into
 * Vue field definitions on `vm.fields`.
 */
window.adfoinHelpers.loadFields = function (vm, action, options) {
    options = options || {};
    var task          = options.task;
    var taskGate      = options.taskGate;
    var requireCredId = options.requireCredId === true;
    var loadingKey    = options.loadingKey || 'fieldsLoading';
    var textareaKeys  = options.textareaKeys || [];
    var clearOnEmpty  = options.clearOnEmpty === true;
    var extraParams   = options.extraParams || {};
    var onStart       = options.onStart;
    var onSuccess     = options.onSuccess;

    if (taskGate) {
        var validTasks = Array.isArray(taskGate) ? taskGate : [taskGate];
        if (!vm.action || validTasks.indexOf(vm.action.task) === -1) {
            vm.fields = [];
            return false;
        }
    }

    if (requireCredId && !vm.fielddata.credId) {
        vm.fields = [];
        return false;
    }

    vm[loadingKey] = true;

    var fieldTask = Array.isArray(task) ? task : [task];

    var requestData = jQuery.extend({
        action: action,
        _nonce: adfoin.nonce
    }, requireCredId ? { credId: vm.fielddata.credId } : {}, extraParams);

    var jqxhr = jQuery.post(ajaxurl, requestData, function (response) {
        if (response.success && response.data) {
            vm.fields = response.data.map(function (field) {
                var type;
                if (textareaKeys.indexOf(field.key) !== -1) {
                    type = 'textarea';
                } else {
                    type = field.type || 'text';
                }
                return {
                    type: type,
                    value: field.key,
                    title: field.value,
                    task: fieldTask,
                    required: !!field.required,
                    description: field.description || ''
                };
            });
            if (typeof onSuccess === 'function') onSuccess.call(vm, response.data);
        } else if (clearOnEmpty) {
            vm.fields = [];
        }
        vm[loadingKey] = false;
    }).fail(function () {
        vm[loadingKey] = false;
    });

    if (typeof onStart === 'function') onStart.call(vm);

    return jqxhr;
};

/**
 * Generic field fetcher that pushes mapped field defs into `vm.fields`.
 */
window.adfoinHelpers.getFields = function (vm, action, options) {
    options = options || {};
    var task          = options.task;
    var taskGate      = options.taskGate;
    var requireCredId = options.requireCredId === true;
    var includeCredId = options.includeCredId === true || requireCredId;
    var loadingKey    = options.loadingKey || 'fieldsLoading';
    var clearBefore   = options.clearBefore === true;
    var extraParams   = options.extraParams || {};
    var mapField      = options.mapField;

    if (taskGate) {
        var validTasks = Array.isArray(taskGate) ? taskGate : [taskGate];
        if (!vm.action || validTasks.indexOf(vm.action.task) === -1) {
            vm.fields = [];
            vm[loadingKey] = false;
            return false;
        }
    }

    if (requireCredId && !vm.fielddata.credId) {
        vm.fields = [];
        return false;
    }

    if (clearBefore) {
        vm.fields = [];
    }

    vm[loadingKey] = true;

    var fieldTask = Array.isArray(task) ? task : [task];
    var resolvedExtra = typeof extraParams === 'function'
        ? extraParams.call(vm, vm)
        : extraParams;

    var requestData = jQuery.extend({
        action: action,
        _nonce: adfoin.nonce
    }, includeCredId ? { credId: vm.fielddata.credId } : {}, resolvedExtra);

    var defaultMapper = function (single) {
        return {
            type: single.type || 'text',
            value: single.key,
            title: single.value,
            task: fieldTask,
            required: !!single.required,
            description: single.description || ''
        };
    };
    var mapper = mapField || defaultMapper;

    return jQuery.post(ajaxurl, requestData, function (response) {
        if (response.success && response.data) {
            response.data.map(function (single) {
                vm.fields.push(mapper.call(vm, single));
            });
        }
    }).always(function () {
        vm[loadingKey] = false;
    });
};

/**
 * Generic resource fetcher that stores `response.data` on `vm.fielddata.<targetKey>`.
 */
window.adfoinHelpers.fetchToFielddata = function (vm, action, options) {
    options = options || {};
    var targetKey      = options.targetKey;
    var loadingKey     = options.loadingKey;
    var requireCredId  = options.requireCredId !== false;
    var includeCredId  = options.includeCredId === true || requireCredId;
    var emptyValue     = options.emptyValue !== undefined ? options.emptyValue : {};
    var requireSuccess = options.requireSuccess === true;
    var extraParams    = options.extraParams || {};

    if (requireCredId && !vm.fielddata.credId) {
        vm.$set(vm.fielddata, targetKey, emptyValue);
        return;
    }

    if (loadingKey) vm[loadingKey] = true;

    var resolvedExtra = typeof extraParams === 'function'
        ? extraParams.call(vm, vm)
        : extraParams;

    var requestData = jQuery.extend({
        action: action,
        _nonce: adfoin.nonce
    }, includeCredId ? { credId: vm.fielddata.credId } : {}, resolvedExtra);

    return jQuery.post(ajaxurl, requestData, function (response) {
        if (!requireSuccess || response.success) {
            vm.$set(vm.fielddata, targetKey,
                (response && response.data) ? response.data : emptyValue);
        }
    }).always(function () {
        if (loadingKey) vm[loadingKey] = false;
    });
};

// =====================================================================
// Per-platform lazy-loading infrastructure
// =====================================================================
//
// `adfoin.platformScripts` is localized by PHP and maps a platform's
// action-provider key (e.g. 'aweber', 'mailchimp') to the URL of its
// component file (platforms/<name>/<name>-component.js). When the user
// picks an action provider, app.js calls
// `adfoinComponentLoader.loadPlatform(name)` which fetches just that one
// file. Previously the loader fetched the entire 14k-line script.js bundle
// the first time any provider was selected.

window.adfoinComponentLoader = {
    loadedPlatforms: {},
    loadingPromises: {},

    /**
     * Load a single platform's Vue component file on demand.
     *
     * @param  {string}  name  The action-provider key (e.g. 'aweber').
     * @return {Promise}
     */
    loadPlatform: function (name) {
        var self = this;

        if (!name) {
            return Promise.reject(new Error('loadPlatform: missing platform name'));
        }

        if (this.loadedPlatforms[name]) {
            return Promise.resolve();
        }

        if (this.loadingPromises[name]) {
            return this.loadingPromises[name];
        }

        // If the component has already been registered through some other
        // means (e.g. a plugin printed it inline), don't re-fetch.
        if (window.Vue && typeof Vue.component === 'function' && Vue.component(name)) {
            this.loadedPlatforms[name] = true;
            return Promise.resolve();
        }

        var map = (window.adfoin && adfoin.platformScripts) || {};
        var url = map[name];

        if (!url) {
            return Promise.reject(new Error('No script registered for platform: ' + name));
        }

        var version = (window.adfoin && adfoin.version) || Date.now();
        var fullUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'ver=' + encodeURIComponent(version);

        this.loadingPromises[name] = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = fullUrl;
            script.async = true;

            script.onload = function () {
                self.loadedPlatforms[name] = true;
                delete self.loadingPromises[name];
                document.dispatchEvent(new CustomEvent('adfoin-platform-loaded', { detail: { name: name } }));
                resolve();
            };

            script.onerror = function () {
                delete self.loadingPromises[name];
                reject(new Error('Failed to load platform script: ' + name + ' (' + fullUrl + ')'));
            };

            document.head.appendChild(script);
        });

        return this.loadingPromises[name];
    },

    /**
     * @return {boolean} Whether the named platform's component is ready.
     */
    isPlatformLoaded: function (name) {
        if (this.loadedPlatforms[name]) return true;
        return !!(window.Vue && typeof Vue.component === 'function' && Vue.component(name));
    },

    /**
     * @deprecated Kept for backward compatibility. Pre-loads every platform
     *             listed in `adfoin.platformScripts` in parallel. New code
     *             should call `loadPlatform(name)` for the specific provider
     *             the user is editing.
     */
    loadActions: function () {
        var self = this;
        var map = (window.adfoin && adfoin.platformScripts) || {};
        var names = Object.keys(map);
        if (names.length === 0) return Promise.resolve();
        return Promise.all(names.map(function (name) {
            return self.loadPlatform(name).catch(function () { /* ignore individual failures */ });
        })).then(function () {
            document.dispatchEvent(new CustomEvent('adfoin-actions-loaded'));
        });
    },

    /**
     * @deprecated Synonym for isPlatformLoaded(name) plus a shortcut for
     *             "any" check. Returns true once at least one platform has
     *             been loaded so legacy guard clauses keep working.
     */
    isLoaded: function (componentName) {
        return this.isPlatformLoaded(componentName);
    },

    /**
     * @deprecated Returns true after the first platform has finished loading.
     *             Old call sites used this to gate fetchTasks().
     */
    areActionsLoaded: function () {
        for (var k in this.loadedPlatforms) {
            if (Object.prototype.hasOwnProperty.call(this.loadedPlatforms, k) && this.loadedPlatforms[k]) {
                return true;
            }
        }
        return false;
    },
    
    // Register component mapping (component name -> category file) - for future category-based splitting
    componentMap: {
        // Email Marketing
        'mailchimp': 'email-marketing',
        'klaviyo': 'email-marketing',
        'brevo': 'email-marketing',
        'sendinblue': 'email-marketing',
        'activecampaign': 'email-marketing',
        'mailerlite': 'email-marketing',
        'mailerlite2': 'email-marketing',
        'convertkit': 'email-marketing',
        'kit': 'email-marketing',
        'getresponse': 'email-marketing',
        'drip': 'email-marketing',
        'aweber': 'email-marketing',
        'constantcontact': 'email-marketing',
        'campaignmonitor': 'email-marketing',
        'sendgrid': 'email-marketing',
        'sendfox': 'email-marketing',
        'vision6': 'email-marketing',
        'moosend': 'email-marketing',
        'omnisend': 'email-marketing',
        'sendpulse': 'email-marketing',
        'benchmark': 'email-marketing',
        'emailoctopus': 'email-marketing',
        'flodesk': 'email-marketing',
        'beehiiv': 'email-marketing',
        'loops': 'email-marketing',
        'sender': 'email-marketing',
        'mailjet': 'email-marketing',
        'elasticemail': 'email-marketing',
        'cleverreach': 'email-marketing',
        'mailup': 'email-marketing',
        'sendy': 'email-marketing',
        'mailpoet': 'email-marketing',
        'fluentcrm': 'email-marketing',
        'groundhogg': 'email-marketing',
        
        // CRM
        'zohocrm': 'crm',
        'hubspot': 'crm',
        'salesforce': 'crm',
        'pipedrive': 'crm',
        'freshsales': 'crm',
        'agilecrm': 'crm',
        'keap': 'crm',
        'insightly': 'crm',
        'copper': 'crm',
        'close': 'crm',
        'nutshell': 'crm',
        'capsulecrm': 'crm',
        'nimble': 'crm',
        'zendesksell': 'crm',
        'salesflare': 'crm',
        'vtiger': 'crm',
        'bigin': 'crm',
        'attio': 'crm',
        'flowlu': 'crm',
        'liondesk': 'crm',
        'followupboss': 'crm',
        'wealthbox': 'crm',
        'lacrm': 'crm',
        'clinchpad': 'crm',
        'companyhub': 'crm',
        'onehash': 'crm',
        'engagebay': 'crm',
        'salesmate': 'crm',
        'dynamics365': 'crm',
        'dynamics365marketing': 'crm',
        'superoffice': 'crm',
        'mautic': 'crm',
        'nocrmio': 'crm',
        'teamleader': 'crm',
        
        // Webinar & Events
        'demio': 'webinar',
        'livestorm': 'webinar',
        'gotowebinar': 'webinar',
        'webinarjam': 'webinar',
        'everwebinar': 'webinar',
        'zoomwebinar': 'webinar',
        'bigmarker': 'webinar',
        'airmeet': 'webinar',
        'on24': 'webinar',
        'adobeconnect': 'webinar',
        'zohomeeting': 'webinar',
        'webinargeek': 'webinar',
        'ewebinar': 'webinar',
        
        // Project Management
        'asana': 'project-management',
        'trello': 'project-management',
        'monday': 'project-management',
        'clickup': 'project-management',
        'todoist': 'project-management',
        'wrike': 'project-management',
        'mstodo': 'project-management',
        'anydo': 'project-management',
        'googletasks': 'project-management',
        'teamwork': 'project-management',

        // Communication
        'slack': 'communication',
        'msteams': 'communication',
        'discord': 'communication',
        'telegram': 'communication',
        'whatsapp': 'communication',
        'twilio': 'communication',
        'pushover': 'communication',
        'slicktext': 'communication',
        'eztexting': 'communication',
        'justcall': 'communication',
        
        // Automation & Webhooks
        'zapier': 'automation',
        'webhook': 'automation',
        'pabbly': 'automation',
        'highlevel': 'automation',
        'autopilot': 'automation',
        'autopilotnew': 'automation',
        'kartra': 'automation',
        'systemeio': 'automation',
        
        // Spreadsheets & Databases
        'googlesheets': 'spreadsheets',
        'airtable': 'spreadsheets',
        'smartsheet': 'spreadsheets',
        'quickbase': 'spreadsheets',
        'knack': 'spreadsheets',
        'ragic': 'spreadsheets',
        'zohosheet': 'spreadsheets',
        'kintone': 'spreadsheets',
        
        // Scheduling
        'acuity': 'scheduling',
        'googlecalendar': 'scheduling',
        'addcal': 'scheduling',
        'fluentbooking': 'scheduling',
        'latepoint': 'scheduling',
        'calendly': 'scheduling',
        'calcom': 'scheduling',

        // AI
        'openai': 'ai',

        // Payments
        'stripe': 'payments',

        // Events
        'eventbrite': 'events',
        
        // Support & Helpdesk
        'zendesk': 'support',
        'freshdesk': 'support',
        'intercom': 'support',
        'fluentsupport': 'support',
        'zohodesk': 'support',
        'gistcrm': 'support',
        'helpscout': 'support',
        'livechat': 'support',
        'tidio': 'support',
        'tawkto': 'support',
        
        // Accounting & Finance
        'zohobooks': 'accounting',
        'moneybird': 'accounting',
        'scoro': 'accounting',
        'xero': 'accounting',
        'quickbooksonline': 'accounting',
        'lexoffice': 'accounting',
        'sevdesk': 'accounting',
        'fortnox': 'accounting',
        'freshbooks': 'accounting',
        'freeagent': 'accounting',
        'myob': 'accounting',
        'economic': 'accounting',
        'vismaeaccounting': 'accounting',

        // E-commerce
        'shopify': 'ecommerce',

        // HR & People
        'personio': 'hr',
        'employmenthero': 'hr',
        'deputy': 'hr',
        'recruitee': 'hr',
        'workable': 'hr',
        'workable': 'hr',
        'recruitee': 'hr',
        
        // WordPress Specific
        'wordpress': 'wordpress',
        'woocommerce': 'wordpress',
        'affiliatewp': 'wordpress',
        'givewp': 'wordpress',
        'slicewp': 'wordpress',
        'mycred': 'wordpress',
        'gamipress': 'wordpress',
        'buddyboss': 'wordpress',
        'bbpress': 'wordpress',
        'academylms': 'wordpress',
        'charitable': 'wordpress',
        'eventsmanager': 'wordpress',
        'theeventscalendar': 'wordpress',
        'fluentaffiliate': 'wordpress',
        'fluentboards': 'wordpress',
        'fluentcommunity': 'wordpress',
        'ninjatables': 'wordpress',
        'mailster': 'wordpress',
        'newsletter': 'wordpress',
        'mailmint': 'wordpress',
        
        // Sales Engagement
        'lemlist': 'sales-engagement',
        'mailshake': 'sales-engagement',
        'reply': 'sales-engagement',
        'woodpecker': 'sales-engagement',
        'instantly': 'sales-engagement',
        'smartlead': 'sales-engagement',
        'saleshandy': 'sales-engagement',
        'salesloft': 'sales-engagement',
        'outreach': 'sales-engagement',
        'apollo': 'sales-engagement',
        'snovio': 'sales-engagement',
        
        // Zoho Suite
        'zohocampaigns': 'zoho',
        'zohoma': 'zoho',
        'zohorecruit': 'zoho',
        'zohopeople': 'zoho',
        
        // Other/Misc
        'customerio': 'misc',
        'intercom': 'misc',
        'apptivo': 'misc',
        'googledrive': 'misc',
        'dropbox': 'misc'
    },
    
    // Get category for a component
    getCategory: function(componentName) {
        return this.componentMap[componentName] || 'misc';
    }
};

// Utility function to initialize field data
window.adfoinInitFieldData = function(vm, fields) {
    fields.forEach(function(field) {
        var key = typeof field === 'string' ? field : field.value;
        if (typeof vm.fielddata[key] === 'undefined') {
            vm.$set(vm.fielddata, key, '');
        }
    });
};

// Utility function for AJAX requests
window.adfoinAjax = function(action, data, onSuccess, onFail) {
    data = data || {};
    data.action = action;
    data._nonce = adfoin.nonce;
    return jQuery.post(ajaxurl, data, onSuccess).fail(onFail || function() {});
};

// Initialize common page handlers (list page, log page, etc.)
jQuery(document).ready(function($) {
    // Delete confirmation for integrations
    $(".adfoin-integration-delete").on("click", function(e) {
        if (confirm(adfoin.delete_confirm)) {
            return;
        } else {
            e.preventDefault();
        }
    });

    // Toggle integration status — with loading state + error path.
    // The previous handler fired-and-forgot, so an auth-expired or
    // 500 response would leave the toggle in the wrong state with no
    // user feedback. Now: disable + spinner during request; on
    // failure, revert the checkbox and surface a toast.
    // Only bind to integration-list toggles (which carry data-id).
    // Settings-page platform checkboxes use the same .adfoin-toggle-form
    // class but have no data-id and must submit via the normal form POST.
    $('.adfoin-toggle-form input[data-id]').on('change', function(e) {
        e.stopPropagation();
        var $checkbox    = $(this);
        var $label       = $checkbox.closest('.adfoin-toggle-form');
        var newValue     = $checkbox.prop('checked') ? 1 : 0;
        var previousValue = newValue ? 0 : 1; // for revert on failure

        // Lock the control + show a spinner overlay so rapid clicks
        // don't queue a second request before the first resolves.
        $label.addClass('is-loading');
        $checkbox.prop('disabled', true);

        $.post(ajaxurl, {
            'action':  'adfoin_enable_integration',
            '_nonce':  adfoin.nonce,
            'id':      $checkbox.data('id'),
            'enabled': newValue
        }).done(function(response) {
            if ( ! response || ! response.success ) {
                revert();
                showToast(
                    ( response && response.data && response.data.message )
                        ? response.data.message
                        : 'Failed to update integration status.',
                    'error'
                );
            }
        }).fail(function(jqxhr) {
            revert();
            var message = 'Failed to update integration status.';
            if ( jqxhr && jqxhr.status === 403 ) {
                message = 'Your session has expired. Please reload the page and try again.';
            } else if ( jqxhr && jqxhr.responseJSON && jqxhr.responseJSON.data && jqxhr.responseJSON.data.message ) {
                message = jqxhr.responseJSON.data.message;
            }
            showToast(message, 'error');
        }).always(function() {
            $label.removeClass('is-loading');
            $checkbox.prop('disabled', false);
        });

        function revert() {
            // Note: we DO NOT trigger 'change' on revert — that would
            // fire the handler again and queue a doomed second AJAX
            // call that would just fail the same way.
            $checkbox.prop('checked', previousValue === 1);
        }
    });

    // Lightweight vanilla toast — no Vue dependency, so it works on
    // the list page where the Vue app isn't booted. Mounts once into
    // <body>, queues messages into a stack, auto-dismisses after
    // 4 seconds (errors stay 6s so they're harder to miss).
    function showToast(message, type) {
        type = type || 'info';
        var $container = $('#afi-core-toast-container');
        if ( ! $container.length ) {
            $container = $('<div id="afi-core-toast-container" class="afi-core-toast-container" role="region" aria-live="polite" aria-atomic="true"></div>');
            $('body').append($container);
        }
        var $toast = $('<div class="afi-core-toast"></div>')
            .addClass('afi-core-toast-' + type)
            .text(message);
        var $close = $('<button type="button" class="afi-core-toast-close" aria-label="Dismiss">&times;</button>');
        $close.on('click', function() { dismiss($toast); });
        $toast.append($close);
        $container.append($toast);

        var lifespan = type === 'error' ? 6000 : 4000;
        setTimeout(function() { dismiss($toast); }, lifespan);

        function dismiss($el) {
            $el.addClass('is-leaving');
            setTimeout(function() { $el.remove(); }, 220);
        }
    }
    // Expose for other modules that might want to surface a toast
    // without having to redefine the helper themselves.
    window.adfoinShowToast = showToast;

    // Highlight freshly-duplicated rows on the integrations list.
    // The list page sets window.adfoinHighlightRows = [12, 15, ...]
    // when redirected from a duplicate action; this picks the values
    // up and toggles a CSS animation on the matching <tr data-id>.
    if ( Array.isArray( window.adfoinHighlightRows ) && window.adfoinHighlightRows.length ) {
        var ids = window.adfoinHighlightRows;
        ids.forEach(function(id) {
            var $row = $('tr.afi-integration-row[data-id="' + id + '"]');
            $row.addClass('afi-row-highlight');
        });
        // Remove the class after the animation has had time to play
        // so it doesn't keep highlighting on subsequent re-renders
        // (e.g., toggle status -> AJAX repaints handlers).
        setTimeout(function() {
            $('tr.afi-row-highlight').removeClass('afi-row-highlight');
        }, 3500);
    }

    // Copy log data — spinner while writing, green tick on success.
    var AFI_COPY_SVG = {
        copy: '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
        spin: '<svg class="afi-svg-icon afi-copy-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10"/></svg>',
        tick: '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>'
    };

    $(document).on('click', '.afi-icon-copy-full-log', function(e) {
        e.preventDefault();
        var $btn = $(this);

        // Prevent double-clicks while a copy is already in flight.
        if ( $btn.hasClass('afi-copy-busy') ) return;

        var originalTitle = $btn.attr('title') || 'Copy Full Log';
        $btn.addClass('afi-copy-busy').html( AFI_COPY_SVG.spin ).attr('title', '');

        navigator.clipboard.writeText( JSON.stringify( $btn.data('full-log') ) )
            .then(function() {
                $btn.html( AFI_COPY_SVG.tick ).addClass('afi-copy-success').attr('title', 'Copied!');
                setTimeout(function() {
                    $btn.html( AFI_COPY_SVG.copy )
                        .removeClass('afi-copy-busy afi-copy-success')
                        .attr('title', originalTitle);
                }, 2000);
            })
            .catch(function() {
                // Revert silently on error (e.g. no clipboard permission).
                $btn.html( AFI_COPY_SVG.copy )
                    .removeClass('afi-copy-busy')
                    .attr('title', originalTitle);
            });
    });
});

Vue.component('api-key-management', {
    template: '#api-key-management-template',
    props: ['title'],
    data() {
        return {
            tableData: [],
            rowData: {},
            isEditing: false,
            editIndex: -1,
            deleteIndex: -1,
            platform: '',
            fields: [],
            showModal: false
        };
    },
    mounted() {
        // Parse slot content
        if (this.$slots.default && this.$slots.default[0] && this.$slots.default[0].text) {
            const slotContent = this.$slots.default[0].text.trim();
            try {
                const slotData = JSON.parse(slotContent);
                this.platform = slotData.platform;
                this.fields = slotData.fields;

                // Initialize rowData with platform-specific fields
                this.initializeRowData();
                this.fetchTableData();
            } catch (error) {
                console.error("Error parsing slot content:", error);
            }
        } else {
            console.error("No slot content found for api-key-management component");
        }
    },
    methods: {
        initializeRowData() {
            this.rowData = { id: '', title: '' };
            this.fields.forEach(field => {
                this.rowData[field.key] = '';
            });
        },
        openAddModal() {
            this.showModal = true;
            this.isEditing = false;
            this.clearForm();
        },
        closeModal() {
            this.showModal = false;
            this.clearForm();
        },
        addOrUpdateRow() {
            if (this.isEditing) {
                this.tableData[this.editIndex] = { ...this.rowData };
                this.isEditing = false;
            } else {
                this.rowData.id = this.generateUniqueId();
                this.tableData.push({ ...this.rowData });
            }
            this.closeModal();
            this.sendTableData();
        },
        editRow(index) {
            this.isEditing = true;
            this.editIndex = index;
            this.rowData = { ...this.tableData[index] };
            this.showModal = true;
        },
        confirmDelete(index) {
            if (confirm("Are you sure you want to delete this information?")) {
                this.deleteRow(index);
            }
        },
        deleteRow(index) {
            this.tableData.splice(index, 1);
            this.clearForm();
            this.sendTableData();
        },
        clearForm() {
            this.initializeRowData();
            this.isEditing = false;
        },
        formatApiKey(item, field) {
            return field.hidden ? item[field.key].substring(0, 6) + '****' : item[field.key];
        },
        generateUniqueId() {
            return Math.random().toString(36).substr(2, 8);
        },
        fetchTableData() {
            const requestData = {
                'action': `adfoin_get_${this.platform}_credentials`,
                '_nonce': adfoin.nonce
            };
            const that = this;

            jQuery.post(ajaxurl, requestData, function(response) {
                that.tableData = response.data;
            });
        },
        sendTableData() {
            const requestData = {
                'action': `adfoin_save_${this.platform}_credentials`,
                '_nonce': adfoin.nonce,
                'platform': this.platform,
                'data': this.tableData
            };
            jQuery.post(ajaxurl, requestData, function(response) {});
        },
        openAuthorize(row) {
            const supported = ['zohobooks', 'zohorecruit', 'zohopeople'];

            if (supported.indexOf(this.platform) === -1) {
                return;
            }

            if (!row.clientId || !row.clientSecret) {
                alert('Please provide the Client ID and Client Secret before authorizing.');
                return;
            }

            const dataCenter = row.dataCenter ? row.dataCenter.trim() : 'com';
            let accountsDomain = 'https://accounts.zoho.com';

            if (dataCenter === 'eu') {
                accountsDomain = 'https://accounts.zoho.eu';
            } else if (dataCenter === 'in') {
                accountsDomain = 'https://accounts.zoho.in';
            } else if (dataCenter === 'com.au') {
                accountsDomain = 'https://accounts.zoho.com.au';
            } else if (dataCenter === 'com.cn') {
                accountsDomain = 'https://accounts.zoho.com.cn';
            }

            let redirectUri = '';
            let scope = '';

            if (this.platform === 'zohobooks') {
                redirectUri = encodeURIComponent(adfoin.siteurl + '/wp-json/advancedformintegration/zohobooks');
                scope = encodeURIComponent('ZohoBooks.fullaccess.all');
            }

            if (this.platform === 'zohorecruit') {
                redirectUri = encodeURIComponent(adfoin.siteurl + '/wp-json/advancedformintegration/zohorecruit');
                scope = encodeURIComponent('ZohoRecruit.modules.ALL');
            }

            if (this.platform === 'zohopeople') {
                redirectUri = encodeURIComponent(adfoin.siteurl + '/wp-json/advancedformintegration/zohopeople');
                scope = encodeURIComponent('ZohoPeople.employee.ALL,ZohoPeople.forms.ALL');
            }

            const authorizeUrl = `${accountsDomain}/oauth/v2/auth?response_type=code&client_id=${row.clientId}&redirect_uri=${redirectUri}&scope=${scope}&access_type=offline&prompt=consent&state=${row.id}`;

            window.open(authorizeUrl, '_blank');
        }
    }
});

// Initialize Vue instance for api-key-management if element exists
if (document.getElementById('api-key-management')) {
    new Vue({
        el: '#api-key-management'
    });
}
