/**
 * Advanced Form Integration - template / role picker for e-signature actions
 * (DocuSign, signNow). Pairs with adfoin_esign_template_picker_rows().
 *
 * Usage in a component: mixins: [adfoinEsignTemplatePicker({ templatesAction, rolesAction, idPattern })]
 *   templatesAction  AJAX action returning [{ id, name }]
 *   rolesAction      AJAX action returning [{ roleName, type, textTabs[] }] for a template
 *   idPattern        RegExp a real template ID matches (mapped {{field}} values don't)
 */

window.adfoinEsignTemplatePicker = function (config) {
    return {
        data: function () {
            return {
                templates: [],
                templatesLoading: false,
                templatesLoaded: false,
                templatesError: '',
                templateRoles: [],
                rolesLoading: false,
                rolesLoaded: false,
                // "Use a form field..." mode: a mapping field replaces the dropdown.
                templateMapped: false,
                roleMapped: false
            };
        },
        computed: {
            templateInList: function () {
                var id = this.fielddata.templateId;
                return this.templates.some(function (template) { return template.id === id; });
            },
            // The template dropdown is the templateId field; the option value is what's saved.
            templateChoice: {
                get: function () {
                    return this.templateMapped ? '__mapped__' : (this.fielddata.templateId || '');
                },
                set: function (value) {
                    this.templateMapped = '__mapped__' === value;
                    this.$set(this.fielddata, 'templateId', this.templateMapped ? '' : value);
                }
            },
            roleChoice: {
                get: function () {
                    return this.roleMapped ? '__mapped__' : (this.fielddata.roleName || '');
                },
                set: function (value) {
                    this.roleMapped = '__mapped__' === value;
                    this.$set(this.fielddata, 'roleName', this.roleMapped ? '' : value);
                }
            },
            showRoleSelect: function () {
                return !!this.fielddata.credId && !this.templateMapped && !this.roleMapped && this.templateRoles.length > 0;
            },
            // Without readable roles (mapped template, a template with no roles,
            // or the user chose "Use a form field...") the role is typed or mapped.
            showRoleField: function () {
                if (!this.fielddata.credId) return false;
                if (this.templateMapped || this.roleMapped) return true;
                return this.isTemplateId(this.fielddata.templateId) && !this.rolesLoading && this.rolesLoaded && !this.templateRoles.length;
            }
        },
        methods: {
            isTemplateId: function (value) {
                return config.idPattern.test((value || '').trim());
            },
            loadTemplates: function () {
                var vm = this;

                vm.templates = [];
                vm.templatesError = '';
                vm.templatesLoaded = false;

                if (!vm.fielddata.credId) {
                    return;
                }

                vm.templatesLoading = true;

                jQuery.post(ajaxurl, { action: config.templatesAction, _nonce: adfoin.nonce, credId: vm.fielddata.credId }, function (response) {
                    if (response.success) {
                        vm.templates = response.data || [];
                    } else {
                        vm.templatesError = (response.data && response.data.message) || 'Could not load templates.';
                    }
                }).fail(function () {
                    vm.templatesError = 'Could not load templates.';
                }).always(function () {
                    vm.templatesLoading = false;
                    vm.templatesLoaded = true;
                });
            },
            loadTemplateRoles: function () {
                var vm = this;

                vm.templateRoles = [];
                vm.rolesLoaded = false;

                if (!vm.fielddata.credId || !vm.isTemplateId(vm.fielddata.templateId)) {
                    return;
                }

                vm.rolesLoading = true;

                jQuery.post(ajaxurl, {
                    action: config.rolesAction,
                    _nonce: adfoin.nonce,
                    credId: vm.fielddata.credId,
                    templateId: vm.fielddata.templateId
                }, function (response) {
                    if (!response.success) {
                        return;
                    }

                    vm.templateRoles = response.data || [];

                    var names = vm.templateRoles.map(function (role) { return role.roleName; });

                    // Pick the first signer when no role is set, or the saved one is not on this template.
                    if (!vm.roleMapped && names.length && names.indexOf(vm.fielddata.roleName) === -1) {
                        var signer = vm.templateRoles.filter(function (role) { return role.type === 'signers'; })[0] || vm.templateRoles[0];
                        vm.$set(vm.fielddata, 'roleName', signer.roleName);
                    }
                }).always(function () {
                    vm.rolesLoading = false;
                    vm.rolesLoaded = true;
                });
            }
        },
        created: function () {
            ['credId', 'templateId', 'roleName'].forEach(function (key) {
                if (typeof this.fielddata[key] === 'undefined') {
                    this.$set(this.fielddata, key, '');
                }
            }, this);

            // Saved integrations that map the template or role from a form
            // field open in "Use a form field..." mode.
            this.templateMapped = !!this.fielddata.templateId && !this.isTemplateId(this.fielddata.templateId);
            this.roleMapped = /\{\{/.test(this.fielddata.roleName || '');
        },
        mounted: function () {
            this.loadTemplates();
            this.loadTemplateRoles();
        },
        watch: {
            'fielddata.credId': function (n, o) {
                if (n !== o) {
                    this.loadTemplates();
                    this.loadTemplateRoles();
                }
            },
            'fielddata.templateId': function (n, o) {
                if (n !== o) {
                    this.loadTemplateRoles();
                }
            }
        }
    };
};
