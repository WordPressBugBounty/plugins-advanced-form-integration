<?php

/**
 * Jungo — mortgage/real-estate CRM built entirely as a managed package on
 * top of Salesforce (confirmed: "The API call originates in the user's
 * Jungo (Salesforce) and goes directly to the [LOS] API, with data not
 * going to or through a Jungo server" — Jungo support docs). There is no
 * separate Jungo-hosted API; integrating with Jungo means writing records
 * into the customer's own Salesforce org via the standard Salesforce REST
 * API, authenticated with a real OAuth2 (authorization-code + refresh)
 * connection to that org — exactly like the standalone Salesforce action
 * elsewhere in this plugin.
 *
 * The previous version of this file had the right base architecture
 * (Salesforce REST calls against an instance URL) but asked the user to
 * paste a static "OAuth Access Token" with no refresh path, and hardcoded
 * loan fields under an invented `mpg__` namespace prefix — Jungo's actual
 * Salesforce managed-package namespace is not publicly documented anywhere
 * (Salesforce doesn't expose installed-package namespaces in any public
 * registry), so guessing one is unsafe. Also, Jungo confirmed to have a
 * **separate custom "Loans" object** distinct from Contact/Lead — loan
 * fields likely don't live on Contact at all.
 *
 * Fix: proper OAuth2 popup flow (matching Clio/Dotloop) so `instance_url`
 * is fetched automatically instead of pasted, tokens are refreshed, and a
 * user-configurable Salesforce object + generic custom-field mapping
 * (cf__<Field_API_Name>) replaces the hardcoded/guessed mpg__ fields —
 * this works correctly regardless of what Jungo's real namespace turns out
 * to be, since the user supplies their own org's actual field API names
 * (visible in Salesforce Setup → Object Manager).
 *
 * @link https://ijungo.com/powered-by-salesforce/
 */

