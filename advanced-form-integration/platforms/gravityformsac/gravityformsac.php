<?php

add_filter( 'adfoin_action_providers', 'adfoin_gravityformsac_actions', 10, 1 );

function adfoin_gravityformsac_actions( $actions ) {
    $actions['gravityformsac'] = array(
        'title' => __( 'Gravity Forms', 'advanced-form-integration' ),
        'tasks' => array(
            'create_entry' => __( 'Create Entry', 'advanced-form-integration' ),
            'update_entry' => __( 'Update Entry', 'advanced-form-integration' ),
            'add_note'     => __( 'Add Entry Note', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_gravityformsac_action_fields' );

function adfoin_gravityformsac_action_fields() {
    ?>
    <script type="text/template" id="gravityformsac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_entry' || action.task == 'update_entry'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Form', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[formId]" v-model="fielddata.formId" @change="getFields(action.task)">
                        <option value=""> <?php _e( 'Select Form...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.forms" :value="index"> {{item}} </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': formLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_gravityformsac_forms', 'adfoin_get_gravityformsac_forms_ajax', 10, 0 );

function adfoin_get_gravityformsac_forms_ajax() {
    adfoin_verify_nonce();

    $forms = array();

    if ( class_exists( 'GFAPI' ) ) {
        $result = GFAPI::get_forms();
        $forms  = wp_list_pluck( $result, 'title', 'id' );
    }

    wp_send_json_success( $forms );
}

add_action( 'wp_ajax_adfoin_get_gravityformsac_fields', 'adfoin_get_gravityformsac_fields_ajax', 10, 0 );

function adfoin_get_gravityformsac_fields_ajax() {
    adfoin_verify_nonce();

    $task    = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';
    $form_id = isset( $_POST['formId'] ) ? sanitize_text_field( wp_unslash( $_POST['formId'] ) ) : '';
    $fields  = array();

    // Common metadata fields shared across create_entry / update_entry / add_note.
    if ( 'update_entry' === $task || 'add_note' === $task ) {
        $fields[] = array( 'key' => 'entryId', 'value' => 'Entry ID', 'description' => 'Entry ID to locate the Gravity Forms submission.', 'required' => true );
    }

    if ( 'create_entry' === $task || 'update_entry' === $task || 'add_note' === $task ) {
        $fields[] = array( 'key' => 'entryNote', 'value' => 'Entry Note', 'description' => 'Optional note saved to the entry after processing.' );
    }

    // Form-field placeholders, mapped one-to-one with Gravity Forms field IDs.
    if ( ( 'create_entry' === $task || 'update_entry' === $task ) && $form_id && class_exists( 'GFAPI' ) ) {
        $form = GFAPI::get_form( $form_id );

        if ( $form && ! empty( $form['fields'] ) ) {
            $raw_fields = json_decode( json_encode( $form['fields'] ) );

            foreach ( $raw_fields as $field ) {
                if ( ! empty( $field->inputs ) && ! in_array( $field->type, array( 'checkbox', 'time', 'consent', 'date' ), true ) ) {
                    foreach ( $field->inputs as $input ) {
                        $fields[] = array(
                            'key'         => (string) $input->id,
                            'value'       => $input->label,
                            'description' => 'Field ID ' . $input->id,
                        );
                    }
                    continue;
                }

                $fields[] = array(
                    'key'         => (string) $field->id,
                    'value'       => $field->label,
                    'description' => 'Field ID ' . $field->id,
                );
            }
        }
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_gravityformsac_job_queue', 'adfoin_gravityformsac_job_queue', 10, 1 );

function adfoin_gravityformsac_job_queue( $data ) {
    adfoin_gravityformsac_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_gravityformsac_send_data( $record, $posted_data ) {
    if ( ! class_exists( 'GFAPI' ) ) {
        adfoin_gravityformsac_action_log( $record, __( 'Gravity Forms is not active.', 'advanced-form-integration' ), array(), false );
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
            if ( 'forms' === $key || 'fieldMap' === $key ) {
                continue;
            }
            $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
        }
    }

    // Backward compat: a previously-saved integration may still include the
    // legacy JSON `fieldMap` blob. Merge its parsed values into $parsed under
    // their Gravity Forms field-id keys so prepare_entry_payload sees them.
    if ( ! empty( $field_data['fieldMap'] ) ) {
        $legacy_map = adfoin_gravityformsac_parse_field_map( $field_data['fieldMap'], $posted_data );
        foreach ( $legacy_map as $field_id => $value ) {
            if ( ! isset( $parsed[ $field_id ] ) || '' === $parsed[ $field_id ] ) {
                $parsed[ $field_id ] = $value;
            }
        }
    }

    if ( 'create_entry' === $task ) {
        adfoin_gravityformsac_action_create_entry( $record, $parsed );
    } elseif ( 'update_entry' === $task ) {
        adfoin_gravityformsac_action_update_entry( $record, $parsed );
    } elseif ( 'add_note' === $task ) {
        adfoin_gravityformsac_action_add_note( $record, $parsed );
    } else {
        adfoin_gravityformsac_action_log( $record, __( 'Unsupported Gravity Forms task.', 'advanced-form-integration' ), array( 'task' => $task ), false );
    }
}

function adfoin_gravityformsac_action_create_entry( $record, $parsed ) {
    $form_id = isset( $parsed['formId'] ) ? absint( $parsed['formId'] ) : 0;

    if ( ! $form_id ) {
        adfoin_gravityformsac_action_log( $record, __( 'A Gravity Form must be selected.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $form = GFAPI::get_form( $form_id );
    if ( ! $form ) {
        adfoin_gravityformsac_action_log(
            $record,
            /* translators: %d: Gravity Forms form ID */
            sprintf( __( 'Gravity Form with ID %d does not exist or is not accessible.', 'advanced-form-integration' ), $form_id ),
            array( 'form_id' => $form_id ),
            false
        );
        return;
    }

    $entry = adfoin_gravityformsac_prepare_entry_payload( $form_id, $parsed );

    $entry_id = GFAPI::add_entry( $entry );

    if ( is_wp_error( $entry_id ) ) {
        adfoin_gravityformsac_action_log( $record, $entry_id->get_error_message(), array( 'form_id' => $form_id, 'payload' => $entry ), false );
        return;
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_gravityformsac_maybe_add_note( $entry_id, $parsed['entryNote'] );
    }

    adfoin_gravityformsac_action_log(
        $record,
        __( 'Gravity Forms entry created successfully.', 'advanced-form-integration' ),
        array(
            'form_id'  => $form_id,
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_gravityformsac_action_update_entry( $record, $parsed ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;

    if ( ! $entry_id ) {
        adfoin_gravityformsac_action_log( $record, __( 'Entry ID is required for updates.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $entry = GFAPI::get_entry( $entry_id );

    if ( is_wp_error( $entry ) ) {
        adfoin_gravityformsac_action_log( $record, $entry->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $form_id = isset( $entry['form_id'] ) ? absint( $entry['form_id'] ) : 0;

    if ( isset( $parsed['formId'] ) && '' !== $parsed['formId'] ) {
        $form_id          = absint( $parsed['formId'] );
        $entry['form_id'] = $form_id;
    }

    $entry = adfoin_gravityformsac_prepare_entry_payload( $form_id, $parsed, $entry, true );

    $updated = GFAPI::update_entry( $entry, $entry_id );

    if ( is_wp_error( $updated ) ) {
        adfoin_gravityformsac_action_log( $record, $updated->get_error_message(), array( 'entry_id' => $entry_id, 'payload' => $entry ), false );
        return;
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_gravityformsac_maybe_add_note( $entry_id, $parsed['entryNote'] );
    }

    adfoin_gravityformsac_action_log(
        $record,
        __( 'Gravity Forms entry updated successfully.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
            'form_id'  => $form_id,
        ),
        true
    );
}

function adfoin_gravityformsac_action_add_note( $record, $parsed ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;
    $note     = isset( $parsed['entryNote'] ) ? $parsed['entryNote'] : '';

    if ( ! $entry_id ) {
        adfoin_gravityformsac_action_log( $record, __( 'Entry ID is required to add a note.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    if ( '' === trim( $note ) ) {
        adfoin_gravityformsac_action_log( $record, __( 'Note content is empty, nothing to add.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $entry = GFAPI::get_entry( $entry_id );

    if ( is_wp_error( $entry ) ) {
        adfoin_gravityformsac_action_log( $record, $entry->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $result = adfoin_gravityformsac_maybe_add_note( $entry_id, $note );

    if ( is_wp_error( $result ) ) {
        adfoin_gravityformsac_action_log( $record, $result->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    if ( ! $result ) {
        adfoin_gravityformsac_action_log( $record, __( 'The note could not be saved to the database.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    adfoin_gravityformsac_action_log(
        $record,
        __( 'Note added to Gravity Forms entry.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_gravityformsac_prepare_entry_payload( $form_id, $parsed, $entry = array(), $is_update = false ) {
    $payload            = is_array( $entry ) ? $entry : array();
    $payload['form_id'] = $form_id;

    $payload = adfoin_gravityformsac_apply_entry_metadata( $payload, $parsed, $is_update );

    // Reserved keys are metadata or form-routing controls — never copy them
    // through as Gravity Forms field values.
    $reserved = array(
        'formId', 'entryId', 'entryNote', 'entryStatus', 'sourceUrl', 'userAgent',
        'ipAddress', 'createdBy', 'currency', 'paymentStatus', 'paymentMethod',
        'paymentAmount', 'paymentDate', 'transactionId', 'transactionType',
        'isFulfilled', 'isStarred', 'isRead', 'postId', 'sourceId',
        'dateCreated', 'dateUpdated',
    );

    foreach ( $parsed as $field_id => $value ) {
        if ( in_array( $field_id, $reserved, true ) ) {
            continue;
        }
        if ( '' === $value ) {
            continue;
        }
        // Gravity Forms field IDs are numeric (e.g. "1") or dotted ("2.1").
        $payload[ $field_id ] = $value;
    }

    return $payload;
}

function adfoin_gravityformsac_apply_entry_metadata( $payload, $parsed, $is_update ) {
    if ( isset( $parsed['entryStatus'] ) && '' !== $parsed['entryStatus'] ) {
        $status = adfoin_gravityformsac_sanitize_status( $parsed['entryStatus'] );
        if ( $status ) {
            $payload['status'] = $status;
        }
    } elseif ( ! $is_update && ! isset( $payload['status'] ) ) {
        $payload['status'] = 'active';
    }

    if ( isset( $parsed['sourceUrl'] ) && '' !== $parsed['sourceUrl'] ) {
        $payload['source_url'] = esc_url_raw( $parsed['sourceUrl'] );
    }

    if ( isset( $parsed['userAgent'] ) && '' !== $parsed['userAgent'] ) {
        $payload['user_agent'] = sanitize_text_field( $parsed['userAgent'] );
    }

    if ( isset( $parsed['ipAddress'] ) && '' !== $parsed['ipAddress'] ) {
        $payload['ip'] = sanitize_text_field( $parsed['ipAddress'] );
    }

    if ( isset( $parsed['createdBy'] ) && '' !== $parsed['createdBy'] ) {
        $payload['created_by'] = absint( $parsed['createdBy'] );
    }

    if ( isset( $parsed['currency'] ) && '' !== $parsed['currency'] ) {
        $payload['currency'] = strtoupper( sanitize_text_field( $parsed['currency'] ) );
    }

    if ( isset( $parsed['paymentStatus'] ) && '' !== $parsed['paymentStatus'] ) {
        $payload['payment_status'] = sanitize_text_field( $parsed['paymentStatus'] );
    }

    if ( isset( $parsed['paymentMethod'] ) && '' !== $parsed['paymentMethod'] ) {
        $payload['payment_method'] = sanitize_text_field( $parsed['paymentMethod'] );
    }

    if ( isset( $parsed['paymentAmount'] ) && '' !== $parsed['paymentAmount'] ) {
        $payload['payment_amount'] = floatval( $parsed['paymentAmount'] );
    }

    if ( isset( $parsed['paymentDate'] ) && '' !== $parsed['paymentDate'] ) {
        $payload['payment_date'] = sanitize_text_field( $parsed['paymentDate'] );
    }

    if ( isset( $parsed['transactionId'] ) && '' !== $parsed['transactionId'] ) {
        $payload['transaction_id'] = sanitize_text_field( $parsed['transactionId'] );
    }

    if ( isset( $parsed['transactionType'] ) && '' !== $parsed['transactionType'] ) {
        $payload['transaction_type'] = (int) $parsed['transactionType'];
    }

    if ( isset( $parsed['isFulfilled'] ) && '' !== $parsed['isFulfilled'] ) {
        $payload['is_fulfilled'] = (int) $parsed['isFulfilled'];
    }

    if ( isset( $parsed['isStarred'] ) && '' !== $parsed['isStarred'] ) {
        $payload['is_starred'] = (int) $parsed['isStarred'];
    }

    if ( isset( $parsed['isRead'] ) && '' !== $parsed['isRead'] ) {
        $payload['is_read'] = (int) $parsed['isRead'];
    }

    if ( isset( $parsed['postId'] ) && '' !== $parsed['postId'] ) {
        $payload['post_id'] = absint( $parsed['postId'] );
    }

    if ( isset( $parsed['sourceId'] ) && '' !== $parsed['sourceId'] ) {
        $payload['source_id'] = absint( $parsed['sourceId'] );
    }

    if ( isset( $parsed['dateCreated'] ) && '' !== $parsed['dateCreated'] ) {
        $payload['date_created'] = sanitize_text_field( $parsed['dateCreated'] );
    }

    if ( isset( $parsed['dateUpdated'] ) && '' !== $parsed['dateUpdated'] ) {
        $payload['date_updated'] = sanitize_text_field( $parsed['dateUpdated'] );
    }

    return $payload;
}

function adfoin_gravityformsac_parse_field_map( $raw_map, $posted_data ) {
    if ( empty( $raw_map ) ) {
        return array();
    }

    if ( is_array( $raw_map ) ) {
        $decoded = $raw_map;
    } else {
        $decoded = json_decode( wp_unslash( $raw_map ), true );
    }

    if ( ! is_array( $decoded ) ) {
        return array();
    }

    $parsed_map = array();

    foreach ( $decoded as $field_id => $template ) {
        $parsed_map[ $field_id ] = adfoin_get_parsed_values( $template, $posted_data );
    }

    return $parsed_map;
}

function adfoin_gravityformsac_maybe_add_note( $entry_id, $note ) {
    if ( '' === trim( $note ) ) {
        return true;
    }

    if ( ! class_exists( 'GFFormsModel' ) || ! method_exists( 'GFFormsModel', 'add_note' ) ) {
        return new WP_Error( 'gravityformsac_note_api_unavailable', __( 'Unable to add a note because the Gravity Forms note API is unavailable.', 'advanced-form-integration' ) );
    }

    $user_id   = get_current_user_id();
    $user      = $user_id ? get_userdata( $user_id ) : false;
    $user_name = $user && ! empty( $user->display_name ) ? $user->display_name : __( 'Advanced Form Integration', 'advanced-form-integration' );

    $note_id = GFFormsModel::add_note( $entry_id, $user_id ?: 0, $user_name, wp_strip_all_tags( $note ), 'user', 'advanced-form-integration' );

    return $note_id;
}

function adfoin_gravityformsac_sanitize_status( $status ) {
    $status  = strtolower( sanitize_text_field( $status ) );
    $allowed = array( 'active', 'spam', 'trash' );

    return in_array( $status, $allowed, true ) ? $status : '';
}

function adfoin_gravityformsac_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'gravityformsac', $log_args, $record );
}

