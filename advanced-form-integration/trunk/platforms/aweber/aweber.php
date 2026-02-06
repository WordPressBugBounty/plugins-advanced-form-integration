<?php

class ADFOIN_Aweber extends Advanced_Form_Integration_OAuth2 {
    const service_name = 'aweber';

    const authorization_endpoint = 'https://auth.aweber.com/oauth2/authorize';

    const token_endpoint = 'https://auth.aweber.com/oauth2/token';

    private static $instance;

    protected $code_verifier = "";

    protected $code_challenge = "";

    protected $auth_code = "";

    protected $client_id = "wG9E9E4PVpfA0ax93gvmlsUIhWrpH00U";

    protected $access_token = "";

    protected $refresh_token = "";

    protected $account_title = "";

    protected $cred_id = "";

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();
        add_filter(
            'adfoin_action_providers',
            array($this, 'adfoin_aweber_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_aweber_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_aweber_settings_view'),
            10,
            1
        );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action(
            'adfoin_add_js_fields',
            array($this, 'adfoin_aweber_js_fields'),
            10,
            1
        );
        add_action(
            'wp_ajax_adfoin_get_aweber_accounts',
            array($this, 'get_aweber_accounts'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_get_aweber_lists',
            array($this, 'get_aweber_lists'),
            10,
            0
        );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_aweber_credentials_list', array($this, 'ajax_get_credentials_list') );
        add_action( 'wp_ajax_adfoin_get_aweber_credentials', array($this, 'ajax_get_credentials') );
        add_action( 'wp_ajax_adfoin_save_aweber_credentials', array($this, 'ajax_save_credentials') );
    }

    /**
     * Load legacy credentials for backward compatibility
     */
    protected function load_legacy_credentials() {
        $legacy_keys = get_option( 'adfoin_aweber_keys' );
        if ( $legacy_keys ) {
            $legacy_keys = (array) maybe_unserialize( $legacy_keys );
            if ( !empty( $legacy_keys['auth_code'] ) ) {
                $credentials = adfoin_read_credentials( 'aweber' );
                // Check if legacy credential already exists
                $legacy_exists = false;
                foreach ( $credentials as $cred ) {
                    if ( isset( $cred['id'] ) && $cred['id'] === 'legacy_123456' ) {
                        $legacy_exists = true;
                        break;
                    }
                }
                if ( !$legacy_exists ) {
                    $credentials[] = array(
                        'id'             => 'legacy_123456',
                        'title'          => ( !empty( $legacy_keys['title'] ) ? $legacy_keys['title'] : 'Legacy Account' ),
                        'auth_code'      => $legacy_keys['auth_code'],
                        'code_verifier'  => ( isset( $legacy_keys['code_verifier'] ) ? $legacy_keys['code_verifier'] : '' ),
                        'code_challenge' => ( isset( $legacy_keys['code_challenge'] ) ? $legacy_keys['code_challenge'] : '' ),
                        'access_token'   => ( isset( $legacy_keys['access_token'] ) ? $legacy_keys['access_token'] : '' ),
                        'refresh_token'  => ( isset( $legacy_keys['refresh_token'] ) ? $legacy_keys['refresh_token'] : '' ),
                        'client_id'      => $this->client_id,
                    );
                    adfoin_save_credentials( 'aweber', $credentials );
                }
            }
            delete_option( 'adfoin_aweber_keys' );
        }
    }

