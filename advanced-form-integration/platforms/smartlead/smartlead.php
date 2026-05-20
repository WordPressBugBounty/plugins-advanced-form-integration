<?php

/**
 * Smartlead.ai — Add Lead to Campaign via
 * POST /api/v1/campaigns/{id}/leads.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: ?api_key= query parameter on every request.
 *
 * @link https://api.smartlead.ai/introduction
 */

add_filter( 'adfoin_action_providers', 'adfoin_smartlead_actions', 10, 1 );

function adfoin_smartlead_actions( $actions ) {
    $actions['smartlead'] = array(
        'title' => __( 'Smartlead.ai', 'advanced-form-integration' ),
        'tasks' => array(
            'add_lead' => __( 'Add Lead to Campaign', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_smartlead_settings_tab', 10, 1 );

function adfoin_smartlead_settings_tab( $providers ) {
    $providers['smartlead'] = __( 'Smartlead', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_smartlead_settings_view', 10, 1 );

function adfoin_smartlead_settings_view( $current_tab ) {
    if ( 'smartlead' !== $current_tab ) {
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
            'placeholder'   => __( 'Smartlead dashboard → Settings → API Keys', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Smartlead go to Settings → API Keys and create / copy your key.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI sends ?api_key={key} on every call to server.smartlead.ai/api/v1/.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'smartlead', __( 'Smartlead.ai', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_smartlead_credentials', 'adfoin_get_smartlead_credentials', 10, 0 );

function adfoin_get_smartlead_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'smartlead' );
}

add_action( 'wp_ajax_adfoin_save_smartlead_credentials', 'adfoin_save_smartlead_credentials', 10, 0 );

function adfoin_save_smartlead_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'smartlead', array( 'apiKey' ) );
}

function adfoin_smartlead_credentials_list() {
    foreach ( adfoin_read_credentials( 'smartlead' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'wp_ajax_adfoin_get_smartlead_campaigns', 'adfoin_get_smartlead_campaigns', 10, 0 );

function adfoin_get_smartlead_campaigns() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'No Smartlead account selected.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_smartlead_request( 'campaigns', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! is_array( $body ) ) {
        wp_send_json_error();
    }

    $campaigns = array();
    foreach ( $body as $campaign ) {
        if ( isset( $campaign['id'], $campaign['name'] ) ) {
            $campaigns[ (string) $campaign['id'] ] = (string) $campaign['name'];
        }
    }

    wp_send_json_success( $campaigns );
}

add_action( 'adfoin_action_fields', 'adfoin_smartlead_action_fields' );

function adfoin_smartlead_action_fields() {
    ?>
    <script type="text/template" id="smartlead-action-template">
        <table class="form-table" v-if="action.task == 'add_lead'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Smartlead Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=smartlead' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
                        <option v-for="(name, id) in fielddata.campaigns" :value="id">{{ name }}</option>
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
            <?php adfoin_pro_feature_notice( 'add_lead', 'Smartlead.ai [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_smartlead_job_queue', 'adfoin_smartlead_job_queue', 10, 1 );

function adfoin_smartlead_job_queue( $data ) {
    adfoin_smartlead_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_smartlead_send_data( $record, $posted_data ) {
    if ( 'add_lead' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] )     ? $field_data['credId']     : '';
    $campaign   = isset( $field_data['campaignId'] ) ? absint( $field_data['campaignId'] ) : 0;

    if ( ! $cred_id || ! $campaign ) {
        return;
    }

    $email = isset( $field_data['email'] )
        ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
        : '';

    if ( ! $email ) {
        return;
    }

    $lead     = array( 'email' => $email );
    $reserved = array( 'credId' => 1, 'campaignId' => 1, 'campaigns' => 1, 'email' => 1, 'customFields' => 1, 'settings' => 1 );

    $documented = array( 'first_name', 'last_name', 'phone_number', 'company_name', 'website', 'location', 'linkedin_profile', 'company_url' );

    foreach ( $documented as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        if ( '' !== $value && null !== $value ) {
            $lead[ $key ] = $value;
        }
    }

    $lead = apply_filters( 'adfoin_smartlead_lead', $lead, $field_data, $posted_data );

    $body = array(
        'lead_list' => array( $lead ),
        'settings'  => apply_filters( 'adfoin_smartlead_settings', array(
            'ignore_global_block_list'                 => false,
            'ignore_unsubscribe_list'                  => false,
            'ignore_community_bounce_list'             => false,
            'ignore_duplicate_leads_in_other_campaign' => false,
        ), $field_data, $posted_data ),
    );

    adfoin_smartlead_request( 'campaigns/' . $campaign . '/leads', 'POST', $body, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_smartlead_request' ) ) :
/**
 * Call the Smartlead API.
 *
 * @param string $endpoint Path under /api/v1/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET, merged with api_key).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_smartlead_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $api_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'smartlead', $cred_id );
        if ( is_array( $credentials ) && isset( $credentials['apiKey'] ) ) {
            $api_key = trim( (string) $credentials['apiKey'] );
        }
    }

    if ( ! $api_key ) {
        return new WP_Error( 'smartlead_missing_key', __( 'Smartlead API key is missing.', 'advanced-form-integration' ) );
    }

    $url    = 'https://server.smartlead.ai/api/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $query = array( 'api_key' => $api_key );

    if ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $query = array_merge( $data, $query );
    }

    $url = add_query_arg( $query, $url );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Accept' => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
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
