<?php

class ADFOIN_Salesforce extends Advanced_Form_Integration_OAuth2 {
    const authorization_endpoint = 'https://login.salesforce.com/services/oauth2/authorize';

    const token_endpoint = 'https://login.salesforce.com/services/oauth2/token';

    const refresh_token_endpoint = 'https://login.salesforce.com/services/oauth2/token';

    private static $instance;

    protected $client_id = '';

    protected $client_secret = '';

    protected $access_token = '';

    protected $refresh_token = '';

    protected $instance_url = '';

    protected $cred_id = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;
        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();
        add_action( 'admin_init', array($this, 'auth_redirect') );
        add_filter(
            'adfoin_action_providers',
            array($this, 'adfoin_salesforce_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_salesforce_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_salesforce_settings_view'),
            10,
            1
        );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_salesforce_credentials', array($this, 'get_credentials') );
        add_filter(
            'adfoin_get_credentials',
            array($this, 'modify_credentials'),
            10,
            2
        );
        add_action( 'wp_ajax_adfoin_save_salesforce_credentials', array($this, 'save_credentials') );
        add_action( 'wp_ajax_adfoin_get_salesforce_fields', array($this, 'get_fields') );
        add_action( 'wp_ajax_adfoin_get_salesforce_campaigns', array($this, 'get_campaigns') );
        add_action( 'wp_ajax_adfoin_get_salesforce_owners', array($this, 'get_owner_list') );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_salesforce_keys' ) );
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
        if ( isset( $option['instance_url'] ) ) {
            $this->instance_url = $option['instance_url'];
        }
    }

    /**
     * Load credentials for a specific account
     */
    public function load_credentials( $cred_id ) {
        if ( empty( $cred_id ) ) {
            return false;
        }
        // Handle legacy credential ID
        if ( strpos( $cred_id, 'legacy_' ) === 0 ) {
            $this->load_legacy_credentials();
            return true;
        }
        // Load from OAuth Manager
        $credentials = adfoin_read_credentials( 'salesforce' );
        foreach ( $credentials as $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] == $cred_id ) {
                $this->cred_id = $cred_id;
                $this->client_id = ( isset( $credential['client_id'] ) ? $credential['client_id'] : '' );
                $this->client_secret = ( isset( $credential['client_secret'] ) ? $credential['client_secret'] : '' );
                $this->access_token = ( isset( $credential['access_token'] ) ? $credential['access_token'] : '' );
                $this->refresh_token = ( isset( $credential['refresh_token'] ) ? $credential['refresh_token'] : '' );
                $this->instance_url = ( isset( $credential['instance_url'] ) ? $credential['instance_url'] : '' );
                return true;
            }
        }
        return false;
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/salesforce', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_webhook_data'],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code = ( isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '' );
        $state = ( isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '' );
        if ( $code ) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                $this->cred_id = $state;
                $credentials = adfoin_read_credentials( 'salesforce' );
                foreach ( $credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->client_id = ( isset( $value['client_id'] ) ? $value['client_id'] : '' );
                        $this->client_secret = ( isset( $value['client_secret'] ) ? $value['client_secret'] : '' );
                    }
                }
                $response = $this->request_token( $code );
                $success = false;
                $message = 'Unknown error';
                if ( !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['access_token'] ) ) {
                        $success = true;
                        $message = 'Connected successfully!';
                    } else {
                        $message = ( isset( $body['error'] ) ? $body['error'] : 'Token exchange failed.' );
                    }
                } else {
                    $message = ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error' );
                }
                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
                exit;
            }
            // Legacy flow - redirect to admin
            $redirect_to = add_query_arg( [
                'service' => 'authorize',
                'action'  => 'adfoin_salesforce_auth_redirect',
                'code'    => $code,
            ], admin_url( 'admin.php?page=advanced-form-integration' ) );
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }

    public function auth_redirect() {
        $action = ( isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '' );
        if ( 'adfoin_salesforce_auth_redirect' == $action ) {
            $code = ( isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' );
            $state = ( isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '' );
            if ( $code ) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->cred_id = $state;
                    $credentials = adfoin_read_credentials( 'salesforce' );
                    foreach ( $credentials as $value ) {
                        if ( $value['id'] == $state ) {
                            $this->client_id = ( isset( $value['client_id'] ) ? $value['client_id'] : '' );
                            $this->client_secret = ( isset( $value['client_secret'] ) ? $value['client_secret'] : '' );
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
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforce' ) );
            exit;
        }
    }

    public function adfoin_salesforce_actions( $actions ) {
        $actions['salesforce'] = [
            'title' => __( 'Salesforce', 'advanced-form-integration' ),
            'tasks' => [
                'add_lead'    => __( 'Add new lead', 'advanced-form-integration' ),
                'add_contact' => __( 'Add new Account, Contact, Opportunity, Case', 'advanced-form-integration' ),
            ],
        ];
        return $actions;
    }

    public function adfoin_salesforce_settings_tab( $providers ) {
        $providers['salesforce'] = __( 'Salesforce', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_salesforce_settings_view( $current_tab ) {
        if ( $current_tab !== 'salesforce' ) {
            return;
        }
        $redirect_uri = $this->get_redirect_uri();
        // Define fields for OAuth Manager
        $fields = array(array(
            'name'          => 'client_id',
            'label'         => __( 'Client ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ), array(
            'name'          => 'client_secret',
            'label'         => __( 'Client Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ));
        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'Log in to your Salesforce account.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click the Settings icon (gear) in the top-right corner and select Setup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'In the left-hand menu, go to Platform Tools, then click Apps, and select App Manager.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click on New Connected App to create a new app.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Enter the Connected App Name, API Name, and Contact Email.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Check Enable OAuth Settings under the API section.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Callback URL:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Add Full Access and Perform requests anytime OAuth scopes.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Uncheck Require Proof Key for Code Exchange (PKCE).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Save and get Consumer Key (Client ID) and Consumer Secret (Client Secret).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';
        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Salesforce', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'salesforce',
            'Salesforce',
            $fields,
            $instructions,
            $config
        );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $all_credentials = adfoin_read_credentials( 'salesforce' );
        wp_send_json_success( $all_credentials );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'salesforce' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_salesforce_keys' ) );
            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && !empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => ( isset( $option['access_token'] ) ? $option['access_token'] : '' ),
                    'refresh_token' => ( isset( $option['refresh_token'] ) ? $option['refresh_token'] : '' ),
                    'instance_url'  => ( isset( $option['instance_url'] ) ? $option['instance_url'] : '' ),
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = 'salesforce';
        $credentials = adfoin_read_credentials( $platform );
        if ( !is_array( $credentials ) ) {
            $credentials = array();
        }
        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[$index] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[$index]['id'] ) && strpos( $credentials[$index]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_salesforce_keys' );
                }
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array(
                    'message' => 'Deleted',
                ) );
            }
            wp_send_json_error( 'Invalid index' );
        }
        // Handle Save/Update
        $id = ( isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '' );
        $title = ( isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '' );
        $client_id = ( isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '' );
        $client_secret = ( isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '' );
        if ( empty( $id ) ) {
            $id = uniqid();
        }
        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
            'instance_url'  => '',
        );
        // Check if updating existing credential
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials haven't changed
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id && isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret ) {
                    $new_data['access_token'] = ( isset( $cred['access_token'] ) ? $cred['access_token'] : '' );
                    $new_data['refresh_token'] = ( isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '' );
                    $new_data['instance_url'] = ( isset( $cred['instance_url'] ) ? $cred['instance_url'] : '' );
                }
                $cred = $new_data;
                $found = true;
                break;
            }
        }
        if ( !$found ) {
            $credentials[] = $new_data;
        }
        adfoin_save_credentials( $platform, $credentials );
        // Generate Auth URL
        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'prompt'        => 'login consent',
            'state'         => $id,
        ), $this->authorization_endpoint );
        wp_send_json_success( array(
            'auth_url' => $auth_url,
        ) );
    }

    public function adfoin_save_salesforce_keys() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_salesforce_settings' ) ) {
            die( __( 'Security check failed', 'advanced-form-integration' ) );
        }
        $client_id = ( isset( $_POST["adfoin_salesforce_client_id"] ) ? sanitize_text_field( $_POST["adfoin_salesforce_client_id"] ) : "" );
        $client_secret = ( isset( $_POST["adfoin_salesforce_client_secret"] ) ? sanitize_text_field( $_POST["adfoin_salesforce_client_secret"] ) : "" );
        $this->client_id = trim( $client_id );
        $this->client_secret = trim( $client_secret );
        $this->save_data();
        $this->authorize();
        wp_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforce' ) );
        exit;
    }

    protected function authorize( $scope = '' ) {
        $endpoint = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
            'prompt'        => 'login consent',
        ), $this->authorization_endpoint );
        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $code ) {
        $url = $this->token_endpoint . '?' . http_build_query( array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->get_redirect_uri(),
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ) );
        $url = 'https://login.salesforce.com/services/oauth2/token?grant_type=authorization_code&client_id=' . $this->client_id . '&client_secret=' . $this->client_secret . '&redirect_uri=' . $this->get_redirect_uri() . '&code=' . $code;
        $args = array(
            'headers' => array(
                'user-agent' => 'wordpress/advanced-form-integration',
            ),
            'timeout' => 30,
            'method'  => 'POST',
            'body'    => array(),
        );
        $response = wp_remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        }
        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }
        if ( isset( $response_body['instance_url'] ) ) {
            $this->instance_url = $response_body['instance_url'];
        }
        $this->save_data();
        return $response;
    }

    protected function refresh_token() {
        $ref_endpoint = $this->refresh_token_endpoint;
        $endpoint = add_query_arg( array(
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ), $ref_endpoint );
        $request = [
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ];
        $response = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        }
        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }
        $this->save_data();
        return $response;
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/salesforce' );
    }

    protected function save_data() {
        // If using OAuth Manager (has cred_id), save to credentials
        if ( !empty( $this->cred_id ) && strpos( $this->cred_id, 'legacy_' ) !== 0 ) {
            $credentials = adfoin_read_credentials( 'salesforce' );
            foreach ( $credentials as &$credential ) {
                if ( isset( $credential['id'] ) && $credential['id'] == $this->cred_id ) {
                    $credential['access_token'] = $this->access_token;
                    $credential['refresh_token'] = $this->refresh_token;
                    $credential['instance_url'] = $this->instance_url;
                    break;
                }
            }
            adfoin_save_credentials( 'salesforce', $credentials );
            return;
        }
        // Legacy save method for backward compatibility
        $data = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'instance_url'  => $this->instance_url,
        ];
        update_option( 'adfoin_salesforce_keys', maybe_serialize( $data ) );
    }

    protected function reset_data() {
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';
        $this->instance_url = '';
        $this->save_data();
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='salesforce-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'add_lead' || action.task == 'add_contact'">
                    <th scope='row'>
                        <?php 
        esc_attr_e( 'Map Fields', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope='row'>
                        <div class='spinner' v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_lead' || action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Select Account', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getOwners">
                            <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                        <br/>
                        <a href="<?php 
        echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforce' );
        ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
        esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
        ?>
                        </a>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_lead' || action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='owner'>
                            <?php 
        esc_attr_e( 'Owner', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[ownerId]" v-model="fielddata.ownerId">
                            <option value=''><?php 
        _e( 'Select Owner...', 'advanced-form-integration' );
        ?></option>
                            <option v-for='(name, id) in fielddata.owners' :value='id'>{{name}}</option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': ownerLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_lead'">
                    <td scope='row-title'>
                        <label for='campaign'>
                            <?php 
        esc_attr_e( 'Campaign', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[campaignId]" v-model="fielddata.campaignId">
                            <option value=''><?php 
        _e( 'Select Campaign...', 'advanced-form-integration' );
        ?></option>
                            <option v-for='(name, id) in fielddata.campaigns' :value='id'>{{name}}</option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': campaignLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>

                <?php 
        ?>

                <?php 
        if ( adfoin_fs()->is_not_paying() ) {
            ?>
                    <tr valign="top" v-if="action.task == 'add_lead'">
                        <th scope="row">
                            <?php 
            esc_attr_e( 'Go Pro', 'advanced-form-integration' );
            ?>
                        </th>
                        <td scope="row">
                            <span><?php 
            printf( __( 'To use custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
            ?></span>
                        </td>
                    </tr>
                <?php 
        }
        ?>
            </table>
        </script>
        <?php 
    }

    public function create_or_update_lead( $lead_data, $record ) {
        // Check if lead already exists
        $existing_lead = $this->find_lead( $lead_data['Email'] );
        if ( $existing_lead ) {
            // Update existing lead
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Lead/' . $existing_lead['Id'];
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $lead_data ),
                'method'  => 'PATCH',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $existing_lead['Id'];
        } else {
            // Create new lead
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Lead/';
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $lead_data ),
                'method'  => 'POST',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $response;
        }
    }

    private function find_lead( $email ) {
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id FROM Lead WHERE Email = '{$email}' LIMIT 1" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['records'] ) && !empty( $response_body['records'] ) ) {
            return $response_body['records'][0];
        }
        return false;
    }

    public function create_or_update_account( $account_data, $record ) {
        // Check if account already exists
        $existing_account = $this->find_account( $account_data['Name'] );
        if ( $existing_account ) {
            // Update existing account
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Account/' . $existing_account['Id'];
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $account_data ),
                'method'  => 'PATCH',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $existing_account['Id'];
        } else {
            // Create new account
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Account/';
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $account_data ),
                'method'  => 'POST',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $response;
        }
    }

    private function find_account( $name ) {
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id FROM Account WHERE Name = '{$name}' LIMIT 1" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['records'] ) && !empty( $response_body['records'] ) ) {
            return $response_body['records'][0];
        }
        return false;
    }

    public function create_or_update_contact( $contact_data, $record ) {
        // Check if contact already exists
        $existing_contact = $this->find_contact( $contact_data['Email'] );
        if ( $existing_contact ) {
            // Update existing contact
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Contact/' . $existing_contact['Id'];
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $contact_data ),
                'method'  => 'PATCH',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $existing_contact['Id'];
        } else {
            // Create new contact
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Contact/';
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $contact_data ),
                'method'  => 'POST',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $response;
        }
    }

    private function find_contact( $email ) {
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id FROM Contact WHERE Email = '{$email}' LIMIT 1" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['records'] ) && !empty( $response_body['records'] ) ) {
            return $response_body['records'][0];
        }
        return false;
    }

    public function create_or_update_opportunity( $opportunity_data, $record ) {
        // Check if opportunity already exists
        $existing_opportunity = $this->find_opportunity( $opportunity_data['Name'] );
        if ( $existing_opportunity ) {
            // Update existing opportunity
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Opportunity/' . $existing_opportunity['Id'];
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $opportunity_data ),
                'method'  => 'PATCH',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $existing_opportunity['Id'];
        } else {
            // Create new opportunity
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Opportunity/';
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $opportunity_data ),
                'method'  => 'POST',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $response;
        }
    }

    private function find_opportunity( $name ) {
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id FROM Opportunity WHERE Name = '{$name}' LIMIT 1" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['records'] ) && !empty( $response_body['records'] ) ) {
            return $response_body['records'][0];
        }
        return false;
    }

    public function create_or_update_case( $case_data, $record ) {
        // Check if case already exists
        $existing_case = $this->find_case( $case_data['Subject'] );
        if ( $existing_case ) {
            // Update existing case
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Case/' . $existing_case['Id'];
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $case_data ),
                'method'  => 'PATCH',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $existing_case['Id'];
        } else {
            // Create new case
            $url = $this->instance_url . '/services/data/v62.0/sobjects/Case/';
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode( $case_data ),
                'method'  => 'POST',
            ];
            $response = $this->remote_request( $url, $args, $record );
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['id'] ) ) {
                return $response_body['id'];
            }
            return $response;
        }
    }

    private function find_case( $subject ) {
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id FROM Case WHERE Subject = '{$subject}' LIMIT 1" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['records'] ) && !empty( $response_body['records'] ) ) {
            return $response_body['records'][0];
        }
        return false;
    }

    public function create_opportunity( $opportunity_data, $record ) {
        $url = $this->instance_url . '/services/data/v62.0/sobjects/Opportunity/';
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => json_encode( $opportunity_data ),
            'method'  => 'POST',
        ];
        $response = $this->remote_request( $url, $args, $record );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['id'] ) ) {
            return $response_body['id'];
        }
        return $response;
    }

    public function create_case( $case_data, $record ) {
        $url = $this->instance_url . '/services/data/v62.0/sobjects/Case/';
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => json_encode( $case_data ),
            'method'  => 'POST',
        ];
        $response = $this->remote_request( $url, $args, $record );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['id'] ) ) {
            return $response_body['id'];
        }
        return $response;
    }

    public function get_campaigns() {
        if ( !adfoin_verify_nonce() ) {
            return;
        }
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id, Name FROM Campaign" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        $campaigns = [];
        if ( isset( $response_body['records'] ) ) {
            foreach ( $response_body['records'] as $record ) {
                $campaigns[$record['Id']] = $record['Name'];
            }
        }
        wp_send_json_success( $campaigns );
    }

    public function assign_lead_to_campaign( $lead_id, $campaign_id, $record ) {
        $campaign_member_data = [
            'LeadId'     => $lead_id,
            'CampaignId' => $campaign_id,
        ];
        $url = $this->instance_url . '/services/data/v62.0/sobjects/CampaignMember/';
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => json_encode( $campaign_member_data ),
            'method'  => 'POST',
        ];
        $campaign_response = $this->remote_request( $url, $args, $record );
        if ( is_wp_error( $campaign_response ) ) {
            // Handle error
        } else {
            // Handle success
        }
    }

    // get owner list through api
    public function get_owner_list() {
        $url = $this->instance_url . '/services/data/v62.0/query/?q=' . urlencode( "SELECT Id, Name FROM User" );
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        $owners = [];
        if ( isset( $response_body['records'] ) ) {
            foreach ( $response_body['records'] as $record ) {
                $owners[$record['Id']] = $record['Name'];
            }
        }
        wp_send_json_success( $owners );
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;
        $request = wp_parse_args( $request, [] );
        $request['headers'] = array_merge( $request['headers'], array(
            'Authorization' => 'Bearer ' . $this->access_token,
        ) );
        $response = wp_remote_request( esc_url_raw( $url ), $request );
        if ( 401 === wp_remote_retrieve_response_code( $response ) && !$refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $response = $this->remote_request( $url, $request, $record );
        }
        if ( $record ) {
            adfoin_add_to_log(
                $response,
                $url,
                $request,
                $record
            );
        }
        return $response;
    }

    public function set_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_salesforce_keys' ) );
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
        if ( isset( $option['instance_url'] ) ) {
            $this->instance_url = $option['instance_url'];
        }
    }

    public function get_fields() {
        if ( !adfoin_verify_nonce() ) {
            return;
        }
        $fields = [];
        $task = ( isset( $_POST['task'] ) ? sanitize_text_field( $_POST['task'] ) : '' );
        if ( 'add_lead' == $task ) {
            $fields = array_merge( $fields, $this->get_lead_fields() );
        } elseif ( 'add_contact' == $task ) {
            $fields = array_merge( $fields, $this->get_account_fields() );
            $fields = array_merge( $fields, $this->get_contact_fields() );
            $fields = array_merge( $fields, $this->get_opportunity_fields() );
            $fields = array_merge( $fields, $this->get_case_fields() );
        }
        wp_send_json_success( $fields );
    }

    public function get_lead_fields() {
        return $this->get_object_fields( 'Lead' );
    }

    public function get_account_fields() {
        return $this->get_object_fields( 'Account' );
    }

    public function get_contact_fields() {
        return $this->get_object_fields( 'Contact' );
    }

    public function get_opportunity_fields() {
        return $this->get_object_fields( 'Opportunity' );
    }

    public function get_case_fields() {
        return $this->get_object_fields( 'Case' );
    }

    private function get_object_fields( $object ) {
        $url = $this->instance_url . '/services/data/v62.0/sobjects/' . $object . '/describe/';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method'  => 'GET',
        ];
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        $fields = [];
        if ( isset( $response_body['fields'] ) ) {
            $skip_list = ['OwnerId', 'AccountId', 'ContactId'];
            foreach ( $response_body['fields'] as $field ) {
                if ( in_array( $field['name'], $skip_list ) ) {
                    continue;
                }
                if ( $field['updateable'] == true && strpos( $field['name'], '__c' ) === false ) {
                    $description = '';
                    if ( isset( $field['picklistValues'] ) && !empty( $field['picklistValues'] ) ) {
                        $values = wp_list_pluck( $field['picklistValues'], 'value' );
                        $description = implode( ', ', $values );
                    }
                    $label = $field['label'];
                    if ( in_array( $object, [
                        'Account',
                        'Contact',
                        'Opportunity',
                        'Case'
                    ] ) ) {
                        $label = $label . " [{$object}]";
                    }
                    if ( $object == 'Account' && $field['name'] == 'Name' || $object == 'Contact' && $field['name'] == 'LastName' || $object == 'Lead' && in_array( $field['name'], ['Company', 'LastName', 'Email'] ) || $object == 'Opportunity' && in_array( $field['name'], ['Name', 'CloseDate'] ) ) {
                        $description = 'Required';
                    }
                    $fields[] = [
                        'key'         => strtolower( $object ) . '__' . $field['type'] . '__' . $field['name'],
                        'value'       => $label,
                        'description' => $description,
                    ];
                }
            }
        }
        return $fields;
    }

}

