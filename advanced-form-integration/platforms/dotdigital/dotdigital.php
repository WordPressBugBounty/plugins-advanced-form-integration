<?php

add_filter( 'adfoin_action_providers', 'adfoin_dotdigital_actions', 10, 1 );

function adfoin_dotdigital_actions( $actions ) {

    $actions['dotdigital'] = array(
        'title' => __( 'Dotdigital', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create/Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_dotdigital_settings_tab', 10, 1 );

function adfoin_dotdigital_settings_tab( $providers ) {
    $providers['dotdigital'] = __( 'Dotdigital', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_dotdigital_settings_view', 10, 1 );

function adfoin_dotdigital_settings_view( $current_tab ) {
    if ( 'dotdigital' !== $current_tab ) {
        return;
    }

    $nonce       = wp_create_nonce( 'adfoin_dotdigital_settings' );
    $api_user    = get_option( 'adfoin_dotdigital_api_user', '' );
    $api_pass    = get_option( 'adfoin_dotdigital_api_pass', '' );
    $api_region  = get_option( 'adfoin_dotdigital_api_region', '' );
    ?>

    <form name="dotdigital_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_dotdigital_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'API Username', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_dotdigital_api_user"
                           value="<?php echo esc_attr( $api_user ); ?>" placeholder="<?php esc_attr_e( 'Enter API Username', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'API Password', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="password" name="adfoin_dotdigital_api_pass"
                           value="<?php echo esc_attr( $api_pass ); ?>" placeholder="<?php esc_attr_e( 'Enter API Password', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'Data Center Region', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_dotdigital_api_region"
                           value="<?php echo esc_attr( $api_region ); ?>" placeholder="<?php esc_attr_e( 'Enter Region (e.g. r1, r2)', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php esc_html_e( 'Find region from your API endpoint, e.g. https://r1-api.dotdigital.com/', 'advanced-form-integration' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_dotdigital_credentials', 'adfoin_save_dotdigital_credentials', 10, 0 );

function adfoin_save_dotdigital_credentials() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_dotdigital_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_user   = isset( $_POST['adfoin_dotdigital_api_user'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_dotdigital_api_user'] ) ) : '';
    $api_pass   = isset( $_POST['adfoin_dotdigital_api_pass'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_dotdigital_api_pass'] ) ) : '';
    $api_region = isset( $_POST['adfoin_dotdigital_api_region'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_dotdigital_api_region'] ) ) : '';

    update_option( 'adfoin_dotdigital_api_user', $api_user );
    update_option( 'adfoin_dotdigital_api_pass', $api_pass );
    update_option( 'adfoin_dotdigital_api_region', $api_region );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=dotdigital' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_dotdigital_js_fields', 10, 1 );

function adfoin_dotdigital_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_dotdigital_action_fields' );

function adfoin_dotdigital_action_fields() {
    ?>
    <script type="text/template" id="dotdigital-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
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
            <?php
            if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
                ?>
                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row">
                        <?php esc_attr_e( 'Using Pro Features', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'For marketing preferences, address fields, and automation, create a <a href="%s">new integration</a> and select Dotdigital [PRO].', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-new' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }

            if ( adfoin_fs()->is_not_paying() ) {
                ?>
                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row">
                        <?php esc_attr_e( 'Go Pro', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'Unlock address fields and segments by <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </script>
    <?php
}

function adfoin_dotdigital_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
    $api_user   = get_option( 'adfoin_dotdigital_api_user', '' );
    $api_pass   = get_option( 'adfoin_dotdigital_api_pass', '' );
    $api_region = get_option( 'adfoin_dotdigital_api_region', 'r1' );

    if ( ! $api_user || ! $api_pass ) {
        return new WP_Error( 'adfoin_dotdigital_missing_credentials', __( 'Dotdigital credentials are missing.', 'advanced-form-integration' ) );
    }

    $endpoint = ltrim( $endpoint, '/' );
    $base_url = sprintf( 'https://%s-api.dotdigital.com/v2/', $api_region );
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body'    => '',
    );

    $args['headers']['Authorization'] = 'Basic ' . base64_encode( $api_user . ':' . $api_pass );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    } elseif ( ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_dotdigital_job_queue', 'adfoin_dotdigital_job_queue', 10, 1 );

function adfoin_dotdigital_job_queue( $data ) {
    adfoin_dotdigital_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_dotdigital_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === $record_data['action_data']['cl']['active'] ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_contact' !== $task ) {
        return;
    }

    $email = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );

    if ( ! $email ) {
        return;
    }

    $payload = array(
        'email'      => $email,
        'optInType'  => 'Unknown',
        'dataFields' => array(),
    );

    $first_name = empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
    $last_name  = empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data );

    if ( $first_name ) {
        $payload['dataFields'][] = array( 'key' => 'FIRSTNAME', 'value' => $first_name );
    }

    if ( $last_name ) {
        $payload['dataFields'][] = array( 'key' => 'LASTNAME', 'value' => $last_name );
    }

    $response = adfoin_dotdigital_request( 'contacts', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $response_code = wp_remote_retrieve_response_code( $response );

    if ( 409 === $response_code ) {
        $payload['matchIdentifiers'] = array( 'email' => $email );
        adfoin_dotdigital_request( 'contacts', 'PUT', $payload, $record );
    }
}
