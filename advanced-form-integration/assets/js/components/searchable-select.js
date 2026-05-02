/**
 * Advanced Form Integration - Searchable Select Component
 *
 * A Vue 2 component that replaces a native <select> with a searchable
 * dropdown. Designed for the form-provider and action-provider pickers in
 * new_integration.php and edit_integration.php where the option list runs
 * to 70+ (triggers) and 250+ (platforms) items.
 *
 * Usage:
 *   <afi-searchable-select
 *       :options="window.adfoinFormProviders"
 *       v-model="trigger.formProviderId"
 *       name="form_provider_id"
 *       placeholder="Select Provider..."
 *       search-placeholder="Search providers..."
 *       @change="changeFormProvider">
 *   </afi-searchable-select>
 *
 * Props:
 *   options             [{ value, label }]   Already-sorted list of choices.
 *   value               string               Bound via v-model.
 *   name                string               Hidden input name (so the form posts unchanged).
 *   placeholder         string               Shown when nothing selected.
 *   searchPlaceholder   string               Shown inside the search box.
 *   disabled            bool
 *   required            bool                 Adds aria-required + html5 validation.
 *
 * Events:
 *   input  (newValue)   For v-model.
 *   change (newValue)   Fires on user-driven change (mirrors native <select>).
 */
(function () {
    'use strict';

    if (typeof Vue === 'undefined') {
        return;
    }

    Vue.component('afi-searchable-select', {
        props: {
            value: { default: '' },
            options: { type: Array, default: function () { return []; } },
            name: { type: String, default: '' },
            placeholder: { type: String, default: 'Select...' },
            searchPlaceholder: { type: String, default: 'Search...' },
            disabled: { type: Boolean, default: false },
            required: { type: Boolean, default: false },
            emptyText: { type: String, default: 'No matches found' },
            noOptionsText: { type: String, default: '' },
            // DOM id assigned to the visible trigger button so a parent
            // <label for=""> can be programmatically associated with it.
            inputId: { type: String, default: '' },
            // When true, the wrapper gets the .has-error class so the host
            // page can style invalid state consistently.
            hasError: { type: Boolean, default: false },
            // ID of an inline error/help element to point screen readers at
            // via aria-describedby.
            describedBy: { type: String, default: '' }
        },

        data: function () {
            return {
                open: false,
                query: '',
                activeIndex: -1
            };
        },

        computed: {
            selectedLabel: function () {
                var v = String(this.value);
                for (var i = 0; i < this.options.length; i++) {
                    if (String(this.options[i].value) === v) {
                        return this.options[i].label;
                    }
                }
                return '';
            },
            filteredOptions: function () {
                var q = (this.query || '').trim().toLowerCase();
                if (!q) return this.options;
                return this.options.filter(function (o) {
                    return (
                        String(o.label).toLowerCase().indexOf(q) !== -1 ||
                        String(o.value).toLowerCase().indexOf(q) !== -1
                    );
                });
            },
            // Message to render in the empty state. If the source list itself
            // is empty (e.g. before a parent provider is picked), use
            // noOptionsText when supplied; otherwise fall back to emptyText.
            currentEmptyText: function () {
                var hasQuery = !!(this.query || '').trim();
                if (!hasQuery && this.options.length === 0 && this.noOptionsText) {
                    return this.noOptionsText;
                }
                return this.emptyText;
            }
        },

        watch: {
            open: function (val) {
                if (val) {
                    document.addEventListener('mousedown', this.onDocMousedown);
                } else {
                    document.removeEventListener('mousedown', this.onDocMousedown);
                }
            }
        },

        beforeDestroy: function () {
            document.removeEventListener('mousedown', this.onDocMousedown);
        },

        methods: {
            toggle: function () {
                if (this.disabled) return;
                if (this.open) {
                    this.closeDropdown();
                } else {
                    this.openDropdown();
                }
            },

            openDropdown: function () {
                var that = this;
                this.open = true;
                this.query = '';

                // Pre-position highlight on the currently selected item if any.
                this.activeIndex = -1;
                if (this.value !== '' && this.value !== null) {
                    for (var i = 0; i < this.filteredOptions.length; i++) {
                        if (String(this.filteredOptions[i].value) === String(this.value)) {
                            this.activeIndex = i;
                            break;
                        }
                    }
                }

                this.$nextTick(function () {
                    if (that.$refs.search) {
                        that.$refs.search.focus();
                    }
                    that.scrollToActive();
                });
            },

            closeDropdown: function () {
                this.open = false;
                this.query = '';
                this.activeIndex = -1;
            },

            selectOption: function (option) {
                if (!option) return;
                this.$emit('input', option.value);
                this.$emit('change', option.value);
                this.closeDropdown();

                // Return focus to the trigger button for accessibility.
                var that = this;
                this.$nextTick(function () {
                    if (that.$refs.trigger) that.$refs.trigger.focus();
                });
            },

            clearSelection: function (e) {
                if (e) {
                    e.stopPropagation();
                    e.preventDefault();
                }
                if (this.disabled) return;
                if (this.value === '' || this.value === null) return;
                this.$emit('input', '');
                this.$emit('change', '');
            },

            onSearchKeydown: function (e) {
                var len = this.filteredOptions.length;

                if (e.key === 'ArrowDown' || e.keyCode === 40) {
                    e.preventDefault();
                    if (len === 0) return;
                    this.activeIndex = (this.activeIndex + 1) % len;
                    this.scrollToActive();
                } else if (e.key === 'ArrowUp' || e.keyCode === 38) {
                    e.preventDefault();
                    if (len === 0) return;
                    this.activeIndex = (this.activeIndex - 1 + len) % len;
                    this.scrollToActive();
                } else if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    if (this.activeIndex >= 0 && this.activeIndex < len) {
                        this.selectOption(this.filteredOptions[this.activeIndex]);
                    }
                } else if (e.key === 'Escape' || e.keyCode === 27) {
                    e.preventDefault();
                    this.closeDropdown();
                    var that = this;
                    this.$nextTick(function () {
                        if (that.$refs.trigger) that.$refs.trigger.focus();
                    });
                } else if (e.key === 'Tab' || e.keyCode === 9) {
                    // Allow tab to close and move on naturally.
                    this.closeDropdown();
                } else if (e.key === 'Home' || e.keyCode === 36) {
                    if (len === 0) return;
                    e.preventDefault();
                    this.activeIndex = 0;
                    this.scrollToActive();
                } else if (e.key === 'End' || e.keyCode === 35) {
                    if (len === 0) return;
                    e.preventDefault();
                    this.activeIndex = len - 1;
                    this.scrollToActive();
                }
            },

            onTriggerKeydown: function (e) {
                if (this.disabled) return;
                if (
                    e.key === 'Enter' || e.keyCode === 13 ||
                    e.key === ' '   || e.keyCode === 32 ||
                    e.key === 'ArrowDown' || e.keyCode === 40
                ) {
                    e.preventDefault();
                    this.openDropdown();
                }
            },

            scrollToActive: function () {
                var that = this;
                this.$nextTick(function () {
                    var list = that.$refs.list;
                    if (!list || that.activeIndex < 0) return;
                    var item = list.children[that.activeIndex];
                    if (!item) return;
                    var top = item.offsetTop;
                    var bottom = top + item.offsetHeight;
                    if (top < list.scrollTop) {
                        list.scrollTop = top;
                    } else if (bottom > list.scrollTop + list.clientHeight) {
                        list.scrollTop = bottom - list.clientHeight;
                    }
                });
            },

            onDocMousedown: function (e) {
                if (!this.$el.contains(e.target)) {
                    this.closeDropdown();
                }
            },

            isSelected: function (option) {
                return String(option.value) === String(this.value);
            },

            // Highlight matching substring inside option labels.
            highlightLabel: function (label) {
                var q = (this.query || '').trim();
                if (!q) return this.escapeHtml(label);
                var safeLabel = this.escapeHtml(label);
                var safeQuery = this.escapeHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                var re = new RegExp('(' + safeQuery + ')', 'ig');
                return safeLabel.replace(re, '<mark class="afi-ss-mark">$1</mark>');
            },

            escapeHtml: function (s) {
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        },

        template: [
            '<div class="afi-searchable-select" :class="{ \'is-open\': open, \'is-disabled\': disabled, \'has-value\': selectedLabel, \'has-error\': hasError }">',
                '<input type="hidden" :name="name" :value="value">',
                '<button type="button"',
                    ' ref="trigger"',
                    ' class="afi-ss-trigger"',
                    ' :id="inputId || false"',
                    ' :disabled="disabled"',
                    ' :aria-expanded="open ? \'true\' : \'false\'"',
                    ' :aria-required="required ? \'true\' : \'false\'"',
                    ' :aria-invalid="hasError ? \'true\' : \'false\'"',
                    ' :aria-describedby="describedBy || false"',
                    ' aria-haspopup="listbox"',
                    ' @click="toggle"',
                    ' @keydown="onTriggerKeydown"',
                    ' @blur="$emit(\'blur\', $event)">',
                    '<span class="afi-ss-value" v-if="selectedLabel">{{ selectedLabel }}</span>',
                    '<span class="afi-ss-placeholder" v-else>{{ placeholder }}</span>',
                    '<span class="afi-ss-arrow dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>',
                '</button>',
                '<button type="button"',
                    ' class="afi-ss-clear"',
                    ' v-if="selectedLabel && !disabled"',
                    ' aria-label="Clear selection"',
                    ' tabindex="-1"',
                    ' @click="clearSelection"',
                    ' @mousedown.prevent>',
                    '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>',
                '</button>',
                '<div class="afi-ss-dropdown" v-show="open">',
                    '<div class="afi-ss-search-wrap">',
                        '<span class="afi-ss-search-icon dashicons dashicons-search" aria-hidden="true"></span>',
                        '<input type="text"',
                            ' ref="search"',
                            ' class="afi-ss-search"',
                            ' v-model="query"',
                            ' :aria-label="searchPlaceholder"',
                            ' @keydown="onSearchKeydown">',
                    '</div>',
                    '<ul class="afi-ss-list" ref="list" role="listbox" tabindex="-1">',
                        '<li v-for="(opt, idx) in filteredOptions"',
                            ' :key="opt.value"',
                            ' class="afi-ss-option"',
                            ' :class="{ \'is-active\': idx === activeIndex, \'is-selected\': isSelected(opt) }"',
                            ' role="option"',
                            ' :aria-selected="isSelected(opt) ? \'true\' : \'false\'"',
                            ' @click="selectOption(opt)"',
                            ' @mouseenter="activeIndex = idx">',
                            '<span class="afi-ss-option-label" v-html="highlightLabel(opt.label)"></span>',
                            '<span class="afi-ss-check dashicons dashicons-yes" v-if="isSelected(opt)" aria-hidden="true"></span>',
                        '</li>',
                        '<li v-if="filteredOptions.length === 0" class="afi-ss-empty">{{ currentEmptyText }}</li>',
                    '</ul>',
                '</div>',
            '</div>'
        ].join('')
    });
})();
