/**
 * Advanced Form Integration - Core JavaScript
 * Contains essential components and lazy loading infrastructure
 */

// Lazy loading infrastructure
window.adfoinComponentLoader = {
    actionsLoaded: false,
    actionsLoading: null,
    
    // Load all action components (script.js)
    loadActions: function() {
        var self = this;
        
        // Already loaded
        if (this.actionsLoaded) {
            return Promise.resolve();
        }
        
        // Currently loading
        if (this.actionsLoading) {
            return this.actionsLoading;
        }
        
        // Start loading
        this.actionsLoading = new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            var basePath = adfoin.assetsUrl || '';
            var version = adfoin.version || Date.now();
            
            script.src = basePath + '/js/script.js?ver=' + version;
            script.async = true;
            
            script.onload = function() {
                self.actionsLoaded = true;
                self.actionsLoading = null;
                // Dispatch event for Vue to re-render
                document.dispatchEvent(new CustomEvent('adfoin-actions-loaded'));
                resolve();
            };
            
            script.onerror = function() {
                self.actionsLoading = null;
                reject(new Error('Failed to load action components'));
            };
            
            document.head.appendChild(script);
        });
        
        return this.actionsLoading;
    },
    
    // Check if component is available
    isLoaded: function(componentName) {
        return !!Vue.options.components[componentName];
    },
    
    // Check if actions are loaded
    areActionsLoaded: function() {
        return this.actionsLoaded;
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
        'mailgun': 'email-marketing',
        'sendfox': 'email-marketing',
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
        'pipelinecrm': 'crm',
        'crmone': 'crm',
        'dynamics365': 'crm',
        'dynamics365marketing': 'crm',
        'superoffice': 'crm',
        'mautic': 'crm',
        
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
        'zoho_meeting': 'webinar',
        
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
        
        // Communication
        'slack': 'communication',
        'discord': 'communication',
        'telegram': 'communication',
        'whatsapp': 'communication',
        'twilio': 'communication',
        'pushover': 'communication',
        'sinch': 'communication',
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
        
        // Support & Helpdesk
        'zendesk': 'support',
        'freshdesk': 'support',
        'intercom': 'support',
        'fluentsupport': 'support',
        'zohodesk': 'support',
        'gistcrm': 'support',
        
        // Accounting & Finance
        'zohobooks': 'accounting',
        'moneybird': 'accounting',
        'netsuite': 'accounting',
        'scoro': 'accounting',
        
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

    // Toggle integration status
    $('.adfoin-toggle-form input').on('change', function(e) {
        e.stopPropagation();
        var requestData = {
            'action': 'adfoin_enable_integration',
            '_nonce': adfoin.nonce,
            'id': $(this).data('id'),
            'enabled': $(this).prop('checked') ? 1 : 0
        };
        $.post(ajaxurl, requestData);
    });

    // Copy log data
    $('.afi-icon-copy-full-log').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        $this.css('color', 'green');
        navigator.clipboard.writeText(JSON.stringify($this.data('full-log')));
        setTimeout(function() {
            $this.removeClass("dashicons-admin-page");
            $this.addClass("dashicons-saved");
            $this.prop('title', 'Copied to Clipboard');
        }, 1000);
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
