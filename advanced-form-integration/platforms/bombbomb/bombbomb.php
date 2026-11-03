<?php

class ADFOIN_BombBomb extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'bombbomb';

    const authorization_endpoint     = 'https://app.bombbomb.com/auth/authorize';
    const token_endpoint             = 'https://app.bombbomb.com/auth/access_token';
    const refresh_token_endpoint     = 'https://app.bombbomb.com/auth/access_token';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Load legacy credentials for backward compatibility
     */
    private function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_bombbomb_keys' ) );

        $this->client_id     = isset($option['client_id']) ? $option['client_id'] : '';
        $this->client_secret = isset($option['client_secret']) ? $option['client_secret'] : '';
        $this->access_token  = isset($option['access_token']) ? $option['access_token'] : '';
        $this->refresh_token = isset($option['refresh_token']) ? $option['refresh_token'] : '';
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_bombbomb_keys', array( $this, 'save_keys' ), 10, 1 );

        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_bombbomb_lists', array( $this, 'get_lists' ) );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_bombbomb_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_bombbomb_credentials', array( $this, 'save_credentials' ) );
        add_action( 'wp_ajax_adfoin_get_bombbomb_fields', array( $this, 'ajax_get_fields' ) );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/bombbomb',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_webhook_data' ),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();

        $code  = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $state = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        if ( $code ) {

            $redirect_to = add_query_arg(
                array_filter( [
                    'service' => 'authorize',
                    'action'  => 'adfoin_bombbomb_auth_redirect',
                    'code'    => rawurlencode( $code ),
                    'state'   => rawurlencode( $state ),
                ] ),
                admin_url( 'admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function actions( $actions ) {
        $actions['bombbomb'] = array(
            'title' => __( 'BombBomb', 'advanced-form-integration' ),
            'tasks' => array( 'add_contact' => __( 'Add Contact', 'advanced-form-integration' ) )
        );
        return $actions;
    }

    public function settings_tab( $providers ) {
        $providers['bombbomb'] = __( 'BombBomb', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( $current_tab !== 'bombbomb' ) return;

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager. Field names are the storage keys —
        // snake_case is the canonical shape the read shim normalizes legacy
        // camelCase records to.
        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'Client ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'placeholder'   => __( 'Enter your Client ID', 'advanced-form-integration' ),
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Client Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'placeholder'   => __( 'Enter your Client Secret', 'advanced-form-integration' ),
                'show_in_table' => false,
            ),
        );

        $instructions = sprintf(
            '<p>%s</p><p><strong>%s:</strong> %s</p>',
            __( 'To connect your BombBomb account, you need to create an OAuth application.', 'advanced-form-integration' ),
            __( 'Redirect URI', 'advanced-form-integration' ),
            $redirect_uri
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'bombbomb', __( 'BombBomb', 'advanced-form-integration' ), $fields, $instructions );
    }

    public function save_keys() {
        adfoin_require_manage_options();
        if (! wp_verify_nonce($_POST['_nonce'], 'adfoin_bombbomb_settings')) {
            die(__('Security check failed.', 'advanced-form-integration'));
        }

        $this->client_id     = sanitize_text_field(isset($_POST['client_id']) ? $_POST['client_id'] : '');
        $this->client_secret = sanitize_text_field(isset($_POST['client_secret']) ? $_POST['client_secret'] : '');
        $this->save_data();

        $this->authorize('all:manage');
    }

    public function auth_redirect() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'adfoin_bombbomb_auth_redirect') return;

        // admin_init fires for every logged-in user; only an admin should
        // be able to complete this OAuth flow (CWE-862).
        if ( ! current_user_can( 'manage_options' ) ) return;

        $code    = isset($_GET['code']) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        $state   = isset($_GET['state']) ? trim( wp_unslash( $_GET['state'] ) ) : '';
        $context = self::consume_oauth_state( $state, 'bombbomb' );

        if ($code && $context) {
            // Account added or reconnected from the accounts screen: the state
            // was issued by save_credentials() for this credential.
            $this->set_credentials( $context['cred_id'] );
            $response = $this->request_token($code);
            $ok       = ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );

            if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            }

            ADFOIN_OAuth_Manager::handle_callback_close_popup(
                $ok,
                $ok ? __( 'BombBomb connected.', 'advanced-form-integration' ) : __( 'BombBomb did not accept the authorization. Check the Client ID, Client Secret and Redirect URI.', 'advanced-form-integration' )
            );
            exit;
        }

        if ($code) {
            // Legacy single-account callback (no state).
            $this->request_token($code);
        }

        wp_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=bombbomb'));
        exit;
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/bombbomb');
    }

    protected function authorize($scope = '') {
        $args = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => $scope
        );
        wp_redirect(add_query_arg($args, self::authorization_endpoint));
        exit;
    }

    protected function request_token($authorization_code) {
        $response = wp_remote_post(self::token_endpoint, array(
            'body' => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code'
            )
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->apply_token_response( $body );
        $this->save_data();
        return $response;
    }

    protected function refresh_token() {
        $response = wp_remote_post(self::refresh_token_endpoint, array(
            'body' => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token'
            )
        ));
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->apply_token_response( $body );
        $this->save_data();
        return $response;
    }

    protected function save_data() {
        // Multi-account flow: persist tokens into the credential record
        // identified by cred_id. The camelCase token keys mirror what
        // bombbomb's existing code reads via set_credentials() — so refreshed
        // tokens land where the next API call will pick them up.
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
            return;
        }

        // Legacy single-account fallback for installs that haven't migrated
        // through the OAuth Manager UI yet.
        update_option( 'adfoin_bombbomb_keys', maybe_serialize( array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ) ) );
    }

    /**
     * Surface a legacy single-account credential set as a `legacy_*` record
     * when the multi-account store is otherwise empty. Mirrors the pattern
     * used by ADFOIN_Account_Manager::register_legacy_option_importer for
     * non-OAuth platforms; per-platform here because OAuth records carry
     * extra fields beyond what that helper handles.
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'bombbomb' !== $platform ) {
            return $credentials;
        }
        foreach ( (array) $credentials as $cred ) {
            if ( isset( $cred['id'] ) && 'legacy_123456' === $cred['id'] ) {
                return $credentials;
            }
        }
        $option = (array) maybe_unserialize( get_option( 'adfoin_bombbomb_keys' ) );
        if ( empty( $option['client_id'] ) || empty( $option['client_secret'] ) ) {
            return $credentials;
        }
        $credentials[] = array(
            'id'            => 'legacy_123456',
            'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
            'client_id'     => $option['client_id'],
            'client_secret' => $option['client_secret'],
            'access_token'  => isset( $option['access_token'] )  ? $option['access_token']  : '',
            'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
        );
        return $credentials;
    }

    public function is_active() {
        return !empty($this->access_token);
    }

    public function bombbomb_request($endpoint, $method = 'GET', $data = array(), $record = array()) {
        $base_url = 'https://api.bombbomb.com/v2/';

        $url = $base_url . $endpoint;

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->access_token,
            ),
        );

        if ('POST' === $method || 'PUT' === $method) {
            if ($data) {
                $args['body'] = wp_json_encode($data);
            }
        }

        $response = $this->remote_request_with_refresh($url, $args, $record);

        return $response;
    }

    protected function remote_request_with_refresh($url, $request = array(), $record = array()) {
        // Shared refresh-and-retry: refreshes once per call on 401 and
        // rebuilds the Authorization header from the new token.
        $response = $this->request_with_token_refresh($url, $request, null, $sent_args);

        if ($record) {
            adfoin_add_to_log($response, $url, self::args_for_log($sent_args), $record);
        }

        return $response;
    }

    public static function args_for_log( $args ) {
        if ( isset( $args['headers']['Authorization'] ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . substr( (string) $args['headers']['Authorization'], 7, 4 ) . '****';
        }

        return $args;
    }

    public function get_lists() {
        adfoin_require_manage_options();
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        if ( empty( $this->access_token ) ) {
            wp_send_json_error( __( 'This BombBomb account is not connected. Open AFI Settings > BombBomb and reconnect it.', 'advanced-form-integration' ) );
        }

        $response = $this->bombbomb_request( 'lists/' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( sprintf( __( 'Could not load lists: %s', 'advanced-form-integration' ), $response->get_error_message() ) );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code || 403 === $code ) {
            wp_send_json_error( __( 'BombBomb rejected the login for this account. Open AFI Settings > BombBomb and reconnect it.', 'advanced-form-integration' ) );
        }

        if ( $code >= 300 || isset( $body['error'] ) ) {
            $reason = is_array( $body ) ? ( $body['message'] ?? $body['error_description'] ?? ( is_string( $body['error'] ?? null ) ? $body['error'] : '' ) ) : '';
            wp_send_json_error( sprintf( __( 'Could not load lists (HTTP %1$d) %2$s', 'advanced-form-integration' ), $code, $reason ) );
        }

        $lists = array();

        if(is_array($body) && !empty($body)) {
            foreach ( $body as $list ) {
                if ( isset( $list['id'], $list['name'] ) ) {
                    $lists[$list['id']] = $list['name'];
                }
            }
        }

        wp_send_json_success( $lists );
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='bombbomb-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'add_contact'">
                    <th scope='row'>
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope='row'>
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php esc_attr_e( 'BombBomb Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadFields">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=bombbomb' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php esc_attr_e( 'List', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=''> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                            <option v-for='(item, index) in fielddata.lists' :value='index' > {{item}}  </option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                        <p class="description" style="color: #b32d2e;" v-if="listError">{{ listError }}</p>
                    </td>
                </tr>

                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>
            </table>
        </script>
        <?php
    }

    /**
     * OAuth Manager credential management methods
     */
    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'bombbomb';
        $credentials = adfoin_read_credentials( $platform );

        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );

            if ( ! isset( $credentials[ $index ] ) ) {
                wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
            }

            if ( isset( $credentials[ $index ]['id'] ) && 'legacy_123456' === $credentials[ $index ]['id'] ) {
                delete_option( 'adfoin_bombbomb_keys' );
            }

            array_splice( $credentials, $index, 1 );
            adfoin_save_credentials( $platform, $credentials );
            wp_send_json_success( array( 'message' => 'Deleted' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';
        $existing_key  = null;

        foreach ( $credentials as $key => $cred ) {
            if ( '' !== $id && isset( $cred['id'] ) && $cred['id'] === $id ) {
                $existing_key = $key;
                break;
            }
        }

        $existing = null === $existing_key ? array() : $credentials[ $existing_key ];

        // The edit form may come back without the secret; keep the saved one.
        if ( '' === $client_id ) {
            $client_id = $existing['client_id'] ?? $existing['clientId'] ?? '';
        }
        if ( '' === $client_secret ) {
            $client_secret = $existing['client_secret'] ?? $existing['clientSecret'] ?? '';
        }

        if ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        if ( '' === $id ) {
            $id = wp_generate_uuid4();
        }

        $record = array_merge( $existing, array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ) );

        // Tokens from a different app can't be refreshed with the new keys.
        if ( ( $existing['client_id'] ?? $existing['clientId'] ?? '' ) !== $client_id ) {
            $record['access_token']  = '';
            $record['refresh_token'] = '';
        }

        unset( $record['clientId'], $record['clientSecret'], $record['accessToken'], $record['refreshToken'] );

        if ( null === $existing_key ) {
            $credentials[] = $record;
        } else {
            $credentials[ $existing_key ] = $record;
        }

        adfoin_save_credentials( $platform, $credentials );

        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => rawurlencode( $client_id ),
                'redirect_uri'  => rawurlencode( $this->get_redirect_uri() ),
                'scope'         => rawurlencode( 'all:manage' ),
                'state'         => self::issue_oauth_state( $platform, $id ),
            ),
            self::authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = array(
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'firstname', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'lastname', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'phone_number', 'value' => __( 'Phone Number', 'advanced-form-integration' ) ),
            array( 'key' => 'address', 'value' => __( 'Address', 'advanced-form-integration' ) ),
            array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
            array( 'key' => 'position', 'value' => __( 'Position', 'advanced-form-integration' ) ),
        );

        wp_send_json_success( $fields );
    }

    /**
     * Set credentials for OAuth Manager — populates `$this` from the
     * credential record matching `$cred_id`. Setting `$this->cred_id` is
     * essential: it tells `save_data()` (called from a later refresh) which
     * record to write back to.
     */
    public function set_credentials( $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'bombbomb', $cred_id );

        if ( $credentials ) {
            $this->cred_id       = $cred_id;
            $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
            $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
            $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
            $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        }
    }
}

