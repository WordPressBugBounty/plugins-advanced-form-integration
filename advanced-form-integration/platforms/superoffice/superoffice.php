<?php

/**
 * SuperOffice CRM — Create Contact (company) via POST /api/v1/Contact.
 *
 * Multi-account OAuth 2.0 (Authorization Code + refresh token) via AFI's
 * OAuth Manager popup flow; see ADFOIN_SuperOffice below. Accounts saved
 * before OAuth support (pasted access token, optional SO-AppToken and
 * SO-ContextIdentifier headers) keep working until their token expires.
 *
 * @link https://docs.superoffice.com/en/api/index.html
 */

add_filter( 'adfoin_action_providers', 'adfoin_superoffice_actions', 10, 1 );

function adfoin_superoffice_actions( $actions ) {
    $actions['superoffice'] = array(
        'title' => __( 'SuperOffice CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact (Company)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_superoffice_settings_tab', 10, 1 );

function adfoin_superoffice_settings_tab( $providers ) {
    $providers['superoffice'] = __( 'SuperOffice CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_superoffice_settings_view', 10, 1 );

function adfoin_superoffice_settings_view( $current_tab ) {
    if ( 'superoffice' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
    }

    $redirect_uri = ADFOIN_SuperOffice::get_instance()->get_redirect_uri();

    $fields = array(
        array(
            'name'          => 'environment',
            'label'         => __( 'Environment', 'advanced-form-integration' ),
            'type'          => 'select',
            'options'       => ADFOIN_SuperOffice::environments(),
            'required'      => true,
            'show_in_table' => true,
        ),
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
            'show_in_table' => false,
            'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
        ),
    );

    $instructions  = '<ol class="afi-instructions-list">';
    $instructions .= '<li>' . sprintf(
        /* translators: %s: link to the SuperOffice Developer Portal. */
        __( 'In the %s, register an application for the environment you use (SOD for testing, Online for production). Choose a web application with the Authorization Code flow.', 'advanced-form-integration' ),
        '<a target="_blank" rel="noopener noreferrer" href="https://dev.superoffice.com/">SuperOffice Developer Portal</a>'
    ) . '</li>';
    $instructions .= '<li>' . __( 'Add this Redirect URI to the application:', 'advanced-form-integration' ) . '</li>';
    $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
    $instructions .= '<li>' . __( 'Click Add Account, choose the environment, paste the Client ID and Client Secret, then click Save & Authorize and sign in with a SuperOffice user that can create and edit companies and contacts.', 'advanced-form-integration' ) . '</li>';
    $instructions .= '<li>' . __( 'AFI stores the refresh token and renews the access token automatically, so no manual token renewal is needed. The tenant URL is detected during sign-in.', 'advanced-form-integration' ) . '</li>';
    $instructions .= '</ol>';

    ADFOIN_OAuth_Manager::render_oauth_settings_view(
        'superoffice',
        __( 'SuperOffice CRM', 'advanced-form-integration' ),
        $fields,
        $instructions,
        array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect SuperOffice', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        )
    );
}

if ( ! function_exists( 'adfoin_superoffice_credentials_list' ) ) :
function adfoin_superoffice_credentials_list() {
    foreach ( adfoin_read_credentials( 'superoffice' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

add_action( 'adfoin_action_fields', 'adfoin_superoffice_action_fields' );

function adfoin_superoffice_action_fields() {
    ?>
    <script type="text/template" id="superoffice-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SuperOffice Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=superoffice' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'create_contact', 'SuperOffice CRM [PRO]', 'user-defined fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_superoffice_fields', 'adfoin_get_superoffice_fields', 10, 0 );

function adfoin_get_superoffice_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'Name',                     'value' => __( 'Company Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'Department',               'value' => __( 'Department', 'advanced-form-integration' ) ),
        array( 'key' => 'CategoryId',               'value' => __( 'Category ID', 'advanced-form-integration' ) ),
        array( 'key' => 'BusinessId',               'value' => __( 'Business ID', 'advanced-form-integration' ) ),
        array( 'key' => 'Number1',                  'value' => __( 'Phone (Number1)', 'advanced-form-integration' ) ),
        array( 'key' => 'Number2',                  'value' => __( 'Phone (Number2)', 'advanced-form-integration' ) ),
        array( 'key' => 'UrlAddress',               'value' => __( 'Website URL', 'advanced-form-integration' ) ),
        array( 'key' => 'Emails__0__Value',         'value' => __( 'Primary Email', 'advanced-form-integration' ) ),
        array( 'key' => 'Emails__0__Description',   'value' => __( 'Email Description', 'advanced-form-integration' ) ),
        array( 'key' => 'Phones__0__Value',         'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'Phones__0__Description',   'value' => __( 'Phone Description', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Address1',   'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Address2',   'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.City',       'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Zipcode',    'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'CountryId',                'value' => __( 'Country ID', 'advanced-form-integration' ) ),
        array( 'key' => 'Description',              'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_superoffice_job_queue', 'adfoin_superoffice_job_queue', 10, 1 );

function adfoin_superoffice_job_queue( $data ) {
    adfoin_superoffice_send_contact( $data['record'], $data['posted_data'] );
}

function adfoin_superoffice_send_contact( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_superoffice_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $mapped_fields = array();

    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed || null === $parsed ) {
            continue;
        }

        $mapped_fields[ $key ] = $parsed;
    }

    if ( empty( $mapped_fields['Name'] ) ) {
        return;
    }

    $payload = adfoin_superoffice_build_payload( $mapped_fields );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_superoffice_request( 'Contact', 'POST', $payload, $record, $credentials );
}

if ( ! function_exists( 'adfoin_superoffice_get_credentials' ) ) :
function adfoin_superoffice_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'superoffice', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_request' ) ) :
/**
 * Call the SuperOffice REST API.
 *
 * @param string $endpoint    Path under the tenant /api/v1/.
 * @param string $method      HTTP verb.
 * @param mixed  $data        Body (POST/PUT/PATCH).
 * @param array  $record      Submission record for logging.
 * @param array  $credentials Saved credentials.
 *
 * @return array|WP_Error
 */
function adfoin_superoffice_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = array() ) {
    if ( empty( $credentials ) ) {
        return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials missing.', 'advanced-form-integration' ) );
    }

    $base_url = isset( $credentials['restBaseUrl'] ) ? trim( $credentials['restBaseUrl'] ) : '';

    if ( '' === $base_url ) {
        return new WP_Error( 'missing_base_url', __( 'SuperOffice REST base URL is not set.', 'advanced-form-integration' ) );
    }

    $base_url = trailingslashit( $base_url );
    $url      = $base_url . ltrim( $endpoint, '/' );
    $is_oauth = ! empty( $credentials['refresh_token'] ) && ! empty( $credentials['id'] );

    $headers = array(
        'Accept' => 'application/json',
    );

    // OAuth accounts get their Bearer token (and automatic refresh) from
    // ADFOIN_SuperOffice; legacy accounts send the pasted token.
    if ( ! $is_oauth && ! empty( $credentials['accessToken'] ) ) {
        $headers['Authorization'] = 'Bearer ' . $credentials['accessToken'];
    }

    if ( ! $is_oauth && ! empty( $credentials['appToken'] ) ) {
        $headers['SO-AppToken'] = $credentials['appToken'];
    }

    if ( ! $is_oauth && ! empty( $credentials['contextIdentifier'] ) ) {
        $headers['SO-ContextIdentifier'] = $credentials['contextIdentifier'];
    }

    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => $headers,
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    }

    if ( $is_oauth ) {
        $client = ADFOIN_SuperOffice::get_instance();

        if ( ! $client->set_credentials( $credentials['id'] ) ) {
            return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials not found.', 'advanced-form-integration' ) );
        }

        // The tenant URL can move (e.g. sod -> sod2); use the one from the latest token.
        if ( $client->get_rest_base_url() ) {
            $url = trailingslashit( $client->get_rest_base_url() ) . ltrim( $endpoint, '/' );
        }

        $response = $client->api_request( $url, $args );
    } else {
        $response = wp_remote_request( $url, $args );
    }

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_build_payload' ) ) :
function adfoin_superoffice_build_payload( $fields, $entity = 'contact' ) {
    $payload = array();

    foreach ( $fields as $path => $value ) {
        // Integrations saved before the __index__ keys were introduced hold
        // truncated keys such as "Emails[0" (PHP cut the name at the inner
        // bracket). They cannot be mapped to a real property, so drop them.
        if ( substr_count( $path, '[' ) !== substr_count( $path, ']' ) ) {
            continue;
        }

        // Normalize double underscore path (e.g. Emails__0__Value -> Emails[0].Value).
        $normalized_path = preg_replace_callback( '/__(\d+)__/', function( $m ) {
            return '[' . $m[1] . '].';
        }, $path );
        $normalized_path = str_replace( '__', '.', $normalized_path );
        $normalized_path = adfoin_superoffice_entity_path( $normalized_path, $entity );

        adfoin_superoffice_assign_path( $payload, $normalized_path, adfoin_superoffice_normalize_value( $normalized_path, $value ) );
    }

    return $payload;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_entity_path' ) ) :
/**
 * Translate mapping keys to the property paths of ContactEntity / PersonEntity.
 *
 * The mapping keys are kept stable so existing saved integrations keep working;
 * the entities use nested objects (Category.Id, Address.Postal.City, ...).
 */
function adfoin_superoffice_entity_path( $path, $entity = 'contact' ) {
    $common = array(
        'CategoryId' => 'Category.Id',
        'BusinessId' => 'Business.Id',
        'CountryId'  => 'Country.CountryId',
        'PositionId' => 'Position.Id',
        'UrlAddress' => 'Urls[0].Value',
    );

    if ( 'person' === $entity ) {
        $common['FirstName'] = 'Firstname';
        $common['LastName']  = 'Lastname';
    } else {
        $common['AssociateId'] = 'Associate.AssociateId';
    }

    if ( isset( $common[ $path ] ) ) {
        return $common[ $path ];
    }

    if ( 0 === strpos( $path, 'PostalAddress.' ) ) {
        return 'Address.Postal.' . substr( $path, strlen( 'PostalAddress.' ) );
    }

    // PersonEntity has no generic Phones list.
    if ( 'person' === $entity && 0 === strpos( $path, 'Phones[' ) ) {
        return 'Office' . $path;
    }

    return $path;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_assign_path' ) ) :
function adfoin_superoffice_assign_path( array &$target, $path, $value ) {
    if ( '' === $path ) {
        return;
    }

    $segments = explode( '.', $path );
    $last     = count( $segments ) - 1;
    $ref      =& $target;

    foreach ( $segments as $index => $segment ) {
        if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $segment, $matches ) ) {
            $key   = $matches[1];
            $array_index = (int) $matches[2];

            if ( ! isset( $ref[ $key ] ) || ! is_array( $ref[ $key ] ) ) {
                $ref[ $key ] = array();
            }

            if ( ! isset( $ref[ $key ][ $array_index ] ) || ! is_array( $ref[ $key ][ $array_index ] ) ) {
                $ref[ $key ][ $array_index ] = array();
            }

            if ( $index === $last ) {
                $ref[ $key ][ $array_index ] = $value;
            } else {
                $ref =& $ref[ $key ][ $array_index ];
            }

            continue;
        }

        if ( $index === $last ) {
            $ref[ $segment ] = $value;
        } else {
            if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
                $ref[ $segment ] = array();
            }

            $ref =& $ref[ $segment ];
        }
    }
}
endif;

if ( ! function_exists( 'adfoin_superoffice_normalize_value' ) ) :
function adfoin_superoffice_normalize_value( $path, $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    $trimmed = is_string( $value ) ? trim( $value ) : $value;

    $last_segment = $path;

    if ( false !== strpos( $path, '.' ) ) {
        $parts        = explode( '.', $path );
        $last_segment = end( $parts );
    }

    if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $last_segment, $matches ) ) {
        $last_segment = $matches[1];
    }

    $int_fields   = array( 'Id', 'CategoryId', 'BusinessId', 'AssociateId', 'OwnerContactId', 'NumberOfEmployees', 'ContactId', 'PersonId', 'CountryId', 'PositionId' );
    $float_fields = array( 'Amount', 'WeightedAmount' );
    $bool_fields  = array( 'HasConsent', 'ConsentGiven', 'ConsentObtained', 'Active', 'Done' );

    if ( in_array( $last_segment, $int_fields, true ) && '' !== $trimmed ) {
        return (int) $trimmed;
    }

    if ( in_array( $last_segment, $float_fields, true ) && '' !== $trimmed ) {
        return (float) $trimmed;
    }

    if ( in_array( $last_segment, $bool_fields, true ) ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        $lower = strtolower( (string) $trimmed );
        return in_array( $lower, array( '1', 'true', 'yes', 'on' ), true );
    }

    return $value;
}
endif;

/**
 * SuperOffice OAuth 2.0 / OpenID Connect (Authorization Code flow).
 *
 * The site admin signs in once; SuperOffice returns a long-lived refresh
 * token (valid until the app's consent is revoked) and a 1-hour access
 * token. Access tokens are refreshed automatically before they expire and
 * on any 401, so form submissions keep working without manual renewal.
 *
 * The tenant REST URL and context identifier are read from the id_token
 * claims, so the user does not have to enter them.
 *
 * @link https://docs.superoffice.com/en/api/authentication/online/sign-in-user/auth-code-flow.html
 */
class ADFOIN_SuperOffice extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'superoffice';

    protected $environment = 'online';

    protected $rest_base_url = '';

    protected $context_identifier = '';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'create_callback_route' ) );
        add_action( 'wp_ajax_adfoin_get_superoffice_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_superoffice_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_superoffice_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public static function environments() {
        return array(
            'online' => __( 'Production (online.superoffice.com)', 'advanced-form-integration' ),
            'sod'    => __( 'Development / SOD (sod.superoffice.com)', 'advanced-form-integration' ),
            'stage'  => __( 'Stage (stage.superoffice.com)', 'advanced-form-integration' ),
        );
    }

    protected static function sanitize_environment( $env ) {
        $env = strtolower( trim( (string) $env ) );
        return array_key_exists( $env, self::environments() ) ? $env : 'online';
    }

    protected function set_endpoints() {
        $base = 'https://' . $this->environment . '.superoffice.com/login/common/oauth/';
        $this->authorization_endpoint = $base . 'authorize';
        $this->token_endpoint         = $base . 'tokens';
        $this->refresh_token_endpoint = $base . 'tokens';
    }

    public function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/superoffice' );
    }

    public function create_callback_route() {
        register_rest_route( 'advancedformintegration', '/superoffice', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'oauth_callback' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /** Load an OAuth credential record. Returns false for unknown IDs. */
    public function set_credentials( $cred_id ) {
        foreach ( (array) adfoin_read_credentials( 'superoffice' ) as $cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $cred_id ) {
                $this->cred_id            = $cred['id'];
                $this->environment        = self::sanitize_environment( $cred['environment'] ?? 'online' );
                $this->client_id          = $cred['client_id'] ?? '';
                $this->client_secret      = $cred['client_secret'] ?? '';
                $this->access_token       = $cred['access_token'] ?? ( $cred['accessToken'] ?? '' );
                $this->refresh_token      = $cred['refresh_token'] ?? '';
                $this->token_expires      = isset( $cred['token_expires'] ) ? (int) $cred['token_expires'] : 0;
                $this->rest_base_url      = $cred['restBaseUrl'] ?? '';
                $this->context_identifier = $cred['contextIdentifier'] ?? '';
                $this->set_endpoints();
                return true;
            }
        }
        return false;
    }

    /**
     * Read webapi_url and ctx from the id_token. The token comes straight
     * from SuperOffice's token endpoint over TLS (OIDC Core 3.1.3.7 allows
     * relying on TLS instead of the signature in that case). The URL is
     * only accepted on a superoffice.com host so tokens are never sent
     * anywhere else.
     */
    protected function apply_id_token( $id_token ) {
        $parts = explode( '.', (string) $id_token );
        if ( count( $parts ) < 2 ) {
            return;
        }

        $claims = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $parts[1] ) % 4 ) % 4 ) ), true );
        if ( ! is_array( $claims ) ) {
            return;
        }

        $webapi = $claims['http://schemes.superoffice.net/identity/webapi_url'] ?? '';
        $host   = $webapi ? strtolower( (string) wp_parse_url( $webapi, PHP_URL_HOST ) ) : '';

        if ( $host && preg_match( '/(^|\.)superoffice\.com$/', $host ) && 0 === strpos( $webapi, 'https://' ) ) {
            $this->rest_base_url = trailingslashit( $webapi ) . 'v1/';
        }

        if ( ! empty( $claims['http://schemes.superoffice.net/identity/ctx'] ) ) {
            $this->context_identifier = sanitize_text_field( $claims['http://schemes.superoffice.net/identity/ctx'] );
        }
    }

    protected function token_post( array $params ) {
        return wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'headers' => array( 'Accept' => 'application/json' ),
            'body'    => $params,
        ) );
    }

    protected function request_token( $authorization_code ) {
        $response = $this->token_post( array(
            'grant_type'    => 'authorization_code',
            'code'          => $authorization_code,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->get_redirect_uri(),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
            $this->apply_id_token( $body['id_token'] ?? '' );
            $this->save_data();
        }

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = $this->token_post( array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refresh_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->get_redirect_uri(),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
            $this->apply_id_token( $body['id_token'] ?? '' );
            $this->save_data();
        } elseif ( in_array( $code, array( 400, 401 ), true ) && is_array( $body ) && in_array( $body['error'] ?? '', array( 'invalid_grant', 'invalid_client', 'unauthorized_client' ), true ) ) {
            // Consent revoked or app secret changed: the admin must reconnect.
            $this->access_token = null;
            $this->save_data();
            $this->mark_connection_failed( (string) $body['error'] );
        }

        return $response;
    }

    protected function save_data() {
        $this->persist_token_to_credential( array(
            'restBaseUrl'       => $this->rest_base_url,
            'contextIdentifier' => $this->context_identifier,
        ) );
    }

    /** Send an authenticated request; refreshes the token proactively and on 401. */
    public function api_request( $url, array $args ) {
        return $this->remote_request( $url, $args );
    }

    public function get_rest_base_url() {
        return $this->rest_base_url;
    }

    public function oauth_callback( $request ) {
        $params  = $request->get_params();
        $code    = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';
        $state   = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';
        $context = self::consume_oauth_state( $state, 'superoffice' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        if ( ! $code || ! $cred_id || ! $this->set_credentials( $cred_id ) || ! $this->client_id || ! $this->client_secret ) {
            $error = isset( $params['error_description'] ) ? sanitize_text_field( $params['error_description'] ) : __( 'OAuth state invalid or expired. Please try again.', 'advanced-form-integration' );
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, $error );
            exit;
        }

        $response = $this->request_token( $code );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_wp_error( $response ) && ! empty( $body['access_token'] ) ) {
            if ( '' === $this->rest_base_url ) {
                ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Connected, but SuperOffice did not return the tenant REST URL. Please try again.', 'advanced-form-integration' ) );
            } else {
                ADFOIN_OAuth_Manager::handle_callback_close_popup( true, __( 'Connected successfully!', 'advanced-form-integration' ) );
            }
        } else {
            $message = is_wp_error( $response ) ? $response->get_error_message() : ( $body['error_description'] ?? ( $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' ) ) );
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, $message );
        }
        exit;
    }

    public function ajax_get_credentials() {
        adfoin_verify_nonce();
        $list = $this->safe_credentials_list();
        foreach ( $list as &$row ) {
            unset( $row['appToken'] );
        }
        unset( $row );
        wp_send_json_success( $list );
    }

    public function ajax_save_credentials() {
        adfoin_verify_nonce();

        $credentials = adfoin_read_credentials( 'superoffice' );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                unset( $credentials[ $index ] );
                adfoin_save_credentials( 'superoffice', array_values( $credentials ) );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $environment   = self::sanitize_environment( isset( $_POST['environment'] ) ? wp_unslash( $_POST['environment'] ) : 'online' );
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        if ( '' === $id ) {
            $id = wp_generate_uuid4();
        }

        $index = null;
        foreach ( $credentials as $i => $cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $index = $i;
                break;
            }
        }

        $existing = null === $index ? array() : $credentials[ $index ];

        if ( '' === $client_id ) {
            $client_id = $existing['client_id'] ?? '';
        }
        if ( '' === $client_secret ) {
            $client_secret = $existing['client_secret'] ?? '';
        }
        if ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        $same_app = ( $existing['client_id'] ?? '' ) === $client_id
            && ( $existing['client_secret'] ?? '' ) === $client_secret
            && ( $existing['environment'] ?? '' ) === $environment;

        // Legacy manual-token fields are dropped once the account is moved to OAuth.
        $record = array(
            'id'            => $id,
            'title'         => $title,
            'environment'   => $environment,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        );

        if ( $same_app ) {
            foreach ( array( 'access_token', 'refresh_token', 'token_expires', 'restBaseUrl', 'contextIdentifier' ) as $keep ) {
                if ( isset( $existing[ $keep ] ) ) {
                    $record[ $keep ] = $existing[ $keep ];
                }
            }
        }

        if ( null === $index ) {
            $credentials[] = $record;
        } else {
            $credentials[ $index ] = $record;
        }

        adfoin_save_credentials( 'superoffice', $credentials );

        $this->environment = $environment;
        $this->set_endpoints();

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'scope'         => 'openid',
            'redirect_uri'  => $this->get_redirect_uri(),
            'state'         => self::issue_oauth_state( 'superoffice', $id ),
        ), $this->authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            $credentials = adfoin_superoffice_get_credentials( $this->cred_id );
            return adfoin_superoffice_request( 'Contact?$select=contactId&$top=1', 'GET', array(), array(), $credentials );
        } );
    }
}

ADFOIN_SuperOffice::get_instance();
