/**
 * Advanced Form Integration - "gravityformsac" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("gravityformsac").
 *
 * Mirrors the fluentcrm pattern:
 *   - On mount, fetch the list of Gravity Forms and store on
 *     `fielddata.forms` (rendered as a dropdown).
 *   - When the user picks a form (or a task), fetch the matching field
 *     definitions and push them into `fields[]`. Each field is then rendered
 *     by the shared <editable-field> component.
 */

Vue.component('gravityformsac', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            formLoading: false,
            fieldLoading: false,
            fields: []
        };
    },
    methods: {
        getForms: function () {
            var that = this;

            this.formLoading = true;

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_gravityformsac_forms',
                _nonce: adfoin.nonce
            }, function (response) {
                that.$set(that.fielddata, 'forms', (response && response.data) || {});
                that.formLoading = false;
            });
        },
        getFields: function (task) {
            var that = this;

            this.fields = [];
            this.fieldLoading = true;

            // editable-field's row uses `v-if="inArray(action.task, field.task)"`,
            // so field.task MUST be an array (matches the shape produced by the
            // shared adfoinHelpers.getFields helper). Passing a plain string
            // causes inArray to iterate the string's character indices and
            // never match, hiding every field.
            var fieldTask = [task];

            jQuery.post(ajaxurl, {
                action: 'adfoin_get_gravityformsac_fields',
                _nonce: adfoin.nonce,
                task: task,
                formId: this.fielddata.formId || ''
            }, function (response) {
                var data = (response && response.data) || [];
                data.forEach(function (single) {
                    that.fields.push({
                        type: single.type || 'text',
                        value: single.key,
                        title: single.value,
                        task: fieldTask,
                        required: !!single.required,
                        description: single.description || ''
                    });
                });
                that.fieldLoading = false;
            });
        }
    },
    watch: {
        'action.task': function (val) {
            this.getFields(val);
        }
    },
    mounted: function () {
        if (typeof this.fielddata.formId === 'undefined') {
            this.$set(this.fielddata, 'formId', '');
        }

        this.getForms();

        if (this.action.task) {
            this.getFields(this.action.task);
        }
    },
    template: '#gravityformsac-action-template'
});
