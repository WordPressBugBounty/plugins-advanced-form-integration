<?php

use NinjaTables\App\Models\NinjaTableItem;

add_filter( 'adfoin_action_providers', 'adfoin_ninjatables_actions', 10, 1 );

function adfoin_ninjatables_actions( $actions ) {

    $actions['ninjatables'] = array(
        'title' => __( 'Ninja Tables', 'advanced-form-integration' ),
        'tasks' => array(
            'create_row' => __( 'Create Row', 'advanced-form-integration' ),
            'update_row' => __( 'Update Row', 'advanced-form-integration' ),
            'delete_row' => __( 'Delete Row', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_ninjatables_action_fields' );

function adfoin_ninjatables_action_fields() {
    ?>
    <script type="text/template" id="ninjatables-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_row'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Table ID and row JSON are required. Row JSON should be an object of column keys mapped to values.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_row'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the row ID and table ID along with the full row JSON payload that should replace the existing row.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'delete_row'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the table ID and row ID to remove. This permanently deletes the row from Ninja Tables.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_ninjatables_job_queue', 'adfoin_ninjatables_job_queue', 10, 1 );

function adfoin_ninjatables_job_queue( $data ) {
    adfoin_ninjatables_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ninjatables_send_data( $record, $posted_data ) {
    if ( ! class_exists( '\NinjaTables\App\Models\NinjaTableItem' ) ) {
        adfoin_ninjatables_action_log( $record, __( 'Ninja Tables is not active.', 'advanced-form-integration' ), array(), false );
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

    switch ( $task ) {
        case 'create_row':
            adfoin_ninjatables_action_create_row( $record, $parsed );
            break;
        case 'update_row':
            adfoin_ninjatables_action_update_row( $record, $parsed );
            break;
        case 'delete_row':
            adfoin_ninjatables_action_delete_row( $record, $parsed );
            break;
        default:
            adfoin_ninjatables_action_log(
                $record,
                __( 'Unknown Ninja Tables task received.', 'advanced-form-integration' ),
                array( 'task' => $task ),
                false
            );
            break;
    }
}

function adfoin_ninjatables_action_create_row( $record, $parsed ) {
    $table_id = isset( $parsed['table_id'] ) ? absint( $parsed['table_id'] ) : 0;
    if ( ! $table_id ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Table ID is required.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $row_json = isset( $parsed['row_json'] ) ? $parsed['row_json'] : '';
    $row      = adfoin_ninjatables_prepare_row( $row_json, $table_id );

    if ( false === $row ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Row JSON is invalid.', 'advanced-form-integration' ),
            array( 'row_json' => $row_json ),
            false
        );
        return;
    }

    if ( empty( $row ) ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Row data is empty after sanitization.', 'advanced-form-integration' ),
            array( 'row_json' => $row_json ),
            false
        );
        return;
    }

    $created_at    = isset( $parsed['created_at'] ) && $parsed['created_at'] !== '' ? sanitize_text_field( $parsed['created_at'] ) : null;
    $insert_after  = isset( $parsed['insert_after_id'] ) && $parsed['insert_after_id'] !== '' ? absint( $parsed['insert_after_id'] ) : null;
    $settings_json = isset( $parsed['settings_json'] ) ? $parsed['settings_json'] : '';
    $settings      = adfoin_ninjatables_prepare_settings( $settings_json );

    $result = NinjaTableItem::insertTableItem(
        0,
        $table_id,
        $row,
        $created_at,
        $insert_after,
        $settings
    );

    if ( ! $result ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Failed to create table row.', 'advanced-form-integration' ),
            array(
                'table_id' => $table_id,
            ),
            false
        );
        return;
    }

    adfoin_ninjatables_action_log(
        $record,
        __( 'Row created successfully.', 'advanced-form-integration' ),
        array(
            'table_id' => $table_id,
            'row_id'   => $result['id'] ?? null,
        ),
        true
    );
}

function adfoin_ninjatables_action_update_row( $record, $parsed ) {
    $table_id = isset( $parsed['table_id'] ) ? absint( $parsed['table_id'] ) : 0;
    $row_id   = isset( $parsed['row_id'] ) ? absint( $parsed['row_id'] ) : 0;

    if ( ! $table_id || ! $row_id ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Table ID and Row ID are required for updating.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $row_json = isset( $parsed['row_json'] ) ? $parsed['row_json'] : '';
    $row      = adfoin_ninjatables_prepare_row( $row_json, $table_id );

    if ( false === $row ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Row JSON is invalid.', 'advanced-form-integration' ),
            array( 'row_json' => $row_json ),
            false
        );
        return;
    }

    if ( empty( $row ) ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Row data is empty after sanitization.', 'advanced-form-integration' ),
            array( 'row_json' => $row_json ),
            false
        );
        return;
    }

    $created_at    = isset( $parsed['created_at'] ) && $parsed['created_at'] !== '' ? sanitize_text_field( $parsed['created_at'] ) : null;
    $settings_json = isset( $parsed['settings_json'] ) ? $parsed['settings_json'] : '';
    $settings      = adfoin_ninjatables_prepare_settings( $settings_json );

    $result = NinjaTableItem::insertTableItem(
        $row_id,
        $table_id,
        $row,
        $created_at,
        null,
        $settings
    );

    if ( ! $result ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Failed to update table row.', 'advanced-form-integration' ),
            array(
                'table_id' => $table_id,
                'row_id'   => $row_id,
            ),
            false
        );
        return;
    }

    adfoin_ninjatables_action_log(
        $record,
        __( 'Row updated successfully.', 'advanced-form-integration' ),
        array(
            'table_id' => $table_id,
            'row_id'   => $row_id,
        ),
        true
    );
}

function adfoin_ninjatables_action_delete_row( $record, $parsed ) {
    $table_id = isset( $parsed['table_id'] ) ? absint( $parsed['table_id'] ) : 0;
    $row_id   = isset( $parsed['row_id'] ) ? absint( $parsed['row_id'] ) : 0;

    if ( ! $table_id || ! $row_id ) {
        adfoin_ninjatables_action_log(
            $record,
            __( 'Table ID and Row ID are required for deletion.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    NinjaTableItem::deleteTableItem( $table_id, array( $row_id ) );

    adfoin_ninjatables_action_log(
        $record,
        __( 'Row deleted successfully.', 'advanced-form-integration' ),
        array(
            'table_id' => $table_id,
            'row_id'   => $row_id,
        ),
        true
    );
}

function adfoin_ninjatables_prepare_row( $row_json, $table_id ) {
    if ( '' === trim( (string) $row_json ) ) {
        return array();
    }

    $decoded = adfoin_ninjatables_decode_json( $row_json );

    if ( false === $decoded ) {
        return false;
    }

    if ( ! is_array( $decoded ) ) {
        return array();
    }

    if ( function_exists( 'ninja_tables_sanitize_table_content_array' ) ) {
        if ( function_exists( 'user_can_richedit' ) && user_can_richedit() ) {
            $sanitized = ninja_tables_sanitize_table_content_array( $decoded, $table_id );
        } else {
            if ( function_exists( 'ninja_tables_allowed_css_properties' ) ) {
                ninja_tables_allowed_css_properties();
            }
            if ( function_exists( 'ninja_tables_sanitize_array' ) ) {
                $sanitized = ninja_tables_sanitize_array( $decoded );
            } else {
                $sanitized = $decoded;
            }
        }
    } else {
        $sanitized = array();
        foreach ( $decoded as $key => $value ) {
            $clean_key = sanitize_text_field( $key );
            if ( is_array( $value ) ) {
                $sanitized[ $clean_key ] = array_map( 'wp_kses_post', $value );
            } else {
                $sanitized[ $clean_key ] = wp_kses_post( $value );
            }
        }
    }

    $formatted = array();
    foreach ( $sanitized as $key => $value ) {
        $formatted[ $key ] = is_array( $value ) ? $value : wp_unslash( $value );
    }

    return $formatted;
}

function adfoin_ninjatables_prepare_settings( $settings_json ) {
    if ( '' === trim( (string) $settings_json ) ) {
        return 'null';
    }

    $decoded = adfoin_ninjatables_decode_json( $settings_json );

    if ( false === $decoded ) {
        return 'null';
    }

    if ( function_exists( 'ninja_tables_sanitize_array' ) && is_array( $decoded ) ) {
        $decoded = ninja_tables_sanitize_array( $decoded );
    }

    return $decoded;
}

function adfoin_ninjatables_decode_json( $value ) {
    if ( '' === trim( (string) $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return false;
    }

    return $decoded;
}

function adfoin_ninjatables_action_log( $record, $message, $payload, $success ) {
    $log_response = array(
        'response' => array(
            'code'    => $success ? 200 : 400,
            'message' => $message,
        ),
        'body' => array(
            'success' => $success,
            'message' => $message,
        ),
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $payload,
    );

    adfoin_add_to_log( $log_response, 'ninjatables', $log_args, $record );
}

