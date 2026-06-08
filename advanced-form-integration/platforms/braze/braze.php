<?php

add_filter( 'adfoin_action_providers', 'adfoin_braze_actions', 10, 1 );

function adfoin_braze_actions( $actions ) {

    $actions['braze'] = array(
        'title' => __( 'Braze', 'advanced-form-integration' ),
        'tasks' => array(
            'add_user' => __( 'Create/Update User', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_braze_settings_tab', 10, 1 );

function adfoin_braze_settings_tab( $providers ) {
    $providers['braze'] = __( 'Braze', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_braze_settings_view', 10, 1 );

function adfoin_braze_settings_view( $current_tab ) {
    if ( 'braze' !== $current_tab ) {
        return;
    }

    $nonce      = wp_create_nonce( 'adfoin_braze_settings' );
    $rest_key   = get_option( 'adfoin_braze_rest_api_key', '' );
    $rest_host  = get_option( 'adfoin_braze_rest_endpoint', '' );
    ?>

    <form name="braze_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_braze_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'REST API Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_braze_rest_api_key"
                           value="<?php echo esc_attr( $rest_key ); ?>" placeholder="<?php esc_attr_e( 'Enter REST API Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'REST Endpoint', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_braze_rest_endpoint"
                           value="<?php echo esc_attr( $rest_host ); ?>" placeholder="<?php esc_attr_e( 'https://rest.iad-01.braze.com', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php esc_html_e( 'Use your Braze REST endpoint (e.g. https://rest.iad-01.braze.com).', 'advanced-form-integration' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_braze_credentials', 'adfoin_save_braze_credentials', 10, 0 );

function adfoin_save_braze_credentials() {
    // Authorization check
    adfoin_require_manage_options();

    if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_braze_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $rest_key  = isset( $_POST['adfoin_braze_rest_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_braze_rest_api_key'] ) ) : '';
    $rest_host = isset( $_POST['adfoin_braze_rest_endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['adfoin_braze_rest_endpoint'] ) ) : '';

    update_option( 'adfoin_braze_rest_api_key', $rest_key );
    update_option( 'adfoin_braze_rest_endpoint', $rest_host );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=braze' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_braze_js_fields', 10, 1 );

function adfoin_braze_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_braze_action_fields' );

function adfoin_braze_action_fields() {
    ?>
    <script type="text/template" id="braze-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_user'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <editable-field v-for="field in fields"
                            v-bind:key="field.value"
                            v-bind:field="field"
                            v-bind:trigger="trigger"
                            v-bind:action="action"
                            v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_user', 'Braze [PRO]', 'custom attributes and subscription groups' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_braze_request( $endpoint, $method = 'POST', $data = array(), $record = array() ) {
    $rest_key  = get_option( 'adfoin_braze_rest_api_key', '' );
    $rest_host = get_option( 'adfoin_braze_rest_endpoint', '' );

    if ( ! $rest_key || ! $rest_host ) {
        return new WP_Error( 'adfoin_braze_missing_credentials', __( 'Braze credentials are missing.', 'advanced-form-integration' ) );
    }

    // Validate that the stored endpoint is a proper HTTPS URL.
    if ( ! filter_var( $rest_host, FILTER_VALIDATE_URL ) || 'https' !== wp_parse_url( $rest_host, PHP_URL_SCHEME ) ) {
        return new WP_Error( 'adfoin_braze_invalid_endpoint', __( 'Braze REST endpoint is not a valid HTTPS URL.', 'advanced-form-integration' ) );
    }

    $endpoint = ltrim( $endpoint, '/' );
    $url      = trailingslashit( $rest_host ) . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $rest_key,
        ),
    );

    if ( ! empty( $data ) ) {
        $encoded = wp_json_encode( $data );
        if ( false === $encoded ) {
            return new WP_Error( 'adfoin_braze_json_encode_failed', __( 'Failed to encode request data as JSON.', 'advanced-form-integration' ) );
        }
        $args['body'] = $encoded;
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_braze_job_queue', 'adfoin_braze_job_queue', 10, 1 );

function adfoin_braze_job_queue( $data ) {
    adfoin_braze_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_braze_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_user' !== $task ) {
        return;
    }

    $email = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );

    // Require a valid email address — Braze uses it as the profile identifier.
    if ( ! $email || ! is_email( $email ) ) {
        return;
    }

    $attributes = array(
        'email' => $email,
    );

    // external_id is Braze's recommended primary identifier; include it when mapped.
    if ( ! empty( $field_data['externalId'] ) ) {
        $external_id = trim( adfoin_get_parsed_values( $field_data['externalId'], $posted_data ) );
        if ( $external_id ) {
            $attributes['external_id'] = $external_id;
        }
    }

    if ( ! empty( $field_data['firstName'] ) ) {
        $attributes['first_name'] = adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
    }

    if ( ! empty( $field_data['lastName'] ) ) {
        $attributes['last_name'] = adfoin_get_parsed_values( $field_data['lastName'], $posted_data );
    }

    if ( ! empty( $field_data['phone'] ) ) {
        $phone = trim( adfoin_get_parsed_values( $field_data['phone'], $posted_data ) );
        if ( $phone ) {
            $attributes['phone'] = $phone;
        }
    }

    $payload = array(
        'attributes' => array( $attributes ),
    );

    $response = adfoin_braze_request( 'users/track', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code < 200 || $status_code >= 300 ) {
        // Log is already written by adfoin_braze_request(); nothing more to do here.
        return;
    }
}

