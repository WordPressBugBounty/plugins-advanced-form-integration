<?php

class ADFOIN_GoogleTasks extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'googletasks';

    const service_name           = 'googletasks';
    const authorization_endpoint = 'https://accounts.google.com/o/oauth2/auth';
    const token_endpoint         = 'https://www.googleapis.com/oauth2/v3/token';

    private static $instance;
    protected $client_id      = '';
    protected $client_secret  = '';
    protected $task_lists     = array();
    protected $token_expires  = 0;
    protected $cred_id        = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {
        $this->token_endpoint         = self::token_endpoint;
        $this->authorization_endpoint = self::authorization_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'register_callback_route' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_googletasks_keys', array( $this, 'save_keys' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_googletasks_lists', array( $this, 'ajax_get_lists' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_googletasks_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_googletasks_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_googletasks_keys' ) );

        if ( isset( $option['client_id'] ) ) {
            $this->client_id = $option['client_id'];
        }
        if ( isset( $option['client_secret'] ) ) {
            $this->client_secret = $option['client_secret'];
        }
        if ( isset( $option['access_token'] ) ) {
            $this->access_token = $option['access_token'];
        }
        if ( isset( $option['refresh_token'] ) ) {
            $this->refresh_token = $option['refresh_token'];
        }
        if ( isset( $option['token_expires'] ) ) {
            $this->token_expires = $option['token_expires'];
        }
        if ( $this->is_active() && isset( $option['task_lists'] ) ) {
            $this->task_lists = $option['task_lists'];
        }
    }

    /**
     * Load credentials for a specific account
     */
    protected function load_credentials( $cred_id ) {
        if ( empty( $cred_id ) ) {
            return false;
        }

        // Handle legacy credential ID
        if ( strpos( $cred_id, 'legacy_' ) === 0 ) {
            $this->load_legacy_credentials();
            return true;
        }

        // Load from OAuth Manager
        $credentials = adfoin_read_credentials( 'googletasks' );
        
        foreach ( $credentials as $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] == $cred_id ) {
                $this->cred_id       = $cred_id;
                $this->client_id     = isset( $credential['client_id'] ) ? $credential['client_id'] : '';
                $this->client_secret = isset( $credential['client_secret'] ) ? $credential['client_secret'] : '';
                $this->access_token  = isset( $credential['access_token'] ) ? $credential['access_token'] : '';
                $this->refresh_token = isset( $credential['refresh_token'] ) ? $credential['refresh_token'] : '';
                $this->token_expires = isset( $credential['token_expires'] ) ? $credential['token_expires'] : 0;
                return true;
            }
        }

        return false;
    }

    public function register_callback_route() {
        register_rest_route(
            'advancedformintegration',
            '/googletasks',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_webhook' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_webhook( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';
        $context = self::consume_oauth_state( $state, 'googletasks' );
        $state   = $context ? $context['cred_id'] : '';

        if ( $code ) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                $this->cred_id = $state;
                $credentials = adfoin_read_credentials( 'googletasks' );
                
                foreach ( $credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                        $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
                    }
                }

                $response = $this->request_token( $code );
                
                $success = false;
                $message = 'Unknown error';
                
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['access_token'] ) ) {
                        $success = true;
                        $message = 'Connected successfully!';
                    } else {
                        $message = isset( $body['error'] ) ? $body['error'] : 'Token exchange failed.';
                    }
                } else {
                    $message = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error';
                }

                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
                exit;
            }
            
            // Legacy flow - redirect to admin
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_googletasks_auth_redirect',
                    'code'    => $code,
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( trim( $_GET['action'] ) ) : '';

        if ( 'adfoin_googletasks_auth_redirect' === $action ) {
            // admin_init fires for every logged-in user; only an admin should
            // be able to complete this OAuth flow (CWE-862).
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
            $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
            $context = self::consume_oauth_state( $state, 'googletasks' );
            $state   = $context ? $context['cred_id'] : '';

            if ( $code ) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->cred_id = $state;
                    $credentials = adfoin_read_credentials( 'googletasks' );
                    
                    foreach ( $credentials as $value ) {
                        if ( $value['id'] == $state ) {
                            $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                            $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
                        }
                    }
                }
                
                $this->request_token( $code );
                
                // For popup flow
                if ( $state ) {
                    require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                    ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
                    exit;
                }
            }

            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=googletasks' ) );
            exit();
        }
    }

    public function register_actions( $actions ) {
        $actions['googletasks'] = array(
            'title' => __( 'Google Tasks', 'advanced-form-integration' ),
            'tasks' => array(
                'create_task' => __( 'Create Task', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $tabs ) {
        $tabs['googletasks'] = __( 'Google Tasks', 'advanced-form-integration' );
        return $tabs;
    }

    public function render_settings( $current_tab ) {
        if ( 'googletasks' !== $current_tab ) {
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
        );

        // Instructions
        $domain = parse_url( get_site_url() );
        $host   = $domain['host'];

        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s and create a New Project.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://console.cloud.google.com/project">Google Developer Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Go to Library and search for Google Tasks API, open it and click ENABLE.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . sprintf( __( 'Go to OAuth consent screen, select External, click Create. Enter %s in Authorized domains. Set publishing status to in production.', 'advanced-form-integration' ), '<code>' . esc_html( $host ) . '</code>' ) . '</li>';
        $instructions .= '<li>' . __( 'Go to Credentials, click CREATE CREDENTIALS, select OAuth client ID, select Web application.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Authorized redirect URIs:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Google Tasks', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'googletasks', 'Google Tasks', $fields, $instructions, $config );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'googletasks' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_googletasks_keys' ) );

            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && ! empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
                    'token_expires' => isset( $option['token_expires'] ) ? $option['token_expires'] : 0,
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'googletasks';
        $credentials = adfoin_read_credentials( $platform );
        
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && strpos( $credentials[ $index ]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_googletasks_keys' );
                }
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        // Handle Save/Update
        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
            'token_expires' => 0,
        );

        // Check if updating existing credential
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials haven't changed
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id &&
                     isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['token_expires'] = isset( $cred['token_expires'] ) ? $cred['token_expires'] : 0;
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Generate Auth URL
        $scope    = 'https://www.googleapis.com/auth/tasks';
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $scope,
                'state'         => self::issue_oauth_state( 'googletasks', $id ),
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    protected function authorize( $scope = '' ) {
        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'client_id'     => $this->client_id,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'scope'         => urlencode( $scope ),
            ),
            $this->authorization_endpoint
        );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit();
        }
    }

    protected function request_token( $authorization_code ) {
        $args = array(
            'timeout' => 30,
            'headers' => array(),
            'body'    => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
            ),
        );

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->apply_token_response( $response_body );

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        $args = array(
            'timeout' => 30,
            'headers' => array(),
            'body'    => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
            ),
        );

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->apply_token_response( $response_body );

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        // OAuth Manager flow: persist canonical token fields via the base helper.
        if ( ! empty( $this->cred_id ) && strpos( $this->cred_id, 'legacy_' ) !== 0 ) {
            $this->persist_token_to_credential();
            return;
        }

        // Legacy save method for backward compatibility
        $data = (array) maybe_unserialize( get_option( 'adfoin_googletasks_keys' ) );

        $option = array_merge(
            $data,
            array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'access_token'  => $this->access_token,
                'refresh_token' => $this->refresh_token,
                'token_expires' => $this->token_expires,
                'task_lists'    => $this->task_lists,
            )
        );

        update_option( 'adfoin_googletasks_keys', maybe_serialize( $option ) );
    }

    protected function reset_data() {
        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';
        $this->refresh_token = '';
        $this->task_lists    = array();

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/googletasks' );
    }

    public function ajax_get_lists() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        
        if ( empty( $cred_id ) ) {
            wp_send_json_error( __( 'No account selected', 'advanced-form-integration' ) );
        }

        // Load credentials for the selected account
        $this->load_credentials( $cred_id );

        if ( empty( $this->access_token ) ) {
            wp_send_json_error( __( 'Account not connected', 'advanced-form-integration' ) );
        }

        $response = $this->fetch_task_lists();

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        wp_send_json_success( $response );
    }

    protected function fetch_task_lists() {
        if ( ! $this->access_token ) {
            return new WP_Error( 'googletasks_no_token', __( 'Authorize Google Tasks first.', 'advanced-form-integration' ) );
        }

        // Inherited remote_request() handles proactive expiry refresh
        // (via is_token_expired) and reactive 401-retry. It also injects
        // the Authorization header — no need to set it here.
        $response = $this->remote_request(
            'https://tasks.googleapis.com/tasks/v1/users/@me/lists',
            array( 'method' => 'GET' )
        );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['items'] ) && is_array( $body['items'] ) ) {
            $lists = array();
            foreach ( $body['items'] as $item ) {
                if ( isset( $item['id'], $item['title'] ) ) {
                    $lists[ $item['id'] ] = $item['title'];
                }
            }

            $this->task_lists = $lists;
            $this->save_data();

            return $lists;
        }

        return array();
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="googletasks-action-template">
            <table class="form-table" v-if="action.task == 'create_task'">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Google Tasks Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getTaskLists">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                        <a id="googletasks-auth-btn" target="_blank" href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=googletasks' ); ?>" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php _e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Task List', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                            <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                            <option v-for="(label, id) in fielddata.taskLists" :value="id">{{ label }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': listsLoading}"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Docs', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <a href="https://developers.google.com/tasks/reference/rest" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Tasks REST reference', 'advanced-form-integration' ); ?></a>
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                    v-bind:key="field.value"
                    v-bind:field="field"
                    v-bind:trigger="trigger"
                    v-bind:action="action"
                    v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    public function create_task( $list_id, $task_data, $record, $cred_id = '' ) {
        if ( ! $list_id || empty( $task_data ) ) {
            return;
        }

        // Load credentials if cred_id is provided
        if ( ! empty( $cred_id ) ) {
            $this->load_credentials( $cred_id );
        }

        $endpoint = sprintf( 'https://tasks.googleapis.com/tasks/v1/lists/%s/tasks', rawurlencode( $list_id ) );
        $request  = array(
            'timeout' => 30,
            'method'  => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode( $task_data ),
        );

        // Inherited remote_request() injects Authorization + handles
        // proactive expiry refresh and reactive 401-retry.
        $response = $this->remote_request( $endpoint, $request );
        adfoin_add_to_log( $response, $endpoint, $request, $record );
    }
}

