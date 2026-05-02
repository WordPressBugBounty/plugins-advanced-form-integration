/**
 * Advanced Form Integration - "asana" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("asana").
 */

Vue.component('asana', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            workspaceLoading: false,
            projectLoading: false,
            sectionLoading: false,
            userLoading: false,
            fields: [
                { type: 'text', value: 'name', title: 'Name', task: ['create_task'], required: true },
                { type: 'textarea', value: 'notes', title: 'Notes', task: ['create_task'], required: false },
                { type: 'text', value: 'dueOn', title: 'Due On', task: ['create_task'], required: false, description: 'Use YYYY-MM-DD format' },
                { type: 'text', value: 'dueOnX', title: 'Due After X Days', task: ['create_task'], required: false, description: 'Accepts numeric value. If filled, due date will be calculated and set' },
            ]

        }
    },
    methods: {
        getWorkspaces: function () {
            var that = this;
            this.workspaceLoading = true;

            var workspaceRequestData = {
                'action': 'adfoin_get_asana_workspaces',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce
            };

            jQuery.post(ajaxurl, workspaceRequestData, function (response) {
                that.fielddata.workspaces = response.data;
                that.workspaceLoading = false;
            });
        },
        getProjects: function () {
            var that = this;
            this.projectLoading = true;
            this.userLoading = true;

            var projectData = {
                'action': 'adfoin_get_asana_projects',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'workspaceId': this.fielddata.workspaceId
            };

            jQuery.post(ajaxurl, projectData, function (response) {
                var projects = response.data;
                that.fielddata.projects = projects;
                that.projectLoading = false;
            });

            var userData = {
                'action': 'adfoin_get_asana_users',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'workspaceId': this.fielddata.workspaceId
            };

            jQuery.post(ajaxurl, userData, function (response) {
                var users = response.data;
                that.fielddata.users = users;
                that.userLoading = false;
            });
        },
        getSections: function () {
            var that = this;
            this.sectionLoading = true;

            var sectionData = {
                'action': 'adfoin_get_asana_sections',
                'credId': this.fielddata.credId,
                '_nonce': adfoin.nonce,
                'projectId': this.fielddata.projectId
            };

            jQuery.post(ajaxurl, sectionData, function (response) {
                var sections = response.data;
                that.fielddata.sections = sections;
                that.sectionLoading = false;
            });
        }
    },
    created: function () {

    },
    mounted: function () {
        var that = this;

        ['credId', 'workspaceId', 'projectId', 'sectionId', 'userId'].forEach(key => {
            if (typeof this.fielddata[key] === 'undefined') {
                this.fielddata[key] = '';
            }
        });

        // this.getWorkspaces();

        if (this.fielddata.credId) {
            this.getWorkspaces();
        }

        if (this.fielddata.workspaceId) {
            this.getProjects();
        }

        if (this.fielddata.workspaceId && this.fielddata.projectId) {
            this.getSections();
        }
    },
    template: '#asana-action-template'
});
