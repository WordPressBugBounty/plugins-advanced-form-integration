<?php

/**
 * PracticePanther — Create Client (Account, with a nested primary_contact)
 * via POST /api/v2/accounts, Create Matter via POST /api/v2/matters.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Bearer {access_token}; tokens refreshed automatically.
 *
 * PracticePanther has no standalone "create contact" endpoint — Contacts
 * only exist nested inside an Account (`primary_contact` / `other_contacts`),
 * and a Matter links to an Account via `account_ref`, not directly to a
 * contact. This mirrors the real /api/v2/accounts and /api/v2/matters
 * schemas (confirmed via the live Swagger docs), not the flat "contacts"
 * resource the previous version of this file assumed.
 *
 * @link https://app.practicepanther.com/content/apidocs/index.html
 * @link https://support.practicepanther.com/en/articles/479897-practicepanther-api
 */

class ADFOIN_PracticePanther extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'practicepanther';

    const authorization_endpoint = 'https://app.practicepanther.com/oauth/authorize';
    const token_endpoint         = 'https://app.practicepanther.com/oauth/token';
    const refresh_token_endpoint = 'https://app.practicepanther.com/oauth/token';
    const api_base_url           = 'https://app.practicepanther.com/api/v2/';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_practicepanther_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_practicepanther_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_practicepanther_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['practicepanther'] = array(
            'title' => __( 'PracticePanther', 'advanced-form-integration' ),
            'tasks' => array(
                'create_account' => __( 'Create Client (Account)', 'advanced-form-integration' ),
                'create_matter'  => __( 'Create Matter', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['practicepanther'] = __( 'PracticePanther', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'practicepanther' !== $current_tab ) {
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
        $instructions .= '<li>' . __( 'PracticePanther enables API access on request — contact their support team to request access and register a Client ID/Secret and Redirect URI.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Give them the Redirect URI below to register.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Once approved, copy the Client ID and Client Secret into the Add Account form here, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect PracticePanther', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'practicepanther',
            __( 'PracticePanther', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="practicepanther-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'create_account' || action.task == 'create_matter'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'create_account' || action.task == 'create_matter'">
                    <td scope="row-title"><label><?php esc_attr_e( 'PracticePanther Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=practicepanther' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_account', 'PracticePanther [PRO]', 'custom fields and tags' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/practicepanther', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by PracticePanther with ?code=…&state=…. Resolves
     * the saved credential, exchanges the code for tokens, and closes the
     * popup with a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'practicepanther' );
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
        foreach ( adfoin_read_credentials( 'practicepanther' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? $entry['clientId']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? $entry['clientSecret'] ?? '';
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

        $platform    = 'practicepanther';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Deletion path.
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

        // Locate any existing record so we can preserve hidden values
        // (client_secret is never re-sent from the browser on update).
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
                // Preserve tokens when nothing material changed.
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
            'state'         => self::issue_oauth_state( 'practicepanther', $id ),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->practicepanther_request( 'accounts?page_size=1', 'GET' );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/practicepanther' );
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
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
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( esc_url_raw( $this->refresh_token_endpoint ), array(
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

        if ( 401 === $code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
        }

        $this->save_data();

        return $response;
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

        $this->cred_id       = $credentials['id'] ?? $cred_id;
        $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        $this->token_expires = isset( $credentials['tokenExpires'] ) ? (int) $credentials['tokenExpires'] : 0;

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'practicepanther' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'practicepanther' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function practicepanther_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = self::api_base_url . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
        );

        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( 'GET' === $method && ! empty( $data ) && is_array( $data ) ) {
            $url = add_query_arg( $data, $url );
        }

        return $this->remote_request( $url, $args, $record );
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

    /**
     * Search Contacts by email (`search_text` query param) and return the
     * matching contact's `account_ref` id, or '' when nothing matches.
     * @link https://app.practicepanther.com/content/apidocs/index.html
     */
    public function find_account_by_contact_email( $email, $record = array() ) {
        if ( ! $email ) {
            return '';
        }
        $response = $this->practicepanther_request( 'contacts', 'GET', array( 'search_text' => $email ), $record );
        if ( is_wp_error( $response ) ) {
            return '';
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['data'][0]['account_ref']['id'] ?? ( $body[0]['account_ref']['id'] ?? '' );
    }
}

ADFOIN_PracticePanther::get_instance();

add_action( 'wp_ajax_adfoin_get_practicepanther_fields', 'adfoin_get_practicepanther_fields' );
function adfoin_get_practicepanther_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_account';

    if ( $task === 'create_matter' ) {
        $fields = array(
            array( 'key' => 'name',          'value' => 'Matter Name',  'description' => '' ),
            array( 'key' => 'number',        'value' => 'Matter Number','description' => '' ),
            array( 'key' => 'client_email',  'value' => 'Client Email', 'description' => 'Looks up (or creates) the client account and links this matter to it.' ),
            array( 'key' => 'status',        'value' => 'Status',       'description' => '' ),
            array( 'key' => 'openDate',      'value' => 'Open Date',    'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'closeDate',     'value' => 'Close Date',   'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'tags',          'value' => 'Tags',         'description' => 'Comma-separated' ),
            array( 'key' => 'note',          'value' => 'Note',         'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phoneMobile','value' => 'Mobile Phone', 'description' => '' ),
            array( 'key' => 'phoneHome', 'value' => 'Home Phone', 'description' => '' ),
            array( 'key' => 'phoneWork', 'value' => 'Work Phone', 'description' => '' ),
            array( 'key' => 'companyName','value' => 'Company Name', 'description' => 'Leave blank for an individual client.' ),
            array( 'key' => 'address1',  'value' => 'Address Line 1', 'description' => '' ),
            array( 'key' => 'address2',  'value' => 'Address Line 2', 'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

/**
 * Build a PracticePanther Account payload (with a nested primary_contact)
 * from parsed field values. Shared with the Pro tier.
 * @link https://app.practicepanther.com/content/apidocs/index.html
 */
function adfoin_practicepanther_build_account_payload( $fields ) {
    $contact = array_filter( array(
        'first_name'   => $fields['firstName']   ?? '',
        'middle_name'  => $fields['middleName']  ?? '',
        'last_name'    => $fields['lastName']    ?? '',
        'email'        => $fields['email']       ?? '',
        'phone_mobile' => $fields['phoneMobile'] ?? '',
        'phone_home'   => $fields['phoneHome']   ?? '',
        'phone_work'   => $fields['phoneWork']   ?? '',
    ) );

    $account = array();
    if ( ! empty( $fields['companyName'] ) ) {
        $account['company_name'] = $fields['companyName'];
    } elseif ( ! empty( $contact['first_name'] ) || ! empty( $contact['last_name'] ) ) {
        // No company — display the account by the individual's name.
        $account['display_name'] = trim( ( $contact['first_name'] ?? '' ) . ' ' . ( $contact['last_name'] ?? '' ) );
    }
    if ( ! empty( $fields['note'] ) ) $account['notes'] = $fields['note'];

    foreach ( array( 'address1' => 'address_street_1', 'address2' => 'address_street_2', 'city' => 'address_city', 'state' => 'address_state', 'zip' => 'address_zip_code', 'country' => 'address_country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $account[ $remote ] = $fields[ $local ];
    }

    if ( $contact ) $account['primary_contact'] = $contact;

    return $account;
}

function adfoin_practicepanther_create_account( $fields, $record, $cred_id ) {
    $pp = ADFOIN_PracticePanther::get_instance();
    if ( ! $pp->set_credentials( $cred_id ) ) {
        return;
    }
    $account = adfoin_practicepanther_build_account_payload( $fields );
    if ( empty( $account['primary_contact'] ) ) {
        return; // Nothing to create.
    }
    return $pp->practicepanther_request( 'accounts', 'POST', $account, $record );
}

/**
 * Matters link to an Account (`account_ref`), not a contact directly. Find
 * (or create) the Account by looking up the client's email among Contacts,
 * falling back to creating a brand-new Account for that email.
 */
function adfoin_practicepanther_create_matter( $fields, $record, $cred_id ) {
    $pp = ADFOIN_PracticePanther::get_instance();
    if ( ! $pp->set_credentials( $cred_id ) ) {
        return;
    }

    $matter = array();
    foreach ( array( 'name' => 'name', 'number' => 'number', 'status' => 'status', 'openDate' => 'open_date', 'closeDate' => 'close_date' ) as $local => $remote ) {
        if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $matter[ $remote ] = $fields[ $local ];
    }
    if ( ! empty( $fields['note'] ) ) $matter['notes'] = $fields['note'];
    if ( ! empty( $fields['tags'] ) ) {
        $matter['tags'] = is_array( $fields['tags'] ) ? $fields['tags'] : array_map( 'trim', explode( ',', $fields['tags'] ) );
    }

    if ( ! empty( $fields['client_email'] ) ) {
        $account_id = $pp->find_account_by_contact_email( $fields['client_email'], $record );
        if ( ! $account_id ) {
            $created = adfoin_practicepanther_create_account( array( 'email' => $fields['client_email'] ), $record, $cred_id );
            if ( ! is_wp_error( $created ) ) {
                $body       = json_decode( wp_remote_retrieve_body( $created ), true );
                $account_id = $body['id'] ?? '';
            }
        }
        if ( $account_id ) {
            $matter['account_ref'] = array( 'id' => $account_id );
        }
    }

    if ( empty( $matter['account_ref'] ) ) {
        return; // PracticePanther requires account_ref on every Matter.
    }

    return $pp->practicepanther_request( 'matters', 'POST', $matter, $record );
}

add_action( 'adfoin_practicepanther_job_queue', 'adfoin_practicepanther_job_queue', 10, 1 );
function adfoin_practicepanther_job_queue( $data ) {
    adfoin_practicepanther_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_practicepanther_send_data( $record, $posted_data ) {
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
    if ( $record['task'] === 'create_account' ) {
        adfoin_practicepanther_create_account( $fields, $record, $cred_id );
    } elseif ( $record['task'] === 'create_matter' ) {
        adfoin_practicepanther_create_matter( $fields, $record, $cred_id );
    }
}
