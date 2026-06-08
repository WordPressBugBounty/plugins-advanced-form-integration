<?php

add_filter( 'adfoin_action_providers', 'adfoin_omnisend_actions', 10, 1 );

function adfoin_omnisend_actions( $actions ) {

    $actions['omnisend'] = array(
        'title' => __( 'Omnisend', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact'   => __( 'Create New Contact', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_omnisend_settings_tab', 10, 1 );

function adfoin_omnisend_settings_tab( $providers ) {
    $providers['omnisend'] = __( 'Omnisend', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_omnisend_settings_view', 10, 1 );

function adfoin_omnisend_settings_view( $current_tab ) {
    if( $current_tab != 'omnisend' ) {
        return;
    }

    $title = __( 'Omnisend', 'advanced-form-integration' );
    $key = 'omnisend';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'apiKey',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                Go to Store Settings > Integrations & API > API Keys.
            </p>',
            'advanced-form-integration'
        )
    );
    
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_omnisend_credentials', 'adfoin_get_omnisend_credentials', 10, 0 );

function adfoin_get_omnisend_credentials() {
    adfoin_verify_nonce();

    $all_credentials = adfoin_read_credentials( 'omnisend' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_omnisend_credentials', 'adfoin_save_omnisend_credentials', 10, 0 );
/*
 * Get Omnisend credentials
 */
function adfoin_save_omnisend_credentials() {

    adfoin_verify_nonce();

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if( 'omnisend' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

// Legacy single-account import: surfaces old `adfoin_omnisend_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'omnisend', array(
            'apiKey' => 'adfoin_omnisend_api_token',
        ), array(
            'id' => '123456',
            'title' => 'Untitled',
        ) );
    }
}, 20 );

function adfoin_omnisend_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'omnisend' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_omnisend_action_fields' );

function adfoin_omnisend_action_fields() {
    ?>
    <script type="text/template" id="omnisend-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Contact Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Omnisend Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=omnisend' ); ?>" target="_blank" class="adfoin-help-link">
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <span v-if="credentialLoading"><img src="<?php echo admin_url( 'images/spinner.gif' ); ?>" /></span>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_contact', 'Omnisend [PRO]', 'tags, custom fields, and SMS status' ); ?>

        </table>
    </script>
    <?php
}

add_action( 'adfoin_omnisend_job_queue', 'adfoin_omnisend_job_queue', 10, 1 );

function adfoin_omnisend_job_queue( $data ) {
    adfoin_omnisend_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Omnisend API
 */
function adfoin_omnisend_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = $record['task'];

    if ( $task == 'add_contact' ) {
        $email        = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name   = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name    = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
        $phone        = empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data );
        $address      = empty( $data['address'] ) ? '' : adfoin_get_parsed_values( $data['address'], $posted_data );
        $city         = empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data );
        $state        = empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data );
        $zip          = empty( $data['zip'] ) ? '' : adfoin_get_parsed_values( $data['zip'], $posted_data );
        $country      = empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data );
        $country_code = empty( $data['countryCode'] ) ? '' : adfoin_get_parsed_values( $data['countryCode'], $posted_data );
        $birthday     = empty( $data['birthday'] ) ? '' : adfoin_get_parsed_values( $data['birthday'], $posted_data );
        $gender       = empty( $data['gender'] ) ? '' : adfoin_get_parsed_values( $data['gender'], $posted_data );
        $email_status = empty( $data['emailStatus'] ) ? 'subscribed' : adfoin_get_parsed_values( $data['emailStatus'], $posted_data );

        $valid_statuses = array( 'subscribed', 'nonSubscribed', 'unsubscribed' );
        if ( ! in_array( $email_status, $valid_statuses, true ) ) {
            $email_status = 'subscribed';
        }

        $body = array(
            'firstName'  => $first_name,
            'lastName'   => $last_name,
            'address'    => $address,
            'city'       => $city,
            'state'      => $state,
            'postalCode' => $zip,
            'country'    => $country,
            'birthdate'  => $birthday,
            'identifiers' => array(
                array(
                    'type'     => 'email',
                    'id'       => trim( $email ),
                    'channels' => array(
                        'email' => array(
                            'status' => $email_status,
                        )
                    )
                )
            )
        );

        if ( $country_code ) {
            $body['countryCode'] = strtoupper( $country_code );
        }

        if ( $phone ) {
            $body['identifiers'][] = array(
                'type'     => 'phone',
                'id'       => $phone,
                'channels' => array(
                    'sms' => array(
                        'status' => 'subscribed',
                    )
                )
            );
        }

        if ( $gender ) {
            $body['gender'] = strtolower( $gender )[0] === 'f' ? 'f' : 'm';
        }

        $body = array_filter( $body );

        $response = adfoin_omnisend_request( 'contacts', 'POST', $body, $record, $cred_id );
    }

    return;
}

function adfoin_omnisend_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'omnisend', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) {
        return new WP_Error( 'missing_credentials', __( 'Omnisend API credentials not found', 'advanced-form-integration' ) );
    }

    $url  = 'https://api.omnisend.com/api/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'     => 'application/json',
            'Authorization'    => 'Omnisend-API-Key ' . $api_key,
            'Omnisend-Version' => '2026-03-15',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}