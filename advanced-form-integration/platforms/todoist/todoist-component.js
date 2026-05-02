/**
 * Advanced Form Integration - "todoist" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("todoist").
 */

Vue.component('todoist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            projectLoading: false,
            sectionLoading: false,
            labelLoading: false,
            fields: [
                { type: 'text', value: 'content', title: 'Task Content', task: ['create_task'], required: true },
                { type: 'textarea', value: 'description', title: 'Description', task: ['create_task'], required: false },
                { type: 'text', value: 'dueDate', title: 'Due Date', task: ['create_task'], required: false, description: 'YYYY-MM-DD' },
                { type: 'text', value: 'dueString', title: 'Due String', task: ['create_task'], required: false, description: 'Examples: tomorrow 9am, next Monday' },
                { type: 'text', value: 'priority', title: 'Priority (1-4)', task: ['create_task'], required: false },
                { type: 'text', value: 'labels', title: 'Dynamic Labels (CSV)', task: ['create_task'], required: false, description: 'Comma-separated list pulled from form data' }
            ]
        }
    },
    methods: {
        handleAccountChange: function () {
            if (!this.fielddata.credId) {
                this.fielddata.projects = {};
                this.fielddata.sections = {};
                this.fielddata.labelsList = [];
                this.fielddata.labelNames = [];
                this.fielddata.projectId = '';
                this.fielddata.sectionId = '';
                return;
            }

            this.getProjects();
            this.getLabels();
        },
        getProjects: function () {
            if (!this.fielddata.credId) return;
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_todoist_projects', {
                targetKey: 'projects',
                loadingKey: 'projectLoading',
                requireSuccess: true
            });
        },
        getSections: function () {
            var that = this;

            if (!this.fielddata.credId || !this.fielddata.projectId) {
                this.fielddata.sections = {};
                this.fielddata.sectionId = '';
                return;
            }

            this.fielddata.sectionId = '';
            this.sectionLoading = true;

            var requestData = {
                'action': 'adfoin_get_todoist_sections',
                'credId': this.fielddata.credId,
                'projectId': this.fielddata.projectId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.sections = response.data;
                } else {
                    that.fielddata.sections = {};
                }
                that.sectionLoading = false;
            });
        },
        getLabels: function () {
            var that = this;

            if (!this.fielddata.credId) {
                this.fielddata.labelsList = [];
                return;
            }

            this.labelLoading = true;

            var requestData = {
                'action': 'adfoin_get_todoist_labels',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, requestData, function (response) {
                if (response.success) {
                    that.fielddata.labelsList = response.data;
                } else {
                    that.fielddata.labelsList = [];
                }
                that.labelLoading = false;
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.fielddata.credId = '';
        }

        if (typeof this.fielddata.projectId === 'undefined') {
            this.fielddata.projectId = '';
        }

        if (typeof this.fielddata.sectionId === 'undefined') {
            this.fielddata.sectionId = '';
        }

        if (typeof this.fielddata.projects === 'undefined') {
            this.fielddata.projects = {};
        }

        if (typeof this.fielddata.sections === 'undefined') {
            this.fielddata.sections = {};
        }

        if (typeof this.fielddata.labelsList === 'undefined') {
            this.fielddata.labelsList = [];
        }

        if (typeof this.fielddata.labelNames === 'undefined') {
            this.fielddata.labelNames = [];
        }

        if (this.fielddata.credId) {
            this.getProjects();
            this.getLabels();
        }

        if (this.fielddata.credId && this.fielddata.projectId) {
            this.getSections();
        }
    },
    template: '#todoist-action-template'
});
