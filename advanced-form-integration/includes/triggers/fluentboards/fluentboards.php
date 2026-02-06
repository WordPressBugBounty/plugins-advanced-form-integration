<?php

// Get Fluent Boards triggers.
function adfoin_fluentboards_get_forms( $form_provider ) {
    if ( $form_provider !== 'fluentboards' ) {
        return;
    }

    return array(
        'boardCreated'     => __( 'Board Created', 'advanced-form-integration' ),
        'boardMemberAdded' => __( 'Board Member Added', 'advanced-form-integration' ),
        'taskCreated'      => __( 'Task Created', 'advanced-form-integration' ),
    );
}

// Get Fluent Boards fields.
function adfoin_fluentboards_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'fluentboards' ) {
        return;
    }

    $fields = array();

    if ( 'boardCreated' === $form_id ) {
        $fields = array(
            'board_id'      => __( 'Board ID', 'advanced-form-integration' ),
            'title'         => __( 'Board Title', 'advanced-form-integration' ),
            'description'   => __( 'Board Description', 'advanced-form-integration' ),
            'type'          => __( 'Board Type', 'advanced-form-integration' ),
            'currency'      => __( 'Currency', 'advanced-form-integration' ),
            'background'    => __( 'Background', 'advanced-form-integration' ),
            'settings'      => __( 'Settings (JSON)', 'advanced-form-integration' ),
            'created_by'    => __( 'Created By (User ID)', 'advanced-form-integration' ),
            'archived_at'   => __( 'Archived At', 'advanced-form-integration' ),
            'board_meta'    => __( 'Board Meta (JSON)', 'advanced-form-integration' ),
            'board_raw'     => __( 'Raw Board (JSON)', 'advanced-form-integration' ),
        );
    } elseif ( 'boardMemberAdded' === $form_id ) {
        $fields = array(
            'board_id'          => __( 'Board ID', 'advanced-form-integration' ),
            'user_id'           => __( 'User ID', 'advanced-form-integration' ),
            'user_login'        => __( 'User Login', 'advanced-form-integration' ),
            'user_email'        => __( 'User Email', 'advanced-form-integration' ),
            'display_name'      => __( 'Display Name', 'advanced-form-integration' ),
            'user_registered'   => __( 'User Registered', 'advanced-form-integration' ),
            'user_status'       => __( 'User Status', 'advanced-form-integration' ),
            'user_photo'        => __( 'User Photo URL', 'advanced-form-integration' ),
            'pivot_settings'    => __( 'Board Settings (JSON)', 'advanced-form-integration' ),
            'pivot_preferences' => __( 'Board Preferences (JSON)', 'advanced-form-integration' ),
            'user_raw'          => __( 'Raw User (JSON)', 'advanced-form-integration' ),
        );
    } elseif ( 'taskCreated' === $form_id ) {
        $fields = array(
            'task_id'        => __( 'Task ID', 'advanced-form-integration' ),
            'board_id'       => __( 'Board ID', 'advanced-form-integration' ),
            'stage_id'       => __( 'Stage ID', 'advanced-form-integration' ),
            'parent_id'      => __( 'Parent Task ID', 'advanced-form-integration' ),
            'title'          => __( 'Task Title', 'advanced-form-integration' ),
            'slug'           => __( 'Task Slug', 'advanced-form-integration' ),
            'type'           => __( 'Task Type', 'advanced-form-integration' ),
            'status'         => __( 'Status', 'advanced-form-integration' ),
            'priority'       => __( 'Priority', 'advanced-form-integration' ),
            'crm_contact_id' => __( 'CRM Contact ID', 'advanced-form-integration' ),
            'lead_value'     => __( 'Lead Value', 'advanced-form-integration' ),
            'description'    => __( 'Description', 'advanced-form-integration' ),
            'due_at'         => __( 'Due At', 'advanced-form-integration' ),
            'started_at'     => __( 'Started At', 'advanced-form-integration' ),
            'remind_at'      => __( 'Remind At', 'advanced-form-integration' ),
            'created_by'     => __( 'Created By (User ID)', 'advanced-form-integration' ),
            'settings'       => __( 'Settings (JSON)', 'advanced-form-integration' ),
            'events'         => __( 'Events (JSON)', 'advanced-form-integration' ),
            'meta'           => __( 'Meta (JSON)', 'advanced-form-integration' ),
            'task_raw'       => __( 'Raw Task (JSON)', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

/**
 * Normalize data coming from Fluent Boards models.
 *
 * @param mixed $data Model or array data.
 *
 * @return array
 */
function adfoin_fluentboards_normalize_data( $data ) {
    if ( empty( $data ) ) {
        return array();
    }

    if ( is_array( $data ) ) {
        return $data;
    }

    $converted = json_decode( wp_json_encode( $data ), true );

    return is_array( $converted ) ? $converted : array();
}

/**
 * Dispatch data to saved Fluent Boards integrations.
 *
 * @param string $trigger Trigger key.
 * @param array  $payload Data payload.
 *
 * @return void
 */
function adfoin_fluentboards_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $records     = $integration->get_by_trigger( 'fluentboards', $trigger );

    if ( empty( $records ) ) {
        return;
    }

    $integration->send( $records, $payload );
}

/**
 * Prepare board payload.
 *
 * @param mixed $board Board data.
 *
 * @return array
 */
function adfoin_fluentboards_prepare_board_payload( $board ) {
    $board_data = adfoin_fluentboards_normalize_data( $board );

    if ( empty( $board_data ) ) {
        return array();
    }

    $payload = array(
        'board_id'    => $board_data['id'] ?? '',
        'title'       => $board_data['title'] ?? '',
        'description' => $board_data['description'] ?? '',
        'type'        => $board_data['type'] ?? '',
        'currency'    => $board_data['currency'] ?? '',
        'background'  => isset( $board_data['background'] ) ? ( is_array( $board_data['background'] ) ? wp_json_encode( $board_data['background'] ) : $board_data['background'] ) : '',
        'settings'    => isset( $board_data['settings'] ) ? ( is_array( $board_data['settings'] ) ? wp_json_encode( $board_data['settings'] ) : $board_data['settings'] ) : '',
        'created_by'  => $board_data['created_by'] ?? '',
        'archived_at' => $board_data['archived_at'] ?? '',
        'board_meta'  => isset( $board_data['meta'] ) ? wp_json_encode( $board_data['meta'] ) : '',
        'board_raw'   => wp_json_encode( $board_data ),
    );

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Prepare board member payload.
 *
 * @param int   $board_id     Board ID.
 * @param mixed $board_member Member data.
 *
 * @return array
 */
function adfoin_fluentboards_prepare_member_payload( $board_id, $board_member ) {
    $member_data = adfoin_fluentboards_normalize_data( $board_member );

    if ( empty( $board_id ) || empty( $member_data ) ) {
        return array();
    }

    $pivot_settings    = isset( $member_data['pivot']['settings'] ) ? maybe_unserialize( $member_data['pivot']['settings'] ) : '';
    $pivot_preferences = isset( $member_data['pivot']['preferences'] ) ? maybe_unserialize( $member_data['pivot']['preferences'] ) : '';

    $payload = array(
        'board_id'          => $board_id,
        'user_id'           => $member_data['ID'] ?? ( $member_data['id'] ?? '' ),
        'user_login'        => $member_data['user_login'] ?? '',
        'user_email'        => $member_data['user_email'] ?? '',
        'display_name'      => $member_data['display_name'] ?? '',
        'user_registered'   => $member_data['user_registered'] ?? '',
        'user_status'       => $member_data['user_status'] ?? '',
        'user_photo'        => $member_data['photo'] ?? '',
        'pivot_settings'    => $pivot_settings ? wp_json_encode( $pivot_settings ) : '',
        'pivot_preferences' => $pivot_preferences ? wp_json_encode( $pivot_preferences ) : '',
        'user_raw'          => wp_json_encode( $member_data ),
    );

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Prepare task payload.
 *
 * @param mixed $task Task data.
 *
 * @return array
 */
function adfoin_fluentboards_prepare_task_payload( $task ) {
    $task_data = adfoin_fluentboards_normalize_data( $task );

    if ( empty( $task_data ) ) {
        return array();
    }

    $payload = array(
        'task_id'        => $task_data['id'] ?? '',
        'board_id'       => $task_data['board_id'] ?? '',
        'stage_id'       => $task_data['stage_id'] ?? '',
        'parent_id'      => $task_data['parent_id'] ?? '',
        'title'          => $task_data['title'] ?? '',
        'slug'           => $task_data['slug'] ?? '',
        'type'           => $task_data['type'] ?? '',
        'status'         => $task_data['status'] ?? '',
        'priority'       => $task_data['priority'] ?? '',
        'crm_contact_id' => $task_data['crm_contact_id'] ?? '',
        'lead_value'     => $task_data['lead_value'] ?? '',
        'description'    => $task_data['description'] ?? '',
        'due_at'         => $task_data['due_at'] ?? '',
        'started_at'     => $task_data['started_at'] ?? '',
        'remind_at'      => $task_data['remind_at'] ?? '',
        'created_by'     => $task_data['created_by'] ?? '',
        'settings'       => isset( $task_data['settings'] ) ? ( is_array( $task_data['settings'] ) ? wp_json_encode( $task_data['settings'] ) : $task_data['settings'] ) : '',
        'events'         => isset( $task_data['events'] ) ? ( is_array( $task_data['events'] ) ? wp_json_encode( $task_data['events'] ) : $task_data['events'] ) : '',
        'meta'           => isset( $task_data['meta'] ) ? wp_json_encode( $task_data['meta'] ) : '',
        'task_raw'       => wp_json_encode( $task_data ),
    );

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

// Handle board created events.
add_action( 'fluent_boards/board_created', 'adfoin_fluentboards_handle_board_created', 10, 1 );
function adfoin_fluentboards_handle_board_created( $board ) {
    $payload = adfoin_fluentboards_prepare_board_payload( $board );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentboards_dispatch( 'boardCreated', $payload );
}

// Handle board member added events.
add_action( 'fluent_boards/board_member_added', 'adfoin_fluentboards_handle_board_member_added', 10, 2 );
function adfoin_fluentboards_handle_board_member_added( $board_id, $board_member ) {
    $payload = adfoin_fluentboards_prepare_member_payload( $board_id, $board_member );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentboards_dispatch( 'boardMemberAdded', $payload );
}

// Handle task created events.
add_action( 'fluent_boards/task_created', 'adfoin_fluentboards_handle_task_created', 10, 1 );
function adfoin_fluentboards_handle_task_created( $task ) {
    $payload = adfoin_fluentboards_prepare_task_payload( $task );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentboards_dispatch( 'taskCreated', $payload );
}
