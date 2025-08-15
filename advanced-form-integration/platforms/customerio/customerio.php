<?php

add_filter( 'adfoin_action_providers', 'adfoin_customerio_actions', 10, 1 );

function adfoin_customerio_actions( $actions ) {
    $actions['customerio'] = array(
        'title' => __( 'Customer.io', 'advanced-form-integration' ),
        'tasks' => array(
        'add_people'     => __( 'Add People', 'advanced-form-integration' ),
    ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_customerio_settings_tab', 10, 1 );

function adfoin_customerio_settings_tab( $providers ) {
    $providers['customerio'] = __( 'Customer.io', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_customerio_settings_view', 10, 1 );

function adfoin_customerio_settings_view( $current_tab ) {
    if ( $current_tab != 'customerio' ) {
        return;
    }

    $title = __( 'Customer.io', 'advanced-form-integration' );
    $key = 'customerio';
    $arguments = json_encode( array(
        'platform' => $key,
        'fields' => array(
            array(
                'key' => 'api_key',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            )
        )
    ) );

    $instructions = __(
        '<ol>
            <li>Log in to your <a href="https://customer.io/" target="_blank">Customer.io account</a>.</li>
            <li>Navigate to <strong>People > Add People > Customer.io API</strong>.</li>
            <li>Copy the API Key but keep the this page open.</li>
            <li>Save the API Key into AFI > Settings > Customer.io.</li>
            <li>Create a basic integration and submit form to test it.</li>
            <li>Click on the <strong>Test connection</strong> button in the opened page.</li>
            <li>A success message will be displayed if the connection is successful.</li>
        </ol>',
        'advanced-form-integration'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action('wp_ajax_adfoin_get_customerio_credentials', 'adfoin_get_customerio_credentials', 10, 0);

function adfoin_get_customerio_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('customerio');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_customerio_credentials', 'adfoin_save_customerio_credentials', 10, 0);

function adfoin_save_customerio_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);

    if ('customerio' == $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_customerio_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('customerio');

    foreach ($credentials as $option) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_customerio_action_fields' );

function adfoin_customerio_action_fields() {
    ?>
    <script type="text/template" id="customerio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_people'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' );?>
                </th>
                <td scope="row"></td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>


<?php 
}

/*
 * customerio API Call
 */
function adfoin_customerio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

    $credentials = adfoin_get_credentials_by_id('customerio', $cred_id);
    $api_key = isset($credentials['api_key']) ? $credentials['api_key'] : '';

    $base_url = "https://cdp.customer.io/v1/";
    $url      = $base_url . $endpoint;
    $url      = esc_url_raw( $url );
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'User-Agent'   => 'Advanced Form Integration',
            'Authorization' => 'Basic '. base64_encode( $api_key .':')
        )
    );
 
    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
 
    $response = wp_remote_request( $url, $args );
 
    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }
 
    return $response;
}

add_action( 'adfoin_customerio_job_queue', 'adfoin_customerio_job_queue', 10, 1 );

function adfoin_customerio_job_queue( $data ) {
    adfoin_customerio_send_data( $data['record'], $data['posted_data'] );
}
 
/*
 * Handles sending data to customerio API
 */
function adfoin_customerio_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    // Conditional logic check
    if ( ! empty( $record_data['action_data']['cl']['active'] ) && $record_data['action_data']['cl']['active'] === 'yes' ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];

    if ( $task === 'add_people' ) {
        $user_id = ! empty( $data['userId'] ) ? adfoin_get_parsed_values( $data['userId'], $posted_data ) : '';
        $email   = ! empty( $data['email'] )  ? trim( adfoin_get_parsed_values( $data['email'], $posted_data ) ) : '';

        $traits = [
            'email' => $email,
            'type' => 'identify'
        ];

        if ( ! empty( $data['name'] ) ) {
            $traits['name'] = adfoin_get_parsed_values( $data['name'], $posted_data );
        }

        foreach ( $data as $key => $value ) {
            if ( ! in_array( $key, ['userId','email','name'], true ) && ! empty( $value ) ) {
                $traits[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        $payload = ['traits' => $traits];

        if ( ! empty( $user_id ) ) {
            $payload['userId'] = $user_id;
        } else {
            $payload['anonymousId'] = wp_generate_uuid4();
        }

        adfoin_customerio_request( 'identify', 'POST', $payload, $record );
    }

    return;
}