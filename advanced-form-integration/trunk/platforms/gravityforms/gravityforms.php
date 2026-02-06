<?php

add_filter( 'adfoin_action_providers', 'adfoin_gravityforms_actions', 10, 1 );

function adfoin_gravityforms_actions( $actions ) {
    $actions['gravityforms'] = array(
        'title' => __( 'Gravity Forms', 'advanced-form-integration' ),
        'tasks' => array(
            'create_entry' => __( 'Create Entry', 'advanced-form-integration' ),
            'update_entry' => __( 'Update Entry', 'advanced-form-integration' ),
            'add_note'     => __( 'Add Entry Note', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_gravityforms_action_fields' );

function adfoin_gravityforms_action_fields() {
    ?>
    <script type="text/template" id="gravityforms-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_entry'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Select the target Gravity Form and map each field to data from your trigger. Only mapped fields are sent.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'update_entry'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide an existing entry ID, select the form (optional) and map the fields you want to update. Unmapped fields remain untouched.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_note'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Supply the entry ID and note content to append a private note to the submission.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_entry' || action.task == 'update_entry'">
                <td scope="row-title">
                    <label for="gravityforms-form-id"><?php esc_attr_e( 'Gravity Form', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select id="gravityforms-form-id" v-model="fielddata.formId" name="fieldData[formId]">
                        <option value=""><?php esc_html_e( 'Select form...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in forms" :key="id" :value="id">{{ label }}</option>
                    </select>
                    <button type="button" class="button" @click="loadForms(true)" :disabled="formsLoading"><?php esc_html_e( 'Reload', 'advanced-form-integration' ); ?></button>
                    <div class="spinner" v-bind:class="{'is-active': formsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php esc_html_e( 'If a form is missing, refresh the list after saving it in Gravity Forms.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="showFieldMapping">
                <th scope="row"><?php esc_attr_e( 'Field Mapping', 'advanced-form-integration' ); ?></th>
                <td>
                    <p class="description"><?php esc_html_e( 'Use the dropdown to insert trigger fields or type static values. Leave a field blank to skip it.', 'advanced-form-integration' ); ?></p>
                    <div class="spinner is-active" v-if="fieldsLoading" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <table class="widefat striped" v-else>
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Gravity Forms Field', 'advanced-form-integration' ); ?></th>
                                <th><?php esc_html_e( 'Value', 'advanced-form-integration' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="field in formFieldList" :key="field.id">
                                <td>
                                    <strong>{{ field.label }}</strong>
                                    <span class="description" style="display:block;margin-top:4px;"><?php esc_html_e( 'Field ID:', 'advanced-form-integration' ); ?> {{ field.id }}</span>
                                </td>
                                <td>
                                    <input type="text" class="regular-text" v-model="fieldMap[field.id]" :placeholder="field.placeholder || ''">
                                    <select class="afi-form-fields" v-model="selected[field.id]" @change="applySelected(field.id)">
                                        <option value=""><?php esc_html_e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                                        <option v-for="(label, key) in trigger.formFields" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <button type="button" class="button-link" v-if="fieldMap[field.id]" @click="clearField(field.id)"><?php esc_html_e( 'Clear', 'advanced-form-integration' ); ?></button>
                                </td>
                            </tr>
                            <tr v-if="! formFieldList.length">
                                <td colspan="2"><em><?php esc_html_e( 'No fields were returned for the selected form.', 'advanced-form-integration' ); ?></em></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>

            <editable-field
                v-for="field in fields"
                :key="field.value"
                :field="field"
                :trigger="trigger"
                :action="action"
                :fielddata="fielddata">
            </editable-field>
        </table>
        <input type="hidden" name="fieldData[fieldMap]" :value="serializedFieldMap">
    </script>
    <?php
}

add_action( 'adfoin_gravityforms_job_queue', 'adfoin_gravityforms_job_queue', 10, 1 );

function adfoin_gravityforms_job_queue( $data ) {
    adfoin_gravityforms_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_gravityforms_send_data( $record, $posted_data ) {
    if ( ! class_exists( 'GFAPI' ) ) {
        adfoin_gravityforms_action_log( $record, __( 'Gravity Forms is not active.', 'advanced-form-integration' ), array(), false );
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
            if ( 'fieldMap' === $key ) {
                continue;
            }
            $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
        }
    }

    $field_map = adfoin_gravityforms_parse_field_map( isset( $field_data['fieldMap'] ) ? $field_data['fieldMap'] : array(), $posted_data );

    if ( 'create_entry' === $task ) {
        adfoin_gravityforms_action_create_entry( $record, $parsed, $field_map );
    } elseif ( 'update_entry' === $task ) {
        adfoin_gravityforms_action_update_entry( $record, $parsed, $field_map );
    } elseif ( 'add_note' === $task ) {
        adfoin_gravityforms_action_add_note( $record, $parsed );
    } else {
        adfoin_gravityforms_action_log( $record, __( 'Unsupported Gravity Forms task.', 'advanced-form-integration' ), array( 'task' => $task ), false );
    }
}

function adfoin_gravityforms_action_create_entry( $record, $parsed, $field_map ) {
    $form_id = isset( $parsed['formId'] ) ? absint( $parsed['formId'] ) : 0;

    if ( ! $form_id ) {
        adfoin_gravityforms_action_log( $record, __( 'A Gravity Form must be selected.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $entry = adfoin_gravityforms_prepare_entry_payload( $form_id, $parsed, $field_map );

    $entry_id = GFAPI::add_entry( $entry );

    if ( is_wp_error( $entry_id ) ) {
        adfoin_gravityforms_action_log( $record, $entry_id->get_error_message(), array( 'form_id' => $form_id, 'payload' => $entry ), false );
        return;
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_gravityforms_maybe_add_note( $entry_id, $parsed['entryNote'] );
    }

    adfoin_gravityforms_action_log(
        $record,
        __( 'Gravity Forms entry created successfully.', 'advanced-form-integration' ),
        array(
            'form_id'  => $form_id,
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_gravityforms_action_update_entry( $record, $parsed, $field_map ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;

    if ( ! $entry_id ) {
        adfoin_gravityforms_action_log( $record, __( 'Entry ID is required for updates.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $entry = GFAPI::get_entry( $entry_id );

    if ( is_wp_error( $entry ) ) {
        adfoin_gravityforms_action_log( $record, $entry->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $form_id = isset( $entry['form_id'] ) ? absint( $entry['form_id'] ) : 0;

    if ( isset( $parsed['formId'] ) && '' !== $parsed['formId'] ) {
        $form_id          = absint( $parsed['formId'] );
        $entry['form_id'] = $form_id;
    }

    $entry = adfoin_gravityforms_prepare_entry_payload( $form_id, $parsed, $field_map, $entry, true );

    $updated = GFAPI::update_entry( $entry );

    if ( is_wp_error( $updated ) ) {
        adfoin_gravityforms_action_log( $record, $updated->get_error_message(), array( 'entry_id' => $entry_id, 'payload' => $entry ), false );
        return;
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_gravityforms_maybe_add_note( $entry_id, $parsed['entryNote'] );
    }

    adfoin_gravityforms_action_log(
        $record,
        __( 'Gravity Forms entry updated successfully.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
            'form_id'  => $form_id,
        ),
        true
    );
}

function adfoin_gravityforms_action_add_note( $record, $parsed ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;
    $note     = isset( $parsed['entryNote'] ) ? $parsed['entryNote'] : '';

    if ( ! $entry_id ) {
        adfoin_gravityforms_action_log( $record, __( 'Entry ID is required to add a note.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    if ( '' === trim( $note ) ) {
        adfoin_gravityforms_action_log( $record, __( 'Note content is empty, nothing to add.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $entry = GFAPI::get_entry( $entry_id );

    if ( is_wp_error( $entry ) ) {
        adfoin_gravityforms_action_log( $record, $entry->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $result = adfoin_gravityforms_maybe_add_note( $entry_id, $note );

    if ( is_wp_error( $result ) ) {
        adfoin_gravityforms_action_log( $record, $result->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    adfoin_gravityforms_action_log(
        $record,
        __( 'Note added to Gravity Forms entry.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_gravityforms_prepare_entry_payload( $form_id, $parsed, $field_map, $entry = array(), $is_update = false ) {
    $payload            = is_array( $entry ) ? $entry : array();
    $payload['form_id'] = $form_id;

    $payload = adfoin_gravityforms_apply_entry_metadata( $payload, $parsed, $is_update );

    if ( ! empty( $field_map ) && is_array( $field_map ) ) {
        foreach ( $field_map as $field_id => $value ) {
            if ( '' === $value ) {
                continue;
            }
            $payload[ $field_id ] = $value;
        }
    }

    return $payload;
}

function adfoin_gravityforms_apply_entry_metadata( $payload, $parsed, $is_update ) {
    if ( isset( $parsed['entryStatus'] ) && '' !== $parsed['entryStatus'] ) {
        $status = adfoin_gravityforms_sanitize_status( $parsed['entryStatus'] );
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

function adfoin_gravityforms_parse_field_map( $raw_map, $posted_data ) {
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

function adfoin_gravityforms_maybe_add_note( $entry_id, $note ) {
    if ( '' === trim( $note ) ) {
        return true;
    }

    if ( ! class_exists( 'GFFormsModel' ) || ! method_exists( 'GFFormsModel', 'add_note' ) ) {
        return new WP_Error( 'gravityforms_note_api_unavailable', __( 'Unable to add a note because the Gravity Forms note API is unavailable.', 'advanced-form-integration' ) );
    }

    $user_id   = get_current_user_id();
    $user      = $user_id ? get_userdata( $user_id ) : false;
    $user_name = $user && ! empty( $user->display_name ) ? $user->display_name : __( 'Advanced Form Integration', 'advanced-form-integration' );

    GFFormsModel::add_note( $entry_id, $user_id ?: 0, $user_name, wp_strip_all_tags( $note ), 'advanced-form-integration' );

    return true;
}

function adfoin_gravityforms_sanitize_status( $status ) {
    $status  = strtolower( sanitize_text_field( $status ) );
    $allowed = array( 'active', 'spam', 'trash' );

    return in_array( $status, $allowed, true ) ? $status : '';
}

function adfoin_gravityforms_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'gravityforms', $log_args, $record );
}