class ADFOIN_Jungo extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'jungo';

    protected $instance_url = '';

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

        add_action( 'wp_ajax_adfoin_get_jungo_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_jungo_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_jungo_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['jungo'] = array(
            'title' => __( 'Jungo', 'advanced-form-integration' ),
            'tasks' => array( 'create_contact' => __( 'Create Contact / Loan Prospect', 'advanced-form-integration' ) ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['jungo'] = __( 'Jungo', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'jungo' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'Consumer Key', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Consumer Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'login_domain',
                'label'         => __( 'Environment', 'advanced-form-integration' ),
                'type'          => 'select',
                'required'      => false,
                'show_in_table' => false,
                'options'       => array(
                    'login.salesforce.com' => 'Production',
                    'test.salesforce.com'  => 'Sandbox',
                ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'In your Jungo/Salesforce org, go to Setup > App Manager > New Connected App.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Enable OAuth Settings and paste the Callback URL below.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Add the "Manage user data via APIs (api)" and "Perform requests at any time (refresh_token, offline_access)" OAuth scopes.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Consumer Key and Consumer Secret into the Add Account form here, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Jungo is a managed package on Salesforce, not a separate hosted API — this connects directly to your org, the same way the standalone Salesforce action does.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Jungo (Salesforce)', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'jungo',
            __( 'Jungo', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="jungo-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'create_contact'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                    <td scope="row-title"><label><?php esc_attr_e( 'Jungo Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=jungo' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_contact', 'Jungo [PRO]', 'additional custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/jungo', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'jungo' );
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
        foreach ( adfoin_read_credentials( 'jungo' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
                $this->login_domain  = $entry['login_domain']  ?? 'login.salesforce.com';
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
                $message = $body['error_description'] ?? __( 'Token exchange failed.', 'advanced-form-integration' );
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

        $platform    = 'jungo';
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
        $login_domain  = isset( $_POST['login_domain'] )  ? sanitize_text_field( wp_unslash( $_POST['login_domain'] ) )  : 'login.salesforce.com';

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
            wp_send_json_error( array( 'message' => __( 'Consumer Key and Consumer Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'login_domain'  => $login_domain ?: 'login.salesforce.com',
            'access_token'  => '',
            'refresh_token' => '',
            'instance_url'  => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

                if ( $same ) {
                    $new_data['access_token']  = $cred['access_token']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? '';
                    $new_data['instance_url']  = $cred['instance_url']  ?? '';
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
            'state'         => self::issue_oauth_state( 'jungo', $id ),
        ), 'https://' . ( $login_domain ?: 'login.salesforce.com' ) . '/services/oauth2/authorize' );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->jungo_request( 'sobjects/Contact/describe', 'GET' );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/jungo' );
    }

    protected function get_token_endpoint() {
        return 'https://' . ( $this->login_domain ?: 'login.salesforce.com' ) . '/services/oauth2/token';
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( $this->get_token_endpoint(), array(
            'timeout' => 30,
            'body'    => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
            ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
            if ( ! empty( $body['instance_url'] ) ) {
                $this->instance_url = $body['instance_url'];
            }
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( $this->get_token_endpoint(), array(
            'timeout' => 30,
            'body'    => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
            ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code || 400 === $code ) {
            $this->access_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
            if ( ! empty( $body['instance_url'] ) ) {
                $this->instance_url = $body['instance_url'];
            }
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential( array(
                'instance_url' => $this->instance_url,
            ) );
        }
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }

        $this->cred_id       = $credentials['id'] ?? $cred_id;
        $this->client_id     = $credentials['client_id']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? '';
        $this->instance_url  = $credentials['instance_url']  ?? '';
        $this->login_domain  = $credentials['login_domain']  ?? 'login.salesforce.com';

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'jungo' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'jungo' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function jungo_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        if ( empty( $this->instance_url ) ) {
            return new WP_Error( 'jungo_no_instance', __( 'Jungo/Salesforce instance URL not available — reconnect this account.', 'advanced-form-integration' ) );
        }

        $url    = rtrim( $this->instance_url, '/' ) . '/services/data/v62.0/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );
        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        return $this->remote_request( $url, $args, $record );
    }

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

ADFOIN_Jungo::get_instance();

add_action( 'wp_ajax_adfoin_get_jungo_fields', 'adfoin_get_jungo_fields' );
function adfoin_get_jungo_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'sobject',       'value' => 'Salesforce Object', 'description' => 'API name of the target object — defaults to Contact. Use your org\'s Jungo Loans object API name (Setup > Object Manager) if loan prospects live there instead.' ),
        array( 'key' => 'FirstName',     'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'LastName',      'value' => 'Last Name',  'description' => 'Required by Salesforce' ),
        array( 'key' => 'Email',         'value' => 'Email',      'description' => '' ),
        array( 'key' => 'Phone',         'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'MobilePhone',   'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'MailingStreet', 'value' => 'Street',     'description' => '' ),
        array( 'key' => 'MailingCity',   'value' => 'City',       'description' => '' ),
        array( 'key' => 'MailingState',  'value' => 'State',      'description' => '' ),
        array( 'key' => 'MailingPostalCode', 'value' => 'Zip',    'description' => '' ),
        array( 'key' => 'LeadSource',    'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'Description',   'value' => 'Note',       'description' => '' ),
        array( 'key' => 'cf__field1',    'value' => 'Custom Field 1', 'description' => 'Replace "field1" with the exact Salesforce field API name, e.g. cf__Loan_Type__c' ),
        array( 'key' => 'cf__field2',    'value' => 'Custom Field 2', 'description' => '' ),
        array( 'key' => 'cf__field3',    'value' => 'Custom Field 3', 'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_jungo_build_payload( $fields ) {
    $body = array();
    foreach ( array( 'FirstName', 'LastName', 'Email', 'Phone', 'MobilePhone', 'MailingStreet', 'MailingCity', 'MailingState', 'MailingPostalCode', 'LeadSource', 'Description' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    foreach ( $fields as $k => $v ) {
        if ( strpos( $k, 'cf__' ) === 0 && $v !== '' ) $body[ substr( $k, 4 ) ] = $v;
    }
    return $body;
}

add_action( 'adfoin_jungo_job_queue', 'adfoin_jungo_job_queue', 10, 1 );
function adfoin_jungo_job_queue( $data ) {
    adfoin_jungo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_jungo_send_data( $record, $posted_data ) {
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

    $jungo = ADFOIN_Jungo::get_instance();
    if ( ! $jungo->set_credentials( $cred_id ) ) {
        return;
    }

    $sobject = ! empty( $fields['sobject'] ) ? $fields['sobject'] : 'Contact';
    $jungo->jungo_request( 'sobjects/' . rawurlencode( $sobject ), 'POST', adfoin_jungo_build_payload( $fields ), $record );
}
