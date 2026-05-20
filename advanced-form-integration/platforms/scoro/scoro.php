<?php

/**
 * Scoro CRM — Create or Update Contact via
 * POST https://{subdomain}.scoro.com/api/v2/contacts/modify.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: apiKey + company_account_id in the JSON request body (no headers).
 *
 * @link https://api.scoro.com/api/v2
 */

add_filter( 'adfoin_action_providers', 'adfoin_scoro_actions', 10, 1 );

function adfoin_scoro_actions( $actions ) {
    $actions['scoro'] = array(
        'title' => __( 'Scoro CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create or Update Contact (Person)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_scoro_settings_tab', 10, 1 );

function adfoin_scoro_settings_tab( $providers ) {
    $providers['scoro'] = __( 'Scoro CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_scoro_settings_view', 10, 1 );

function adfoin_scoro_settings_view( $current_tab ) {
    if ( 'scoro' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'subdomain',
            'label'         => __( 'Company Subdomain', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'e.g. myworkspace (the part before .scoro.com)', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'companyAccountId',
            'label'         => __( 'Company Account ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Usually identical to the subdomain', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'lang',
            'label'         => __( 'Language Code (optional)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'placeholder'   => 'eng',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Scoro open Settings → Integrations → API & web services and generate an API key.', 'advanced-form-integration' ),
        esc_html__( 'Note your company subdomain (the part before .scoro.com in your dashboard URL). The Company Account ID is usually the same string.', 'advanced-form-integration' ),
        esc_html__( 'Paste everything below. AFI POSTs to {subdomain}.scoro.com/api/v2/ with apiKey + company_account_id in the JSON body — no headers required.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'scoro', __( 'Scoro CRM', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_scoro_credentials', 'adfoin_get_scoro_credentials', 10, 0 );

function adfoin_get_scoro_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'scoro' );
}

add_action( 'wp_ajax_adfoin_save_scoro_credentials', 'adfoin_save_scoro_credentials', 10, 0 );

function adfoin_save_scoro_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'scoro', array( 'subdomain', 'companyAccountId', 'apiKey', 'lang' ) );
}

if ( ! function_exists( 'adfoin_scoro_credentials_list' ) ) :
function adfoin_scoro_credentials_list() {
    foreach ( adfoin_read_credentials( 'scoro' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

add_action( 'adfoin_action_fields', 'adfoin_scoro_action_fields' );

function adfoin_scoro_action_fields() {
    ?>
    <script type="text/template" id="scoro-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Scoro Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=scoro' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'create_contact', 'Scoro CRM [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_scoro_fields', 'adfoin_get_scoro_fields', 10, 0 );

function adfoin_get_scoro_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_scoro_base_fields() );
}

function adfoin_scoro_base_fields() {
    return array(
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',  'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',      'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',      'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile',     'value' => __( 'Mobile', 'advanced-form-integration' ) ),
        array( 'key' => 'position',   'value' => __( 'Position / Job Title', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_scoro_job_queue', 'adfoin_scoro_job_queue', 10, 1 );

function adfoin_scoro_job_queue( $data ) {
    adfoin_scoro_send_contact( $data['record'], $data['posted_data'] );
}

function adfoin_scoro_send_contact( $record, $posted_data ) {
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

    $first = isset( $field_data['first_name'] ) ? trim( (string) adfoin_get_parsed_values( $field_data['first_name'], $posted_data ) ) : '';
    $last  = isset( $field_data['last_name'] )  ? trim( (string) adfoin_get_parsed_values( $field_data['last_name'],  $posted_data ) ) : '';
    $email = isset( $field_data['email'] )      ? sanitize_email( adfoin_get_parsed_values( $field_data['email'],     $posted_data ) ) : '';

    if ( '' === $first && '' === $last && '' === $email ) {
        return;
    }

    $contact = array( 'contact_type' => 'person' );

    if ( '' !== $first ) {
        $contact['name'] = $first;
    }
    if ( '' !== $last ) {
        $contact['lastname'] = $last;
    }

    if ( isset( $field_data['position'] ) ) {
        $pos = trim( (string) adfoin_get_parsed_values( $field_data['position'], $posted_data ) );
        if ( '' !== $pos ) {
            $contact['position'] = $pos;
        }
    }

    $means = array();

    if ( '' !== $email ) {
        $means[] = array( 'type' => 'email', 'value' => $email );
    }

    foreach ( array( 'phone' => 'phone', 'mobile' => 'mobile' ) as $key => $type ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $means[] = array( 'type' => $type, 'value' => $value );
        }
    }

    if ( ! empty( $means ) ) {
        $contact['means_of_contact'] = $means;
    }

    $contact = apply_filters( 'adfoin_scoro_contact', $contact, $field_data, $posted_data );

    adfoin_scoro_request( 'contacts/modify', 'POST', $contact, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_scoro_request' ) ) :
/**
 * Call the Scoro v2 API. Wraps the body in the
 * {lang, company_account_id, apiKey, request: ...} envelope and POSTs
 * to the tenant subdomain.
 *
 * @param string $endpoint Path under /api/v2/ (e.g. 'contacts/modify').
 * @param string $method   HTTP verb. Scoro v2 endpoints are POST-only.
 * @param mixed  $request  The inner `request` object (or array/scalar) for the call.
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_scoro_request( $endpoint, $method = 'POST', $request = array(), $record = array(), $cred_id = '' ) {
    $subdomain  = '';
    $company_id = '';
    $api_key    = '';
    $lang       = 'eng';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'scoro', $cred_id );
        if ( is_array( $credentials ) ) {
            $subdomain  = isset( $credentials['subdomain'] )        ? trim( (string) $credentials['subdomain'] )        : '';
            $company_id = isset( $credentials['companyAccountId'] ) ? trim( (string) $credentials['companyAccountId'] ) : '';
            $api_key    = isset( $credentials['apiKey'] )           ? trim( (string) $credentials['apiKey'] )           : '';
            $lang       = isset( $credentials['lang'] ) && $credentials['lang'] ? trim( (string) $credentials['lang'] ) : 'eng';
        }
    }

    if ( ! $subdomain || ! $company_id || ! $api_key ) {
        return new WP_Error( 'scoro_missing_credentials', __( 'Scoro credentials are not configured.', 'advanced-form-integration' ) );
    }

    // Strip an accidental ".scoro.com" suffix users sometimes paste in.
    $subdomain = preg_replace( '/\.scoro\.com.*$/i', '', $subdomain );

    $url = 'https://' . rawurlencode( $subdomain ) . '.scoro.com/api/v2/' . ltrim( $endpoint, '/' );

    $body = array(
        'lang'               => $lang,
        'company_account_id' => $company_id,
        'apiKey'             => $api_key,
        'request'            => is_array( $request ) || is_object( $request ) ? $request : new stdClass(),
    );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
