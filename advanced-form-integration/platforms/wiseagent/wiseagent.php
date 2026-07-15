<?php

/**
 * Wise Agent — Create Contact via the legacy webconnect.asp "webcontact"
 * request, authenticated with a modern OAuth2 Bearer token (confirmed via
 * Wise Agent's own developer docs at wiseagent.com/docs/api.asp — the
 * previous implementation used a static "key" body param and the wrong
 * request type name "AddNewLeadFromWebsite", neither of which exist in the
 * real API).
 *
 * OAuth endpoints live on sync.wiseagent.com; the actual data endpoint is
 * on the older sync.thewiseagent.com/http/webconnect.asp host — both
 * confirmed directly from the docs page.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 *
 * @link https://wiseagent.com/docs/api.asp
 */

class ADFOIN_WiseAgent extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'wiseagent';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_wiseagent_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_wiseagent_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_wiseagent_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['wiseagent'] = array(
            'title' => __( 'Wise Agent', 'advanced-form-integration' ),
            'tasks' => array(
                'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['wiseagent'] = __( 'Wise Agent', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'wiseagent' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'Client ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Client Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'Request a Wise Agent developer application (Name, logo URL, and Redirect Domain) via their API docs page.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below as your app\'s redirect domain/URL.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret into the Add Account form here, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Wise Agent', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'wiseagent',
            __( 'Wise Agent', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="wiseagent-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'create_contact'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                    <td scope="row-title"><label><?php esc_attr_e( 'Wise Agent Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=wiseagent' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_contact', 'Wise Agent [PRO]', 'custom fields and tags' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/wiseagent', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Wise Agent with ?code=…&state=…. Resolves the
     * saved credential, exchanges the code for tokens, and closes the popup.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'wiseagent' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        if ( ! $code || ! $cred_id ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'OAuth state invalid or expired. Please try again.', 'advanced-form-integration' ) );
            exit;
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'wiseagent' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
                $found = true;
                break;
            }
        }

        if ( ! $found || ! $this->client_id || ! $this->client_secret ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Credential not found or incomplete.', 'advanced-form-integration' ) );
            exit;
        }

        $response = $this->request_token( $code );

        $success = false;
        $message = __( 'Unknown error.', 'advanced-form-integration' );

        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['access_token'] ) ) {
                $success = true;
                $message = __( 'Connected successfully!', 'advanced-form-integration' );
            } else {
                $message = $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' );
            }
        } elseif ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    public function ajax_get_credentials() {
        adfoin_verify_nonce();
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function ajax_save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'wiseagent';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                unset( $credentials[ $index ] );
                adfoin_save_credentials( $platform, array_values( $credentials ) );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] )            ? sanitize_text_field( wp_unslash( $_POST['id'] ) )            : '';
        $title         = isset( $_POST['title'] )         ? sanitize_text_field( wp_unslash( $_POST['title'] ) )         : '';
        $client_id     = isset( $_POST['client_id'] )     ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) )     : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $existing = null;
        foreach ( $credentials as $cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $existing = $cred;
                break;
            }
        }

        if ( $existing ) {
            if ( '' === $client_id && ! empty( $existing['client_id'] ) ) {
                $client_id = $existing['client_id'];
            }
            if ( '' === $client_secret && ! empty( $existing['client_secret'] ) ) {
                $client_secret = $existing['client_secret'];
            }
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

                if ( $same ) {
                    $new_data['access_token']  = $cred['access_token']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? '';
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }
        unset( $cred );

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => 'contacts',
            'state'         => self::issue_oauth_state( 'wiseagent', $id ),
        ), 'https://sync.wiseagent.com/WiseAuth/auth' );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->wiseagent_request( 'webcontact', array(
                'CFirst' => 'Test',
                'CLast'  => 'Connection',
                'Source' => 'AFI Test',
            ) );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/wiseagent' );
    }

    /**
     * Wise Agent's token endpoint takes a JSON body (client_id/client_secret
     * inline, not Basic auth) and returns `expires_at` (an absolute ISO
     * timestamp) rather than the more common `expires_in` seconds-from-now
     * the base class's apply_token_response() expects — so both are
     * overridden here rather than relying on the OAuth2 base defaults.
     */
    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( 'https://sync.wiseagent.com/WiseAuth/token', array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'code'          => $authorization_code,
                'grant_type'    => 'authorization_code',
            ) ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_wiseagent_token_response( $body );
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( 'https://sync.wiseagent.com/WiseAuth/token', array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
            ) ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_wiseagent_token_response( $body );
        }

        $this->save_data();

        return $response;
    }

    protected function apply_wiseagent_token_response( $body ) {
        if ( isset( $body['access_token'] ) && $body['access_token'] !== '' ) {
            $this->access_token = $body['access_token'];
        }
        if ( isset( $body['refresh_token'] ) && $body['refresh_token'] !== '' ) {
            $this->refresh_token = $body['refresh_token'];
        }
        if ( ! empty( $body['expires_at'] ) ) {
            $timestamp = strtotime( $body['expires_at'] );
            if ( $timestamp ) {
                $this->token_expires = $timestamp;
            }
        }
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
        }
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }

        $this->cred_id        = $credentials['id'] ?? $cred_id;
        $this->client_id      = $credentials['client_id']     ?? '';
        $this->client_secret  = $credentials['client_secret'] ?? '';
        $this->access_token   = $credentials['access_token']  ?? '';
        $this->refresh_token  = $credentials['refresh_token'] ?? '';
        $this->token_expires  = isset( $credentials['token_expires'] ) ? (int) $credentials['token_expires'] : 0;

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'wiseagent' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'wiseagent' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    /**
     * All webconnect.asp requests confirmed to take `requestType` plus the
     * method's own form fields as a single POST body, authenticated with
     * `Authorization: Bearer {access_token}` (not the legacy static `key`
     * param the previous implementation used).
     */
    public function wiseagent_request( $request_type, $data = array(), $record = array() ) {
        $data['requestType'] = $request_type;

        $args = array(
            'timeout' => 30,
            'method'  => 'POST',
            'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
            'body'    => http_build_query( $data ),
        );

        return $this->remote_request( 'https://sync.thewiseagent.com/http/webconnect.asp', $args, $record );
    }

    /**
     * Inject Authorization: Bearer and refresh once on 401.
     */
    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;

        $request            = wp_parse_args( $request, array() );
        $request['headers'] = array_merge(
            $request['headers'] ?? array(),
            array( 'Authorization' => $this->get_http_authorization_header( 'bearer' ) )
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        if ( 401 === (int) wp_remote_retrieve_response_code( $response ) && ! $refreshed ) {
            $this->refresh_token();
            $refreshed = true;

            $request['headers']['Authorization'] = $this->get_http_authorization_header( 'bearer' );
            $response = wp_remote_request( esc_url_raw( $url ), $request );
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }
}