$googletasks = ADFOIN_GoogleTasks::get_instance();

add_action( 'adfoin_googletasks_job_queue', 'adfoin_googletasks_job_queue', 10, 1 );

function adfoin_googletasks_job_queue( $data ) {
    adfoin_googletasks_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_googletasks_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task = isset( $record['task'] ) ? $record['task'] : '';

    // Template stores the chosen account in field_data via
    // name="fieldData[credId]" — read from there. Older saves that
    // predate this template fall back to the legacy single-account
    // credential.
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    if ( empty( $cred_id ) ) {
        $cred_id = 'legacy_123456';
    }

    if ( 'create_task' !== $task ) {
        return;
    }

    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $title   = empty( $data['title'] ) ? '' : adfoin_get_parsed_values( $data['title'], $posted_data );

    if ( empty( $list_id ) || empty( $title ) ) {
        return;
    }

    $notes     = empty( $data['notes'] ) ? '' : adfoin_get_parsed_values( $data['notes'], $posted_data );
    $due       = empty( $data['due'] ) ? '' : adfoin_get_parsed_values( $data['due'], $posted_data );
    $status    = empty( $data['status'] ) ? '' : strtolower( adfoin_get_parsed_values( $data['status'], $posted_data ) );
    $parent    = empty( $data['parent'] ) ? '' : adfoin_get_parsed_values( $data['parent'], $posted_data );
    $position  = empty( $data['position'] ) ? '' : adfoin_get_parsed_values( $data['position'], $posted_data );

    $payload = array( 'title' => $title );

    if ( $notes ) {
        $payload['notes'] = $notes;
    }

    if ( $due ) {
        $timestamp = strtotime( $due );
        if ( false !== $timestamp ) {
            $payload['due'] = gmdate( 'Y-m-d\TH:i:s.000\Z', $timestamp );
        }
    }

    if ( $parent ) {
        $payload['parent'] = $parent;
    }

    if ( $position ) {
        $payload['position'] = $position;
    }

    if ( in_array( $status, array( 'completed', 'needsaction' ), true ) ) {
        $payload['status'] = ( 'completed' === $status ) ? 'completed' : 'needsAction';
        if ( 'completed' === $payload['status'] ) {
            $payload['completed'] = gmdate( 'Y-m-d\TH:i:s.000\Z' );
        }
    }

    $googletasks = ADFOIN_GoogleTasks::get_instance();
    $googletasks->create_task( $list_id, $payload, $record, $cred_id );
}
