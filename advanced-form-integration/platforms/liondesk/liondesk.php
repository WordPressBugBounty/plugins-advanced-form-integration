<?php

class ADFOIN_LionDesk extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'liondesk';

    const service_name           = 'liondesk';
    const authorization_endpoint = 'https://api-v2.liondesk.com/oauth2/authorize';
    const token_endpoint         = 'https://api-v2.liondesk.com/oauth2/token';
    const refresh_token_endpoint = 'https://api-v2.liondesk.com/oauth2/token';

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
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_liondesk_keys' ) );

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
    }

    private function __construct() {

        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( "rest_api_init", array( $this, "create_webhook_route" ) );

        add_filter( 'adfoin_action_providers', array( $this, 'adfoin_liondesk_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'adfoin_liondesk_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'adfoin_liondesk_settings_view' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_liondesk_keys', array( $this, 'adfoin_save_liondesk_keys' ), 10, 0 );

        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_liondesk_credentials', array( $this, 'get_credentials' ) );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_liondesk_credentials', array( $this, 'save_credentials' ) );
        add_action( 'wp_ajax_adfoin_get_liondesk_fields', array( $this, 'ajax_get_fields' ) );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/liondesk',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_webhook_data' ),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();

        $code = isset( $params['code'] ) ? trim( $params['code'] ) : '';

        if ( $code ) {

            $redirect_to = add_query_arg(
                [
                    'service' => 'authorize',
                    'action'  => 'adfoin_liondesk_auth_redirect',
                    'code'    => $code,
                ],
                admin_url( 'admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function adfoin_liondesk_actions( $actions ) {

        $actions['liondesk'] = array(
            'title' => __( 'LionDesk', 'advanced-form-integration' ),
            'tasks' => array(
                'add_contact'   => __( 'Create Contact', 'advanced-form-integration' )
            )
        );

        return $actions;
    }

    public function adfoin_liondesk_settings_tab( $providers ) {
        $providers['liondesk'] = __( 'LionDesk', 'advanced-form-integration' );

        return $providers;
    }

    public function adfoin_liondesk_settings_view( $current_tab ) {
        if( $current_tab != 'liondesk' ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
        $fields = array(
            array(
                'name' => 'clientId',
                'label' => __( 'Client ID', 'advanced-form-integration' ),
                'type' => 'text',
                'required' => true,
                'placeholder' => __( 'Enter your Client ID', 'advanced-form-integration' ),
                'show_in_table' => true
            ),
            array(
                'name' => 'clientSecret',
                'label' => __( 'Client Secret', 'advanced-form-integration' ),
                'type' => 'text',
                'required' => true,
                'mask' => true,
                'placeholder' => __( 'Enter your Client Secret', 'advanced-form-integration' ),
                'show_in_table' => false
            )
        );

        $instructions = sprintf(
            '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol><p><strong>%s:</strong> %s</p>',
            __( 'Login to your LionDesk account and go to the <a href="https://developers.liondesk.com/account/apps" target="_blank">Apps Page</a>.', 'advanced-form-integration' ),
            __( 'Click the button to create an app.', 'advanced-form-integration' ),
            __( 'Enter a name and copy Redirect URI from below.', 'advanced-form-integration' ),
            __( 'A Client ID and Secret will be generated for you, copy both and paste below.', 'advanced-form-integration' ),
            __( 'Click on the Authorize button and grant access.', 'advanced-form-integration' ),
            __( 'Redirect URI', 'advanced-form-integration' ),
            $redirect_uri
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'liondesk', __( 'LionDesk', 'advanced-form-integration' ), $fields, $instructions );
    }

    public function adfoin_save_liondesk_keys() {
        // Security Check
        adfoin_require_manage_options();
        if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_liondesk_settings' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $client_id     = isset( $_POST["adfoin_liondesk_client_id"] ) ? sanitize_text_field( wp_unslash( $_POST["adfoin_liondesk_client_id"] ) ) : "";
        $client_secret = isset( $_POST["adfoin_liondesk_client_secret"] ) ? sanitize_text_field( wp_unslash( $_POST["adfoin_liondesk_client_secret"] ) ) : "";

        if( !$client_id || !$client_secret ) {
            $this->reset_data();
        } else{
            $this->client_id     = trim( $client_id );
            $this->client_secret = trim( $client_secret );

            $this->save_data();
            $this->authorize( 'write' );
        }

        advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=liondesk" );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="liondesk-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'LionDesk Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadFields">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=liondesk' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'add_contact', 'LionDesk [PRO]', 'tags & custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function auth_redirect() {

        $auth   = isset( $_GET['auth'] ) ? trim( $_GET['auth'] ) : '';
        $code   = isset( $_GET['code'] ) ? trim( $_GET['code'] ) : '';
        $action = isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '';
        $state = isset($_GET['state']) ? trim($_GET['state']) : '';

        if ( 'adfoin_liondesk_auth_redirect' == $action ) {
            $code = isset( $_GET['code'] ) ? $_GET['code'] : '';

            if ( $code ) {
                // Legacy callback handling. This platform's credential-save
                // flow never issues an "oauth_manager_<cred_id>" state (no
                // code path here generates one), so the prior branch that
                // special-cased that prefix by trusting it verbatim was
                // removed. It had no legitimate caller and only served as
                // an unauthenticated way to overwrite an arbitrary stored
                // credential's tokens.
                $this->request_token( $code );
            }

            if ( ! empty( $this->access_token ) ) {
                $message = 'success';
            } else {
                $message = 'failed';
            }

            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=liondesk' ) );

            exit();
        }
    }

    protected function request_token( $code ) {

        $body = array(
            'code'          => $code,
            'redirect_uri'  => $this->get_redirect_uri(),
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret
        );

        $request = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( $body )
        ];

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            $this->apply_token_response( $response_body );
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {

        $body = array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
            );

        $request = [
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body )
        ];

        $response      = wp_remote_post( esc_url_raw( $this->refresh_token_endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            $this->apply_token_response( $response_body );
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        // Multi-account flow: persist tokens into the credential record
        // identified by cred_id. Tokens are written under camelCase keys to match what set_credentials reads.
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
            return;
        }

        // Legacy single-account fallback for installs that haven't migrated
        // through the OAuth Manager UI yet.
        update_option( 'adfoin_liondesk_keys', maybe_serialize( array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ) ) );
    }

    protected function reset_data() {

        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';
        $this->refresh_token = '';

        $this->save_data();
    }

    protected function get_redirect_uri() {

        return site_url( '/wp-json/advancedformintegration/liondesk' );
    }

    function liondesk_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {

        $base_url = 'https://api-v2.liondesk.com/';
        $url      = $base_url . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );

        if ( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = $this->remote_request( $url, $args);

        if ( $record ) {
            adfoin_add_to_log($response, $url, $args, $record);
        }

        return $response;
    }

    // Check if contact exists
    public function check_if_contact_exists( $email ) {
        $endpoint = "contacts?email={$email}";
        $data = $this->liondesk_request( $endpoint );
    
        if ( is_wp_error( $data ) ) {
            return false;
        }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $data ) ) {
            return false;
        }
        $body = json_decode( wp_remote_retrieve_body( $data ), true );
        
        if ( isset( $body['data'], $body['data'][0], $body['data'][0]['id'] ) ) {
            return $body['data'][0]['id'];
        } else {
            return false;
        }
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

    public function modify_credentials( $credentials, $platform ) {
        if ( 'liondesk' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }

        // Load legacy credentials for backward compatibility
        $option = (array) maybe_unserialize( get_option( 'adfoin_liondesk_keys' ) );

        if ( ! empty( $option ) && isset( $option['access_token'] ) ) {
            $credentials[] = array(
                'id'            => '123456',
                'title'         => __( 'Untitled', 'advanced-form-integration' ),
                'access_token'  => $option['access_token'],
            );
        }

        return $credentials;
    }

    public function save_credentials() {
        adfoin_verify_nonce();

        $platform = 'liondesk';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && $credentials[ $index ]['id'] === '123456' ) {
                    delete_option( 'adfoin_liondesk_keys' );
                }
                unset( $credentials[ $index ] );
                adfoin_save_credentials( $platform, array_values( $credentials ) );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        // Handle Save/Update
        $id           = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title        = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $access_token = isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $new_data = array(
            'id'           => $id,
            'title'        => $title,
            'access_token' => $access_token,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                $cred = $new_data;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );
        wp_send_json_success( array( 'message' => 'Saved' ) );
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = array(
            array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'address', 'value' => __( 'Address', 'advanced-form-integration' ) ),
            array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'state', 'value' => __( 'State', 'advanced-form-integration' ) ),
            array( 'key' => 'zip', 'value' => __( 'ZIP Code', 'advanced-form-integration' ) ),
            array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
            array( 'key' => 'website', 'value' => __( 'Website', 'advanced-form-integration' ) ),
        );

        wp_send_json_success( $fields );
    }

    /**
     * Set credentials for OAuth Manager
     */
    public function set_credentials( $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'liondesk', $cred_id );

        if ( $credentials ) {
            $this->cred_id       = $cred_id;
            $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
            $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
            $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
            $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        }
    }
}

