/**
 * Advanced Form Integration - "fluentcommunity" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("fluentcommunity").
 */

Vue.component('fluentcommunity', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                create_space: [
                    { type: 'text', value: 'title', title: 'Title', task: ['create_space'], required: true, description: 'Required space title.' },
                    { type: 'text', value: 'privacy', title: 'Privacy', task: ['create_space'], required: true, description: 'Privacy level must be public, private, or secret.' },
                    { type: 'text', value: 'slug', title: 'Slug', task: ['create_space'], description: 'Optional slug; defaults from the title when empty.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_space'] },
                    { type: 'text', value: 'parent_id', title: 'Parent Group ID', task: ['create_space'], description: 'Optional parent space group ID.' },
                    { type: 'text', value: 'settings_json', title: 'Settings (JSON)', task: ['create_space'], description: 'Optional JSON settings following Fluent Community schema.' }
                ],
                invite_member: [
                    { type: 'text', value: 'space_id', title: 'Space ID', task: ['invite_member'], required: true, description: 'Numeric ID of the target space.' },
                    { type: 'text', value: 'user_id', title: 'Inviter User ID', task: ['invite_member'], required: true, description: 'Existing user ID that sends the invitation.' },
                    { type: 'text', value: 'invitee_email', title: 'Invitee Email', task: ['invite_member'], required: true, description: 'Email address of the member to invite.' },
                    { type: 'text', value: 'invitee_name', title: 'Invitee Name', task: ['invite_member'], description: 'Optional display name for the invitee.' }
                ],
                create_space_group: [
                    { type: 'text', value: 'title', title: 'Group Title', task: ['create_space_group'], required: true, description: 'Required group title.' },
                    { type: 'text', value: 'description', title: 'Description', task: ['create_space_group'] },
                    { type: 'text', value: 'parent_id', title: 'Parent Group ID', task: ['create_space_group'], description: 'Optional parent group ID for nesting.' }
                ]
            }
        }
    },
    computed: {
        fields: function () {
            if (!this.action || !this.action.task) {
                return [];
            }
            return this.fieldLists[this.action.task] || [];
        }
    },
    template: '#fluentcommunity-action-template'
});
