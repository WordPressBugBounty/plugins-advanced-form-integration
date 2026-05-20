<?php

/**
 * SharpSpring (Constant Contact Lead Gen & CRM) — Create or Update Lead
 * via the JSON-RPC `createOrUpdateLeads` method on /pubapi/v1/.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: accountID + secretKey as query string parameters.
 *
 * @link https://help.sharpspring.com/hc/en-us/articles/115002372912-Understanding-SharpSpring-Open-API-Example-Code
 */

add_filter( 'adfoin_action_providers', 'adfoin_sharpspring_actions', 10, 1 );

function adfoin_sharpspring_actions( $actions ) {

    $actions['sharpspring'] = array(
        'title' => __( 'SharpSpring', 'advanced-form-integration' ),
        'tasks' => array(
            'add_lead' => __( 'Create or Update Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sharpspring_settings_tab', 10, 1 );

function adfoin_sharpspring_settings_tab( $providers ) {
    $providers['sharpspring'] = __( 'SharpSpring', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sharpspring_settings_view', 10, 1 );

function adfoin_sharpspring_settings_view( $current_tab ) {
    if ( 'sharpspring' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'accountId',
            'label'         => __( 'Account ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'secretKey',
            'label'         => __( 'Secret Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s: link to SharpSpring API settings */
            esc_html__( 'In %s, copy your Account ID and Secret Key.', 'advanced-form-integration' ),
            '<a href="https://app.sharpspring.com/app/settings/account/apiSettings.jsf" target="_blank" rel="noopener noreferrer">Settings → API Settings</a>'
        ),
        esc_html__( 'Paste them below. AFI calls api.sharpspring.com/pubapi/v1/ with both keys as query parameters. (Account is now branded as Constant Contact Lead Gen & CRM; the API path is unchanged.)', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'sharpspring', __( 'SharpSpring', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_sharpspring_credentials', 'adfoin_get_sharpspring_credentials', 10, 0 );

function adfoin_get_sharpspring_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sharpspring' );
}

add_action( 'wp_ajax_adfoin_save_sharpspring_credentials', 'adfoin_save_sharpspring_credentials', 10, 0 );

function adfoin_save_sharpspring_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sharpspring', array( 'accountId', 'secretKey' ) );
}

if ( ! function_exists( 'adfoin_sharpspring_credentials_list' ) ) :
function adfoin_sharpspring_credentials_list() {
    foreach ( adfoin_read_credentials( 'sharpspring' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

/**
 * Migrate legacy single-option credentials into the multi-account store.
 */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'sharpspring', array(
            'accountId' => 'adfoin_sharpspring_account_id',
            'secretKey' => 'adfoin_sharpspring_secret_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_sharpspring_action_fields' );

function adfoin_sharpspring_action_fields() {
    ?>
    <script type="text/template" id="sharpspring-action-template">
        <table class="form-table" v-if="action.task == 'add_lead'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SharpSpring Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sharpspring' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'add_lead', 'SharpSpring [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_sharpspring_job_queue', 'adfoin_sharpspring_job_queue', 10, 1 );

function adfoin_sharpspring_job_queue( $data ) {
    adfoin_sharpspring_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sharpspring_send_data( $record, $posted_data ) {
    if ( 'add_lead' !== ( $record['task'] ?? '' ) ) {
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

    $email = isset( $field_data['email'] )
        ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
        : '';

    if ( ! $email ) {
        return;
    }

    $lead = array( 'emailAddress' => $email );

    foreach ( array( 'firstName', 'lastName' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $lead[ $key ] = $value;
        }
    }

    $lead = apply_filters( 'adfoin_sharpspring_lead', $lead, $field_data, $posted_data );

    adfoin_sharpspring_request( 'createOrUpdateLeads', array(
        'objects' => array( $lead ),
        'idField' => 'emailAddress',
    ), $record, $cred_id );
}

if ( ! function_exists( 'adfoin_sharpspring_request' ) ) :
/**
 * Call the SharpSpring JSON-RPC API. accountID + secretKey are sent as
 * query parameters; the body is a raw JSON-RPC envelope.
 *
 * @param string $method  JSON-RPC method name.
 * @param array  $params  Method parameters.
 * @param array  $record  Submission record for logging.
 * @param string $cred_id Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_sharpspring_request( $method, $params = array(), $record = array(), $cred_id = '' ) {
    $account_id = '';
    $secret_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'sharpspring', $cred_id );
        if ( is_array( $credentials ) ) {
            $account_id = isset( $credentials['accountId'] ) ? trim( (string) $credentials['accountId'] ) : '';
            $secret_key = isset( $credentials['secretKey'] ) ? trim( (string) $credentials['secretKey'] ) : '';
        }
    }

    if ( ! $account_id ) {
        $account_id = (string) get_option( 'adfoin_sharpspring_account_id', '' );
    }
    if ( ! $secret_key ) {
        $secret_key = (string) get_option( 'adfoin_sharpspring_secret_key', '' );
    }

    if ( ! $account_id || ! $secret_key ) {
        return new WP_Error( 'sharpspring_missing_credentials', __( 'SharpSpring credentials are missing.', 'advanced-form-integration' ) );
    }

    $url = add_query_arg(
        array(
            'accountID' => $account_id,
            'secretKey' => $secret_key,
        ),
        'https://api.sharpspring.com/pubapi/v1/'
    );

    $body = array(
        'method' => $method,
        'params' => $params,
        'id'     => uniqid( 'adfoin_', true ),
    );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
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