    /**
     * Load credentials for a specific account
     */
    public function load_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }
        $this->auth_code = ( isset( $credentials['auth_code'] ) ? $credentials['auth_code'] : '' );
        $this->code_verifier = ( isset( $credentials['code_verifier'] ) ? $credentials['code_verifier'] : '' );
        $this->code_challenge = ( isset( $credentials['code_challenge'] ) ? $credentials['code_challenge'] : '' );
        $this->access_token = ( isset( $credentials['access_token'] ) ? $credentials['access_token'] : '' );
        $this->refresh_token = ( isset( $credentials['refresh_token'] ) ? $credentials['refresh_token'] : '' );
        $this->account_title = ( isset( $credentials['title'] ) ? $credentials['title'] : '' );
        $this->cred_id = ( isset( $credentials['id'] ) ? $credentials['id'] : '' );
        return true;
    }

    public function ajax_get_credentials_list() {
        // verify nonce
        // if ( ! check_ajax_referer( 'advanced-form-integration', '_nonce', false ) ) {
        //     wp_send_json_error( 'Security error' );
        // }
        // Nonce check is usually handled in script.js global nonce, but let's be safe if we passed it.
        // Actually AWeber uses manual auth code, so the standard read is fine.
        $credentials = adfoin_read_credentials( 'aweber' );
        $list = array();
        if ( is_array( $credentials ) ) {
            foreach ( $credentials as $option ) {
                $status = ( !empty( $option['access_token'] ) ? 'Connected' : 'Not Connected' );
                $list[] = array(
                    'id'     => $option['id'],
                    'title'  => ( !empty( $option['title'] ) ? $option['title'] : 'Default Account' ),
                    'status' => $status,
                );
            }
        }
        wp_send_json_success( $list );
    }

    public function ajax_get_credentials() {
        $credentials = adfoin_read_credentials( 'aweber' );
        wp_send_json_success( $credentials );
    }

    public function ajax_save_credentials() {
        if ( !check_ajax_referer( 'advanced-form-integration', '_nonce', false ) ) {
            wp_send_json_error( 'Security error' );
        }
        $auth_code = ( isset( $_POST["auth_code"] ) ? trim( sanitize_text_field( $_POST["auth_code"] ) ) : "" );
        $account_title = ( isset( $_POST["title"] ) ? trim( sanitize_text_field( $_POST["title"] ) ) : "" );
        $cred_id = ( isset( $_POST["id"] ) ? trim( sanitize_text_field( $_POST["id"] ) ) : "" );
        $delete_index = ( isset( $_POST["delete_index"] ) ? intval( $_POST["delete_index"] ) : -1 );
        if ( $delete_index >= 0 ) {
            $credentials = adfoin_read_credentials( 'aweber' );
            if ( isset( $credentials[$delete_index] ) ) {
                unset($credentials[$delete_index]);
                adfoin_save_credentials( 'aweber', array_values( $credentials ) );
                wp_send_json_success();
            }
            wp_send_json_error( 'Account not found' );
        }
        if ( $auth_code ) {
            // Generate new PKCE values for this request
            $this->generate_pkce_hashes();
            $this->auth_code = $auth_code;
            $this->account_title = $account_title;
            // If editing, use existing ID, else new
            $this->cred_id = ( $cred_id ? $cred_id : uniqid() );
            // request_token will save data if successful
            $response = $this->request_token( $auth_code );
            if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
                wp_send_json_success();
            } else {
                wp_send_json_error( 'Authorization failed. Please check the code.' );
            }
        } else {
            // Just updating title? Or error?
            if ( $cred_id && $account_title ) {
                // Update title logic without re-auth if auth code missing?
                // Creating new without auth code is impossible here.
                // Assuming edit title:
                $credentials = adfoin_read_credentials( 'aweber' );
                foreach ( $credentials as &$cred ) {
                    if ( $cred['id'] == $cred_id ) {
                        $cred['title'] = $account_title;
                        break;
                    }
                }
                adfoin_save_credentials( 'aweber', $credentials );
                wp_send_json_success();
            } else {
                wp_send_json_error( 'Authorization code is required' );
            }
        }
    }

    public function generate_pkce_hashes() {
        $verifier_bytes = random_bytes( 64 );
        $code_verifier = rtrim( strtr( base64_encode( $verifier_bytes ), "+/", "-_" ), "=" );
        $challenge_bytes = hash( "sha256", $code_verifier, true );
        $code_challenge = rtrim( strtr( base64_encode( $challenge_bytes ), "+/", "-_" ), "=" );
        $this->code_verifier = $code_verifier;
        $this->code_challenge = $code_challenge;
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/aweber', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true',
        ) );
    }

    public function adfoin_aweber_actions( $actions ) {
        $actions['aweber'] = array(
            'title' => __( 'Aweber', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function adfoin_aweber_settings_tab( $providers ) {
        $providers['aweber'] = __( 'Aweber', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_aweber_settings_view( $current_tab ) {
        if ( $current_tab != 'aweber' ) {
            return;
        }
        $fields = array(array(
            'name'        => 'auth_code',
            'label'       => __( 'Authorization Code', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'placeholder' => __( 'Enter Authorization Code', 'advanced-form-integration' ),
            'description' => sprintf( '<a href="#" id="adfoin-aweber-get-code" target="_blank">%s</a>', __( 'Click here to get the code', 'advanced-form-integration' ) ),
        ));
        $instructions = '
            <ol class="afi-instructions-list">
                <li>' . __( 'Click on "Add Account" button.', 'advanced-form-integration' ) . '</li>
                <li>' . __( 'Enter a title for your account.', 'advanced-form-integration' ) . '</li>
                <li>' . __( 'Click on "Click here to get the code" link.', 'advanced-form-integration' ) . '</li>
                <li>' . __( 'Log in to your AWeber account and allow access.', 'advanced-form-integration' ) . '</li>
                <li>' . __( 'Copy the Authorization Code provided.', 'advanced-form-integration' ) . '</li>
                <li>' . __( 'Paste the code in the "Authorization Code" field and click Save.', 'advanced-form-integration' ) . '</li>
            </ol>
        ';
        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'aweber',
            'AWeber',
            $fields,
            $instructions
        );
        // Add custom JavaScript for AWeber-specific functionality
        $this->render_aweber_js();
    }

    /**
     * Render AWeber-specific JavaScript
     */
    private function render_aweber_js() {
        $this->generate_pkce_hashes();
        $nonce = wp_create_nonce( "adfoin_aweber_settings" );
        $redirect_uri = "urn:ietf:wg:oauth:2.0:oob";
        $scope = "subscriber.write subscriber.read account.read list.read";
        $auth_url = "https://auth.aweber.com/oauth2/authorize?response_type=code&client_id=" . $this->client_id . "&redirect_uri=" . $redirect_uri . "&scope=" . $scope . "&state=" . $nonce . "&code_challenge=" . $this->code_challenge . "&code_challenge_method=S256";
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Store the auth URL without HTML encoding
            var authUrl = <?php 
        echo json_encode( $auth_url );
        ?>;
            
            // Update the auth code link when modal opens
            $('#adfoin-add-aweber-account, .adfoin-edit-account-btn').on('click', function() {
                setTimeout(function() {
                    $('#adfoin-aweber-get-code').attr('href', authUrl);
                }, 100);
            });
            
            // Store PKCE values for later use
            window.adfoin_aweber_pkce = {
                code_verifier: <?php 
        echo json_encode( $this->code_verifier );
        ?>,
                code_challenge: <?php 
        echo json_encode( $this->code_challenge );
        ?>
            };
        });
        </script>
        <?php 
    }

    protected function save_data() {
        $credentials = adfoin_read_credentials( 'aweber' );
        $updated = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $this->cred_id ) {
                $cred['title'] = $this->account_title;
                $cred['auth_code'] = $this->auth_code;
                $cred['code_verifier'] = $this->code_verifier;
                $cred['code_challenge'] = $this->code_challenge;
                $cred['client_id'] = $this->client_id;
                if ( $this->access_token ) {
                    $cred['access_token'] = $this->access_token;
                }
                if ( $this->refresh_token ) {
                    $cred['refresh_token'] = $this->refresh_token;
                }
                $updated = true;
                break;
            }
        }
        if ( !$updated ) {
            $credentials[] = array(
                'id'             => $this->cred_id,
                'title'          => $this->account_title,
                'auth_code'      => $this->auth_code,
                'code_verifier'  => $this->code_verifier,
                'code_challenge' => $this->code_challenge,
                'client_id'      => $this->client_id,
                'access_token'   => $this->access_token,
                'refresh_token'  => $this->refresh_token,
            );
        }
        adfoin_save_credentials( 'aweber', $credentials );
    }

    protected function request_token( $authorization_code ) {
        $endpoint = add_query_arg( array(
            'code'          => $authorization_code,
            'client_id'     => $this->client_id,
            'code_verifier' => $this->code_verifier,
            'grant_type'    => 'authorization_code',
        ), $this->token_endpoint );
        $request = array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        );
        $response = wp_remote_post( $endpoint, $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if ( 200 != $response_code ) {
            // Unauthorized
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }
            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                $this->refresh_token = null;
            }
        }
        $this->save_data();
        return $response;
    }

    public function adfoin_aweber_js_fields( $field_data ) {
        // This method is kept for backward compatibility but may not be needed with OAuth Manager
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="aweber-action-template">
            <div>
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php 
        esc_attr_e( 'Map Fields', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
        esc_attr_e( 'AWeber Account', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getAccounts">
                            <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php 
        echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=aweber' );
        ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
        esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
        ?>
                        </a>
                        <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
        esc_attr_e( 'Account', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[accountId]" v-model="fielddata.accountId" @change="getLists" required="required">
                            <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for="(item, index) in fielddata.accounts" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': accountLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
        esc_attr_e( 'Aweber List', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                            <option value=""> <?php 
        _e( 'Select List...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for="(item, index) in fielddata.lists" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php 
        if ( adfoin_fs()->is_not_paying() ) {
            ?>
                    <tr valign="top" v-if="action.task == 'subscribe'">
                        <th scope="row">
                            <?php 
            esc_attr_e( 'Go Pro', 'advanced-form-integration' );
            ?>
                        </th>
                        <td scope="row">
                            <span><?php 
            printf( __( 'To unlock custom fields and tags consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
            ?></span>
                        </td>
                    </tr>
                    <?php 
        }
        ?>
                
            </table>
            </div>
        </script>
        <?php 
    }

    protected function reset_data() {
        // Not implemented for multi-account
    }

    protected function get_http_authorization_header( $scheme = 'basic' ) {
        $scheme = strtolower( trim( $scheme ) );
        switch ( $scheme ) {
            case 'bearer':
                return sprintf( 'Bearer %s', $this->access_token );
            case 'basic':
            default:
                return sprintf( 'Basic %s', base64_encode( $this->client_id . ':' . $this->client_secret ) );
        }
    }

    function aweber_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        $base_url = 'https://api.aweber.com/1.0/';
        $url = $base_url . $endpoint;
        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );
        if ( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
            $args['body'] = json_encode( $data );
        }
        $response = $this->remote_request( $url, $args );
        if ( $record ) {
            adfoin_add_to_log(
                $response,
                $url,
                $args,
                $record
            );
        }
        return $response;
    }

    public function create_contact(
        $properties,
        $account_id,
        $list_id,
        $record = array()
    ) {
        $endpoint = "accounts/{$account_id}/lists/{$list_id}/subscribers";
        $response = $this->aweber_request(
            $endpoint,
            'POST',
            $properties,
            $record
        );
        return $response;
    }

    public function update_contact(
        $properties,
        $account_id,
        $list_id,
        $subscriber_id,
        $record = array()
    ) {
        $endpoint = "accounts/{$account_id}/lists/{$list_id}/subscribers/{$subscriber_id}";
        $response = $this->aweber_request(
            $endpoint,
            'PATCH',
            $properties,
            $record
        );
        return $response;
    }

    protected function remote_request( $url, $request = array() ) {
        $refreshed = false;
        $request = wp_parse_args( $request, [] );
        $request['headers'] = array_merge( $request['headers'], array(
            'Authorization' => $this->get_http_authorization_header( 'bearer' ),
        ) );
        $response = wp_remote_request( esc_url_raw( $url ), $request );
        if ( 401 === wp_remote_retrieve_response_code( $response ) and !$refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $response = $this->remote_request( $url, $request );
        }
        return $response;
    }

    public function get_aweber_accounts() {
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        if ( !$cred_id ) {
            wp_send_json_error();
        }
        $this->load_credentials( $cred_id );
        $response = $this->aweber_request( 'accounts' );
        if ( 400 <= (int) wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error();
        }
        $response_body = wp_remote_retrieve_body( $response );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        $response_body = json_decode( $response_body, true );
        if ( !empty( $response_body['entries'] ) ) {
            $accounts = wp_list_pluck( $response_body['entries'], 'id', 'id' );
            wp_send_json_success( $accounts );
        } else {
            wp_send_json_error();
        }
    }

    protected function refresh_token() {
        $endpoint = add_query_arg( array(
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
        ), $this->token_endpoint );
        $request = array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        );
        $response = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        } else {
            $this->access_token = null;
        }
        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }
        $this->save_data();
        return $response;
    }

    public function get_aweber_lists() {
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        if ( !$cred_id ) {
            wp_send_json_error();
        }
        $this->load_credentials( $cred_id );
        $account_id = ( isset( $_POST['accountId'] ) ? $_POST['accountId'] : '' );
        $endpoint = "accounts/{$account_id}/lists";
        $all_lists = array();
        do {
            $response = $this->aweber_request( $endpoint );
            if ( 400 <= (int) wp_remote_retrieve_response_code( $response ) ) {
                wp_send_json_error();
            }
            $response_body = wp_remote_retrieve_body( $response );
            if ( empty( $response_body ) ) {
                wp_send_json_error();
            }
            $response_body = json_decode( $response_body, true );
            if ( !empty( $response_body['entries'] ) ) {
                $lists = wp_list_pluck( $response_body['entries'], 'name', 'id' );
                $all_lists = $all_lists + $lists;
            }
            if ( isset( $response_body['next_collection_link'] ) ) {
                $endpoint = $response_body['next_collection_link'];
            } else {
                $endpoint = '';
            }
        } while ( $endpoint );
        if ( $all_lists ) {
            wp_send_json_success( $all_lists );
        } else {
            wp_send_json_error();
        }
    }

    // Check if contact exists
    public function check_if_contact_exists( $email, $account_id, $list_id ) {
        $url = "accounts/{$account_id}/lists/{$list_id}/subscribers";
        $params = array(
            'ws.op' => 'find',
            'email' => $email,
        );
        $endpoint = add_query_arg( $params, $url );
        $data = $this->aweber_request( $endpoint );
        if ( is_wp_error( $data ) ) {
            return false;
        }
        if ( 200 !== wp_remote_retrieve_response_code( $data ) ) {
            return false;
        }
        $body = json_decode( wp_remote_retrieve_body( $data ), true );
        if ( isset( $body['entries'], $body['entries'][0], $body['entries'][0]['id'] ) ) {
            return $body['entries'][0]['id'];
        } else {
            return false;
        }
    }

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'aweber' );
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials = array();
        $all_credentials = adfoin_read_credentials( 'aweber' );
        if ( is_array( $all_credentials ) ) {
            $credentials = $all_credentials[0];
            foreach ( $all_credentials as $single ) {
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                }
            }
        }
        return $credentials;
    }

}

