<?php

/**
 * Ontraport — Create or Update Contact via POST /1/Contacts/saveorupdate.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Api-Appid + Api-Key headers (form-urlencoded body).
 *
 * @link https://api.ontraport.com/doc/
 */

add_filter( 'adfoin_action_providers', 'adfoin_ontraport_actions', 10, 1 );

function adfoin_ontraport_actions( $actions ) {

    $actions['ontraport'] = array(
        'title' => __( 'Ontraport', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create or Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ontraport_settings_tab', 10, 1 );

function adfoin_ontraport_settings_tab( $providers ) {
    $providers['ontraport'] = __( 'Ontraport', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ontraport_settings_view', 10, 1 );

function adfoin_ontraport_settings_view( $current_tab ) {
    if ( 'ontraport' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'appId',
            'label'         => __( 'App ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Your Ontraport site ID', 'advanced-form-integration' ),
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
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s: link to Ontraport API key page. */
            esc_html__( 'In Ontraport go to %s and create an API Key with the permissions you need (typically Contacts read/write and Tags read).', 'advanced-form-integration' ),
            '<a href="https://app.ontraport.com/#!/account/api" target="_blank" rel="noopener noreferrer">Administration → Integrations → API Keys</a>'
        ),
        esc_html__( 'Copy the App ID and the API Key into the form below.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'ontraport', __( 'Ontraport', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_ontraport_credentials', 'adfoin_get_ontraport_credentials', 10, 0 );

function adfoin_get_ontraport_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'ontraport' );
}

add_action( 'wp_ajax_adfoin_save_ontraport_credentials', 'adfoin_save_ontraport_credentials', 10, 0 );

function adfoin_save_ontraport_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'ontraport', array( 'appId', 'apiKey' ) );
}

function adfoin_ontraport_credentials_list() {
    foreach ( adfoin_read_credentials( 'ontraport' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Migrate legacy single-option credentials into the multi-account store.
 */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'ontraport', array(
            'appId'  => 'adfoin_ontraport_app_id',
            'apiKey' => 'adfoin_ontraport_api_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_ontraport_action_fields' );

function adfoin_ontraport_action_fields() {
    ?>
    <script type="text/template" id="ontraport-action-template">
        <table class="form-table" v-if="action.task == 'add_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Ontraport Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=ontraport' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'add_contact', 'Ontraport [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_ontraport_job_queue', 'adfoin_ontraport_job_queue', 10, 1 );

function adfoin_ontraport_job_queue( $data ) {
    adfoin_ontraport_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ontraport_send_data( $record, $posted_data ) {
    if ( 'add_contact' !== ( $record['task'] ?? '' ) ) {
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

    $email = isset( $field_data['email'] ) ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) ) : '';

    if ( ! $email ) {
        return;
    }

    $payload = array(
        'email' => $email,
    );

    $simple = array( 'firstname', 'lastname' );

    foreach ( $simple as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $payload[ $key ] = $value;
        }
    }

    $payload = apply_filters( 'adfoin_ontraport_contact_payload', $payload, $field_data, $posted_data );

    adfoin_ontraport_request( 'Contacts/saveorupdate', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_ontraport_request' ) ) :
/**
 * Call the Ontraport REST API. Form-urlencoded for write methods.
 *
 * @param string $endpoint Path under /1/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_ontraport_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $app_id  = '';
    $api_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'ontraport', $cred_id );
        if ( is_array( $credentials ) ) {
            $app_id  = isset( $credentials['appId'] )  ? trim( (string) $credentials['appId'] )  : '';
            $api_key = isset( $credentials['apiKey'] ) ? trim( (string) $credentials['apiKey'] ) : '';
        }
    }

    if ( ! $app_id ) {
        $app_id = (string) get_option( 'adfoin_ontraport_app_id', '' );
    }
    if ( ! $api_key ) {
        $api_key = (string) get_option( 'adfoin_ontraport_api_key', '' );
    }

    if ( ! $app_id || ! $api_key ) {
        return new WP_Error( 'ontraport_missing_credentials', __( 'Ontraport credentials are not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.ontraport.com/1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Api-Appid' => $app_id,
            'Api-Key'   => $api_key,
            'Accept'    => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $args['body']                    = is_array( $data ) ? http_build_query( $data ) : (string) $data;
    } elseif ( ! empty( $data ) && is_array( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
