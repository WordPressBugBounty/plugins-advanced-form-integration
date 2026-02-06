<?php

add_filter( 'adfoin_action_providers', 'adfoin_addcal_actions', 10, 1 );

/**
 * Register AddCal provider and task.
 *
 * @param array $actions Existing actions.
 *
 * @return array
 */
function adfoin_addcal_actions( $actions ) {
    $actions['addcal'] = array(
        'title' => __( 'AddCal', 'advanced-form-integration' ),
        'tasks' => array(
            'create_event' => __( 'Create Event', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_addcal_settings_tab', 10, 1 );

/**
 * Register AddCal settings tab.
 *
 * @param array $tabs Existing tabs.
 *
 * @return array
 */
function adfoin_addcal_settings_tab( $tabs ) {
    $tabs['addcal'] = __( 'AddCal', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_addcal_settings_view', 10, 1 );

/**
 * Render AddCal settings UI.
 *
 * @param string $current_tab Current tab slug.
 */
function adfoin_addcal_settings_view( $current_tab ) {
    if ( 'addcal' !== $current_tab ) {
        return;
    }

    $title = __( 'AddCal', 'advanced-form-integration' );
    $key   = 'addcal';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'    => 'apiToken',
                    'label'  => __( 'API Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        '<p>%s</p><ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Generate an API token inside your AddCal dashboard and paste it below.', 'advanced-form-integration' ),
        esc_html__( 'Open the AddCal dashboard and create an API token from the user menu → “API token”.', 'advanced-form-integration' ),
        esc_html__( 'Copy the token into the API Token field and click “Add”.', 'advanced-form-integration' ),
        sprintf(
            /* translators: 1: opening anchor tag, 2: closing anchor tag. */
            esc_html__( 'See the %1$sAddCal API docs%2$s for field descriptions and examples.', 'advanced-form-integration' ),
            '<a href="https://addcal.co/docs" target="_blank" rel="noopener noreferrer">',
            '</a>'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_addcal_credentials', 'adfoin_get_addcal_credentials', 10, 0 );

/**
 * AJAX: return stored credentials.
 */
function adfoin_get_addcal_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'addcal' ) );
}

add_action( 'wp_ajax_adfoin_save_addcal_credentials', 'adfoin_save_addcal_credentials', 10, 0 );

/**
 * AJAX: save credentials payload.
 */
function adfoin_save_addcal_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'addcal' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

/**
 * Print credential dropdown options.
 */
function adfoin_addcal_credentials_list() {
    $credentials = adfoin_read_credentials( 'addcal' );

    foreach ( $credentials as $credential ) {
        printf(
            '<option value="%1$s">%2$s</option>',
            esc_attr( $credential['id'] ),
            esc_html( $credential['title'] )
        );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_addcal_action_fields', 10, 1 );

/**
 * Output the AddCal action template.
 */
function adfoin_addcal_action_fields() {
    ?>
    <script type="text/template" id="addcal-action-template">
        <table class="form-table" v-if="action.task == 'create_event'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Map at least Title, Start, and End (ISO 8601). Either pick an existing calendar by UID or provide a calendar name to auto-create/reuse one. Start/end times inherit the calendar timezone unless a timezone is supplied.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'AddCal Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select account…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_addcal_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Include HTML Snippets', 'advanced-form-integration' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" v-model="fielddata.withHtml">
                        <?php esc_html_e( 'Append ?with_html=true to fetch HTML button templates in the response.', 'advanced-form-integration' ); ?>
                    </label>
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
    </script>
    <?php
}

add_action( 'adfoin_job_queue', 'adfoin_addcal_job_queue', 10, 1 );

/**
 * Queue callback for AddCal jobs.
 *
 * @param array $data Job data.
 */
function adfoin_addcal_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'addcal' || ( $data['task'] ?? '' ) !== 'create_event' ) {
        return;
    }

    adfoin_addcal_send_data( $data['record'], $data['posted_data'] );
}

/**
 * Dispatch AddCal API call.
 *
 * @param array $record      Integration record.
 * @param array $posted_data Trigger payload.
 */
function adfoin_addcal_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $payload = adfoin_addcal_prepare_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, 'addcal', array(), $record );
        return;
    }

    $query = array();

    if ( isset( $field_data['withHtml'] ) && adfoin_addcal_boolean_value( $field_data['withHtml'] ) ) {
        $query['with_html'] = 'true';
    }

    adfoin_addcal_request( 'api/events', 'POST', $payload, $cred_id, $record, $query );
}

/**
 * Prepare payload for AddCal.
 *
 * @param array $field_data Saved mapping config.
 * @param array $posted_data Trigger data.
 *
 * @return array|WP_Error
 */
function adfoin_addcal_prepare_payload( $field_data, $posted_data ) {
    $map = array(
        'title'          => 'title',
        'description'    => 'description',
        'location'       => 'location',
        'is_all_day'     => 'is_all_day',
        'recurrence_rule'=> 'recurrence_rule',
        'has_rsvp'       => 'has_rsvp',
        'rsvp_limit'     => 'rsvp_limit',
        'date_start'     => 'date_start',
        'date_end'       => 'date_end',
        'timezone'       => 'timezone',
        'busy_type'      => 'busy_type',
        'reminder_before'=> 'reminder_before',
        'short_link'     => 'short_link',
        'team_uid'       => 'team_uid',
        'calendar_uid'   => 'calendar_uid',
        'calendar_name'  => 'calendar_name',
        'image_url'      => 'image_url',
        'location_url'   => 'location_url',
        'internal_name'  => 'internal_name',
        'is_draft'       => 'is_draft',
    );

    $payload = array();

    foreach ( $map as $field_key => $api_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        switch ( $field_key ) {
            case 'is_all_day':
            case 'has_rsvp':
            case 'is_draft':
                $payload[ $api_key ] = adfoin_addcal_boolean_value( $value );
                break;
            case 'reminder_before':
                $payload[ $api_key ] = max( 0, (int) $value );
                break;
            case 'rsvp_limit':
                $payload[ $api_key ] = (int) $value;
                break;
            default:
                $payload[ $api_key ] = $value;
                break;
        }
    }

    $required = array( 'title', 'date_start', 'date_end' );

    foreach ( $required as $field ) {
        $api_key = $map[ $field ];

        if ( empty( $payload[ $api_key ] ) ) {
            return new WP_Error(
                'adfoin_addcal_missing_field',
                sprintf(
                    /* translators: %s field name. */
                    __( 'The %s field is required for AddCal events.', 'advanced-form-integration' ),
                    $api_key
                )
            );
        }
    }

    return $payload;
}

/**
 * Perform HTTP request against AddCal.
 *
 * @param string $endpoint Endpoint path.
 * @param string $method   HTTP method.
 * @param array  $data     Payload.
 * @param string $cred_id  Credential ID.
 * @param array  $record   Record used for logging.
 * @param array  $query    Query params.
 *
 * @return array|WP_Error
 */
function adfoin_addcal_request( $endpoint, $method = 'POST', $data = array(), $cred_id = '', $record = array(), $query = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'addcal', $cred_id );
    $api_token   = isset( $credentials['apiToken'] ) ? trim( $credentials['apiToken'] ) : '';

    if ( empty( $api_token ) ) {
        return new WP_Error( 'adfoin_addcal_missing_token', __( 'AddCal API token is missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://addcal.co/' . ltrim( $endpoint, '/' );

    if ( ! empty( $query ) ) {
        $url = add_query_arg( $query, $url );
    }

    $args = array(
        'method'  => strtoupper( $method ),
        'timeout' => 20,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );

    if ( $code >= 400 ) {
        $body    = wp_remote_retrieve_body( $response );
        $message = '';

        if ( $body ) {
            $decoded = json_decode( $body, true );

            if ( isset( $decoded['message'] ) ) {
                $message = $decoded['message'];
            } elseif ( isset( $decoded['error'] ) ) {
                $message = $decoded['error'];
            } else {
                $message = wp_strip_all_tags( $body );
            }
        }

        return new WP_Error( 'adfoin_addcal_http_error', $message ? $message : __( 'AddCal API request failed.', 'advanced-form-integration' ) );
    }

    return $response;
}

/**
 * Normalize boolean-ish values.
 *
 * @param mixed $value Raw value.
 *
 * @return bool
 */
function adfoin_addcal_boolean_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}
