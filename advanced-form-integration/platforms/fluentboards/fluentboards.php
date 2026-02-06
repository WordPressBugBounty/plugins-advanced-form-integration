<?php

add_filter( 'adfoin_action_providers', 'adfoin_fluentboards_actions', 10, 1 );

function adfoin_fluentboards_actions( $actions ) {

    $actions['fluentboards'] = array(
        'title' => __( 'Fluent Boards', 'advanced-form-integration' ),
        'tasks' => array(
            'create_board' => __( 'Create Board', 'advanced-form-integration' ),
            'create_task'  => __( 'Create Task', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_fluentboards_action_fields' );

function adfoin_fluentboards_action_fields() {
    ?>
    <script type="text/template" id="fluentboards-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_board'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Title is required. Optionally set type (to-do/roadmap), description, currency, and creator user ID.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_task'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Board ID, stage ID (or stage name), and task title are required. Assignee/label IDs accept comma separated lists.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            :key="field.value"
                            :field="field"
                            :trigger="trigger"
                            :action="action"
                            :fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_fluentboards_job_queue', 'adfoin_fluentboards_job_queue', 10, 1 );

function adfoin_fluentboards_job_queue( $data ) {
    adfoin_fluentboards_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_fluentboards_send_data( $record, $posted_data ) {
    if ( ! class_exists( '\FluentBoards\App\Services\BoardService' ) ) {
        adfoin_fluentboards_log( $record, __( 'Fluent Boards is not active.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $parsed = array();

    if ( is_array( $field_data ) ) {
        foreach ( $field_data as $key => $value ) {
            $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
        }
    }

    if ( 'create_board' === $task ) {
        adfoin_fluentboards_create_board( $record, $parsed );
    } elseif ( 'create_task' === $task ) {
        adfoin_fluentboards_create_task( $record, $parsed );
    }
}

function adfoin_fluentboards_create_board( $record, $parsed ) {
    $title = isset( $parsed['title'] ) ? sanitize_text_field( $parsed['title'] ) : '';

    if ( '' === $title ) {
        adfoin_fluentboards_log( $record, __( 'Board title is required.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $type = isset( $parsed['type'] ) ? sanitize_key( $parsed['type'] ) : 'to-do';
    if ( ! in_array( $type, array( 'to-do', 'roadmap' ), true ) ) {
        $type = 'to-do';
    }

    $description = isset( $parsed['description'] ) ? sanitize_textarea_field( $parsed['description'] ) : '';
    $currency    = isset( $parsed['currency'] ) ? sanitize_text_field( $parsed['currency'] ) : '';
    $background  = isset( $parsed['background'] ) ? sanitize_text_field( $parsed['background'] ) : '';

    $created_by = isset( $parsed['created_by'] ) ? absint( $parsed['created_by'] ) : 0;
    if ( $created_by && ! get_user_by( 'id', $created_by ) ) {
        adfoin_fluentboards_log(
            $record,
            __( 'Provided creator user ID does not exist.', 'advanced-form-integration' ),
            array( 'created_by' => $created_by ),
            false
        );
        return;
    }

    if ( ! $created_by ) {
        $created_by = get_current_user_id();
    }

    $board_data = array(
        'title'       => $title,
        'type'        => $type,
        'description' => $description,
        'currency'    => $currency ? $currency : 'USD',
        'background'  => $background,
        'created_by'  => $created_by,
    );

    try {
        $service = new \FluentBoards\App\Services\BoardService();
        $board   = $service->createBoard( $board_data );
    } catch ( \Exception $e ) {
        adfoin_fluentboards_log(
            $record,
            sprintf(
                /* translators: %s error message */
                __( 'Failed to create board: %s', 'advanced-form-integration' ),
                $e->getMessage()
            ),
            $board_data,
            false
        );
        return;
    }

    adfoin_fluentboards_log(
        $record,
        __( 'Board created successfully.', 'advanced-form-integration' ),
        array(
            'board_id' => $board->id,
            'title'    => $board->title,
            'type'     => $board->type,
        ),
        true
    );
}

function adfoin_fluentboards_create_task( $record, $parsed ) {
    if ( ! class_exists( '\FluentBoards\App\Services\TaskService' ) ) {
        adfoin_fluentboards_log( $record, __( 'Fluent Boards task service is not available.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $board_id = isset( $parsed['board_id'] ) ? absint( $parsed['board_id'] ) : 0;
    $stage_id = isset( $parsed['stage_id'] ) ? absint( $parsed['stage_id'] ) : 0;
    $stage_name = isset( $parsed['stage_name'] ) ? sanitize_text_field( $parsed['stage_name'] ) : '';
    $title    = isset( $parsed['title'] ) ? sanitize_text_field( $parsed['title'] ) : '';

    if ( ! $board_id ) {
        adfoin_fluentboards_log( $record, __( 'Board ID is required to create a task.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $board = \FluentBoards\App\Models\Board::find( $board_id );
    if ( ! $board ) {
        adfoin_fluentboards_log(
            $record,
            __( 'Board was not found.', 'advanced-form-integration' ),
            array( 'board_id' => $board_id ),
            false
        );
        return;
    }

    if ( ! $stage_id && $stage_name ) {
        $stage = \FluentBoards\App\Models\Stage::where( 'board_id', $board_id )
            ->where( 'title', $stage_name )
            ->whereNull( 'archived_at' )
            ->first();
        if ( $stage ) {
            $stage_id = $stage->id;
        }
    } else {
        $stage = \FluentBoards\App\Models\Stage::find( $stage_id );
    }

    if ( ! $stage_id || ! $stage ) {
        adfoin_fluentboards_log(
            $record,
            __( 'Stage was not found for the provided board.', 'advanced-form-integration' ),
            array(
                'board_id'  => $board_id,
                'stage_id'  => $stage_id,
                'stage_name'=> $stage_name,
            ),
            false
        );
        return;
    }

    if ( '' === $title ) {
        adfoin_fluentboards_log(
            $record,
            __( 'Task title is required.', 'advanced-form-integration' ),
            array(),
            false
        );
        return;
    }

    $task_data = array(
        'title'          => $title,
        'board_id'       => $board_id,
        'stage_id'       => $stage_id,
        'description'    => isset( $parsed['description'] ) ? sanitize_textarea_field( $parsed['description'] ) : '',
        'priority'       => isset( $parsed['priority'] ) ? sanitize_text_field( $parsed['priority'] ) : '',
        'crm_contact_id' => isset( $parsed['crm_contact_id'] ) && $parsed['crm_contact_id'] !== '' ? absint( $parsed['crm_contact_id'] ) : null,
        'due_at'         => isset( $parsed['due_at'] ) ? sanitize_text_field( $parsed['due_at'] ) : '',
        'started_at'     => isset( $parsed['started_at'] ) ? sanitize_text_field( $parsed['started_at'] ) : '',
        'is_template'    => isset( $parsed['is_template'] ) ? sanitize_text_field( $parsed['is_template'] ) : '',
        'settings'       => isset( $parsed['settings_json'] ) && $parsed['settings_json'] !== '' ? adfoin_fluentboards_decode_json( $parsed['settings_json'] ) : null,
        'type'           => isset( $parsed['type'] ) ? sanitize_text_field( $parsed['type'] ) : '',
        'scope'          => isset( $parsed['scope'] ) ? sanitize_text_field( $parsed['scope'] ) : '',
        'source'         => isset( $parsed['source'] ) ? sanitize_text_field( $parsed['source'] ) : '',
        'reminder_type'  => isset( $parsed['reminder_type'] ) ? sanitize_text_field( $parsed['reminder_type'] ) : '',
        'remind_at'      => isset( $parsed['remind_at'] ) ? sanitize_text_field( $parsed['remind_at'] ) : '',
    );

    if ( isset( $parsed['assignee_ids'] ) && '' !== $parsed['assignee_ids'] ) {
        $task_data['assignees'] = adfoin_fluentboards_parse_ids( $parsed['assignee_ids'] );
    }

    if ( isset( $parsed['label_ids'] ) && '' !== $parsed['label_ids'] ) {
        $task_data['labels'] = adfoin_fluentboards_parse_ids( $parsed['label_ids'] );
    }

    $task_data = array_filter(
        $task_data,
        static function ( $value ) {
            return null !== $value && '' !== $value;
        }
    );

    $task_data = \FluentBoards\App\Services\Helper::sanitizeTask( $task_data );

    try {
        $task_service = new \FluentBoards\App\Services\TaskService();
        $task         = $task_service->createTask( $task_data, $board_id );
    } catch ( \Exception $e ) {
        adfoin_fluentboards_log(
            $record,
            sprintf(
                /* translators: %s error message */
                __( 'Failed to create task: %s', 'advanced-form-integration' ),
                $e->getMessage()
            ),
            $task_data,
            false
        );
        return;
    }

    if ( isset( $parsed['watcher_ids'] ) && '' !== $parsed['watcher_ids'] ) {
        $watchers = adfoin_fluentboards_parse_ids( $parsed['watcher_ids'] );
        if ( $watchers ) {
            foreach ( $watchers as $watcher_id ) {
                $task->watchers()->syncWithoutDetaching( array(
                    $watcher_id => array(
                        'object_type' => \FluentBoards\App\Services\Constant::OBJECT_TYPE_USER_TASK_WATCH,
                    ),
                ) );
            }
        }
    }

    adfoin_fluentboards_log(
        $record,
        __( 'Task created successfully.', 'advanced-form-integration' ),
        array(
            'task_id'  => $task->id,
            'board_id' => $task->board_id,
            'stage_id' => $task->stage_id,
        ),
        true
    );
}

function adfoin_fluentboards_parse_ids( $value ) {
    $ids = array_map( 'trim', explode( ',', $value ) );
    $ids = array_filter( $ids, static function ( $val ) {
        return '' !== $val && is_numeric( $val );
    } );

    return array_map( 'intval', $ids );
}

function adfoin_fluentboards_decode_json( $value ) {
    $decoded = json_decode( $value, true );
    if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
        return $decoded;
    }

    return null;
}

function adfoin_fluentboards_log( $record, $message, $payload, $success ) {
    $log_response = array(
        'response' => array(
            'code'    => $success ? 200 : 400,
            'message' => $message,
        ),
        'body'     => array(
            'success' => $success,
            'message' => $message,
        ),
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $payload,
    );

    adfoin_add_to_log( $log_response, 'fluentboards', $log_args, $record );
}