$liondesk = ADFOIN_LionDesk::get_instance();

add_action( 'adfoin_liondesk_job_queue', 'adfoin_liondesk_job_queue', 10, 1 );

function adfoin_liondesk_job_queue( $data ) {
    adfoin_liondesk_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to LionDesk API
 */
function adfoin_liondesk_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = $record['task'];

    if( $task == 'add_contact' ) {
        $email            = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $s_email          = empty( $data['secondaryEmail'] ) ? '' : adfoin_get_parsed_values( $data['secondaryEmail'], $posted_data );
        $first_name       = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name        = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
        $mobile_phone     = empty( $data['mobilePhone'] ) ? '' : adfoin_get_parsed_values( $data['mobilePhone'], $posted_data );
        $home_phone       = empty( $data['homePhone'] ) ? '' : adfoin_get_parsed_values( $data['homePhone'], $posted_data );
        $office_phone     = empty( $data['officePhone'] ) ? '' : adfoin_get_parsed_values( $data['officePhone'], $posted_data );
        $fax              = empty( $data['fax'] ) ? '' : adfoin_get_parsed_values( $data['fax'], $posted_data );
        $company          = empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data );
        $birthday         = empty( $data['birthday'] ) ? '' : adfoin_get_parsed_values( $data['birthday'], $posted_data );
        $anniversary      = empty( $data['anniversary'] ) ? '' : adfoin_get_parsed_values( $data['anniversary'], $posted_data );
        $spouce_name      = empty( $data['spouseName'] ) ? '' : adfoin_get_parsed_values( $data['spouseName'], $posted_data );
        $spouce_email     = empty( $data['spouseEmail'] ) ? '' : adfoin_get_parsed_values( $data['spouseEmail'], $posted_data );
        $spouce_phone     = empty( $data['spousePhone'] ) ? '' : adfoin_get_parsed_values( $data['spousePhone'], $posted_data );
        $spouce_birthday  = empty( $data['spouseBirthday'] ) ? '' : adfoin_get_parsed_values( $data['spouseBirthday'], $posted_data );
        $address1_type    = empty( $data['address1_type'] ) ? '' : adfoin_get_parsed_values( $data['address1_type'], $posted_data );
        $address1_street1 = empty( $data['address1_street1'] ) ? '' : adfoin_get_parsed_values( $data['address1_street1'], $posted_data );
        $address1_street2 = empty( $data['address1_street2'] ) ? '' : adfoin_get_parsed_values( $data['address1_street2'], $posted_data );
        $address1_zip     = empty( $data['address1_zip'] ) ? '' : adfoin_get_parsed_values( $data['address1_zip'], $posted_data );
        $address1_city    = empty( $data['address1_city'] ) ? '' : adfoin_get_parsed_values( $data['address1_city'], $posted_data );
        $address1_state   = empty( $data['address1_state'] ) ? '' : adfoin_get_parsed_values( $data['address1_state'], $posted_data );
        $address2_type    = empty( $data['address2_type'] ) ? '' : adfoin_get_parsed_values( $data['address2_type'], $posted_data );
        $address2_street1 = empty( $data['address2_street1'] ) ? '' : adfoin_get_parsed_values( $data['address2_street1'], $posted_data );
        $address2_street2 = empty( $data['address2_street2'] ) ? '' : adfoin_get_parsed_values( $data['address2_street2'], $posted_data );
        $address2_zip     = empty( $data['address2_zip'] ) ? '' : adfoin_get_parsed_values( $data['address2_zip'], $posted_data );
        $address2_city    = empty( $data['address2_city'] ) ? '' : adfoin_get_parsed_values( $data['address2_city'], $posted_data );
        $address2_state   = empty( $data['address2_state'] ) ? '' : adfoin_get_parsed_values( $data['address2_state'], $posted_data );

        $contact_id = '';
        $body = array(
            'first_name'      => $first_name,
            'last_name'       => $last_name,
            'email'           => $email,
            'secondary_email' => $s_email,
            'mobile_phone'    => $mobile_phone,
            'home_phone'      => $home_phone,
            'office_phone'    => $office_phone,
            'fax'             => $fax,
            'company'         => $company,
            'birthday'        => $birthday,
            'anniversary'     => $anniversary,
            'spouce_name'     => $spouce_name,
            'spouce_email'    => $spouce_email,
            'spouce_phone'    => $spouce_phone,
            'spouce_birthday' => $spouce_birthday,
        );

        $body = array_filter( $body );

        $liondesk   = ADFOIN_LionDesk::get_instance();

        // Set credentials if provided
        if ( $cred_id ) {
            $liondesk->set_credentials( $cred_id );
        }

        $contact_id = $liondesk->check_if_contact_exists( $email );

        if ( $contact_id ) {
            $response = $liondesk->liondesk_request( "contacts/" . $contact_id, 'PATCH', $body, $record);
        } else {
            $response = $liondesk->liondesk_request( "contacts", 'POST', $body, $record);
        }

        if( !is_wp_error( $response ) ) {
            $response_body = json_decode( wp_remote_retrieve_body( $response ) );
            $contact_id  = $response_body->id;
        }

        if( $contact_id && $address1_type && $address1_street1 ) {
            $address1 = array(
                'type'             => $address1_type,
                'street_address_1' => $address1_street1
            );

            if( $address1_street2 ) {
                $address1['street_address_2'] = $address1_street2;
            }

            if( $address1_zip ) {
                $address1['zip'] = $address1_zip;
            }

            if( $address1_city ) {
                $address1['city'] = $address1_city;
            }

            if( $address1_state ) {
                $address1['state'] = $address1_state;
            }

            $address1_url = "contacts/{$contact_id}/addresses";
            $address1_response = $liondesk->liondesk_request( $address1_url, 'POST', $address1, $record );
        }

        if( $contact_id && $address2_type && $address2_street1 ) {
            $address2 = array(
                'type'             => $address2_type,
                'street_address_1' => $address2_street1
            );

            if( $address2_street2 ) {
                $address2['street_address_2'] = $address2_street2;
            }

            if( $address2_zip ) {
                $address2['zip'] = $address2_zip;
            }

            if( $address2_city ) {
                $address2['city'] = $address2_city;
            }

            if( $address2_state ) {
                $address2['state'] = $address2_state;
            }

            $address2_url = "contacts/{$contact_id}/addresses";
            $address2_response = $liondesk->liondesk_request( $address2_url, 'POST', $address2, $record );
        }
    }

    return;
}