ADFOIN_BombBomb::get_instance();

add_action( 'adfoin_bombbomb_job_queue', 'adfoin_bombbomb_job_queue', 10, 1 );

function adfoin_bombbomb_job_queue( $data ) {
    adfoin_bombbomb_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to BombBomb API
 */
function adfoin_bombbomb_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    if( $task == 'add_contact' ) {
        $bombbomb = ADFOIN_BombBomb::get_instance();

        // Set credentials if provided
        if ( $cred_id ) {
            $bombbomb->set_credentials( $cred_id );
        }

        $contact_data = array();

        foreach ( $data as $key => $value ) {
            if ( is_string( $value ) ) {
                $contact_data[$key] = trim( (string) adfoin_get_parsed_values( $value, $posted_data ) );
            }
        }

        $list_id = isset( $contact_data['listId']) ? $contact_data['listId'] : '';
        unset( $contact_data['listId'] );
        unset( $contact_data['credId'] );

        // The editor saves these under its own keys; older integrations may
        // carry the API names, so accept either.
        $editor_keys = array(
            'first_name'     => 'firstname',
            'last_name'      => 'lastname',
            'address_line_1' => 'address',
            'business_name'  => 'company',
        );

        foreach ( $editor_keys as $api_key => $editor_key ) {
            if ( ( ! isset( $contact_data[ $api_key ] ) || '' === $contact_data[ $api_key ] ) && isset( $contact_data[ $editor_key ] ) ) {
                $contact_data[ $api_key ] = $contact_data[ $editor_key ];
            }
        }

        $raw_email = isset( $contact_data['email'] ) ? $contact_data['email'] : '';
        $contact_email = sanitize_email( $raw_email );

        if ( ! $contact_email ) {
            adfoin_bombbomb_log_skipped(
                $record,
                '' === $raw_email
                    ? __( 'Not sent: the mapped email field is empty.', 'advanced-form-integration' )
                    : sprintf( __( 'Not sent: "%s" is not a valid email address.', 'advanced-form-integration' ), $raw_email )
            );
            return;
        }

        if ( ! $bombbomb->is_active() ) {
            adfoin_bombbomb_log_skipped( $record, __( 'Not sent: the BombBomb account is not connected. Reconnect it under AFI Settings > BombBomb.', 'advanced-form-integration' ) );
            return;
        }

        $contact_info = array(
            'formValues' => array(
                'basicInfo' => array_filter(array(
                    'first_name'     => isset($contact_data['first_name']) ? $contact_data['first_name'] : '',
                    'last_name'      => isset($contact_data['last_name']) ? $contact_data['last_name'] : '',
                    'email'          => $contact_email,
                    'address_line_1' => isset($contact_data['address_line_1']) ? $contact_data['address_line_1'] : '',
                    'address_line_2' => isset($contact_data['address_line_2']) ? $contact_data['address_line_2'] : '',
                    'city'           => isset($contact_data['city']) ? $contact_data['city'] : '',
                    'state'          => isset($contact_data['state']) ? $contact_data['state'] : '',
                    'country'        => isset($contact_data['country']) ? $contact_data['country'] : '',
                    'postal_code'    => isset($contact_data['postal_code']) ? $contact_data['postal_code'] : '',
                    'phone_number'   => isset($contact_data['phone_number']) ? $contact_data['phone_number'] : '',
                    'business_name'  => isset($contact_data['business_name']) ? $contact_data['business_name'] : '',
                    'position'       => isset($contact_data['position']) ? $contact_data['position'] : '',
                    'comments'       => isset($contact_data['comments']) ? $contact_data['comments'] : '',
                ))
            ),
        );

        $payload = array(
            'contactEmail' => $contact_email,
            'contactInfo'  => wp_json_encode($contact_info),
        );

        $response = $bombbomb->bombbomb_request('contacts/', 'POST', $payload, $record);

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['success']) && $response_body['success'] === true) {
            $contact_id = isset($response_body['id']) ? $response_body['id'] : '';
            if (!empty($list_id) && !empty($contact_id)) {
                $list_payload = array(
                    'ids' => array( $contact_id ),
                );

                $bombbomb->bombbomb_request("lists/{$list_id}/contacts", 'POST', $list_payload, $record);
            }
        }
    }

    return;
}

function adfoin_bombbomb_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'bombbomb_not_sent', $message ), 'https://api.bombbomb.com/v2/contacts/', array( 'method' => 'POST' ), $record );
}
