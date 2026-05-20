<?php

/**
 * SlickText — Create Contact via POST /v1/brands/{brand_id}/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer {api_key} (SlickText v2 accounts).
 *
 * @link https://api.slicktext.com/docs/v2/overview
 */

add_filter( 'adfoin_action_providers', 'adfoin_slicktext_actions', 10, 1 );

function adfoin_slicktext_actions( $actions ) {
    $actions['slicktext'] = array(
        'title' => __( 'SlickText', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_slicktext_settings_tab', 10, 1 );

function adfoin_slicktext_settings_tab( $providers ) {
    $providers['slicktext'] = __( 'SlickText', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_slicktext_settings_view', 10, 1 );

function adfoin_slicktext_settings_view( $current_tab ) {
    if ( 'slicktext' !== $current_tab ) {
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
            'placeholder'   => __( 'SlickText dashboard → Settings → API & Webhooks', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'brandId',
            'label'         => __( 'Brand ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Numeric ID from the same screen', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In SlickText go to Settings → API & Webhooks → API Keys. Generate a key with contact write permissions.', 'advanced-form-integration' ),
        esc_html__( 'Copy the API key and the numeric Brand ID from the same screen.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI calls https://dev.slicktext.com/v1/brands/{brandId}/contacts with Authorization: Bearer {key}.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'slicktext', __( 'SlickText', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_slicktext_credentials', 'adfoin_get_slicktext_credentials', 10, 0 );

function adfoin_get_slicktext_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'slicktext' );
}

add_action( 'wp_ajax_adfoin_save_slicktext_credentials', 'adfoin_save_slicktext_credentials', 10, 0 );

function adfoin_save_slicktext_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'slicktext', array( 'apiKey', 'brandId' ) );
}

function adfoin_slicktext_credentials_list() {
    foreach ( adfoin_read_credentials( 'slicktext' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_slicktext_action_fields' );

function adfoin_slicktext_action_fields() {
    ?>
    <script type="text/template" id="slicktext-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SlickText Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=slicktext' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Opt-In Status', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[opt_in_status]" v-model="fielddata.opt_in_status">
                        <option value="subscribed"><?php esc_html_e( 'Subscribed (set only with verified consent)', 'advanced-form-integration' ); ?></option>
                        <option value="pending"><?php esc_html_e( 'Pending', 'advanced-form-integration' ); ?></option>
                        <option value="unsubscribed"><?php esc_html_e( 'Unsubscribed', 'advanced-form-integration' ); ?></option>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'SlickText [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_slicktext_fields', 'adfoin_get_slicktext_fields', 10, 0 );

function adfoin_get_slicktext_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_slicktext_base_fields() );
}

function adfoin_slicktext_base_fields() {
    return array(
        array( 'key' => 'mobile_number', 'value' => __( 'Mobile Number (10+ digits, e.g. +14155551234)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name',    'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',     'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',         'value' => __( 'Email', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_slicktext_job_queue', 'adfoin_slicktext_job_queue', 10, 1 );

function adfoin_slicktext_job_queue( $data ) {
    adfoin_slicktext_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_slicktext_send_data( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $mobile = isset( $field_data['mobile_number'] )
        ? trim( (string) adfoin_get_parsed_values( $field_data['mobile_number'], $posted_data ) )
        : '';

    if ( '' === $mobile ) {
        return;
    }

    $payload = array(
        'mobile_number' => $mobile,
        'opt_in_status' => isset( $field_data['opt_in_status'] ) ? (string) $field_data['opt_in_status'] : 'subscribed',
    );

    foreach ( array( 'first_name', 'last_name', 'email' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $payload[ $key ] = $value;
        }
    }

    $payload = apply_filters( 'adfoin_slicktext_contact_payload', $payload, $field_data, $posted_data );

    adfoin_slicktext_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_slicktext_request' ) ) :
/**
 * Call the SlickText v2 API. Brand ID is taken from the saved credential
 * and prefixed onto every endpoint as /brands/{brand_id}/{endpoint}.
 *
 * @param string $endpoint Path under /v1/brands/{brand_id}/ (or absolute with leading "_/").
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_slicktext_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $api_key  = '';
    $brand_id = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'slicktext', $cred_id );
        if ( is_array( $credentials ) ) {
            $api_key  = isset( $credentials['apiKey'] )  ? trim( (string) $credentials['apiKey'] )  : '';
            $brand_id = isset( $credentials['brandId'] ) ? trim( (string) $credentials['brandId'] ) : '';
        }
    }

    if ( ! $api_key ) {
        return new WP_Error( 'slicktext_missing_key', __( 'SlickText API key is missing.', 'advanced-form-integration' ) );
    }
    if ( ! $brand_id ) {
        return new WP_Error( 'slicktext_missing_brand', __( 'SlickText Brand ID is missing.', 'advanced-form-integration' ) );
    }

    $url    = 'https://dev.slicktext.com/v1/brands/' . rawurlencode( $brand_id ) . '/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Accept'        => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
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