$salesforce = ADFOIN_Salesforce::get_instance();
add_action(
    'adfoin_salesforce_job_queue',
    'adfoin_salesforce_job_queue',
    10,
    1
);
function adfoin_salesforce_job_queue(  $data  ) {
    adfoin_salesforce_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Salesforce API
 */
function adfoin_salesforce_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $salesforce = ADFOIN_Salesforce::get_instance();
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $owner_id = ( isset( $data['ownerId'] ) ? $data['ownerId'] : '' );
    unset($data['credId']);
    unset($data['ownerId']);
    // Load credentials if provided
    if ( $cred_id ) {
        $salesforce->load_credentials( $cred_id );
    }
    if ( $task == 'add_lead' ) {
        $campaign_id = ( isset( $data['campaignId'] ) ? $data['campaignId'] : '' );
        unset($data['campaignId']);
        $lead_data = [];
        foreach ( $data as $key => $value ) {
            list( , , $key ) = explode( '__', $key, 3 );
            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
            if ( $parsed_value ) {
                $lead_data[$key] = $parsed_value;
            }
        }
        if ( $owner_id ) {
            $lead_data['OwnerId'] = $owner_id;
        }
        $lead_id = $salesforce->create_or_update_lead( $lead_data, $record );
        if ( $campaign_id && $lead_id ) {
            $salesforce->assign_lead_to_campaign( $lead_id, $campaign_id, $record );
        }
    }
    if ( $task == 'add_contact' ) {
        $account_data = [];
        $contact_data = [];
        $opportunity_data = [];
        $case_data = [];
        foreach ( $data as $key => $value ) {
            list( $object, $type, $key ) = explode( '__', $key, 3 );
            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
            if ( $parsed_value ) {
                if ( $object == 'account' ) {
                    $account_data[$key] = $parsed_value;
                } elseif ( $object == 'contact' ) {
                    $contact_data[$key] = $parsed_value;
                } elseif ( $object == 'opportunity' ) {
                    $opportunity_data[$key] = $parsed_value;
                } elseif ( $object == 'case' ) {
                    $case_data[$key] = $parsed_value;
                }
            }
        }
        if ( !empty( $account_data ) ) {
            if ( $owner_id ) {
                $account_data['OwnerId'] = $owner_id;
            }
            $account_id = $salesforce->create_or_update_account( $account_data, $record );
        }
        if ( !empty( $contact_data ) ) {
            if ( isset( $account_id ) ) {
                $contact_data['AccountId'] = $account_id;
            }
            if ( $owner_id ) {
                $contact_data['OwnerId'] = $owner_id;
            }
            $contact_id = $salesforce->create_or_update_contact( $contact_data, $record );
        }
        if ( !empty( $opportunity_data ) ) {
            if ( isset( $account_id ) ) {
                $opportunity_data['AccountId'] = $account_id;
            }
            if ( isset( $contact_id ) ) {
                $opportunity_data['ContactId'] = $contact_id;
            }
            if ( $owner_id ) {
                $opportunity_data['OwnerId'] = $owner_id;
            }
            $opportunity_id = $salesforce->create_opportunity( $opportunity_data, $record );
        }
        if ( !empty( $case_data ) ) {
            if ( isset( $account_id ) ) {
                $case_data['AccountId'] = $account_id;
            }
            if ( isset( $contact_id ) ) {
                $case_data['ContactId'] = $contact_id;
            }
            if ( $owner_id ) {
                $case_data['OwnerId'] = $owner_id;
            }
            $case_id = $salesforce->create_case( $case_data, $record );
        }
    }
}
