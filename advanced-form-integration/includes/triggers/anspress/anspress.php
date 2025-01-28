<?php

// Get AnsPress Triggers
function adfoin_anspress_get_forms($form_provider) {
    if ($form_provider != 'anspress') {
        return;
    }

    $triggers = array(
        'askQuestion' => __('User asks a question', 'advanced-form-integration'),
        'answerQuestion' => __('User answers a question', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get AnsPress Fields
function adfoin_anspress_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'anspress') {
        return;
    }

    $fields = array();

    if ($form_id === 'askQuestion') {
        $fields = [
            'question_id' => __('Question ID', 'advanced-form-integration'),
            'question_title' => __('Question Title', 'advanced-form-integration'),
            'question_content' => __('Question Content', 'advanced-form-integration'),
            'user_id' => __('User ID', 'advanced-form-integration'),
            'user_name' => __('User Name', 'advanced-form-integration'),
            'post_date' => __('Posted Date', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'answerQuestion') {
        $fields = [
            'answer_id' => __('Answer ID', 'advanced-form-integration'),
            'answer_content' => __('Answer Content', 'advanced-form-integration'),
            'question_id' => __('Question ID', 'advanced-form-integration'),
            'question_title' => __('Question Title', 'advanced-form-integration'),
            'user_id' => __('User ID', 'advanced-form-integration'),
            'user_name' => __('User Name', 'advanced-form-integration'),
            'post_date' => __('Posted Date', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into AnsPress "ask question" action
add_action('ap_after_new_question', 'adfoin_anspress_handle_ask_question', 10, 2);
function adfoin_anspress_handle_ask_question($post_id, $post) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('anspress', 'askQuestion');

    if (empty($saved_records)) {
        return;
    }

    $posted_data = array(
        'question_id' => $post_id,
        'question_title' => $post->post_title,
        'question_content' => $post->post_content,
        'user_id' => $post->post_author,
        'user_name' => get_the_author_meta('display_name', $post->post_author),
        'post_date' => $post->post_date,
    );

    adfoin_anspress_send_trigger_data($saved_records, $posted_data);
}

// Hook into AnsPress "answer question" action
add_action('ap_after_new_answer', 'adfoin_anspress_handle_answer_question', 10, 2);
function adfoin_anspress_handle_answer_question($post_id, $post) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('anspress', 'answerQuestion');

    if (empty($saved_records)) {
        return;
    }

    $question_id = get_post_meta($post_id, 'question_id', true);
    $question_title = get_the_title($question_id);

    $posted_data = array(
        'answer_id' => $post_id,
        'answer_content' => $post->post_content,
        'question_id' => $question_id,
        'question_title' => $question_title,
        'user_id' => $post->post_author,
        'user_name' => get_the_author_meta('display_name', $post->post_author),
        'post_date' => $post->post_date,
    );

    adfoin_anspress_send_trigger_data($saved_records, $posted_data);
}

// Send data
function adfoin_anspress_send_trigger_data($saved_records, $posted_data) {
    $job_queue = get_option('adfoin_general_settings_job_queue');

    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];
        if ($job_queue) {
            as_enqueue_async_action("adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data
                )
            ));
        } else {
            call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
        }
    }
}