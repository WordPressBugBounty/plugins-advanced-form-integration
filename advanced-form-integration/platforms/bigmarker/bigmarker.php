<?php

add_filter( 'adfoin_action_providers', 'adfoin_bigmarker_actions', 10, 1 );

function adfoin_bigmarker_actions( $actions ) {
    $actions['bigmarker'] = array(
        'title' => __( 'BigMarker', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_bigmarker_settings_tab', 10, 1 );

function adfoin_bigmarker_settings_tab( $providers ) {
    $providers['bigmarker'] = __( 'BigMarker', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_bigmarker_settings_view', 10, 1 );

function adfoin_bigmarker_settings_view( $current_tab ) {
    if ( 'bigmarker' !== $current_tab ) {
        return;
    }

    $nonce     = wp_create_nonce( 'adfoin_bigmarker_settings' );
    $api_key   = get_option( 'adfoin_bigmarker_api_key', '' );
    $api_secret= get_option( 'adfoin_bigmarker_api_secret', '' );
    ?>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="container">
        <input type="hidden" name="action" value="adfoin_save_bigmarker_keys">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>">

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <ol>
                        <li><?php esc_html_e( 'Log into BigMarker and open Account Settings → Integrations → API Access.', 'advanced-form-integration' ); ?></li>
                        <li><?php esc_html_e( 'Create or copy an API key and API secret with permission to manage registrations.', 'advanced-form-integration' ); ?></li>
                        <li><?php esc_html_e( 'Paste the credentials below and click “Save Changes”.', 'advanced-form-integration' ); ?></li>
                        <li><?php esc_html_e( 'When configuring an action, provide the channel slug and conference slug from the webinar URL.', 'advanced-form-integration' ); ?></li>
                    </ol>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'API Key', 'advanced-form-integration' ); ?></th>
                <td><input type="text" name="adfoin_bigmarker_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'API Secret', 'advanced-form-integration' ); ?></th>
                <td><input type="text" name="adfoin_bigmarker_api_secret" value="<?php echo esc_attr( $api_secret ); ?>" class="regular-text"></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}

add_action( 'admin_post_adfoin_save_bigmarker_keys', 'adfoin_save_bigmarker_keys' );

function adfoin_save_bigmarker_keys() {
    if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ), 'adfoin_bigmarker_settings' ) ) {
        wp_die( esc_html__( 'Security check failed', 'advanced-form-integration' ) );
    }

    $api_key    = isset( $_POST['adfoin_bigmarker_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_bigmarker_api_key'] ) ) : '';
    $api_secret = isset( $_POST['adfoin_bigmarker_api_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_bigmarker_api_secret'] ) ) : '';

    update_option( 'adfoin_bigmarker_api_key', $api_key );
    update_option( 'adfoin_bigmarker_api_secret', $api_secret );

    wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=bigmarker' ) );
    exit;
}

add_action( 'adfoin_action_fields', 'adfoin_bigmarker_action_fields' );

function adfoin_bigmarker_action_fields() {
    ?>
    <script type="text/template" id="bigmarker-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Channel Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[channelSlug]" v-model="fielddata.channelSlug" placeholder="your-channel" />
                    <p class="description"><?php esc_html_e( 'Use the channel slug from the BigMarker URL (e.g. https://www.bigmarker.com/{channel}/{conference}).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr>
                <td scope="row-title">
                    <label><?php esc_html_e( 'Conference Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[conferenceSlug]" v-model="fielddata.conferenceSlug" placeholder="awesome-webinar" />
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Tips', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Map at least Email and Name fields. BigMarker requires an email and name for registrations.', 'advanced-form-integration' ); ?></p>
                    <p><?php esc_html_e( 'Provide JSON for Custom Answers to pass responses for custom registration questions.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_bigmarker_fields', 'adfoin_get_bigmarker_fields' );

function adfoin_get_bigmarker_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email *', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'full_name', 'value' => __( 'Full Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_answers_json', 'value' => __( 'Custom Answers (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: {"q1":"Yes","q2":"No"}', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_bigmarker_job_queue', 'adfoin_bigmarker_job_queue', 10, 1 );

function adfoin_bigmarker_job_queue( $data ) {
    adfoin_bigmarker_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_bigmarker_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data   = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $api_key      = get_option( 'adfoin_bigmarker_api_key', '' );
    $api_secret   = get_option( 'adfoin_bigmarker_api_secret', '' );

    if ( '' === $api_key || '' === $api_secret ) {
        adfoin_add_to_log( new WP_Error( 'bigmarker_missing_credentials', __( 'Add your BigMarker API key and secret in Settings → BigMarker.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $channel_slug    = adfoin_bigmarker_parse_value( $field_data, 'channelSlug', $posted_data );
    $conference_slug = adfoin_bigmarker_parse_value( $field_data, 'conferenceSlug', $posted_data );

    if ( '' === $channel_slug || '' === $conference_slug ) {
        adfoin_add_to_log( new WP_Error( 'bigmarker_missing_slugs', __( 'Channel slug and conference slug are required for BigMarker registration.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $payload = adfoin_bigmarker_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    $endpoint = sprintf(
        'https://www.bigmarker.com/api/v1/conferences/%s/%s/register',
        rawurlencode( $channel_slug ),
        rawurlencode( $conference_slug )
    );

    $response = adfoin_bigmarker_request( $endpoint, $payload, $api_key, $api_secret, $record );

    if ( is_wp_error( $response ) ) {
        adfoin_add_to_log( $response, '', array(), $record );
    }
}

function adfoin_bigmarker_collect_payload( $field_data, $posted_data ) {
    $email = adfoin_bigmarker_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $email ) {
        return new WP_Error( 'bigmarker_missing_email', __( 'BigMarker requires an email address.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'email' => $email,
    );

    $map = array(
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'full_name'  => 'name',
        'company'    => 'company',
        'job_title'  => 'job_title',
        'phone'      => 'phone',
        'country'    => 'country',
        'state'      => 'state',
        'city'       => 'city',
    );

    foreach ( $map as $key => $api_key ) {
        $value = adfoin_bigmarker_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    $custom_answers = adfoin_bigmarker_parse_value( $field_data, 'custom_answers_json', $posted_data );

    if ( '' !== $custom_answers ) {
        $decoded = json_decode( $custom_answers, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'bigmarker_invalid_custom', __( 'Custom answers JSON must be an object.', 'advanced-form-integration' ) );
        }

        $payload['answers'] = $decoded;
    }

    return $payload;
}

function adfoin_bigmarker_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_bigmarker_request( $url, $payload, $api_key, $api_secret, $record = array() ) {
    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status >= 400 ) {
        $body    = wp_remote_retrieve_body( $response );
        $message = $body ? $body : __( 'BigMarker request failed.', 'advanced-form-integration' );

        return new WP_Error( 'bigmarker_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
