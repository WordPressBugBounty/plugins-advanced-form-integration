/**
 * Advanced Form Integration - "anydo" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("anydo").
 */

Vue.component('anydo', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            categoriesLoading: false,
            fields: [
                { type: 'text', value: 'title', title: 'Task Title', task: ['add_task'], required: true },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['add_task'], required: false, description: 'YYYY-MM-DD or any parsable date' },
                { type: 'text', value: 'dueShortcut', title: 'Due Shortcut', task: ['add_task'], required: false, description: 'Supports tomorrow (t), upcoming (u), someday (s)' },
                { type: 'text', value: 'categoryIdCustom', title: 'Category ID Override', task: ['add_task'], required: false, description: 'Use a mapped category id to override the dropdown' }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.categories = {};
                this.fielddata.categoryId = '';
                return;
            }

            this.getCategories();
        },
        getCategories: function () {
            var that = this;

            if (!this.fielddata.credId) {
                return;
            }

            this.categoriesLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_anydo_categories',
                credId: this.fielddata.credId,
                _nonce: adfoin.nonce
            }, function (response) {
                if (response.success) {
                    that.fielddata.categories = response.data;
                } else {
                    that.fielddata.categories = {};
                }
                that.categoriesLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.categoryId === 'undefined') {
            this.fielddata.categoryId = '';
        }

        if (typeof this.fielddata.categories === 'undefined') {
            this.fielddata.categories = {};
        }

        if (this.fielddata.credId) {
            this.getCategories();
        }
    },
    template: '#anydo-action-template'
});
