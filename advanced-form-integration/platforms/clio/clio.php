<?php

/**
 * Clio — Create Contact via POST /api/v4/contacts, Create Matter via
 * POST /api/v4/matters (linked to a client contact found/created by email).
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Bearer {access_token}; tokens refreshed automatically. Clio is
 * multi-region — the OAuth endpoints AND the API base both live on the
 * account's region host (app.clio.com / eu.app.clio.com / ca.app.clio.com /
 * au.app.clio.com); mixing regions returns errors.
 *
 * @link https://docs.developers.clio.com/api-docs/authorization/
 * @link https://docs.developers.clio.com/api-reference/
 */

class ADFOIN_Clio extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'clio';

    public $region = 'us';

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

        add_action( 'wp_ajax_adfoin_get_clio_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_clio_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_clio_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['clio'] = array(
            'title' => __( 'Clio', 'advanced-form-integration' ),
            'tasks' => array(
                'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
                'create_matter'  => __( 'Create Matter', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['clio'] = __( 'Clio', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'clio' !== $current_tab ) {
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
            array(
                'name'          => 'region',
                'label'         => __( 'Region', 'advanced-form-integration' ),
                'type'          => 'select',
                'required'      => false,
                'show_in_table' => false,
                'options'       => array(
                    'us' => 'United States (app.clio.com)',
                    'eu' => 'Europe (eu.app.clio.com)',
                    'ca' => 'Canada (ca.app.clio.com)',
                    'au' => 'Australia (au.app.clio.com)',
                ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s and create a new application.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.clio.com/settings/developer_applications">Clio Developer Applications</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into the application\'s Redirect URI field.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret into the Add Account form here, choose your region, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Clio', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'clio',
            __( 'Clio', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="clio-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_matter'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_matter'">
                    <td scope="row-title"><label><?php esc_attr_e( 'Clio Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=clio' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_contact', 'Clio [PRO]', 'custom fields and tags' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/clio', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Clio with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, and closes the popup with
     * a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'clio' );
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
        foreach ( adfoin_read_credentials( 'clio' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? $entry['clientId']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? $entry['clientSecret'] ?? '';
                $this->region        = $entry['region']        ?? 'us';
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

        $platform    = 'clio';
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
        $region        = isset( $_POST['region'] )        ? sanitize_text_field( wp_unslash( $_POST['region'] ) )        : 'us';

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
            'region'        => $region,
            'access_token'  => '',
            'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                // Preserve tokens when nothing material changed.
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret )
                    && ( ( $cred['region'] ?? 'us' ) === $region );

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
            'state'         => self::issue_oauth_state( 'clio', $id ),
        ), $this->get_oauth_endpoint( 'authorize', $region ) );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->clio_request( 'users/who_am_i.json', 'GET' );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/clio' );
    }

    protected function get_accounts_base( $region = 'us' ) {
        $map = array(
            'us' => 'https://app.clio.com',
            'eu' => 'https://eu.app.clio.com',
            'ca' => 'https://ca.app.clio.com',
            'au' => 'https://au.app.clio.com',
        );
        return $map[ $region ] ?? $map['us'];
    }

    protected function get_oauth_endpoint( $path = 'authorize', $region = '' ) {
        return trailingslashit( $this->get_accounts_base( $region ?: $this->region ) ) . 'oauth/' . $path;
    }

    protected function get_apis_base_url() {
        return trailingslashit( $this->get_accounts_base( $this->region ) ) . 'api/v4/';
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->get_oauth_endpoint( 'token' ) ), array(
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

        $response = wp_remote_post( esc_url_raw( $this->get_oauth_endpoint( 'token' ) ), array(
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
            $this->persist_token_to_credential( array(
                'region' => $this->region,
            ) );
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
        $this->region         = $credentials['region']        ?? 'us';
        $this->token_expires  = isset( $credentials['tokenExpires'] ) ? (int) $credentials['tokenExpires'] : 0;

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'clio' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'clio' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function clio_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = $this->get_apis_base_url() . ltrim( $endpoint, '/' );
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
     * Search Contacts by email (Clio's `query` wildcard param), returning
     * the first matching contact id, or '' when nothing matches.
     * @link https://docs.developers.clio.com/api-reference/
     */
    public function find_contact_by_email( $email, $record = array() ) {
        if ( ! $email ) {
            return '';
        }
        $response = $this->clio_request( 'contacts.json', 'GET', array( 'query' => $email ), $record );
        if ( is_wp_error( $response ) ) {
            return '';
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['data'][0]['id'] ?? '';
    }
}

ADFOIN_Clio::get_instance();

add_action( 'wp_ajax_adfoin_get_clio_fields', 'adfoin_get_clio_fields' );
function adfoin_get_clio_fields() {
    adfoin_verify_nonce();

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( $task === 'create_matter' ) {
        $fields = array(
            array( 'key' => 'description', 'value' => 'Description',  'description' => '' ),
            array( 'key' => 'client_email','value' => 'Client Email',  'description' => 'Looks up (or creates) the client contact and links this matter to them.' ),
            array( 'key' => 'practiceArea','value' => 'Practice Area', 'description' => '' ),
            array( 'key' => 'status',      'value' => 'Status',        'description' => 'Open / Pending / Closed' ),
            array( 'key' => 'openDate',    'value' => 'Open Date',     'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'displayNumber','value' => 'Matter Number','description' => '' ),
            array( 'key' => 'note',        'value' => 'Note',          'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'emailType', 'value' => 'Email Type', 'description' => 'Work / Home / Other' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'phoneType', 'value' => 'Phone Type', 'description' => 'Work / Home / Mobile' ),
            array( 'key' => 'type',      'value' => 'Type',       'description' => 'Person or Company' ),
            array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
            array( 'key' => 'jobTitle',  'value' => 'Job Title',  'description' => '' ),
            array( 'key' => 'street',    'value' => 'Street',     'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'province',  'value' => 'State / Province', 'description' => '' ),
            array( 'key' => 'postal',    'value' => 'Postal / Zip', 'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'website',   'value' => 'Website',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

/**
 * Build a Clio Contact payload from parsed field values. Shared with the
 * Pro tier so both stay in sync with the API's confirmed schema.
 * @link https://docs.developers.clio.com/api-reference/
 */
function adfoin_clio_build_contact_payload( $fields ) {
    $contact = array(
        'type'        => ! empty( $fields['type'] ) ? $fields['type'] : 'Person',
        'first_name'  => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
        'middle_name' => isset( $fields['middleName'] ) ? $fields['middleName'] : '',
        'last_name'   => isset( $fields['lastName'] ) ? $fields['lastName'] : '',
    );
    if ( ! empty( $fields['company'] ) )  $contact['name']  = $fields['company'];
    if ( ! empty( $fields['jobTitle'] ) ) $contact['title'] = $fields['jobTitle'];
    if ( ! empty( $fields['email'] ) ) {
        $contact['email_addresses'] = array( array(
            'address'       => $fields['email'],
            'name'          => ! empty( $fields['emailType'] ) ? $fields['emailType'] : 'Work',
            'default_email' => true,
        ) );
    }
    if ( ! empty( $fields['phone'] ) ) {
        $contact['phone_numbers'] = array( array(
            'number'         => $fields['phone'],
            'name'           => ! empty( $fields['phoneType'] ) ? $fields['phoneType'] : 'Work',
            'default_number' => true,
        ) );
    }
    if ( ! empty( $fields['website'] ) ) {
        $contact['web_sites'] = array( array( 'address' => $fields['website'], 'name' => 'Work' ) );
    }
    $address = array();
    foreach ( array( 'street' => 'street', 'city' => 'city', 'province' => 'province', 'postal' => 'postal_code', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $address[ $remote ] = $fields[ $local ];
    }
    if ( $address ) {
        $address['name'] = 'Work';
        $contact['addresses'] = array( $address );
    }
    return array_filter( $contact );
}

function adfoin_clio_create_contact( $fields, $record, $cred_id ) {
    $clio = ADFOIN_Clio::get_instance();
    if ( ! $clio->set_credentials( $cred_id ) ) {
        return;
    }
    $contact = adfoin_clio_build_contact_payload( $fields );
    return $clio->clio_request( 'contacts.json', 'POST', array( 'data' => $contact ), $record );
}

/**
 * Matters require a `client` relationship — find (or create) the Contact by
 * email first, then link it. Previously this field was collected in the UI
 * ("Client Email — used to look up the client contact") but never actually
 * used, so every Matter was created unlinked from any client.
 */
function adfoin_clio_create_matter( $fields, $record, $cred_id ) {
    $clio = ADFOIN_Clio::get_instance();
    if ( ! $clio->set_credentials( $cred_id ) ) {
        return;
    }

    $matter = array();
    if ( ! empty( $fields['description'] ) )   $matter['description']    = $fields['description'];
    if ( ! empty( $fields['displayNumber'] ) ) $matter['display_number'] = $fields['displayNumber'];
    if ( ! empty( $fields['status'] ) )        $matter['status']         = strtolower( $fields['status'] );
    if ( ! empty( $fields['openDate'] ) )      $matter['open_date']      = $fields['openDate'];
    if ( ! empty( $fields['practiceArea'] ) )  $matter['practice_area']  = array( 'name' => $fields['practiceArea'] );
    if ( ! empty( $fields['note'] ) )          $matter['notes']          = $fields['note'];

    if ( ! empty( $fields['client_email'] ) ) {
        $client_id = $clio->find_contact_by_email( $fields['client_email'], $record );
        if ( ! $client_id ) {
            $created = adfoin_clio_create_contact( array( 'email' => $fields['client_email'] ), $record, $cred_id );
            if ( ! is_wp_error( $created ) ) {
                $body      = json_decode( wp_remote_retrieve_body( $created ), true );
                $client_id = $body['data']['id'] ?? '';
            }
        }
        if ( $client_id ) {
            $matter['client'] = array( 'id' => $client_id );
        }
    }

    return $clio->clio_request( 'matters.json', 'POST', array( 'data' => $matter ), $record );
}

add_action( 'adfoin_clio_job_queue', 'adfoin_clio_job_queue', 10, 1 );
function adfoin_clio_job_queue( $data ) {
    adfoin_clio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_clio_send_data( $record, $posted_data ) {
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
    if ( $record['task'] === 'create_contact' ) {
        adfoin_clio_create_contact( $fields, $record, $cred_id );
    } elseif ( $record['task'] === 'create_matter' ) {
        adfoin_clio_create_matter( $fields, $record, $cred_id );
    }
}
