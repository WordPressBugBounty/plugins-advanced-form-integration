<?php

add_filter( 'adfoin_action_providers', 'adfoin_wpformsac_actions', 10, 1 );

function adfoin_wpformsac_actions( $actions ) {

    $actions['wpformsac'] = array(
        'title' => __( 'WPForms', 'advanced-form-integration' ),
        'tasks' => array(
            'create_entry' => __( 'Create Entry', 'advanced-form-integration' ),
            'update_entry' => __( 'Update Entry', 'advanced-form-integration' ),
            'add_note'     => __( 'Add Entry Note', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_wpformsac_action_fields' );

function adfoin_wpformsac_action_fields() {
    ?>
    <script type="text/template" id="wpformsac-action-template">
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

add_action( 'wp_ajax_adfoin_get_wpformsac_forms', 'adfoin_get_wpformsac_forms_ajax', 10, 0 );

function adfoin_get_wpformsac_forms_ajax() {
    adfoin_verify_nonce();

    $forms = array();

    if ( function_exists( 'wpforms' ) ) {
        $form_handler = wpforms()->obj( 'form' );

        if ( $form_handler ) {
            $result = $form_handler->get( '', array( 'cap' => false ) );

            if ( is_array( $result ) ) {
                foreach ( $result as $form_post ) {
                    if ( isset( $form_post->ID, $form_post->post_title ) ) {
                        $forms[ (string) $form_post->ID ] = $form_post->post_title;
                    }
                }
            }
        }
    }

    // Fallback to direct post lookup if WPForms helper isn't available — keeps
    // the action usable on non-standard WPForms builds (lite, custom forks).
    if ( empty( $forms ) ) {
        $posts = get_posts( array( 'post_type' => 'wpforms', 'posts_per_page' => -1 ) );
        foreach ( $posts as $form_post ) {
            $forms[ (string) $form_post->ID ] = $form_post->post_title;
        }
    }

    wp_send_json_success( $forms );
}

add_action( 'wp_ajax_adfoin_get_wpformsac_fields', 'adfoin_get_wpformsac_fields_ajax', 10, 0 );

function adfoin_get_wpformsac_fields_ajax() {
    adfoin_verify_nonce();

    $task    = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';
    $form_id = isset( $_POST['formId'] ) ? sanitize_text_field( wp_unslash( $_POST['formId'] ) ) : '';
    $fields  = array();

    // Common metadata fields shared across update_entry / add_note. Required
    // for those tasks because the Entry ID is the only way to locate the row.
    if ( 'update_entry' === $task || 'add_note' === $task ) {
        $fields[] = array( 'key' => 'entryId', 'value' => 'Entry ID', 'description' => 'Existing entry ID to update or annotate.', 'required' => true );
    }

    if ( 'create_entry' === $task || 'update_entry' === $task || 'add_note' === $task ) {
        $fields[] = array( 'key' => 'entryNote', 'value' => 'Entry Note', 'type' => 'textarea', 'description' => 'Optional note saved to the entry timeline.' );
    }

    if ( 'create_entry' === $task || 'update_entry' === $task ) {
        $fields[] = array( 'key' => 'entryStatus',  'value' => 'Entry Status',          'description' => 'Allowed: empty, spam, trash, archived, pending.' );
        $fields[] = array( 'key' => 'entryType',    'value' => 'Entry Type' );
        $fields[] = array( 'key' => 'postId',       'value' => 'Post ID' );
        $fields[] = array( 'key' => 'userId',       'value' => 'User ID',               'description' => 'Defaults to the current user when blank.' );
        $fields[] = array( 'key' => 'ipAddress',    'value' => 'IP Address',            'description' => 'Defaults to the visitor IP when blank.' );
        $fields[] = array( 'key' => 'userAgent',    'value' => 'User Agent',            'description' => 'Defaults to the request user agent when blank.' );
    }

    // Form-field placeholders, mapped one-to-one with WPForms field IDs. Name,
    // address and date-time fields explode into per-subfield keys (e.g. 1_first,
    // 2_address1, 3_date) so the user can map each subfield independently.
    if ( ( 'create_entry' === $task || 'update_entry' === $task ) && $form_id ) {
        $form_post = get_post( $form_id );

        if ( $form_post && isset( $form_post->post_content ) ) {
            $form_data = function_exists( 'wpforms_decode' )
                ? wpforms_decode( $form_post->post_content )
                : json_decode( $form_post->post_content, true );

            if ( is_array( $form_data ) && ! empty( $form_data['fields'] ) ) {
                $skip_types = array( 'html', 'divider', 'pagebreak', 'content', 'captcha', 'internal-information' );

                foreach ( $form_data['fields'] as $field ) {
                    if ( ! is_array( $field ) || ! isset( $field['id'], $field['type'] ) ) {
                        continue;
                    }

                    if ( in_array( $field['type'], $skip_types, true ) ) {
                        continue;
                    }

                    $field_id    = (string) $field['id'];
                    $field_label = isset( $field['label'] ) && '' !== $field['label']
                        ? $field['label']
                        : sprintf( /* translators: %s: WPForms field id */ __( 'Field %s', 'advanced-form-integration' ), $field_id );

                    switch ( $field['type'] ) {
                        case 'name':
                            $format = isset( $field['format'] ) ? $field['format'] : 'first-last';

                            if ( 'simple' === $format ) {
                                $fields[] = array( 'key' => $field_id, 'value' => $field_label, 'description' => 'Field ID ' . $field_id );
                                break;
                            }

                            $fields[] = array( 'key' => $field_id . '_first', 'value' => $field_label . ' — First', 'description' => 'Field ID ' . $field_id . '.first' );

                            if ( 'first-middle-last' === $format ) {
                                $fields[] = array( 'key' => $field_id . '_middle', 'value' => $field_label . ' — Middle', 'description' => 'Field ID ' . $field_id . '.middle' );
                            }

                            $fields[] = array( 'key' => $field_id . '_last', 'value' => $field_label . ' — Last', 'description' => 'Field ID ' . $field_id . '.last' );
                            break;

                        case 'address':
                            $fields[] = array( 'key' => $field_id . '_address1', 'value' => $field_label . ' — Address Line 1', 'description' => 'Field ID ' . $field_id . '.address1' );
                            $fields[] = array( 'key' => $field_id . '_address2', 'value' => $field_label . ' — Address Line 2', 'description' => 'Field ID ' . $field_id . '.address2' );
                            $fields[] = array( 'key' => $field_id . '_city',     'value' => $field_label . ' — City',           'description' => 'Field ID ' . $field_id . '.city' );
                            $fields[] = array( 'key' => $field_id . '_state',    'value' => $field_label . ' — State',          'description' => 'Field ID ' . $field_id . '.state' );
                            $fields[] = array( 'key' => $field_id . '_postal',   'value' => $field_label . ' — Postal Code',    'description' => 'Field ID ' . $field_id . '.postal' );
                            $fields[] = array( 'key' => $field_id . '_country',  'value' => $field_label . ' — Country',        'description' => 'Field ID ' . $field_id . '.country' );
                            break;

                        case 'date-time':
                            $date_format = isset( $field['date_format'] ) ? $field['date_format'] : '';
                            $time_format = isset( $field['time_format'] ) ? $field['time_format'] : '';
                            $sub_format  = isset( $field['format'] ) ? $field['format'] : 'date-time';

                            if ( 'date' === $sub_format || 'date-time' === $sub_format ) {
                                $fields[] = array( 'key' => $field_id . '_date', 'value' => $field_label . ' — Date', 'description' => 'Field ID ' . $field_id . '.date' . ( $date_format ? ' (' . $date_format . ')' : '' ) );
                            }

                            if ( 'time' === $sub_format || 'date-time' === $sub_format ) {
                                $fields[] = array( 'key' => $field_id . '_time', 'value' => $field_label . ' — Time', 'description' => 'Field ID ' . $field_id . '.time' . ( $time_format ? ' (' . $time_format . ')' : '' ) );
                            }
                            break;

                        case 'textarea':
                            $fields[] = array( 'key' => $field_id, 'value' => $field_label, 'type' => 'textarea', 'description' => 'Field ID ' . $field_id );
                            break;

                        case 'checkbox':
                        case 'payment-checkbox':
                            $fields[] = array(
                                'key'         => $field_id,
                                'value'       => $field_label,
                                'description' => 'Field ID ' . $field_id . ' — separate multiple choices with a newline or comma.',
                            );
                            break;

                        default:
                            $fields[] = array(
                                'key'         => $field_id,
                                'value'       => $field_label,
                                'description' => 'Field ID ' . $field_id,
                            );
                            break;
                    }
                }
            }
        }
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_wpformsac_job_queue', 'adfoin_wpformsac_job_queue', 10, 1 );

function adfoin_wpformsac_job_queue( $data ) {
    adfoin_wpformsac_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_wpformsac_send_data( $record, $posted_data ) {
    if ( ! function_exists( 'wpforms' ) ) {
        adfoin_wpformsac_action_log( $record, __( 'WPForms is not active.', 'advanced-form-integration' ), array(), false );
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

    // Reserved metadata keys are routed via $parsed (used by
    // prepare_entry_insert_data / prepare_entry_update_data) and must NOT be
    // forwarded to WPForms as form-field values. Anything else under $parsed
    // is treated as a WPForms field id (or composite id like "1_first").
    $reserved_keys = array(
        'formId', 'entryId', 'entryNote', 'entryStatus', 'entryType',
        'postId', 'userId', 'viewed', 'starred', 'notesCount', 'metaJson',
        'dateCreated', 'dateModified', 'ipAddress', 'userAgent', 'userUuid',
    );

    $field_map = array();
    foreach ( $parsed as $field_key => $value ) {
        if ( in_array( $field_key, $reserved_keys, true ) ) {
            continue;
        }
        $field_map[ $field_key ] = $value;
    }

    // Backward compat: a previously-saved integration may still carry the
    // legacy JSON `fieldMap` blob from the old field-mapping table. Merge
    // its parsed values into $field_map so build_fields keeps seeing them.
    if ( ! empty( $field_data['fieldMap'] ) ) {
        $legacy_map = adfoin_wpformsac_parse_field_map( $field_data['fieldMap'], $posted_data );
        foreach ( $legacy_map as $field_id => $value ) {
            if ( ! isset( $field_map[ $field_id ] ) || '' === $field_map[ $field_id ] ) {
                $field_map[ $field_id ] = $value;
            }
        }
    }

    if ( 'create_entry' === $task ) {
        adfoin_wpformsac_action_create_entry( $record, $parsed, $field_map );
    } elseif ( 'update_entry' === $task ) {
        adfoin_wpformsac_action_update_entry( $record, $parsed, $field_map );
    } elseif ( 'add_note' === $task ) {
        adfoin_wpformsac_action_add_note( $record, $parsed );
    } else {
        adfoin_wpformsac_action_log( $record, __( 'Unsupported WPForms task.', 'advanced-form-integration' ), array( 'task' => $task ), false );
    }
}

function adfoin_wpformsac_action_create_entry( $record, $parsed, $field_map ) {
    $form_id = isset( $parsed['formId'] ) ? absint( $parsed['formId'] ) : 0;

    if ( ! $form_id ) {
        adfoin_wpformsac_action_log( $record, __( 'A WPForm must be selected.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $form_payload = adfoin_wpformsac_get_form_data( $form_id );

    if ( is_wp_error( $form_payload ) ) {
        adfoin_wpformsac_action_log( $record, $form_payload->get_error_message(), array( 'form_id' => $form_id ), false );
        return;
    }

    $fields_payload = adfoin_wpformsac_build_fields( $form_payload['data'], $field_map );
    $entry_fields   = $fields_payload['list'];
    $fields_map     = $fields_payload['map'];

    $entry_data = adfoin_wpformsac_prepare_entry_insert_data( $form_id, $parsed, $fields_map );

    $entry_handler = wpforms()->obj( 'entry' );

    if ( ! $entry_handler ) {
        adfoin_wpformsac_action_log( $record, __( 'Entry handler not available in WPForms.', 'advanced-form-integration' ), array(), false );
        return;
    }

    global $wpdb;
    $wpdb->last_error = '';

    $entry_id = $entry_handler->add( $entry_data );

    if ( empty( $entry_id ) ) {
        // Surface what we *can* see when WPForms' Entry::add() returns 0/false.
        // Common causes (in order of likelihood):
        //   1. WPForms Lite is active — the free version stubs out entry
        //      storage; the Pro entry handler is required to persist rows.
        //   2. The form has "Disable storing entry information" enabled in
        //      WPForms → Settings → General or per-form settings.
        //   3. A third-party plugin is hooked into wpforms_entry_handler_add*
        //      filters and short-circuiting the insert.
        //   4. The DB insert itself failed (column type mismatch, length
        //      overflow on user_agent, etc.) — $wpdb->last_error captures it.
        // wpforms() returns the WPForms_Core main plugin object; is_pro() is
        // the canonical way to distinguish Pro from Lite. Older versions
        // expose a bare wpforms_is_pro() function as a fallback.
        if ( method_exists( wpforms(), 'is_pro' ) ) {
            $is_pro = (bool) wpforms()->is_pro();
        } elseif ( function_exists( 'wpforms_is_pro' ) ) {
            $is_pro = (bool) wpforms_is_pro();
        } else {
            $is_pro = null;
        }

        $debug = array(
            'form_id'        => $form_id,
            'entry_handler'  => is_object( $entry_handler ) ? get_class( $entry_handler ) : gettype( $entry_handler ),
            'is_pro'         => null === $is_pro ? 'unknown' : ( $is_pro ? 'yes' : 'no' ),
            'wpdb_last_err'  => $wpdb->last_error,
            'entry_data'     => $entry_data,
        );

        $message = __( 'Failed to create WPForms entry.', 'advanced-form-integration' );

        if ( false === $is_pro ) {
            $message .= ' ' . __( 'WPForms Lite does not support entry storage — install WPForms Pro to use this action.', 'advanced-form-integration' );
        } elseif ( '' !== $wpdb->last_error ) {
            $message .= ' DB: ' . $wpdb->last_error;
        }

        adfoin_wpformsac_action_log( $record, $message, $debug, false );
        return;
    }

    if ( ! empty( $entry_fields ) ) {
        $entry_fields_handler = wpforms()->obj( 'entry_fields' );

        if ( $entry_fields_handler ) {
            $entry_fields_handler->save( $entry_fields, $form_payload['data'], $entry_id );
        }
    }

    if ( ! empty( $parsed['entryNote'] ) ) {
        adfoin_wpformsac_maybe_add_note( $entry_id, $form_id, $parsed['entryNote'] );
    }

    adfoin_wpformsac_action_log(
        $record,
        __( 'WPForms entry created successfully.', 'advanced-form-integration' ),
        array(
            'form_id'  => $form_id,
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_wpformsac_action_update_entry( $record, $parsed, $field_map ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;

    if ( ! $entry_id ) {
        adfoin_wpformsac_action_log( $record, __( 'Entry ID is required for updates.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $entry_handler = wpforms()->obj( 'entry' );

    if ( ! $entry_handler ) {
        adfoin_wpformsac_action_log( $record, __( 'Entry handler not available in WPForms.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $entry = $entry_handler->get( $entry_id, array( 'cap' => '' ) );

    if ( empty( $entry ) ) {
        adfoin_wpformsac_action_log( $record, __( 'WPForms entry not found.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $form_id = isset( $parsed['formId'] ) && $parsed['formId'] !== '' ? absint( $parsed['formId'] ) : absint( $entry->form_id );

    $form_payload = adfoin_wpformsac_get_form_data( $form_id );

    if ( is_wp_error( $form_payload ) ) {
        adfoin_wpformsac_action_log( $record, $form_payload->get_error_message(), array( 'form_id' => $form_id ), false );
        return;
    }

    $fields_payload = adfoin_wpformsac_build_fields( $form_payload['data'], $field_map );
    $fields_map     = $fields_payload['map'];

    $entry_update = adfoin_wpformsac_prepare_entry_update_data( $entry, $parsed, $fields_map );

    if ( ! empty( $entry_update ) ) {
        $updated = $entry_handler->update( $entry_id, $entry_update, '', '', array( 'cap' => false ) );

        if ( is_wp_error( $updated ) || false === $updated ) {
            adfoin_wpformsac_action_log( $record, __( 'Failed to update WPForms entry.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id, 'payload' => $entry_update ), false );
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
        adfoin_wpformsac_maybe_add_note( $entry_id, $form_id, $parsed['entryNote'] );
    }

    adfoin_wpformsac_action_log(
        $record,
        __( 'WPForms entry updated successfully.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
            'form_id'  => $form_id,
        ),
        true
    );
}

function adfoin_wpformsac_action_add_note( $record, $parsed ) {
    $entry_id = isset( $parsed['entryId'] ) ? absint( $parsed['entryId'] ) : 0;
    $note     = isset( $parsed['entryNote'] ) ? $parsed['entryNote'] : '';

    if ( ! $entry_id ) {
        adfoin_wpformsac_action_log( $record, __( 'Entry ID is required to add a note.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    if ( '' === trim( $note ) ) {
        adfoin_wpformsac_action_log( $record, __( 'Note content is empty, nothing to add.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $entry_handler = wpforms()->obj( 'entry' );
    $entry         = $entry_handler ? $entry_handler->get( $entry_id, array( 'cap' => '' ) ) : null;

    if ( empty( $entry ) ) {
        adfoin_wpformsac_action_log( $record, __( 'WPForms entry not found.', 'advanced-form-integration' ), array( 'entry_id' => $entry_id ), false );
        return;
    }

    $note_result = adfoin_wpformsac_maybe_add_note( $entry_id, absint( $entry->form_id ), $note );

    if ( is_wp_error( $note_result ) ) {
        adfoin_wpformsac_action_log( $record, $note_result->get_error_message(), array( 'entry_id' => $entry_id ), false );
        return;
    }

    adfoin_wpformsac_action_log(
        $record,
        __( 'Note added to WPForms entry.', 'advanced-form-integration' ),
        array(
            'entry_id' => $entry_id,
        ),
        true
    );
}

function adfoin_wpformsac_get_form_data( $form_id ) {
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

function adfoin_wpformsac_build_fields( $form_data, $field_map ) {
    $form_fields = isset( $form_data['fields'] ) && is_array( $form_data['fields'] ) ? $form_data['fields'] : array();
    $grouped     = array();

    foreach ( $field_map as $field_key => $value ) {
        if ( '' === $value || null === $value ) {
            continue;
        }

        list( $base, $sub ) = adfoin_wpformsac_split_field_key( $field_key );

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

        $formatted = adfoin_wpformsac_format_field_value( $field_type, $parts );

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

function adfoin_wpformsac_format_field_value( $field_type, $parts ) {
    $field_type = sanitize_key( $field_type );
    $extras     = array();

    switch ( $field_type ) {
        case 'name':
            $first  = isset( $parts['first'] ) ? adfoin_wpformsac_clean_value( $parts['first'] ) : '';
            $middle = isset( $parts['middle'] ) ? adfoin_wpformsac_clean_value( $parts['middle'] ) : '';
            $last   = isset( $parts['last'] ) ? adfoin_wpformsac_clean_value( $parts['last'] ) : '';

            $extras['first']  = $first;
            $extras['middle'] = $middle;
            $extras['last']   = $last;

            $value = trim( implode( ' ', array_filter( array( $first, $middle, $last ) ) ) );
            break;

        case 'address':
            $address1 = isset( $parts['address1'] ) ? adfoin_wpformsac_clean_value( $parts['address1'] ) : '';
            $address2 = isset( $parts['address2'] ) ? adfoin_wpformsac_clean_value( $parts['address2'] ) : '';
            $city     = isset( $parts['city'] ) ? adfoin_wpformsac_clean_value( $parts['city'] ) : '';
            $state    = isset( $parts['state'] ) ? adfoin_wpformsac_clean_value( $parts['state'] ) : '';
            $postal   = isset( $parts['postal'] ) ? adfoin_wpformsac_clean_value( $parts['postal'] ) : '';
            $country  = isset( $parts['country'] ) ? adfoin_wpformsac_clean_value( $parts['country'] ) : '';

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
            $date = isset( $parts['date'] ) ? adfoin_wpformsac_clean_value( $parts['date'] ) : '';
            $time = isset( $parts['time'] ) ? adfoin_wpformsac_clean_value( $parts['time'] ) : '';

            $extras['date'] = $date;
            $extras['time'] = $time;

            $value = trim( $date . ' ' . $time );
            break;

        case 'checkbox':
        case 'payment-checkbox':
            $values      = array_map( 'adfoin_wpformsac_clean_value', array_values( $parts ) );
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
            $value = adfoin_wpformsac_clean_value( reset( $parts ) );
            break;
    }

    return array(
        'value'  => $value,
        'extras' => $extras,
    );
}

function adfoin_wpformsac_clean_value( $value ) {
    $value = is_scalar( $value ) ? $value : '';

    return wp_kses_post( $value );
}

function adfoin_wpformsac_prepare_entry_insert_data( $form_id, $parsed, $fields_map ) {
    $current_time = current_time( 'mysql' );

    // WPForms reads the fields column as either an associative array keyed by
    // field id (preferred — matches the shape WPForms writes during a real
    // submission) or as a JSON-encoded equivalent. `array_values()` would
    // throw the keys away, which makes the entry table show but breaks the
    // edit-entry / single-entry views that look fields up by id.
    $fields_json = ! empty( $fields_map ) ? wp_json_encode( $fields_map ) : wp_json_encode( new stdClass() );

    $user_id = isset( $parsed['userId'] ) && $parsed['userId'] !== ''
        ? absint( $parsed['userId'] )
        : ( get_current_user_id() ?: 0 );

    // user_uuid is required by WPForms' schema; generate a real UUID if the
    // user didn't override it. Falling back to '' would still insert (varchar
    // accepts it) but breaks WPForms' entry deduplication and any downstream
    // tooling that keys on user_uuid.
    $user_uuid = adfoin_wpformsac_sanitize_user_uuid( isset( $parsed['userUuid'] ) ? $parsed['userUuid'] : '' );
    if ( '' === $user_uuid && function_exists( 'wpforms_generate_uuid' ) ) {
        $user_uuid = wpforms_generate_uuid();
    }

    $ip_address = adfoin_wpformsac_sanitize_ip( isset( $parsed['ipAddress'] ) ? $parsed['ipAddress'] : '' );
    $user_agent = adfoin_wpformsac_sanitize_user_agent( isset( $parsed['userAgent'] ) ? $parsed['userAgent'] : '' );

    $data = array(
        'form_id'       => $form_id,
        'fields'        => $fields_json,
        'user_id'       => $user_id,
        'date'          => adfoin_wpformsac_sanitize_datetime( $parsed, 'dateCreated', $current_time ),
        'date_modified' => adfoin_wpformsac_sanitize_datetime( $parsed, 'dateModified', $current_time ),
        'ip_address'    => $ip_address,
        'user_agent'    => $user_agent,
        'user_uuid'     => $user_uuid,
        'post_id'       => isset( $parsed['postId'] ) && $parsed['postId'] !== '' ? absint( $parsed['postId'] ) : 0,
        // Default WPForms entry status is 'publish' (not empty). An empty
        // string passes the insert but hides the entry in the default
        // "Published" filter on the entries list.
        'status'        => adfoin_wpformsac_sanitize_status( isset( $parsed['entryStatus'] ) ? $parsed['entryStatus'] : '' ) ?: 'publish',
        'type'          => isset( $parsed['entryType'] ) ? sanitize_text_field( $parsed['entryType'] ) : '',
        'viewed'        => isset( $parsed['viewed'] ) && $parsed['viewed'] !== '' ? absint( $parsed['viewed'] ) : 0,
        'starred'       => isset( $parsed['starred'] ) && $parsed['starred'] !== '' ? absint( $parsed['starred'] ) : 0,
        'meta'          => isset( $parsed['metaJson'] ) ? wp_kses_post( $parsed['metaJson'] ) : '',
    );

    return $data;
}

function adfoin_wpformsac_prepare_entry_update_data( $entry, $parsed, $fields_map ) {
    $update = array();

    if ( isset( $parsed['formId'] ) && $parsed['formId'] !== '' ) {
        $update['form_id'] = absint( $parsed['formId'] );
    }

    if ( ! empty( $fields_map ) ) {
        $current = wpforms_decode( $entry->fields );
        $current = is_array( $current ) ? $current : array();

        foreach ( $fields_map as $field_id => $field_data ) {
            // Prefer an associative merge keyed by field id (the shape WPForms
            // writes during a real submission). Fall back to the legacy
            // index-based lookup when an existing entry is still indexed.
            if ( isset( $current[ $field_id ] ) && is_array( $current[ $field_id ] ) ) {
                $current[ $field_id ] = array_merge( $current[ $field_id ], $field_data );
                continue;
            }

            $current_index = adfoin_wpformsac_find_field_index( $current, $field_id );

            if ( false === $current_index ) {
                $current[ $field_id ] = $field_data;
            } else {
                $current[ $current_index ] = array_merge( $current[ $current_index ], $field_data );
            }
        }

        $update['fields'] = wp_json_encode( $current );
    }

    if ( isset( $parsed['dateCreated'] ) && $parsed['dateCreated'] !== '' ) {
        $update['date'] = adfoin_wpformsac_sanitize_datetime( $parsed, 'dateCreated', $entry->date );
    }

    if ( isset( $parsed['dateModified'] ) && $parsed['dateModified'] !== '' ) {
        $update['date_modified'] = adfoin_wpformsac_sanitize_datetime( $parsed, 'dateModified', $entry->date_modified );
    }

    if ( isset( $parsed['userId'] ) && $parsed['userId'] !== '' ) {
        $update['user_id'] = absint( $parsed['userId'] );
    }

    if ( isset( $parsed['postId'] ) && $parsed['postId'] !== '' ) {
        $update['post_id'] = absint( $parsed['postId'] );
    }

    if ( isset( $parsed['entryStatus'] ) && $parsed['entryStatus'] !== '' ) {
        $update['status'] = adfoin_wpformsac_sanitize_status( $parsed['entryStatus'] );
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
        $update['ip_address'] = adfoin_wpformsac_sanitize_ip( $parsed['ipAddress'] );
    }

    if ( isset( $parsed['userAgent'] ) && $parsed['userAgent'] !== '' ) {
        $update['user_agent'] = adfoin_wpformsac_sanitize_user_agent( $parsed['userAgent'] );
    }

    if ( isset( $parsed['userUuid'] ) && $parsed['userUuid'] !== '' ) {
        $update['user_uuid'] = adfoin_wpformsac_sanitize_user_uuid( $parsed['userUuid'] );
    }

    return $update;
}

function adfoin_wpformsac_parse_field_map( $raw_map, $posted_data ) {
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

function adfoin_wpformsac_split_field_key( $key ) {
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

function adfoin_wpformsac_sanitize_datetime( $parsed, $key, $fallback ) {
    if ( ! isset( $parsed[ $key ] ) || '' === $parsed[ $key ] ) {
        return $fallback;
    }

    $value = sanitize_text_field( $parsed[ $key ] );

    return $value;
}

function adfoin_wpformsac_sanitize_ip( $ip ) {
    $ip = trim( $ip );

    if ( '' === $ip ) {
        return wpforms_get_ip();
    }

    return sanitize_text_field( $ip );
}

function adfoin_wpformsac_sanitize_user_agent( $agent ) {
    $agent = trim( $agent );

    if ( '' === $agent && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
    }

    return substr( sanitize_text_field( $agent ), 0, 255 );
}

function adfoin_wpformsac_sanitize_user_uuid( $uuid ) {
    return sanitize_text_field( $uuid );
}

function adfoin_wpformsac_sanitize_status( $status ) {
    $status  = strtolower( sanitize_text_field( $status ) );
    // 'publish' is the default status WPForms writes during a real submission;
    // 'completed' is set on payment/partial flows in newer WPForms versions.
    $allowed = array( '', 'publish', 'completed', 'spam', 'trash', 'archived', 'pending', 'partial' );

    return in_array( $status, $allowed, true ) ? $status : '';
}

function adfoin_wpformsac_find_field_index( $fields, $field_id ) {
    foreach ( $fields as $index => $field ) {
        if ( isset( $field['id'] ) && (string) $field['id'] === (string) $field_id ) {
            return $index;
        }
    }

    return false;
}

function adfoin_wpformsac_maybe_add_note( $entry_id, $form_id, $note ) {
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

function adfoin_wpformsac_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'wpformsac', $log_args, $record );
}

