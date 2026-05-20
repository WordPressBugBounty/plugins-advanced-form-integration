<?php

/**
 * Mailshake — Add Recipient to Campaign via POST /recipients/add.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: HTTP Basic with the API key as the username, empty password.
 *
 * @link https://api-docs.mailshake.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_mailshake_actions', 10, 1 );

function adfoin_mailshake_actions( $actions ) {

    $actions['mailshake'] = array(
        'title' => __( 'Mailshake', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Add Recipient to Campaign', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailshake_settings_tab', 10, 1 );

function adfoin_mailshake_settings_tab( $tabs ) {
    $tabs['mailshake'] = __( 'Mailshake', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_mailshake_settings_view', 10, 1 );

function adfoin_mailshake_settings_view( $current_tab ) {
    if ( 'mailshake' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Settings → Extensions → API in Mailshake', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Mailshake go to Settings → Extensions → API and copy your API key.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI authenticates with HTTP Basic using this key as the username.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'mailshake', __( 'Mailshake', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailshake_credentials', 'adfoin_get_mailshake_credentials', 10, 0 );

function adfoin_get_mailshake_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailshake' );
}

add_action( 'wp_ajax_adfoin_save_mailshake_credentials', 'adfoin_save_mailshake_credentials', 10, 0 );

function adfoin_save_mailshake_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailshake', array( 'apiKey' ) );
}

function adfoin_mailshake_credentials_list() {
    foreach ( adfoin_read_credentials( 'mailshake' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_mailshake_action_fields' );

function adfoin_mailshake_action_fields() {
    ?>
    <script type="text/template" id="mailshake-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Mailshake Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailshake' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Campaign', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[campaignId]" v-model="fielddata.campaignId">
                        <option value=""><?php esc_html_e( 'Select Campaign...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(title, id) in fielddata.campaigns" :value="id">{{ title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': campaignLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_to_list', 'Mailshake [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mailshake_campaigns', 'adfoin_get_mailshake_campaigns', 10, 0 );

function adfoin_get_mailshake_campaigns() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error( array( 'message' => __( 'No Mailshake account selected.', 'advanced-form-integration' ) ) );
    }

    $campaigns = array();
    $next      = '';

    do {
        $params = array( 'perPage' => 100 );
        if ( $next ) {
            $params['nextToken'] = $next;
        }

        $response = adfoin_mailshake_request( 'campaigns/list', 'GET', $params, array(), $cred_id );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || empty( $body['results'] ) ) {
            break;
        }

        foreach ( $body['results'] as $campaign ) {
            if ( isset( $campaign['id'], $campaign['title'] ) ) {
                $campaigns[ (string) $campaign['id'] ] = $campaign['title'];
            }
        }

        $next = isset( $body['nextToken'] ) ? (string) $body['nextToken'] : '';
    } while ( $next );

    wp_send_json_success( $campaigns );
}

add_action( 'adfoin_mailshake_job_queue', 'adfoin_mailshake_job_queue', 10, 1 );

function adfoin_mailshake_job_queue( $data ) {
    adfoin_mailshake_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailshake_send_data( $record, $posted_data ) {
    if ( 'add_to_list' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data  = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id     = isset( $field_data['credId'] )     ? $field_data['credId']      : '';
    $campaign_id = isset( $field_data['campaignId'] ) ? absint( $field_data['campaignId'] ) : 0;

    if ( ! $cred_id || ! $campaign_id ) {
        return;
    }

    $email = isset( $field_data['email'] ) ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) ) : '';

    if ( ! $email ) {
        return;
    }

    $first_name = isset( $field_data['firstName'] ) ? trim( (string) adfoin_get_parsed_values( $field_data['firstName'], $posted_data ) ) : '';
    $last_name  = isset( $field_data['lastName'] )  ? trim( (string) adfoin_get_parsed_values( $field_data['lastName'],  $posted_data ) ) : '';
    $full_name  = trim( $first_name . ' ' . $last_name );

    $address = array( 'emailAddress' => $email );

    if ( $full_name ) {
        $address['fullName'] = $full_name;
    }

    $payload = array(
        'campaignID' => $campaign_id,
        'addresses'  => array( apply_filters( 'adfoin_mailshake_address', $address, $field_data, $posted_data ) ),
    );

    $payload = apply_filters( 'adfoin_mailshake_payload', $payload, $field_data, $posted_data );

    adfoin_mailshake_request( 'recipients/add', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_mailshake_request' ) ) :
/**
 * Call the Mailshake API. Auth = HTTP Basic with the API key as the username.
 *
 * @param string $endpoint Path under /2017-04-01/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST) or query args (GET).
 * @param array  $record   Submission record for logging (omit for admin-side fetches).
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_mailshake_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $api_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'mailshake', $cred_id );
        if ( is_array( $credentials ) && isset( $credentials['apiKey'] ) ) {
            $api_key = trim( (string) $credentials['apiKey'] );
        }
    }

    if ( ! $api_key ) {
        return new WP_Error( 'mailshake_missing_key', __( 'Mailshake API key is missing.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.mailshake.com/2017-04-01/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ),
            'Accept'        => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( array_map( 'rawurlencode', $data ), $url );
        }
    } else {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
