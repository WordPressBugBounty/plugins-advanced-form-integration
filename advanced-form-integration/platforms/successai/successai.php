<?php

/**
 * Success.ai — Add Lead to Campaign via POST /api/v2/leads.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer {api_key}.
 *
 * @link https://api.success.ai/api/docs
 */

add_filter( 'adfoin_action_providers', 'adfoin_successai_actions', 10, 1 );

function adfoin_successai_actions( $actions ) {
    $actions['successai'] = array(
        'title' => __( 'Success.ai', 'advanced-form-integration' ),
        'tasks' => array(
            'add_lead' => __( 'Add Lead to Campaign', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_successai_settings_tab', 10, 1 );

function adfoin_successai_settings_tab( $providers ) {
    $providers['successai'] = __( 'Success.ai', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_successai_settings_view', 10, 1 );

function adfoin_successai_settings_view( $current_tab ) {
    if ( 'successai' !== $current_tab ) {
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
            'placeholder'   => __( 'Success.ai dashboard → Settings → API', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Success.ai go to Settings → API and generate / copy your key.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI sends Authorization: Bearer {key} to api.success.ai/api/v2/.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'successai', __( 'Success.ai', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_successai_credentials', 'adfoin_get_successai_credentials', 10, 0 );

function adfoin_get_successai_credentials() {
    adfoin_verify_nonce();
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'successai' );
}

add_action( 'wp_ajax_adfoin_save_successai_credentials', 'adfoin_save_successai_credentials', 10, 0 );

function adfoin_save_successai_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'successai', array( 'apiKey' ) );
}

add_action( 'adfoin_action_fields', 'adfoin_successai_action_fields' );

function adfoin_successai_action_fields() {
    ?>
    <script type="text/template" id="successai-action-template">
        <table class="form-table" v-if="action.task == 'add_lead'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Success.ai Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=successai' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Campaign ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[campaign]" v-model="fielddata.campaign">
                    <p class="description"><?php esc_html_e( 'Copy the campaign ID from the Success.ai campaign URL or the campaigns list endpoint.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_lead', 'Success.ai [PRO]', 'custom variables' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_successai_fields', 'adfoin_get_successai_fields', 10, 0 );

function adfoin_get_successai_fields() {
    adfoin_verify_nonce();

    wp_send_json_success( adfoin_successai_base_fields() );
}

function adfoin_successai_base_fields() {
    return array(
        array( 'key' => 'email',        'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name',   'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',    'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',        'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'website',      'value' => __( 'Website', 'advanced-form-integration' ) ),
        array( 'key' => 'linkedin_url', 'value' => __( 'LinkedIn URL', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_successai_job_queue', 'adfoin_successai_job_queue', 10, 1 );

function adfoin_successai_job_queue( $data ) {
    adfoin_successai_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_successai_send_data( $record, $posted_data ) {
    if ( 'add_lead' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] )   ? $field_data['credId']   : '';
    $campaign   = isset( $field_data['campaign'] ) ? trim( (string) $field_data['campaign'] ) : '';

    if ( ! $cred_id || ! $campaign ) {
        return;
    }

    $email = isset( $field_data['email'] )
        ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
        : '';

    if ( ! $email ) {
        return;
    }

    $payload = array(
        'campaign' => $campaign,
        'email'    => $email,
    );

    foreach ( array( 'first_name', 'last_name', 'company_name', 'phone', 'website', 'linkedin_url' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $payload[ $key ] = $value;
        }
    }

    $payload = apply_filters( 'adfoin_successai_lead_payload', $payload, $field_data, $posted_data );

    adfoin_successai_request( 'leads', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_successai_request' ) ) :
/**
 * Call the Success.ai v2 API.
 *
 * @param string $endpoint Path under /api/v2/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_successai_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $api_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'successai', $cred_id );
        if ( is_array( $credentials ) && isset( $credentials['apiKey'] ) ) {
            $api_key = trim( (string) $credentials['apiKey'] );
        }
    }

    if ( ! $api_key ) {
        return new WP_Error( 'successai_missing_key', __( 'Success.ai API key is missing.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.success.ai/api/v2/' . ltrim( $endpoint, '/' );
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