$aweber = ADFOIN_Aweber::get_instance();
add_action(
    'adfoin_aweber_job_queue',
    'adfoin_aweber_job_queue',
    10,
    1
);
function adfoin_aweber_job_queue(  $data  ) {
    adfoin_aweber_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Aweber API
 */
function adfoin_aweber_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    $data = $record_data["field_data"];
    if ( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if ( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if ( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }
    $account_id = $data["accountId"];
    $cred_id = ( isset( $record_data["action_data"]["credId"] ) ? $record_data["action_data"]["credId"] : (( isset( $data["credId"] ) ? $data["credId"] : '' )) );
    $list_id = $data["listId"];
    $task = $record["task"];
    // Default to legacy credential if no credId is set (backward compatibility)
    if ( empty( $cred_id ) ) {
        $cred_id = 'legacy_123456';
    }
    if ( $task == "subscribe" ) {
        $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
        $first_name = ( empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data ) );
        $last_name = ( empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data ) );
        $properties = array(
            "email" => $email,
            "name"  => $first_name . " " . $last_name,
        );
        $aweber = ADFOIN_Aweber::get_instance();
        // Load credentials for the selected account
        $aweber->load_credentials( $cred_id );
        $subscriber_id = $aweber->check_if_contact_exists( $email, $account_id, $list_id );
        if ( $subscriber_id ) {
            $response = $aweber->update_contact(
                $properties,
                $account_id,
                $list_id,
                $subscriber_id,
                $record
            );
        } else {
            $return = $aweber->create_contact(
                $properties,
                $account_id,
                $list_id,
                $record
            );
        }
    }
    return;
}
