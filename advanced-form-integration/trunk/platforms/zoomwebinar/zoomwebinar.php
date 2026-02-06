<?php

if ( ! class_exists( 'ADFoin_Zoom_Webinar' ) ) :

class ADFoin_Zoom_Webinar extends Advanced_Form_Integration_OAuth2 {

    const AUTHORIZATION_ENDPOINT = 'https://zoom.us/oauth/authorize';
    const TOKEN_ENDPOINT         = 'https://zoom.us/oauth/token';
    const API_BASE               = 'https://api.zoom.us/v2';
    const DEFAULT_SCOPE          = 'webinar:write:admin webinar:read:admin';

    private static $instance;

    protected $client_id     = '';
    protected $client_secret = '';
    protected $access_token  = '';
    protected $refresh_token = '';
    protected $expires_at    = 0;
    protected $account_id    = '';
    protected $scope         = self::DEFAULT_SCOPE;
    protected $cred_id       = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::AUTHORIZATION_ENDPOINT;
        $this->token_endpoint         = self::TOKEN_ENDPOINT;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ) );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_zoomwebinar_keys', array( $this, 'save_keys' ) );

        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_zoomwebinar_fields', array( $this, 'ajax_get_fields' ) );

        add_action( 'adfoin_zoomwebinar_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_zoomwebinar_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_zoomwebinar_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $stored = (array) maybe_unserialize( get_option( 'adfoin_zoomwebinar_keys' ) );

        if ( isset( $stored['client_id'] ) ) {
            $this->client_id = $stored['client_id'];
        }

        if ( isset( $stored['client_secret'] ) ) {
            $this->client_secret = $stored['client_secret'];
        }

        if ( isset( $stored['access_token'] ) ) {
            $this->access_token = $stored['access_token'];
        }

        if ( isset( $stored['refresh_token'] ) ) {
            $this->refresh_token = $stored['refresh_token'];
        }

        if ( isset( $stored['expires_at'] ) ) {
            $this->expires_at = $stored['expires_at'];
        }

        if ( isset( $stored['account_id'] ) ) {
            $this->account_id = $stored['account_id'];
        }

        if ( isset( $stored['scope'] ) ) {
            $this->scope = $stored['scope'];
        }
    }

    public function register_actions( $actions ) {
        $actions['zoomwebinar'] = array(
            'title' => __( 'Zoom Webinar', 'advanced-form-integration' ),
            'tasks' => array(
                'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['zoomwebinar'] = __( 'Zoom Webinar', 'advanced-form-integration' );
        return $providers;
    }

    public function render_settings( $current_tab ) {
        if ( 'zoomwebinar' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
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
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'account_id',
                'label'         => __( 'Account ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'description'   => __( 'Required only for Server-to-Server OAuth apps.', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'scope',
                'label'         => __( 'Scopes', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'default'       => self::DEFAULT_SCOPE,
                'description'   => __( 'Default: webinar:write:admin webinar:read:admin', 'advanced-form-integration' ),
            ),
        );

        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Create an OAuth app in %s (Account-level or Server-to-Server) with webinar permissions.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://marketplace.zoom.us/">https://marketplace.zoom.us/</a>' ) . '</li>';
        $instructions .= '<li>' . sprintf( __( 'Set the redirect URL to:', 'advanced-form-integration' ) ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Add scopes webinar:write:admin webinar:read:admin (or the equivalent master scopes).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret (include the Account ID if prompted) in the Add Account form, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Zoom Webinar', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'zoomwebinar', 'Zoom Webinar', $fields, $instructions, $config );
    }

    public function save_keys() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ), 'adfoin_zoomwebinar_settings' ) ) {
            wp_die( esc_html__( 'Security check failed', 'advanced-form-integration' ) );
        }

        $this->client_id     = isset( $_POST['adfoin_zoomwebinar_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_zoomwebinar_client_id'] ) ) : '';
        $this->client_secret = isset( $_POST['adfoin_zoomwebinar_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_zoomwebinar_client_secret'] ) ) : '';
        $this->account_id    = isset( $_POST['adfoin_zoomwebinar_account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_zoomwebinar_account_id'] ) ) : '';
        $this->scope         = isset( $_POST['adfoin_zoomwebinar_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_zoomwebinar_scope'] ) ) : self::DEFAULT_SCOPE;

        $this->save_data();
        $this->authorize( $this->scope );

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zoomwebinar' ) );
        exit;
    }

    public function auth_redirect() {
        if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'adfoin_zoomwebinar_state' ) ) {
            return;
        }

        $code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
        $this->request_token( $code );

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zoomwebinar' ) );
        exit;
    }

    protected function authorize( string $scope = '' ) {
        $scope = $scope ? $scope : self::DEFAULT_SCOPE;

        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $this->client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'state'         => wp_create_nonce( 'adfoin_zoomwebinar_state' ),
            ),
            $this->authorization_endpoint
        );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $code ) {
        $body = array(
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $this->get_redirect_uri(),
        );

        if ( $this->account_id ) {
            $body['account_id'] = $this->account_id;
        }

        $response = wp_remote_post(
            $this->token_endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
                'body'    => http_build_query( $body, '', '&' ),
                'timeout' => 30,
            )
        );

        $this->handle_token_response( $response );
        return $response;
    }

    protected function refresh_token() {
        if ( ! $this->refresh_token ) {
            return new WP_Error( 'zoomwebinar_missing_refresh_token', __( 'Refresh token is missing.', 'advanced-form-integration' ) );
        }

        $body = array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refresh_token,
        );

        if ( $this->account_id ) {
            $body['account_id'] = $this->account_id;
        }

        $response = wp_remote_post(
            $this->token_endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
                'body'    => http_build_query( $body, '', '&' ),
                'timeout' => 30,
            )
        );

        $this->handle_token_response( $response );
        return $response;
    }

    protected function get_redirect_uri() {
        return admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zoomwebinar' );
    }

    protected function save_data() {
        $data = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'account_id'    => $this->account_id,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_at'    => $this->expires_at,
            'scope'         => $this->scope,
        );

        update_option( 'adfoin_zoomwebinar_keys', $data );
    }

    protected function handle_token_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status >= 400 || ! isset( $body['access_token'] ) ) {
            return;
        }

        $this->access_token  = $body['access_token'];
        $this->refresh_token = isset( $body['refresh_token'] ) ? $body['refresh_token'] : $this->refresh_token;
        $this->expires_at    = time() + ( isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600 );

        $this->save_data();
    }

    protected function maybe_refresh_token() {
        if ( $this->expires_at && $this->expires_at > time() + 60 ) {
            return;
        }

        $response = $this->refresh_token();

        if ( is_wp_error( $response ) ) {
            return;
        }

        $this->handle_token_response( $response );
    }

    public function render_action_template( $provider ) {
        if ( 'zoomwebinar' !== $provider ) {
            return;
        }
        ?>
        <script type="text/template" id="zoomwebinar-action-template">
            <table class="form-table" v-if="action.task == 'register_attendee'">
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td>
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Zoom Webinar Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadFields">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zoomwebinar' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Webinar ID', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <input type="text" name="fieldData[webinarId]" v-model="fielddata.webinarId" placeholder="123456789" />
                        <p class="description"><?php esc_html_e( 'Paste the webinar ID from Zoom (from the webinar details page or URL).', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Auto Approve', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[autoApprove]" v-model="fielddata.autoApprove">
                            <option value="auto"><?php esc_html_e( 'Auto approve', 'advanced-form-integration' ); ?></option>
                            <option value="manual"><?php esc_html_e( 'Manual approve', 'advanced-form-integration' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Language (optional)', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <input type="text" name="fieldData[language]" v-model="fielddata.language" placeholder="en-US" />
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                    v-bind:key="field.value"
                    v-bind:field="field"
                    v-bind:trigger="trigger"
                    v-bind:action="action"
                    v-bind:fielddata="fielddata"></editable-field>

                <tr class="alternate">
                    <th scope="row"><?php esc_html_e( 'Tips', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php esc_html_e( 'Map at least email and first name. Zoom requires first/last name for many webinars.', 'advanced-form-integration' ); ?></p>
                        <p><?php esc_html_e( 'Use Custom Questions JSON to supply additional registration fields configured in Zoom.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
            </table>
        </script>
        <?php
    }

    public function ajax_get_fields() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = array(
            array( 'key' => 'email', 'value' => __( 'Email *', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'address', 'value' => __( 'Address', 'advanced-form-integration' ) ),
            array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
            array( 'key' => 'zip', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
            array( 'key' => 'state', 'value' => __( 'State', 'advanced-form-integration' ) ),
            array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'industry', 'value' => __( 'Industry', 'advanced-form-integration' ) ),
            array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
            array( 'key' => 'org', 'value' => __( 'Organization', 'advanced-form-integration' ) ),
            array( 'key' => 'no_of_employees', 'value' => __( 'Number of Employees', 'advanced-form-integration' ) ),
            array( 'key' => 'comments', 'value' => __( 'Comments', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'custom_questions_json', 'value' => __( 'Custom Questions (JSON array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: [{"title":"How did you hear about us?","value":"Newsletter"}]', 'advanced-form-integration' ) ),
        );

        wp_send_json_success( $fields );
    }

    /**
     * Set credentials from credential ID or load legacy credentials
     */
    public function set_credentials( $cred_id = '' ) {
        // If cred_id is provided, load from credentials storage
        if ( ! empty( $cred_id ) ) {
            $credentials = $this->get_credentials_by_id( $cred_id );

            if ( empty( $credentials ) ) {
                return;
            }

            $this->client_id     = isset( $credentials['client_id'] ) ? $credentials['client_id'] : '';
            $this->client_secret = isset( $credentials['client_secret'] ) ? $credentials['client_secret'] : '';
            $this->access_token  = isset( $credentials['access_token'] ) ? $credentials['access_token'] : '';
            $this->refresh_token = isset( $credentials['refresh_token'] ) ? $credentials['refresh_token'] : '';
            $this->expires_at    = isset( $credentials['expires_at'] ) ? $credentials['expires_at'] : 0;
            $this->account_id    = isset( $credentials['account_id'] ) ? $credentials['account_id'] : '';
            $this->scope         = isset( $credentials['scope'] ) ? $credentials['scope'] : self::DEFAULT_SCOPE;
            $this->cred_id       = $credentials['id'];
        } else {
            // Load legacy credentials from old option for backward compatibility
            $stored = (array) maybe_unserialize( get_option( 'adfoin_zoomwebinar_keys' ) );

            $this->client_id     = isset( $stored['client_id'] ) ? $stored['client_id'] : '';
            $this->client_secret = isset( $stored['client_secret'] ) ? $stored['client_secret'] : '';
            $this->account_id    = isset( $stored['account_id'] ) ? $stored['account_id'] : '';
            $this->access_token  = isset( $stored['access_token'] ) ? $stored['access_token'] : '';
            $this->refresh_token = isset( $stored['refresh_token'] ) ? $stored['refresh_token'] : '';
            $this->expires_at    = isset( $stored['expires_at'] ) ? (int) $stored['expires_at'] : 0;
            $this->scope         = isset( $stored['scope'] ) ? $stored['scope'] : self::DEFAULT_SCOPE;
        }
    }

    /**
     * Get credentials list for dropdown
     */
    public function get_credentials_list() {
        $html        = '';
        $credentials = adfoin_read_credentials( 'zoomwebinar' );

        foreach ( $credentials as $option ) {
            $html .= '<option value="' . esc_attr( $option['id'] ) . '">' . esc_html( $option['title'] ) . '</option>';
        }

        echo $html;
    }

    /**
     * Get credentials by ID
     */
    public function get_credentials_by_id( $cred_id ) {
        $credentials     = array();
        $all_credentials = adfoin_read_credentials( 'zoomwebinar' );

        if ( is_array( $all_credentials ) && ! empty( $all_credentials ) ) {
            // Default to first credential
            $credentials = $all_credentials[0];

            foreach ( $all_credentials as $single ) {
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                    break;
                }
            }
        }

        return $credentials;
    }

    /**
     * Get all credentials via AJAX
     */
    public function get_credentials() {
        // Security Check
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $all_credentials = adfoin_read_credentials( 'zoomwebinar' );
        wp_send_json_success( $all_credentials );
    }

    /**
     * Modify credentials filter for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'zoomwebinar' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }

        // Load legacy credentials for backward compatibility
        $option = (array) maybe_unserialize( get_option( 'adfoin_zoomwebinar_keys' ) );

        if ( ! empty( $option ) && isset( $option['client_id'] ) && isset( $option['client_secret'] ) ) {
            $credentials[] = array(
                'id'              => '123456',
                'title'           => __( 'Untitled', 'advanced-form-integration' ),
                'client_id'       => $option['client_id'],
                'client_secret'   => $option['client_secret'],
                'account_id'      => $option['account_id'] ?? '',
                'access_token'    => $option['access_token'] ?? '',
                'refresh_token'   => $option['refresh_token'] ?? '',
                'expires_at'      => $option['expires_at'] ?? 0,
                'scope'           => $option['scope'] ?? self::DEFAULT_SCOPE,
            );
        }

        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        // Security Check
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform = 'zoomwebinar';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && $credentials[ $index ]['id'] === '123456' ) {
                    delete_option( 'adfoin_zoomwebinar_keys' );
                }
                unset( $credentials[ $index ] );
                adfoin_save_credentials( $platform, array_values( $credentials ) );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( 'Invalid index' );
        }

        // Handle Save/Update
        $id            = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '';
        $account_id    = isset( $_POST['account_id'] ) ? sanitize_text_field( $_POST['account_id'] ) : '';
        $scope         = isset( $_POST['scope'] ) ? sanitize_text_field( $_POST['scope'] ) : self::DEFAULT_SCOPE;

        if ( empty( $id ) ) {
            $id = uniqid();
            $new_entry = true;
        } else {
            $new_entry = false;
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'account_id'    => $account_id,
            'scope'         => $scope,
            'access_token'  => '',
            'refresh_token' => '',
            'expires_at'    => 0,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials haven't changed
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id &&
                     isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['expires_at']    = isset( $cred['expires_at'] ) ? $cred['expires_at'] : 0;
                }
                $cred = $new_data;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Generate Auth URL
        $redirect_uri = admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zoomwebinar' );
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'redirect_uri'  => $redirect_uri,
                'state'         => wp_create_nonce( 'adfoin_zoomwebinar_state' ),
            ),
            self::AUTHORIZATION_ENDPOINT
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function handle_job_queue( $data ) {
        $record      = $data['record'];
        $posted_data = $data['posted_data'];
        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        } else {
            $this->set_credentials();
        }

        if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
            adfoin_add_to_log( new WP_Error( 'zoomwebinar_missing_credentials', __( 'Connect Zoom Webinar under Settings → Zoom Webinar before using this action.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $this->maybe_refresh_token();
        
        // Refresh credentials after token refresh
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        } else {
            $this->set_credentials();
        }

        if ( empty( $this->access_token ) ) {
            adfoin_add_to_log( new WP_Error( 'zoomwebinar_not_connected', __( 'Zoom Webinar access token is not available. Reconnect the integration from Settings.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $webinar_id = adfoin_zoomwebinar_parse_value( $field_data, 'webinarId', $posted_data );

        if ( '' === $webinar_id ) {
            adfoin_add_to_log( new WP_Error( 'zoomwebinar_missing_webinar', __( 'Zoom webinar ID is required.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $payload = adfoin_zoomwebinar_collect_payload( $field_data, $posted_data );

        if ( is_wp_error( $payload ) ) {
            adfoin_add_to_log( $payload, '', array(), $record );
            return;
        }

        $auto_approve = adfoin_zoomwebinar_parse_value( $field_data, 'autoApprove', $posted_data );
        $language     = adfoin_zoomwebinar_parse_value( $field_data, 'language', $posted_data );

        if ( '' !== $auto_approve ) {
            $payload['auto_approve'] = ( 'manual' === $auto_approve ) ? 'false' : 'true';
        }

        if ( '' !== $language ) {
            $payload['language'] = $language;
        }

        $endpoint = sprintf( 'webinars/%s/registrants', rawurlencode( $webinar_id ) );
        $response = $this->api_request( $endpoint, 'POST', $payload, $record );

        if ( is_wp_error( $response ) ) {
            adfoin_add_to_log( $response, '', array(), $record );
        }
    }

    protected function api_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $retry = false ) {
        $url  = trailingslashit( self::API_BASE ) . ltrim( $endpoint, '/' );
        $args = array(
            'timeout' => 30,
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
        );

        if ( 'GET' !== strtoupper( $method ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );

        if ( 401 === $status && ! $retry ) {
            $this->maybe_refresh_token();
            $this->set_credentials();

            if ( empty( $this->access_token ) ) {
                return new WP_Error( 'zoomwebinar_token_refresh_failed', __( 'Unable to refresh Zoom Webinar access token.', 'advanced-form-integration' ) );
            }

            return $this->api_request( $endpoint, $method, $data, $record, true );
        }

        if ( $status >= 400 ) {
            $body = wp_remote_retrieve_body( $response );
            return new WP_Error( 'zoomwebinar_http_error', $body ? $body : __( 'Zoom Webinar request failed.', 'advanced-form-integration' ), array( 'status' => $status ) );
        }

        return $response;
    }
}

endif;

add_action( 'plugins_loaded', function() {
    ADFoin_Zoom_Webinar::get_instance();
} );

function adfoin_zoomwebinar_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_zoomwebinar_collect_payload( $field_data, $posted_data ) {
    $email = adfoin_zoomwebinar_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $email ) {
        return new WP_Error( 'zoomwebinar_missing_email', __( 'Zoom Webinar requires an email address.', 'advanced-form-integration' ) );
    }

    $payload = array( 'email' => $email );

    $map = array(
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'address'    => 'address',
        'city'       => 'city',
        'country'    => 'country',
        'zip'        => 'zip',
        'state'      => 'state',
        'phone'      => 'phone',
        'industry'   => 'industry',
        'job_title'  => 'job_title',
        'org'        => 'org',
        'no_of_employees' => 'no_of_employees',
    );

    foreach ( $map as $key => $api_key ) {
        $value = adfoin_zoomwebinar_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    $comments = adfoin_zoomwebinar_parse_value( $field_data, 'comments', $posted_data );

    if ( '' !== $comments ) {
        $payload['comments'] = $comments;
    }

    $custom = adfoin_zoomwebinar_parse_value( $field_data, 'custom_questions_json', $posted_data );

    if ( '' !== $custom ) {
        $decoded = json_decode( $custom, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'zoomwebinar_invalid_custom', __( 'Custom questions JSON must be an array.', 'advanced-form-integration' ) );
        }

        $payload['custom_questions'] = $decoded;
    }

    return $payload;
}