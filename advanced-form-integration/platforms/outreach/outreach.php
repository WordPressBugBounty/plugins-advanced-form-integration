<?php

/**
 * Outreach — Create Prospect via POST /api/v2/prospects (JSON:API).
 *
 * OAuth2 authorization-code flow handled by AFI's OAuth Manager — the user
 * only enters Client ID + Client Secret, then clicks "Save & Authorize" to
 * trigger the popup. Refresh tokens are exchanged, rotated, and persisted
 * automatically per credential.
 *
 * @link https://developers.outreach.io/api/
 */

class ADFOIN_Outreach extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'outreach';

    const authorization_endpoint = 'https://api.outreach.io/oauth/authorize';
    const token_endpoint         = 'https://api.outreach.io/oauth/token';

    // Scopes required for create-prospect (free) and add-to-sequence (Pro).
    const oauth_scopes = 'prospects.read prospects.write sequenceStates.read sequenceStates.write mailboxes.read sequences.read';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::token_endpoint;

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'render_settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'render_action_fields' ) );

        add_action( 'adfoin_outreach_job_queue', array( $this, 'job_queue' ), 10, 1 );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_outreach_credentials', array( $this, 'ajax_get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_outreach_credentials', array( $this, 'ajax_save_credentials' ) );
    }

    public function register_actions( $actions ) {
        $actions['outreach'] = array(
            'title' => __( 'Outreach', 'advanced-form-integration' ),
            'tasks' => array(
                'add_to_list' => __( 'Create Prospect', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['outreach'] = __( 'Outreach', 'advanced-form-integration' );
        return $providers;
    }

    public function render_settings_view( $current_tab ) {
        if ( 'outreach' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'clientId',
                'label'         => __( 'Client ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'clientSecret',
                'label'         => __( 'Client Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => false,
            ),
        );

        $instructions = sprintf(
            '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol><div style="margin-top: 15px;"><strong>%s:</strong><div style="margin-top: 8px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; font-family: monospace; font-size: 12px; line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word;">%s</div></div>',
            sprintf(
                /* translators: %s: link to Outreach OAuth apps page */
                __( 'Open %s and create a new OAuth app.', 'advanced-form-integration' ),
                '<a href="https://web.outreach.io/oauth_applications/new" target="_blank" rel="noopener noreferrer">Outreach → Settings → OAuth Applications</a>'
            ),
            __( 'Paste the Redirect URI below into the app\'s redirect URIs list.', 'advanced-form-integration' ),
            __( 'Grant these scopes: <code>prospects.read prospects.write sequenceStates.read sequenceStates.write mailboxes.read sequences.read</code>.', 'advanced-form-integration' ),
            __( 'Copy the resulting Client ID + Client Secret into the form and click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ),
            __( 'Redirect URI', 'advanced-form-integration' ),
            esc_html( $redirect_uri )
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'outreach', __( 'Outreach', 'advanced-form-integration' ), $fields, $instructions );
    }

    public function render_action_fields() {
        ?>
        <script type="text/template" id="outreach-action-template">
            <table class="form-table" v-if="action.task == 'add_to_list'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Outreach Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=outreach' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
                <?php adfoin_pro_feature_notice( 'add_to_list', 'Outreach [PRO]', 'custom fields and tags' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/outreach', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        if ( $code ) {
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_outreach_auth_redirect',
                    'code'    => $code,
                    'state'   => $state,
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            );
            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '';
        $code   = isset( $_GET['code'] )   ? trim( $_GET['code'] )   : '';
        $state  = isset( $_GET['state'] )  ? trim( $_GET['state'] )  : '';

        if ( 'adfoin_outreach_auth_redirect' !== $action || ! $code ) {
            return;
        }

        if ( strpos( $state, 'oauth_manager_' ) === 0 ) {
            $cred_id = str_replace( 'oauth_manager_', '', $state );
            $this->handle_oauth_manager_callback( $code, $cred_id );

            if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            }

            $success = ! empty( $this->access_token );
            $message = $success
                ? __( 'Authorization successful!', 'advanced-form-integration' )
                : __( 'Authorization failed. Please try again.', 'advanced-form-integration' );

            ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        }
    }

    private function handle_oauth_manager_callback( $code, $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'outreach', $cred_id );
        if ( ! $credentials ) {
            return;
        }

        // Setting cred_id BEFORE request_token routes the new tokens into
        // the right credential record automatically via save_data().
        $this->cred_id       = $cred_id;
        $this->client_id     = $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['clientSecret'] ?? '';

        $this->request_token( $code );
    }

    public function ajax_get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function ajax_save_credentials() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $cred_id       = isset( $_POST['id'] )           ? sanitize_text_field( wp_unslash( $_POST['id'] ) )           : '';
        $title         = isset( $_POST['title'] )        ? sanitize_text_field( wp_unslash( $_POST['title'] ) )        : '';
        $client_id     = isset( $_POST['clientId'] )     ? sanitize_text_field( wp_unslash( $_POST['clientId'] ) )     : '';
        $client_secret = isset( $_POST['clientSecret'] ) ? sanitize_text_field( wp_unslash( $_POST['clientSecret'] ) ) : '';

        if ( empty( $client_id ) || empty( $client_secret ) ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        $credentials = array(
            'title'        => $title,
            'clientId'     => $client_id,
            'clientSecret' => $client_secret,
        );

        if ( ! empty( $cred_id ) ) {
            $credentials['id'] = $cred_id;
        }

        ADFOIN_OAuth_Manager::save_credentials( 'outreach', $credentials );

        if ( empty( $cred_id ) ) {
            $cred_id = $credentials['id'];
        }

        $state    = 'oauth_manager_' . $cred_id;
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'scope'         => urlencode( self::oauth_scopes ),
                'state'         => $state,
            ),
            self::authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function set_credentials( $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'outreach', $cred_id );
        if ( ! $credentials ) {
            return false;
        }

        $this->cred_id       = $cred_id;
        $this->client_id     = $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['accessToken']  ?? '';
        $this->refresh_token = $credentials['refreshToken'] ?? '';
        $this->token_expires = isset( $credentials['tokenExpires'] ) ? (int) $credentials['tokenExpires'] : 0;

        return true;
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/outreach' );
    }

    /**
     * Override base request_token: Outreach expects form params in the POST
     * body (not the query string).
     */
    protected function request_token( $authorization_code ) {
        $body = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->get_redirect_uri(),
            'grant_type'    => 'authorization_code',
            'code'          => $authorization_code,
        );

        $response = wp_remote_post( self::token_endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'body'    => $body,
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $response_code && ! empty( $response_body['access_token'] ) ) {
            $this->apply_token_response( $response_body );
        } else {
            $this->access_token  = null;
            $this->refresh_token = null;
        }

        $this->save_data();

        return $response;
    }

    /**
     * Override base refresh_token: same reason — Outreach expects form
     * params in the body, and the refresh_token rotates on every call.
     */
    protected function refresh_token() {
        $body = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
            'redirect_uri'  => $this->get_redirect_uri(),
        );

        $response = wp_remote_post( self::token_endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'body'    => $body,
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $response_code && ! empty( $response_body['access_token'] ) ) {
            $this->apply_token_response( $response_body );
        } elseif ( 401 === $response_code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            if ( method_exists( $this, 'mark_connection_failed' ) ) {
                $this->mark_connection_failed( 'refresh_token_revoked' );
            }
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
        }
    }

    /**
     * Make an authenticated JSON:API call to Outreach. Used by the
     * dispatcher and by the Pro overlay (via adfoin_outreach_request()).
     *
     * @return array|WP_Error
     */
    public function request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = 'https://api.outreach.io/api/v2/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Accept' => 'application/vnd.api+json',
            ),
        );

        if ( 'GET' === $method ) {
            if ( is_array( $data ) && ! empty( $data ) ) {
                $url = add_query_arg( $data, $url );
            }
        } else {
            $args['headers']['Content-Type'] = 'application/vnd.api+json';
            $args['body']                    = wp_json_encode( $data );
        }

        $response = $this->remote_request( $url, $args );

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        return $response;
    }

    public function job_queue( $data ) {
        $this->send_data( $data['record'], $data['posted_data'] );
    }

    public function send_data( $record, $posted_data ) {
        if ( 'add_to_list' !== ( $record['task'] ?? '' ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

        if ( ! $cred_id || ! $this->set_credentials( $cred_id ) ) {
            return;
        }

        $email = isset( $field_data['email'] )
            ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
            : '';

        if ( ! $email ) {
            return;
        }

        $attributes = array( 'emails' => array( $email ) );

        $simple = array( 'firstName', 'lastName', 'jobTitle', 'company', 'workPhone', 'mobilePhone', 'city', 'state', 'country' );

        foreach ( $simple as $key ) {
            if ( empty( $field_data[ $key ] ) ) {
                continue;
            }
            $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
            if ( '' !== $value ) {
                $attributes[ $key ] = $value;
            }
        }

        $payload = array(
            'data' => array(
                'type'       => 'prospect',
                'attributes' => apply_filters( 'adfoin_outreach_prospect_attributes', $attributes, $field_data, $posted_data ),
            ),
        );

        $this->request( 'prospects', 'POST', $payload, $record );
    }
}

ADFOIN_Outreach::get_instance();

/**
 * Public function wrapper so the Pro overlay can issue authenticated calls
 * against the same credential without re-implementing the OAuth machinery.
 */
if ( ! function_exists( 'adfoin_outreach_request' ) ) :
function adfoin_outreach_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $instance = ADFOIN_Outreach::get_instance();

    if ( $cred_id && ! $instance->set_credentials( $cred_id ) ) {
        return new WP_Error( 'outreach_missing_credentials', __( 'Outreach credentials not found.', 'advanced-form-integration' ) );
    }

    return $instance->request( $endpoint, $method, $data, $record );
}
endif;
