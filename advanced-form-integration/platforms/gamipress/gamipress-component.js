/**
 * Advanced Form Integration - "gamipress" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("gamipress").
 */

Vue.component('gamipress', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                award_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['award_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['award_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['award_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'points', title: 'Points', task: ['award_points'], required: true, description: 'Required numeric value to award.' },
                    { type: 'text', value: 'points_type', title: 'Points Type Slug', task: ['award_points'], required: true, description: 'Required GamiPress points type slug.' },
                    { type: 'text', value: 'reason', title: 'Reason', task: ['award_points'], description: 'Optional award reason stored in the log.' },
                    { type: 'text', value: 'log_type', title: 'Log Type', task: ['award_points'], description: 'Optional log type identifier.' },
                    { type: 'text', value: 'admin_id', title: 'Admin User ID', task: ['award_points'], description: 'Optional admin/user performing the award.' },
                    { type: 'text', value: 'achievement_id', title: 'Related Achievement ID', task: ['award_points'], description: 'Optional achievement ID to associate with the award.' }
                ],
                deduct_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['deduct_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['deduct_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['deduct_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'points', title: 'Points', task: ['deduct_points'], required: true, description: 'Required positive numeric value to deduct.' },
                    { type: 'text', value: 'points_type', title: 'Points Type Slug', task: ['deduct_points'], required: true, description: 'Required GamiPress points type slug.' },
                    { type: 'text', value: 'reason', title: 'Reason', task: ['deduct_points'], description: 'Optional deduction reason stored in the log.' },
                    { type: 'text', value: 'log_type', title: 'Log Type', task: ['deduct_points'], description: 'Optional log type identifier.' },
                    { type: 'text', value: 'admin_id', title: 'Admin User ID', task: ['deduct_points'], description: 'Optional admin/user performing the deduction.' },
                    { type: 'text', value: 'achievement_id', title: 'Related Achievement ID', task: ['deduct_points'], description: 'Optional achievement ID to associate with the deduction.' }
                ],
                award_achievement: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['award_achievement'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['award_achievement'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['award_achievement'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'achievement_id', title: 'Achievement ID', task: ['award_achievement'], required: true, description: 'Required achievement, step, or rank post ID.' },
                    { type: 'text', value: 'admin_id', title: 'Admin User ID', task: ['award_achievement'], description: 'Optional admin/user performing the award.' },
                    { type: 'text', value: 'trigger', title: 'Trigger', task: ['award_achievement'], description: 'Optional trigger string sent to hooks.' },
                    { type: 'text', value: 'site_id', title: 'Site ID', task: ['award_achievement'], description: 'Optional site/blog ID for multisite use.' },
                    { type: 'text', value: 'args_json', title: 'Args (JSON)', task: ['award_achievement'], description: 'Optional JSON object of extra arguments.' }
                ],
                revoke_achievement: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['revoke_achievement'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['revoke_achievement'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['revoke_achievement'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'achievement_id', title: 'Achievement ID', task: ['revoke_achievement'], required: true, description: 'Required achievement, step, or rank post ID to revoke.' },
                    { type: 'text', value: 'earning_id', title: 'Earning ID', task: ['revoke_achievement'], description: 'Optional specific earning ID to revoke.' }
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
    template: '#gamipress-action-template'
});
