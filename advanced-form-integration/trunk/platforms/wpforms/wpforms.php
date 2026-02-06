<?php

add_filter( 'adfoin_action_providers', 'adfoin_wpforms_actions', 10, 1 );

function adfoin_wpforms_actions( $actions ) {

    $actions['wpforms'] = array(
        'title' => __( 'WPForms', 'advanced-form-integration' ),
        'tasks' => array(
            'create_entry' => __( 'Create Entry', 'advanced-form-integration' ),
            'update_entry' => __( 'Update Entry', 'advanced-form-integration' ),
            'add_note'     => __( 'Add Entry Note', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_wpforms_action_fields' );

function adfoin_wpforms_action_fields() {
    ?>
    <script type="text/template" id="wpforms-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_entry'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Select the target WPForm and map each entry field. Only the values you map are sent to WPForms.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'update_entry'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide an existing entry ID and map the fields you want to change. Unmapped fields keep their current values.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_note'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Supply the entry ID and the note content. Notes are stored inside the entry timeline.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_entry' || action.task == 'update_entry'">
                <td scope="row-title">
                    <label for="wpforms-form-id"><?php esc_attr_e( 'WPForm', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select id="wpforms-form-id" v-model="fielddata.formId" name="fieldData[formId]" required>
                        <option value=""><?php esc_html_e( 'Select form...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in forms" :key="id" :value="id">{{ label }}</option>
                    </select>
                    <button type="button" class="button" @click="loadForms(true)" :disabled="formsLoading">
                        <?php esc_html_e( 'Reload', 'advanced-form-integration' ); ?>
                    </button>
                    <div class="spinner" v-bind:class="{'is-active': formsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php esc_html_e( 'Save or refresh the WPForm if changes are not visible, then click reload.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="showFieldMapping">
                <th scope="row"><?php esc_attr_e( 'Field Mapping', 'advanced-form-integration' ); ?></th>
                <td>
                    <p class="description"><?php esc_html_e( 'Use the dropdown to insert trigger values or type static text. Leave a field blank to skip it.', 'advanced-form-integration' ); ?></p>
                    <div class="spinner is-active" v-if="fieldsLoading" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <table class="widefat striped" v-else>
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'WPForms Field', 'advanced-form-integration' ); ?></th>
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

add_action( 'adfoin_wpforms_job_queue', 'adfoin_wpforms_job_queue', 10, 1 );

function adfoin_wpforms_job_queue( $data ) {
    adfoin_wpforms_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_wpforms_send_data( $record, $posted_data ) {
    if ( ! function_exists( 'wpforms' ) ) {
        adfoin_wpforms_action_log( $record, __( 'WPForms is not active.', 'advanced-form-integration' ), array(), false );
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

    $field_map = adfoin_wpforms_parse_field_map( isset( $field_data['fieldMap'] ) ? $field_data['fieldMap'] : array(), $posted_data );

    if ( 'create_entry' === $task ) {
        adfoin_wpforms_action_create_entry( $record, $parsed, $field_map );
    } elseif ( 'update_entry' === $task ) {
        adfoin_wpforms_action_update_entry( $record, $parsed, $field_map );
    } elseif ( 'add_note' === $task ) {
        adfoin_wpforms_action_add_note( $record, $parsed );
    } else {
        adfoin_wpforms_action_log( $record, __( 'Unsupported WPForms task.', 'advanced-form-integration' ), array( 'task' => $task ), false );
    }
}

function adfoin_wpforms_action_create_entry( $record, $parsed, $field_map ) {
    $form_id = isset( $parsed['formId'] ) ? absint( $parsed['formId'] ) : 0;

    if ( ! $form_id ) {
        adfoin_wpforms_action_log( $record, __( 'A WPForm must be selected.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $form_payload = adfoin_wpforms_get_form_data( $form_id );

    if ( is_wp_error( $form_payload ) ) {
        adfoin_wpforms_action_log( $record, $form_payload->get_error_message(), array( 'form_id' => $form_id ), false );
        return;
    }

    $fields_payload = adfoin_wpforms_build_fields( $form_payload['data'], $field_map );
    $entry_fields   = $fields_payload['list'];
    $fields_map     = $fields_payload['map'];

    $entry_data = adfoin_wpforms_prepare_entry_insert_data( $form_id, $parsed, $fields_map );

    $entry_handler = wpforms()->obj( 'entry' );

    if ( ! $entry_handler ) {
        adfoin_wpforms_action_log( $record, __( 'Entry handler not available in WPForms.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $entry_id = $entry_handler->add( $entry_data );

    if ( empty( $entry_id ) ) {
        adfoin_wpforms_action_log( $record, __( 'Failed to create WPForms entry.', 'advanced-form-integration' ), $entry_data, false );
        return;
    }

    if ( ! empty( $entry_fields ) ) {
        $entry_fields_handler = wpforms()->obj( 'entry_fields' );

        if ( $entry_fields_handler ) {
            $entry_fields_handler->save( $entry_fields, $form_payload['data'], $entry_id );
        }
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_wpforms_maybe_add_note( $entry_id, $form_id, $parsed['entryNote'] );
    }

    adfoin_wpforms_action_log(
        $record,
        __( 'WPForms entry created successfully.', 'advanced-form-integration' ),
        array(
            'form_id'  => $form_id,
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_wpforms_action_update_entry( $record, $parsed, $field_map ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;

    if ( ! $entry_id ) {
        adfoin_wpforms_action_log( $record, __( 'Entry ID is required for updates.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $entry_handler = wpforms()->obj( 'entry' );

    if ( ! $entry_handler ) {
        adfoin_wpforms_action_log( $record, __( 'Entry handler not available in WPForms.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $entry = $entry_handler->get( $entry_id, array( 'cap' => '' ) );

    if ( empty( $entry ) ) {
        adfoin_wpforms_action_log( $record, __( 'WPForms entry not found.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $form_id = isset( $parsed['formId'] ) && $parsed['formId'] !== '' ? absint( $parsed['formId'] ) : absint( $entry->form_id );

    $form_payload = adfoin_wpforms_get_form_data( $form_id );

    if ( is_wp_error( $form_payload ) ) {
        adfoin_wpforms_action_log( $record, $form_payload->get_error_message(), array( 'form_id' => $form_id ), false );
        return;
    }

    $fields_payload = adfoin_wpforms_build_fields( $form_payload['data'], $field_map );
    $fields_map     = $fields_payload['map'];

    $entry_update = adfoin_wpforms_prepare_entry_update_data( $entry, $parsed, $fields_map );

    if ( ! empty( $entry_update ) ) {
        $updated = $entry_handler->update( $entry_id, $entry_update, '', '', array( 'cap' => false ) );

        if ( is_wp_error( $updated ) || false === $updated ) {
            adfoin_wpforms_action_log( $record, __( 'Failed to update WPForms entry.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id, 'payload' => $entry_update ), false );
            return;
        }
    }

    if ( ! empty( $fields_payload['list'] ) ) {
        $entry_fields_handler = wpforms()->obj( 'entry_fields' );

        if ( $entry_fields_handler ) {
            $entry_fields_handler->save( $fields_payload['list'], $form_payload['data'], $entry_id, true );
        }
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_wpforms_maybe_add_note( $entry_id, $form_id, $parsed['entryNote'] );
    }

    adfoin_wpforms_action_log(
        $record,
        __( 'WPForms entry updated successfully.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
            'form_id'  => $form_id,
        ),
        true
    );
}

function adfoin_wpforms_action_add_note( $record, $parsed ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;
    $note     = isset( $parsed['entryNote'] ) ? $parsed['entryNote'] : '';

    if ( ! $entry_id ) {
        adfoin_wpforms_action_log( $record, __( 'Entry ID is required to add a note.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    if ( '' === trim( $note ) ) {
        adfoin_wpforms_action_log( $record, __( 'Note content is empty, nothing to add.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $entry_handler = wpforms()->obj( 'entry' );
    $entry         = $entry_handler ? $entry_handler->get( $entry_id, array( 'cap' => '' ) ) : null;

    if ( empty( $entry ) ) {
        adfoin_wpforms_action_log( $record, __( 'WPForms entry not found.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $note_result = adfoin_wpforms_maybe_add_note( $entry_id, absint( $entry->form_id ), $note );

    if ( is_wp_error( $note_result ) ) {
        adfoin_wpforms_action_log( $record, $note_result->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    adfoin_wpforms_action_log(
        $record,
        __( 'Note added to WPForms entry.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_wpforms_get_form_data( $form_id ) {
    if ( ! function_exists( 'wpforms' ) ) {
        return new WP_Error( 'wpforms_inactive', __( 'WPForms is not available.', 'advanced-form-integration' ) );
    }

    $form_handler = wpforms()->obj( 'form' );

    if ( ! $form_handler ) {
        return new WP_Error( 'wpforms_form_handler_missing', __( 'Unable to load WPForms form handler.', 'advanced-form-integration' ) );
    }

    $form = $form_handler->get( $form_id, array( 'cap' => false ) );

    if ( empty( $form ) || ! isset( $form->post_content ) ) {
        return new WP_Error( 'wpforms_form_not_found', __( 'WPForm could not be found.', 'advanced-form-integration' ) );
    }

    $form_data = wpforms_decode( $form->post_content );

    if ( empty( $form_data ) || ! is_array( $form_data ) ) {
        return new WP_Error( 'wpforms_invalid_form_data', __( 'Failed to decode WPForms configuration.', 'advanced-form-integration' ) );
    }

    return array(
        'form' => $form,
        'data' => $form_data,
    );
}

function adfoin_wpforms_build_fields( $form_data, $field_map ) {
    $form_fields = isset( $form_data['fields'] ) && is_array( $form_data['fields'] ) ? $form_data['fields'] : array();
    $grouped     = array();

    foreach ( $field_map as $field_key => $value ) {
        if ( '' === $value || null === $value ) {
            continue;
        }

        list( $base, $sub ) = adfoin_wpforms_split_field_key( $field_key );

        if ( ! isset( $grouped[ $base ] ) ) {
            $grouped[ $base ] = array();
        }

        $grouped[ $base ][ $sub ] = $value;
    }

    $prepared_map = array();

    foreach ( $grouped as $base_id => $parts ) {
        $field_conf = isset( $form_fields[ $base_id ] ) ? $form_fields[ $base_id ] : array();
        $field_type = isset( $field_conf['type'] ) ? $field_conf['type'] : 'text';
        $label      = isset( $field_conf['label'] ) ? $field_conf['label'] : sprintf( __( 'Field %s', 'advanced-form-integration' ), $base_id );

        $formatted = adfoin_wpforms_format_field_value( $field_type, $parts );

        $field_entry = array(
            'name'  => sanitize_text_field( $label ),
            'id'    => is_numeric( $base_id ) ? absint( $base_id ) : sanitize_key( $base_id ),
            'type'  => sanitize_key( $field_type ),
            'value' => $formatted['value'],
        );

        if ( isset( $formatted['extras'] ) && is_array( $formatted['extras'] ) ) {
            $field_entry = array_merge( $field_entry, $formatted['extras'] );
        }

        if ( isset( $formatted['value_raw'] ) ) {
            $field_entry['value_raw'] = $formatted['value_raw'];
        }

        $prepared_map[ $base_id ] = $field_entry;
    }

    return array(
        'map'  => $prepared_map,
        'list' => array_values( $prepared_map ),
    );
}

function adfoin_wpforms_format_field_value( $field_type, $parts ) {
    $field_type = sanitize_key( $field_type );
    $extras     = array();

    switch ( $field_type ) {
        case 'name':
            $first  = isset( $parts['first'] ) ? adfoin_wpforms_clean_value( $parts['first'] ) : '';
            $middle = isset( $parts['middle'] ) ? adfoin_wpforms_clean_value( $parts['middle'] ) : '';
            $last   = isset( $parts['last'] ) ? adfoin_wpforms_clean_value( $parts['last'] ) : '';

            $extras['first']  = $first;
            $extras['middle'] = $middle;
            $extras['last']   = $last;

            $value = trim( implode( ' ', array_filter( array( $first, $middle, $last ) ) ) );
            break;

        case 'address':
            $address1 = isset( $parts['address1'] ) ? adfoin_wpforms_clean_value( $parts['address1'] ) : '';
            $address2 = isset( $parts['address2'] ) ? adfoin_wpforms_clean_value( $parts['address2'] ) : '';
            $city     = isset( $parts['city'] ) ? adfoin_wpforms_clean_value( $parts['city'] ) : '';
            $state    = isset( $parts['state'] ) ? adfoin_wpforms_clean_value( $parts['state'] ) : '';
            $postal   = isset( $parts['postal'] ) ? adfoin_wpforms_clean_value( $parts['postal'] ) : '';
            $country  = isset( $parts['country'] ) ? adfoin_wpforms_clean_value( $parts['country'] ) : '';

            $extras['address1'] = $address1;
            $extras['address2'] = $address2;
            $extras['city']     = $city;
            $extras['state']    = $state;
            $extras['postal']   = $postal;
            $extras['country']  = $country;

            $value = implode(
                "\n",
                array_filter(
                    array(
                        $address1,
                        $address2,
                        trim( implode( ', ', array_filter( array( $city, $state, $postal ) ) ) ),
                        $country,
                    )
                )
            );
            break;

        case 'date-time':
            $date = isset( $parts['date'] ) ? adfoin_wpforms_clean_value( $parts['date'] ) : '';
            $time = isset( $parts['time'] ) ? adfoin_wpforms_clean_value( $parts['time'] ) : '';

            $extras['date'] = $date;
            $extras['time'] = $time;

            $value = trim( $date . ' ' . $time );
            break;

        case 'checkbox':
        case 'payment-checkbox':
            $values      = array_map( 'adfoin_wpforms_clean_value', array_values( $parts ) );
            $value       = implode( "\n", array_filter( $values ) );
            $value_raw   = implode( ',', array_filter( $values ) );
            $extras      = array();
            $return_data = array(
                'value'     => $value,
                'value_raw' => $value_raw,
                'extras'    => $extras,
            );

            return $return_data;

        default:
            $value = adfoin_wpforms_clean_value( reset( $parts ) );
            break;
    }

    return array(
        'value'  => $value,
        'extras' => $extras,
    );
}

function adfoin_wpforms_clean_value( $value ) {
    $value = is_scalar( $value ) ? $value : '';

    return wp_kses_post( $value );
}

function adfoin_wpforms_prepare_entry_insert_data( $form_id, $parsed, $fields_map ) {
    $current_time = current_time( 'mysql' );

    $fields_json = ! empty( $fields_map ) ? wp_json_encode( array_values( $fields_map ) ) : '';

    $user_id = isset( $parsed['userId'] ) && $parsed['userId'] !== ''
        ? absint( $parsed['userId'] )
        : ( get_current_user_id() ?: 0 );

    $data = array(
        'form_id'       => $form_id,
        'fields'        => $fields_json,
        'user_id'       => $user_id,
        'date'          => adfoin_wpforms_sanitize_datetime( $parsed, 'dateCreated', $current_time ),
        'date_modified' => adfoin_wpforms_sanitize_datetime( $parsed, 'dateModified', $current_time ),
        'ip_address'    => adfoin_wpforms_sanitize_ip( isset( $parsed['ipAddress'] ) ? $parsed['ipAddress'] : '' ),
        'user_agent'    => adfoin_wpforms_sanitize_user_agent( isset( $parsed['userAgent'] ) ? $parsed['userAgent'] : '' ),
        'user_uuid'     => adfoin_wpforms_sanitize_user_uuid( isset( $parsed['userUuid'] ) ? $parsed['userUuid'] : '' ),
        'post_id'       => isset( $parsed['postId'] ) && $parsed['postId'] !== '' ? absint( $parsed['postId'] ) : 0,
        'status'        => adfoin_wpforms_sanitize_status( isset( $parsed['entryStatus'] ) ? $parsed['entryStatus'] : '' ),
        'type'          => isset( $parsed['entryType'] ) ? sanitize_text_field( $parsed['entryType'] ) : '',
        'viewed'        => isset( $parsed['viewed'] ) && $parsed['viewed'] !== '' ? absint( $parsed['viewed'] ) : 0,
        'starred'       => isset( $parsed['starred'] ) && $parsed['starred'] !== '' ? absint( $parsed['starred'] ) : 0,
        'notes_count'   => isset( $parsed['notesCount'] ) && $parsed['notesCount'] !== '' ? absint( $parsed['notesCount'] ) : 0,
        'meta'          => isset( $parsed['metaJson'] ) ? wp_kses_post( $parsed['metaJson'] ) : '',
    );

    return $data;
}

function adfoin_wpforms_prepare_entry_update_data( $entry, $parsed, $fields_map ) {
    $update = array();

    if ( isset( $parsed['formId'] ) && $parsed['formId'] !== '' ) {
        $update['form_id'] = absint( $parsed['formId'] );
    }

    if ( ! empty( $fields_map ) ) {
        $current = wpforms_decode( $entry->fields );
        $current = is_array( $current ) ? $current : array();

        foreach ( $fields_map as $field_id => $field_data ) {
            $current_index = adfoin_wpforms_find_field_index( $current, $field_id );

            if ( false === $current_index ) {
                $current[] = $field_data;
            } else {
                $current[ $current_index ] = array_merge( $current[ $current_index ], $field_data );
            }
        }

        $update['fields'] = wp_json_encode( array_values( $current ) );
    }

    if ( isset( $parsed['dateCreated'] ) && $parsed['dateCreated'] !== '' ) {
        $update['date'] = adfoin_wpforms_sanitize_datetime( $parsed, 'dateCreated', $entry->date );
    }

    if ( isset( $parsed['dateModified'] ) && $parsed['dateModified'] !== '' ) {
        $update['date_modified'] = adfoin_wpforms_sanitize_datetime( $parsed, 'dateModified', $entry->date_modified );
    }

    if ( isset( $parsed['userId'] ) && $parsed['userId'] !== '' ) {
        $update['user_id'] = absint( $parsed['userId'] );
    }

    if ( isset( $parsed['postId'] ) && $parsed['postId'] !== '' ) {
        $update['post_id'] = absint( $parsed['postId'] );
    }

    if ( isset( $parsed['entryStatus'] ) && $parsed['entryStatus'] !== '' ) {
        $update['status'] = adfoin_wpforms_sanitize_status( $parsed['entryStatus'] );
    }

    if ( isset( $parsed['entryType'] ) && $parsed['entryType'] !== '' ) {
        $update['type'] = sanitize_text_field( $parsed['entryType'] );
    }

    if ( isset( $parsed['viewed'] ) && $parsed['viewed'] !== '' ) {
        $update['viewed'] = absint( $parsed['viewed'] );
    }

    if ( isset( $parsed['starred'] ) && $parsed['starred'] !== '' ) {
        $update['starred'] = absint( $parsed['starred'] );
    }

    if ( isset( $parsed['notesCount'] ) && $parsed['notesCount'] !== '' ) {
        $update['notes_count'] = absint( $parsed['notesCount'] );
    }

    if ( isset( $parsed['metaJson'] ) && $parsed['metaJson'] !== '' ) {
        $update['meta'] = wp_kses_post( $parsed['metaJson'] );
    }

    if ( isset( $parsed['ipAddress'] ) && $parsed['ipAddress'] !== '' ) {
        $update['ip_address'] = adfoin_wpforms_sanitize_ip( $parsed['ipAddress'] );
    }

    if ( isset( $parsed['userAgent'] ) && $parsed['userAgent'] !== '' ) {
        $update['user_agent'] = adfoin_wpforms_sanitize_user_agent( $parsed['userAgent'] );
    }

    if ( isset( $parsed['userUuid'] ) && $parsed['userUuid'] !== '' ) {
        $update['user_uuid'] = adfoin_wpforms_sanitize_user_uuid( $parsed['userUuid'] );
    }

    return $update;
}

function adfoin_wpforms_parse_field_map( $raw_map, $posted_data ) {
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

    $parsed = array();

    foreach ( $decoded as $field_id => $template ) {
        $value = adfoin_get_parsed_values( $template, $posted_data );
        $parsed[ $field_id ] = $value;
    }

    return $parsed;
}

function adfoin_wpforms_split_field_key( $key ) {
    if ( false !== strpos( $key, '_' ) ) {
        $parts = explode( '_', $key, 2 );

        if ( is_numeric( $parts[0] ) ) {
            return array( $parts[0], $parts[1] );
        }
    }

    if ( false !== strpos( $key, '.' ) ) {
        $parts = explode( '.', $key, 2 );

        if ( is_numeric( $parts[0] ) ) {
            return array( $parts[0], $parts[1] );
        }
    }

    return array( $key, '' );
}

function adfoin_wpforms_sanitize_datetime( $parsed, $key, $fallback ) {
    if ( ! isset( $parsed[ $key ] ) || '' === $parsed[ $key ] ) {
        return $fallback;
    }

    $value = sanitize_text_field( $parsed[ $key ] );

    return $value;
}

function adfoin_wpforms_sanitize_ip( $ip ) {
    $ip = trim( $ip );

    if ( '' === $ip ) {
        return wpforms_get_ip();
    }

    return sanitize_text_field( $ip );
}

function adfoin_wpforms_sanitize_user_agent( $agent ) {
    $agent = trim( $agent );

    if ( '' === $agent && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
    }

    return substr( sanitize_text_field( $agent ), 0, 255 );
}

function adfoin_wpforms_sanitize_user_uuid( $uuid ) {
    return sanitize_text_field( $uuid );
}

function adfoin_wpforms_sanitize_status( $status ) {
    $status  = strtolower( sanitize_text_field( $status ) );
    $allowed = array( '', 'spam', 'trash', 'archived', 'pending' );

    return in_array( $status, $allowed, true ) ? $status : '';
}

function adfoin_wpforms_find_field_index( $fields, $field_id ) {
    foreach ( $fields as $index => $field ) {
        if ( isset( $field['id'] ) && (string) $field['id'] === (string) $field_id ) {
            return $index;
        }
    }

    return false;
}

function adfoin_wpforms_maybe_add_note( $entry_id, $form_id, $note ) {
    $note = trim( wp_kses_post( $note ) );

    if ( '' === $note ) {
        return new WP_Error( 'wpforms_note_empty', __( 'Note content is empty.', 'advanced-form-integration' ) );
    }

    $entry_meta_handler = wpforms()->obj( 'entry_meta' );

    if ( ! $entry_meta_handler ) {
        return new WP_Error( 'wpforms_entry_meta_unavailable', __( 'WPForms entry meta handler unavailable.', 'advanced-form-integration' ) );
    }

    $entry_meta_handler->add(
        array(
            'entry_id' => $entry_id,
            'form_id'  => $form_id,
            'type'     => 'note',
            'user_id'  => get_current_user_id(),
            'data'     => wpautop( $note ),
        ),
        'entry_meta'
    );

    $entry_handler = wpforms()->obj( 'entry' );

    if ( $entry_handler ) {
        $entry = $entry_handler->get( $entry_id, array( 'cap' => '' ) );
        $count = isset( $entry->notes_count ) ? absint( $entry->notes_count ) + 1 : 1;
        $entry_handler->update( $entry_id, array( 'notes_count' => $count ), '', '', array( 'cap' => false ) );
    }

    return true;
}

function adfoin_wpforms_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'wpforms', $log_args, $record );
}

