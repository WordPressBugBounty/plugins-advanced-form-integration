/**
 * Advanced Form Integration - "mycred" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("mycred").
 */

Vue.component('mycred', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fieldLists: {
                award_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['award_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['award_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['award_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['award_points'], required: true, description: 'Required positive amount to credit.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['award_points'], description: 'Optional myCred point type key.' },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['award_points'], description: 'Optional reference slug; defaults to adfoin_award.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['award_points'], description: 'Optional log message stored with the transaction.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['award_points'], description: 'Optional numeric/string reference ID.' },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['award_points'], description: 'Optional JSON object stored with the log entry.' }
                ],
                deduct_points: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['deduct_points'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['deduct_points'], description: 'Optional email used to locate the user.' },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['deduct_points'], description: 'Optional login/username used to locate the user.' },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['deduct_points'], required: true, description: 'Required positive amount to debit.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['deduct_points'], description: 'Optional myCred point type key.' },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['deduct_points'], description: 'Optional reference slug; defaults to adfoin_deduct.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['deduct_points'], description: 'Optional log message stored with the transaction.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['deduct_points'] },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['deduct_points'] }
                ],
                set_balance: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['set_balance'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['set_balance'] },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['set_balance'] },
                    { type: 'text', value: 'target_balance', title: 'Target Balance', task: ['set_balance'], required: true, description: 'Required target balance amount.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['set_balance'] },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['set_balance'], description: 'Optional reference slug for the logged adjustment.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['set_balance'], description: 'Optional message stored with the adjustment log.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['set_balance'] },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['set_balance'] }
                ],
                add_log_entry: [
                    { type: 'text', value: 'user_id', title: 'User ID', task: ['add_log_entry'], description: 'Optional WordPress user ID; provide user_id, email, or login.' },
                    { type: 'text', value: 'user_email', title: 'User Email', task: ['add_log_entry'] },
                    { type: 'text', value: 'user_login', title: 'User Login', task: ['add_log_entry'] },
                    { type: 'text', value: 'amount', title: 'Amount', task: ['add_log_entry'], required: true, description: 'Required non-zero amount recorded in the log.' },
                    { type: 'text', value: 'point_type', title: 'Point Type Key', task: ['add_log_entry'] },
                    { type: 'text', value: 'reference', title: 'Reference', task: ['add_log_entry'], description: 'Optional reference slug; defaults to adfoin_log.' },
                    { type: 'text', value: 'log_entry', title: 'Log Entry', task: ['add_log_entry'], description: 'Optional log message.' },
                    { type: 'text', value: 'ref_id', title: 'Reference ID', task: ['add_log_entry'] },
                    { type: 'text', value: 'data_json', title: 'Data (JSON)', task: ['add_log_entry'], description: 'Optional JSON object stored with the log entry.' }
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
    template: '#mycred-action-template'
});
