<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_omnisend_actions',
    10,
    1
);
function adfoin_omnisend_actions(  $actions  ) {
    $actions['omnisend'] = array(
        'title' => __( 'Omnisend', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create New Contact', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_omnisend_settings_tab',
    10,
    1
);
function adfoin_omnisend_settings_tab(  $providers  ) {
    $providers['omnisend'] = __( 'Omnisend', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_omnisend_settings_view',
    10,
    1
);
function adfoin_omnisend_settings_view(  $current_tab  ) {
    if ( $current_tab != 'omnisend' ) {
        return;
    }
    $title = __( 'Omnisend', 'advanced-form-integration' );
    $key = 'omnisend';
    $arguments = json_encode( [
        'platform' => $key,
        'fields'   => [[
            'key'    => 'apiKey',
            'label'  => __( 'API Key', 'advanced-form-integration' ),
            'hidden' => true,
        ]],
    ] );
    $instructions = sprintf( __( '<p>
                Go to Store Settings > Integrations & API > API Keys.
            </p>', 'advanced-form-integration' ) );
    echo adfoin_platform_settings_template(
        $title,
        $key,
        $arguments,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_omnisend_credentials',
    'adfoin_get_omnisend_credentials',
    10,
    0
);
function adfoin_get_omnisend_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $all_credentials = adfoin_read_credentials( 'omnisend' );
    wp_send_json_success( $all_credentials );
}

add_action(
    'wp_ajax_adfoin_save_omnisend_credentials',
    'adfoin_save_omnisend_credentials',
    10,
    0
);
/*
 * Get Omnisend credentials
 */
function adfoin_save_omnisend_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $platform = sanitize_text_field( $_POST['platform'] );
    if ( 'omnisend' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_omnisend_modify_credentials',
    10,
    2
);
function adfoin_omnisend_modify_credentials(  $credentials, $platform  ) {
    if ( 'omnisend' == $platform && empty( $credentials ) ) {
        $private_key = ( get_option( 'adfoin_omnisend_api_token' ) ? get_option( 'adfoin_omnisend_api_token' ) : '' );
        if ( $private_key ) {
            $credentials[] = array(
                'id'     => '123456',
                'title'  => __( 'Untitled', 'advanced-form-integration' ),
                'apiKey' => $private_key,
            );
        }
    }
    return $credentials;
}

function adfoin_omnisend_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'omnisend' );
    foreach ( $credentials as $option ) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
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
                    <?php 
    esc_attr_e( 'Contact Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Omnisend Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                    <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <?php 
    adfoin_omnisend_credentials_list();
    ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'add_contact'">
                        <th scope="row">
                            <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                        </th>
                        <td scope="row">
                            <span><?php 
        printf( __( 'To unlock tags and custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                        </td>
                    </tr>
                    <?php 
    }
    ?>
            
        </table>
    </script>
    <?php 
}

add_action(
    'adfoin_omnisend_job_queue',
    'adfoin_omnisend_job_queue',
    10,
    1
);
function adfoin_omnisend_job_queue(  $data  ) {
    adfoin_omnisend_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Omnisend API
 */
function adfoin_omnisend_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    if ( $task == 'add_contact' ) {
        $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
        $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
        $phone = ( empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data ) );
        $address = ( empty( $data['address'] ) ? '' : adfoin_get_parsed_values( $data['address'], $posted_data ) );
        $city = ( empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data ) );
        $state = ( empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data ) );
        $zip = ( empty( $data['zip'] ) ? '' : adfoin_get_parsed_values( $data['zip'], $posted_data ) );
        $country = ( empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data ) );
        $birthday = ( empty( $data['birthday'] ) ? '' : adfoin_get_parsed_values( $data['birthday'], $posted_data ) );
        $gender = ( empty( $data['gender'] ) ? '' : adfoin_get_parsed_values( $data['gender'], $posted_data ) );
        $body = array(
            'firstName'   => $first_name,
            'lastName'    => $last_name,
            'address'     => $address,
            'city'        => $city,
            'state'       => $state,
            'postalCode'  => $zip,
            'country'     => $country,
            'birthdate'   => $birthday,
            'identifiers' => array(array(
                'type'     => 'email',
                'id'       => trim( $email ),
                'channels' => array(
                    'email' => array(
                        'status'     => 'subscribed',
                        'statusDate' => date( 'c' ),
                    ),
                ),
            )),
        );
        if ( $phone ) {
            $body['identifiers'][] = array(
                'type'     => 'phone',
                'id'       => $phone,
                'channels' => array(
                    'sms' => array(
                        'status'     => 'subscribed',
                        'statusDate' => date( 'c' ),
                    ),
                ),
            );
        }
        if ( $gender ) {
            $gender = ( strtolower( $gender )[0] == 'f' ? 'f' : 'm' );
            $body['gender'] = $gender;
        }
        $body = array_filter( $body );
        $response = adfoin_omnisend_request(
            'contacts',
            'POST',
            $body,
            $record,
            $cred_id
        );
    }
    return;
}

function adfoin_omnisend_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'omnisend', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $base_url = "https://api.omnisend.com/v3/";
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-API-KEY'    => $api_key,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}
