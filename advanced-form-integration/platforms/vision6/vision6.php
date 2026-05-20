<?php

/**
 * Vision6 — Add Contact to List via POST /v4/lists/{list_id}/contacts.
 *
 * Australian SMB email marketing platform, popular with AU/NZ agencies.
 * Targets the REST API v4 (the modern generation). Multi-account credential
 * storage via ADFOIN_Account_Manager.
 *
 * Auth: Authorization: Bearer <api_key>
 *
 * @link https://developers.vision6.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_vision6_actions', 10, 1 );

function adfoin_vision6_actions( $actions ) {
    $actions['vision6'] = array(
        'title' => __( 'Vision6', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add Contact to List', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_vision6_settings_tab', 10, 1 );

function adfoin_vision6_settings_tab( $providers ) {
    $providers['vision6'] = __( 'Vision6', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_vision6_settings_view', 10, 1 );

function adfoin_vision6_settings_view( $current_tab ) {
    if ( 'vision6' !== $current_tab ) {
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
            'placeholder'   => __( 'Paste your Vision6 API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Vision6 and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developers.vision6.com/">the developer portal</a>' ),
        esc_html__( 'Navigate to Account Settings -> API Keys and create a new REST API v4 key.', 'advanced-form-integration' ),
        esc_html__( 'Copy the API key value (treat it like a password).', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.vision6.com/v4/ with this key in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'vision6', __( 'Vision6', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_vision6_credentials', 'adfoin_get_vision6_credentials', 10, 0 );

function adfoin_get_vision6_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'vision6' );
}

add_action( 'wp_ajax_adfoin_save_vision6_credentials', 'adfoin_save_vision6_credentials', 10, 0 );

function adfoin_save_vision6_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'vision6', array( 'apiKey' ) );
}

function adfoin_vision6_credentials_list() {
    foreach ( adfoin_read_credentials( 'vision6' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_vision6_action_fields' );

function adfoin_vision6_action_fields() {
    ?>
    <script type="text/template" id="vision6-action-template">
        <table class="form-table" v-if="action.task == 'add_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Vision6 Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=vision6' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_vision6_fields', 'adfoin_get_vision6_fields' );

function adfoin_get_vision6_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'list_id',       'value' => __( 'List ID (required) — find it in your Vision6 list URL', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email_address', 'value' => __( 'Email Address (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name',    'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',     'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile_number', 'value' => __( 'Mobile Number (E.164, e.g. +61400000000)', 'advanced-form-integration' ) ),
        array( 'key' => 'company',       'value' => __( 'Company (custom field)', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title',     'value' => __( 'Job Title (custom field)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_vision6_job_queue', 'adfoin_vision6_job_queue', 10, 1 );

function adfoin_vision6_job_queue( $data ) {
    adfoin_vision6_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_vision6_send_data( $record, $posted_data ) {
    if ( 'add_contact' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    // Resolve flat values up-front. list_id is pulled into the URL path; the
    // company/job_title pair is nested under custom_fields below.
    $values   = array();
    $reserved = array( 'credId' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // Required: list_id (path) + email_address (body).
    if ( empty( $values['list_id'] ) || empty( $values['email_address'] ) ) {
        return;
    }

    $list_id = rawurlencode( (string) $values['list_id'] );

    // Top-level standard fields per Vision6 v4 contact schema.
    $payload = array(
        'email_address' => $values['email_address'],
    );

    foreach ( array( 'first_name', 'last_name', 'mobile_number' ) as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $payload[ $key ] = $values[ $key ];
        }
    }

    // company + job_title go inside the custom_fields object so they can be
    // routed to whatever custom field names the user has set up in Vision6.
    $custom = array();
    foreach ( array( 'company', 'job_title' ) as $ckey ) {
        if ( ! empty( $values[ $ckey ] ) ) {
            $custom[ $ckey ] = $values[ $ckey ];
        }
    }
    if ( ! empty( $custom ) ) {
        $payload['custom_fields'] = $custom;
    }

    adfoin_vision6_request( 'lists/' . $list_id . '/contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_vision6_request' ) ) :
function adfoin_vision6_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'vision6', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'vision6_missing_credentials', __( 'Vision6 API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.vision6.com/v4/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiKey'],
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