ADFOIN_WiseAgent::get_instance();

add_action( 'wp_ajax_adfoin_get_wiseagent_fields', 'adfoin_get_wiseagent_fields' );
function adfoin_get_wiseagent_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => 'Required' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => 'Required' ),
        array( 'key' => 'source',    'value' => 'Source',     'description' => 'Required — used to trigger Wise Agent automations' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'mobile',    'value' => 'Mobile Phone', 'description' => '' ),
        array( 'key' => 'homePhone', 'value' => 'Home Phone', 'description' => '' ),
        array( 'key' => 'workPhone', 'value' => 'Work Phone', 'description' => '' ),
        array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
        array( 'key' => 'address',   'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'category',  'value' => 'Category',   'description' => 'Buyer / Seller / etc.' ),
        array( 'key' => 'rank',      'value' => 'Rank',       'description' => 'A / B / C / D' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

/**
 * Builds the webconnect.asp "webcontact" body. Shared with the Pro tier so
 * both stay in sync with the API's confirmed field names.
 */
function adfoin_wiseagent_build_contact( $fields ) {
    $body = array();
    foreach ( array( 'firstName' => 'CFirst', 'lastName' => 'CLast', 'email' => 'CEmail', 'mobile' => 'MobilePhone', 'homePhone' => 'HomePhone', 'workPhone' => 'WorkPhone', 'company' => 'Company', 'address' => 'AddressStreet', 'city' => 'City', 'state' => 'State', 'zip' => 'zip', 'category' => 'Categories', 'rank' => 'Rank', 'note' => 'Notes' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    $body['Source'] = ! empty( $fields['source'] ) ? $fields['source'] : 'Website Form';
    return $body;
}

add_action( 'adfoin_wiseagent_job_queue', 'adfoin_wiseagent_job_queue', 10, 1 );
function adfoin_wiseagent_job_queue( $data ) {
    adfoin_wiseagent_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_wiseagent_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    if ( ! $cred_id ) return;

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_contact' ) return;

    $wiseagent = ADFOIN_WiseAgent::get_instance();
    if ( ! $wiseagent->set_credentials( $cred_id ) ) return;

    $wiseagent->wiseagent_request( 'webcontact', adfoin_wiseagent_build_contact( $fields ), $record );
}
