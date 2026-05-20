<?php

/**
 * Softr — Create a Softr Database record via
 * POST https://tables-api.softr.io/api/v1/databases/{databaseId}/tables/{tableId}/records.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Softr-Api-Key header (Personal Access Token). Optional Softr-Domain
 * is used by the Pro overlay's user-management tasks.
 *
 * @link https://docs.softr.io/softr-api/api-setup-and-endpoints
 * @link https://docs.softr.io/softr-api/softr-database-api
 */

add_filter( 'adfoin_action_providers', 'adfoin_softr_actions', 10, 1 );

function adfoin_softr_actions( $actions ) {
    $actions['softr'] = array(
        'title' => __( 'Softr', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Database Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_softr_settings_tab', 10, 1 );

function adfoin_softr_settings_tab( $providers ) {
    $providers['softr'] = __( 'Softr', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_softr_settings_view', 10, 1 );

function adfoin_softr_settings_view( $current_tab ) {
    if ( 'softr' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key (PAT)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Softr Studio → Settings → API & Embed', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'domain',
            'label'         => __( 'Softr Domain (only required for AFI Pro user-management tasks)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'placeholder'   => 'yourapp.softr.app',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Softr Studio open Settings → API & Embed and generate a Personal Access Token.', 'advanced-form-integration' ),
        esc_html__( 'Paste it as the API Key below. AFI calls tables-api.softr.io/api/v1/ with Softr-Api-Key for database tasks.', 'advanced-form-integration' ),
        esc_html__( 'Domain is only needed for user-management actions in AFI Pro (Create User / Invite / Magic Link).', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'softr', __( 'Softr', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_softr_credentials', 'adfoin_get_softr_credentials', 10, 0 );

function adfoin_get_softr_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'softr' );
}

add_action( 'wp_ajax_adfoin_save_softr_credentials', 'adfoin_save_softr_credentials', 10, 0 );

function adfoin_save_softr_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'softr', array( 'apiKey', 'domain' ) );
}

if ( ! function_exists( 'adfoin_softr_credentials_list' ) ) :
function adfoin_softr_credentials_list() {
    foreach ( adfoin_read_credentials( 'softr' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

add_action( 'adfoin_action_fields', 'adfoin_softr_action_fields' );

function adfoin_softr_action_fields() {
    ?>
    <script type="text/template" id="softr-action-template">
        <table class="form-table" v-if="action.task == 'create_record'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Softr Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=softr' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Database ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[databaseId]" v-model="fielddata.databaseId">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Table ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[tableId]" v-model="fielddata.tableId">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Fields', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <table>
                        <tr v-for="(row, index) in fielddata.customFields" :key="index">
                            <td>
                                <input type="text" :name="'fieldData[customFields]['+index+'][key]'" v-model="row.key" placeholder="<?php esc_attr_e( 'Field name or id', 'advanced-form-integration' ); ?>">
                            </td>
                            <td>
                                <input type="text" :name="'fieldData[customFields]['+index+'][value]'" v-model="row.value" placeholder="<?php esc_attr_e( 'Form tag or text', 'advanced-form-integration' ); ?>">
                            </td>
                            <td>
                                <button type="button" class="button" @click="removeCustomField(index)">&times;</button>
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="button" @click="addCustomField"><?php esc_html_e( '+ Add Field', 'advanced-form-integration' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Use the exact field name (or field ID) as it appears in your Softr Database table.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <?php adfoin_pro_feature_notice( 'create_record', 'Softr [PRO]', 'user management actions' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_softr_job_queue', 'adfoin_softr_job_queue', 10, 1 );

function adfoin_softr_job_queue( $data ) {
    adfoin_softr_send_record( $data['record'], $data['posted_data'] );
}

function adfoin_softr_send_record( $record, $posted_data ) {
    if ( 'create_record' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data  = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id     = isset( $field_data['credId'] )     ? $field_data['credId']     : '';
    $database_id = isset( $field_data['databaseId'] ) ? trim( (string) $field_data['databaseId'] ) : '';
    $table_id    = isset( $field_data['tableId'] )    ? trim( (string) $field_data['tableId'] )    : '';

    if ( ! $cred_id || ! $database_id || ! $table_id ) {
        return;
    }

    $fields = array();

    if ( ! empty( $field_data['customFields'] ) && is_array( $field_data['customFields'] ) ) {
        foreach ( $field_data['customFields'] as $row ) {
            $key = isset( $row['key'] )   ? trim( (string) $row['key'] )   : '';
            $val = isset( $row['value'] ) ? (string) $row['value']         : '';
            if ( '' === $key ) {
                continue;
            }
            $parsed = adfoin_get_parsed_values( $val, $posted_data );
            if ( '' !== $parsed && null !== $parsed ) {
                $fields[ $key ] = $parsed;
            }
        }
    }

    if ( empty( $fields ) ) {
        return;
    }

    $payload = apply_filters(
        'adfoin_softr_record_payload',
        array( 'fields' => $fields ),
        $field_data,
        $posted_data
    );

    adfoin_softr_request(
        'databases/' . rawurlencode( $database_id ) . '/tables/' . rawurlencode( $table_id ) . '/records',
        'POST',
        $payload,
        $record,
        $cred_id,
        'tables'
    );
}

if ( ! function_exists( 'adfoin_softr_request' ) ) :
/**
 * Call a Softr REST API. Tables vs studio share the API key but use
 * different host and version prefixes.
 *
 * @param string $endpoint Path under the version prefix.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 * @param string $surface  'tables' (default) or 'studio' (user management).
 *
 * @return array|WP_Error
 */
function adfoin_softr_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $surface = 'tables' ) {
    $api_key = '';
    $domain  = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'softr', $cred_id );
        if ( is_array( $credentials ) ) {
            $api_key = isset( $credentials['apiKey'] ) ? trim( (string) $credentials['apiKey'] ) : '';
            $domain  = isset( $credentials['domain'] ) ? trim( (string) $credentials['domain'] ) : '';
        }
    }

    if ( ! $api_key ) {
        return new WP_Error( 'softr_missing_key', __( 'Softr API key is missing.', 'advanced-form-integration' ) );
    }

    $base = ( 'studio' === $surface )
        ? 'https://studio-api.softr.io/v1/api/'
        : 'https://tables-api.softr.io/api/v1/';

    $url    = $base . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Softr-Api-Key' => $api_key,
            'Accept'        => 'application/json',
        ),
    );

    if ( 'studio' === $surface ) {
        if ( ! $domain ) {
            return new WP_Error( 'softr_missing_domain', __( 'Softr Domain is required for user-management tasks. Set it on the saved account.', 'advanced-form-integration' ) );
        }
        $args['headers']['Softr-Domain'] = $domain;
    }

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
