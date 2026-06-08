<?php

class ADFOIN_Dynamics365 extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'dynamics365';

    public $instance_url = '';
    public $tenant_id    = '';
    public $cred_id      = '';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_dynamics365_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_dynamics365_credentials', array( $this, 'save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_dynamics365_connection', array( $this, 'test_connection' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_dynamics365_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dynamics365'] = array(
            'title' => 'Dynamics 365 CRM',
            'tasks' => array(
                'create_contact' => __( 'Create / Update Contact (by email)', 'advanced-form-integration' ),
                'create_lead'    => __( 'Create / Update Lead (by email)', 'advanced-form-integration' ),
                'create_account' => __( 'Create Account', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dynamics365'] = 'Dynamics 365 CRM';
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'dynamics365' !== $current_tab ) {
            return;
        }

        $fields = array(
            array(
                'name'          => 'instance_url',
                'label'         => __( 'Instance URL', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => false,
                'show_in_table' => true,
                'placeholder'   => 'https://yourorg.crm.dynamics.com',
            ),
            array(
                'name'          => 'tenant_id',
                'label'         => __( 'Tenant (Directory) ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_id',
                'label'         => __( 'Application (Client) ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Client Secret Value', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'In <a target="_blank" href="https://portal.azure.com">portal.azure.com</a> → Microsoft Entra ID → App registrations → New registration. Name it (e.g., "AFI Dynamics"), leave Redirect URI blank, register.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'On the Overview blade copy the <strong>Application (client) ID</strong> and <strong>Directory (tenant) ID</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Certificates & secrets → New client secret → copy the <strong>Value</strong> column (not the Secret ID). It is shown only once.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'API permissions → Add a permission → Dynamics CRM → <strong>Application permissions</strong> → <code>user_impersonation</code> → Add. Then click <strong>Grant admin consent</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( '<strong>Required:</strong> Create an Application User inside Dynamics. Go to <a target="_blank" href="https://admin.powerplatform.microsoft.com">admin.powerplatform.microsoft.com</a> → Environments → your environment → Settings → Users + permissions → Application users → New app user. Pick your Azure app, a Business Unit, and assign a Security Role (e.g., "System Administrator" for testing, or a custom role with write access to the entities you need).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'In Dynamics, copy your environment base URL (e.g., <code>https://yourorg.crm.dynamics.com</code>) and paste above. Then click <strong>Save & Verify</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';
        $instructions .= '<p>' . __( '<em>Why both Azure permissions and a Power Platform Application User?</em> Azure decides whether your app can ask for a Dynamics token; Dynamics decides whether that token grants access to records. Both are required.', 'advanced-form-integration' ) . '</p>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Dynamics 365', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Verify', 'advanced-form-integration' ),
        );

        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'dynamics365', 'Dynamics 365 CRM', $fields, $instructions, $config );
    }

    /* ---------- Credential CRUD ---------- */

    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'dynamics365';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $instance_url  = isset( $_POST['instance_url'] ) ? esc_url_raw( wp_unslash( $_POST['instance_url'] ) ) : '';
        $tenant_id     = isset( $_POST['tenant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tenant_id'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        $instance_url = $this->normalize_instance_url( $instance_url );

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        // Preserve previously-saved values when the edit form submits empty
        // sensitive fields (client_secret is the common case — never shipped
        // to the browser). Also accept legacy camelCase storage keys.
        $existing = null;
        foreach ( $credentials as $cred_check ) {
            if ( isset( $cred_check['id'] ) && $cred_check['id'] === $id ) {
                $existing = $cred_check;
                break;
            }
        }
        if ( $existing ) {
            if ( '' === $instance_url ) {
                $instance_url = $existing['instance_url'] ?? ( $existing['instanceUrl'] ?? '' );
            }
            if ( '' === $tenant_id ) {
                $tenant_id = $existing['tenant_id'] ?? ( $existing['tenantId'] ?? '' );
            }
            if ( '' === $client_id ) {
                $client_id = $existing['client_id'] ?? ( $existing['clientId'] ?? '' );
            }
            if ( '' === $client_secret ) {
                $client_secret = $existing['client_secret'] ?? ( $existing['clientSecret'] ?? '' );
            }
        } else {
            if ( '' === $instance_url || '' === $tenant_id || '' === $client_id || '' === $client_secret ) {
                wp_send_json_error( array(
                    'message' => __( 'Instance URL, Tenant ID, Client ID and Client Secret are all required.', 'advanced-form-integration' ),
                ) );
            }
        }

        // Hydrate so we can immediately verify by requesting a token.
        $this->cred_id       = $id;
        $this->instance_url  = $instance_url;
        $this->tenant_id     = $tenant_id;
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token  = '';
        $this->refresh_token = '';

        $token_response = $this->fetch_app_token();

        $new_data = array(
            'id'             => $id,
            'title'          => $title,
            'instance_url'   => $instance_url,
            'tenant_id'      => $tenant_id,
            'client_id'      => $client_id,
            'client_secret'  => $client_secret,
            'access_token'   => $this->access_token ?: '',
            'refresh_token'  => '',
            'token_expires'  => $this->token_expires ?: 0,
        );

        // Drop legacy camelCase fields from the persisted record. New writes
        // are snake_case throughout; reads still accept either.
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
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

        // If token fetch failed, surface Azure's error_description so the
        // user can actually fix what's wrong. This is the biggest UX hole
        // in the previous implementation — auth used to fail silently.
        if ( is_wp_error( $token_response ) ) {
            wp_send_json_error( array(
                'message' => sprintf( __( 'Saved, but token request failed: %s', 'advanced-form-integration' ), $token_response->get_error_message() ),
            ) );
        }
        if ( ! $this->access_token ) {
            wp_send_json_error( array(
                'message' => sprintf( __( 'Saved, but Azure AD rejected the credentials: %s', 'advanced-form-integration' ), $this->last_token_error ?: __( 'Unknown error.', 'advanced-form-integration' ) ),
            ) );
        }

        // No auth_url — the OAuth Manager JS treats this as "done, refresh
        // table" rather than opening a popup. Client_credentials needs no
        // user redirect.
        wp_send_json_success( array(
            'message' => __( 'Connected successfully.', 'advanced-form-integration' ),
        ) );
    }

    public function get_credentials_by_id( $cred_id ) {
        $all = adfoin_read_credentials( 'dynamics365' );
        if ( ! is_array( $all ) || empty( $all ) ) {
            return array();
        }
        foreach ( $all as $single ) {
            if ( $cred_id && ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }
        return $all[0];
    }

    public function get_credentials_list() {
        $credentials = adfoin_read_credentials( 'dynamics365' );
        if ( ! is_array( $credentials ) ) {
            return;
        }
        foreach ( $credentials as $option ) {
            printf(
                '<option value="%1$s">%2$s</option>',
                esc_attr( $option['id'] ),
                esc_html( $option['title'] ?? '' )
            );
        }
    }

    /**
     * Hydrate $this from a stored credential record. Reads both snake_case
     * (new) and camelCase (legacy) keys.
     */
    public function set_credentials( $cred_id ) {
        $c = $this->get_credentials_by_id( $cred_id );
        if ( empty( $c ) ) {
            return;
        }

        $this->cred_id       = $c['id']             ?? '';
        $this->instance_url  = $this->normalize_instance_url(
            $c['instance_url'] ?? ( $c['instanceUrl'] ?? '' )
        );
        $this->tenant_id     = $c['tenant_id']      ?? ( $c['tenantId']      ?? '' );
        $this->client_id     = $c['client_id']      ?? ( $c['clientId']      ?? '' );
        $this->client_secret = $c['client_secret']  ?? ( $c['clientSecret']  ?? '' );
        $this->access_token  = $c['access_token']   ?? ( $c['accessToken']   ?? '' );
        $this->refresh_token = '';
        $this->token_expires = isset( $c['token_expires'] ) ? (int) $c['token_expires'] : 0;
    }

    /* ---------- OAuth (Azure AD client_credentials) ---------- */

    /**
     * Cached Azure error_description from the last failed token call.
     * Surfaced in save_credentials/test_connection so users see real Azure
     * errors instead of silent failures.
     */
    private $last_token_error = '';

    /**
     * client_credentials flow. Posts to login.microsoftonline.com/<tenant>/oauth2/v2.0/token
     * with scope = <instance_origin>/.default. Returns the wp_remote_*
     * response (or WP_Error). On success, $this->access_token and
     * $this->token_expires are populated.
     */
    protected function fetch_app_token() {
        $this->last_token_error = '';

        if ( ! $this->tenant_id || ! $this->client_id || ! $this->client_secret || ! $this->instance_url ) {
            $this->last_token_error = __( 'Missing tenant, client, secret or instance URL.', 'advanced-form-integration' );
            return null;
        }

        $login_host = $this->get_login_host_for_instance( $this->instance_url );
        $token_url  = sprintf( 'https://%s/%s/oauth2/v2.0/token', $login_host, rawurlencode( $this->tenant_id ) );

        $parsed = wp_parse_url( $this->instance_url );
        $origin = isset( $parsed['scheme'], $parsed['host'] )
            ? $parsed['scheme'] . '://' . $parsed['host']
            : 'https://crm.dynamics.com';

        $response = wp_remote_post(
            esc_url_raw( $token_url ),
            array(
                'timeout' => 30,
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => array(
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'scope'         => $origin . '/.default',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->last_token_error = $response->get_error_message();
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
            $expires_in         = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3599;
            $this->token_expires = time() + max( 60, $expires_in - 60 );
            $this->save_data();
            $this->clear_connection_failed();
            return $response;
        }

        // Azure returns { error: "...", error_description: "AADSTS..." }
        $msg = $body['error_description']
            ?? ( $body['error'] ?? sprintf( 'HTTP %d', $code ) );
        $this->last_token_error = is_array( $msg ) ? wp_json_encode( $msg ) : (string) $msg;

        $this->access_token = '';
        $this->mark_connection_failed( 'token_fetch_failed' );

        return $response;
    }

    /**
     * Base class plumbing. `request_token` and `refresh_token` are called by
     * the shared `remote_request` 401 retry path; for client_credentials
     * both simply re-fetch.
     */
    protected function request_token( $authorization_code = '' ) {
        return $this->fetch_app_token();
    }

    protected function refresh_token() {
        return $this->fetch_app_token();
    }

    protected function save_data() {
        // We don't carry a refresh_token in client_credentials, but the base
        // helper handles that gracefully (it's only written when non-null).
        $this->persist_token_to_credential( array(
            'instance_url' => $this->instance_url,
            'tenant_id'    => $this->tenant_id,
            'client_id'    => $this->client_id,
            'client_secret'=> $this->client_secret,
        ) );
    }

    /**
     * Override base class: it short-circuits when refresh_token is empty,
     * but client_credentials apps don't have one — we can still detect
     * expiry from `token_expires` alone (and refresh by re-running the
     * client_credentials grant).
     */
    protected function is_token_expired(): bool {
        if ( empty( $this->token_expires ) ) {
            return false;
        }
        return time() >= ( (int) $this->token_expires - 60 );
    }

    /* ---------- Helpers ---------- */

    /**
     * Strip path, query, fragment, and trailing slash. Users paste anything
     * from "yourorg.crm.dynamics.com" to a deep app URL — normalize to
     * "https://yourorg.crm.dynamics.com".
     */
    protected function normalize_instance_url( $url ) {
        if ( ! is_string( $url ) || '' === trim( $url ) ) {
            return '';
        }
        $url = trim( $url );

        // Add scheme if missing so wp_parse_url interprets the host correctly.
        if ( ! preg_match( '~^https?://~i', $url ) ) {
            $url = 'https://' . ltrim( $url, '/' );
        }
        $parsed = wp_parse_url( $url );
        if ( empty( $parsed['host'] ) ) {
            return '';
        }
        $scheme = $parsed['scheme'] ?? 'https';
        return $scheme . '://' . $parsed['host'];
    }

    /**
     * Different sovereign clouds use different login hosts.
     */
    protected function get_login_host_for_instance( $instance_url ) {
        $host = wp_parse_url( $instance_url, PHP_URL_HOST );
        if ( ! is_string( $host ) ) {
            return 'login.microsoftonline.com';
        }
        $host = strtolower( $host );

        if ( preg_match( '~\.dynamics\.cn$~', $host ) || preg_match( '~\.partner\.microsoftonline\.cn$~', $host ) ) {
            return 'login.partner.microsoftonline.cn';
        }
        if ( preg_match( '~\.microsoftdynamics\.us$~', $host ) || preg_match( '~\.crm\.appsplatform\.us$~', $host ) ) {
            return 'login.microsoftonline.us';
        }
        return 'login.microsoftonline.com';
    }

    /* ---------- Test connection ---------- */

    public function test_connection() {
        $this->run_test_connection_ajax( function () {
            // WhoAmI is the canonical Dynamics ping — returns the calling
            // app user's GUID. Confirms both the token and that the
            // Application User exists inside Dynamics with at least one
            // security role.
            return $this->dynamics365_request( 'WhoAmI()', 'GET' );
        } );
    }

    /* ---------- API requests ---------- */

    public function dynamics365_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        static $refreshed = false;

        if ( ! $this->instance_url ) {
            return new WP_Error( 'dynamics365_no_instance', __( 'No instance URL configured.', 'advanced-form-integration' ) );
        }

        // Proactive token refresh when we know it has expired (cached
        // token_expires on the credential record).
        if ( $this->is_token_expired() || empty( $this->access_token ) ) {
            $this->fetch_app_token();
        }

        $url = trailingslashit( $this->instance_url ) . 'api/data/v9.2/' . ltrim( $endpoint, '/' );

        $args = array(
            'method'  => strtoupper( $method ),
            'timeout' => 30,
            'headers' => array(
                'Authorization'    => 'Bearer ' . $this->access_token,
                'Accept'           => 'application/json',
                'OData-MaxVersion' => '4.0',
                'OData-Version'    => '4.0',
            ),
        );

        if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['headers']['Content-Type'] = 'application/json';
            // `Prefer: return=representation` tells Dynamics to return the
            // created record (with its GUID) in the response body. Without
            // this, POST returns 204 and we'd have to parse the Location
            // header to get the new ID.
            $args['headers']['Prefer'] = 'return=representation';
            if ( ! empty( $data ) ) {
                $args['body'] = wp_json_encode( $data );
            }
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );

        // Reactive 401: token might have been revoked between cached
        // expiry and now. Refresh once and retry.
        if ( 401 === wp_remote_retrieve_response_code( $response ) && ! $refreshed ) {
            $this->fetch_app_token();
            $refreshed = true;
            $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
            $response = wp_remote_request( esc_url_raw( $url ), $args );
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        return $response;
    }

    /* ---------- Action UI ---------- */

    public function action_fields() {
        ?>
        <script type="text/template" id="dynamics365-action-template">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <?php esc_attr_e( 'Dynamics 365 Account', 'advanced-form-integration' ); ?>
                    </th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=dynamics365' ) ); ?>"
                           target="_blank"
                           style="margin-left:10px;text-decoration:none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top:3px;"></span>
                            <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <editable-field
                    v-for="field in fields"
                    :key="field.value"
                    :field="field"
                    :trigger="trigger"
                    :action="action"
                    :fielddata="fielddata">
                </editable-field>

                <?php if ( function_exists( 'adfoin_fs' ) && adfoin_fs()->is_not_paying() ) : ?>
                <tr class="alternate">
                    <th scope="row"><?php esc_html_e( 'Need custom Dataverse fields?', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php printf( wp_kses( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">AFI Pro</a> to map any custom Dataverse column (new_*, prefix_*, etc.) into Contact, Lead, or Account.', 'advanced-form-integration' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </script>
        <?php
    }

    /**
     * Per-task field list for the action UI. The JS component asks for
     * fields whenever the user picks a different account or task.
     */
    public function ajax_get_fields() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

        wp_send_json_success( $this->get_fields_for_task( $task ) );
    }

    protected function get_fields_for_task( $task ) {
        $contact_common = array(
            array( 'key' => 'firstname',                  'value' => 'First Name' ),
            array( 'key' => 'lastname',                   'value' => 'Last Name' ),
            array( 'key' => 'emailaddress1',              'value' => 'Primary Email' ),
            array( 'key' => 'emailaddress2',              'value' => 'Secondary Email' ),
            array( 'key' => 'telephone1',                 'value' => 'Business Phone' ),
            array( 'key' => 'mobilephone',                'value' => 'Mobile Phone' ),
            array( 'key' => 'address1_line1',             'value' => 'Address Line 1' ),
            array( 'key' => 'address1_line2',             'value' => 'Address Line 2' ),
            array( 'key' => 'address1_city',              'value' => 'City' ),
            array( 'key' => 'address1_stateorprovince',   'value' => 'State / Province' ),
            array( 'key' => 'address1_postalcode',        'value' => 'Postal Code' ),
            array( 'key' => 'address1_country',           'value' => 'Country' ),
            array( 'key' => 'jobtitle',                   'value' => 'Job Title' ),
            array( 'key' => 'department',                 'value' => 'Department' ),
            array( 'key' => 'companyname',                'value' => 'Company Name (free text)' ),
            array( 'key' => 'parentcustomerid_account',   'value' => 'Account Name (link to Account)', 'description' => 'If a matching Account exists, the contact is linked via parentcustomerid.' ),
            array( 'key' => 'websiteurl',                 'value' => 'Website' ),
            array( 'key' => 'description',                'value' => 'Description / Notes' ),
            array( 'key' => 'leadsourcecode',             'value' => 'Lead Source (option set value)' ),
            array( 'key' => 'preferredcontactmethodcode', 'value' => 'Preferred Contact Method (option set)' ),
            array( 'key' => 'gendercode',                 'value' => 'Gender (1=Male, 2=Female)' ),
            array( 'key' => 'birthdate',                  'value' => 'Birth Date (YYYY-MM-DD)' ),
            array( 'key' => 'donotemail',                 'value' => 'Do Not Email (true/false)' ),
            array( 'key' => 'donotbulkemail',             'value' => 'Do Not Bulk Email (true/false)' ),
            array( 'key' => 'donotphone',                 'value' => 'Do Not Phone (true/false)' ),
            array( 'key' => 'ownerid',                    'value' => 'Owner ID (systemuser GUID)' ),
        );

        if ( 'create_lead' === $task ) {
            return array(
                array( 'key' => 'subject',                  'value' => 'Topic / Subject' ),
                array( 'key' => 'firstname',                'value' => 'First Name' ),
                array( 'key' => 'lastname',                 'value' => 'Last Name' ),
                array( 'key' => 'emailaddress1',            'value' => 'Email' ),
                array( 'key' => 'telephone1',               'value' => 'Business Phone' ),
                array( 'key' => 'mobilephone',              'value' => 'Mobile Phone' ),
                array( 'key' => 'companyname',              'value' => 'Company Name' ),
                array( 'key' => 'jobtitle',                 'value' => 'Job Title' ),
                array( 'key' => 'websiteurl',               'value' => 'Website' ),
                array( 'key' => 'industrycode',             'value' => 'Industry (option set value)' ),
                array( 'key' => 'revenue',                  'value' => 'Estimated Revenue (number)' ),
                array( 'key' => 'numberofemployees',        'value' => 'Number of Employees' ),
                array( 'key' => 'leadsourcecode',           'value' => 'Lead Source (option set)' ),
                array( 'key' => 'description',              'value' => 'Description / Notes' ),
                array( 'key' => 'address1_line1',           'value' => 'Address Line 1' ),
                array( 'key' => 'address1_city',            'value' => 'City' ),
                array( 'key' => 'address1_stateorprovince', 'value' => 'State / Province' ),
                array( 'key' => 'address1_postalcode',      'value' => 'Postal Code' ),
                array( 'key' => 'address1_country',         'value' => 'Country' ),
                array( 'key' => 'ownerid',                  'value' => 'Owner ID (systemuser GUID)' ),
            );
        }

        if ( 'create_account' === $task ) {
            return array(
                array( 'key' => 'name',                     'value' => 'Account Name (required)' ),
                array( 'key' => 'accountnumber',            'value' => 'Account Number' ),
                array( 'key' => 'emailaddress1',            'value' => 'Email' ),
                array( 'key' => 'telephone1',               'value' => 'Phone' ),
                array( 'key' => 'fax',                      'value' => 'Fax' ),
                array( 'key' => 'websiteurl',               'value' => 'Website' ),
                array( 'key' => 'industrycode',             'value' => 'Industry (option set)' ),
                array( 'key' => 'revenue',                  'value' => 'Annual Revenue' ),
                array( 'key' => 'numberofemployees',        'value' => 'Number of Employees' ),
                array( 'key' => 'address1_line1',           'value' => 'Address Line 1' ),
                array( 'key' => 'address1_line2',           'value' => 'Address Line 2' ),
                array( 'key' => 'address1_city',            'value' => 'City' ),
                array( 'key' => 'address1_stateorprovince', 'value' => 'State / Province' ),
                array( 'key' => 'address1_postalcode',      'value' => 'Postal Code' ),
                array( 'key' => 'address1_country',         'value' => 'Country' ),
                array( 'key' => 'description',              'value' => 'Description' ),
                array( 'key' => 'ownerid',                  'value' => 'Owner ID (systemuser GUID)' ),
            );
        }

        // Default: create_contact
        return $contact_common;
    }

    /* ---------- Payload builders ---------- */

    /**
     * Coerce string values to the type Dynamics expects for known fields.
     * - integer fields (option set codes): cast to int
     * - boolean fields (donot*): map 'true'/'1'/'yes' → true, else false
     * - lookup fields (ownerid, parentcustomerid): expand to OData @bind
     *
     * Unknown keys are passed through unchanged.
     */
    protected function coerce_field( $entity, $key, $value ) {
        $int_keys  = array(
            'leadsourcecode', 'preferredcontactmethodcode', 'gendercode',
            'industrycode', 'numberofemployees', 'statuscode',
        );
        $bool_keys = array(
            'donotemail', 'donotbulkemail', 'donotphone',
            'donotsendmm', 'donotfax', 'donotpostalmail',
        );
        $float_keys = array( 'revenue' );

        if ( in_array( $key, $int_keys, true ) ) {
            return (int) $value;
        }
        if ( in_array( $key, $bool_keys, true ) ) {
            $v = strtolower( (string) $value );
            return in_array( $v, array( 'true', '1', 'yes', 'on' ), true );
        }
        if ( in_array( $key, $float_keys, true ) ) {
            return (float) $value;
        }
        if ( 'ownerid' === $key ) {
            return null; // handled separately as a binding
        }
        return $value;
    }

    /**
     * Apply coercion + lookup bindings to a flat key=>value dict, returning
     * the payload object suitable for Dynamics POST.
     */
    public function finalize_payload( $entity, $values ) {
        $payload = array();
        foreach ( $values as $key => $value ) {
            if ( '' === $value || null === $value ) {
                continue;
            }
            if ( 'ownerid' === $key ) {
                $payload['ownerid@odata.bind'] = '/systemusers(' . trim( $value, "{}" ) . ')';
                continue;
            }
            if ( 'parentcustomerid_account' === $key ) {
                // Resolve account by name and bind. Handled in the caller
                // because it needs an API round trip.
                continue;
            }
            $coerced = $this->coerce_field( $entity, $key, $value );
            if ( null === $coerced ) {
                continue;
            }
            $payload[ $key ] = $coerced;
        }
        return $payload;
    }

    /* ---------- Search / upsert helpers ---------- */

    public function find_record_by_email( $entity_set, $email, $record = array() ) {
        if ( ! $email ) {
            return array();
        }
        // Primary key column: contacts → contactid, leads → leadid, accounts → accountid.
        $singular = preg_replace( '/s$/', '', $entity_set );
        $filter   = "emailaddress1 eq '" . str_replace( "'", "''", $email ) . "'";
        // http_build_query handles all URL-encoding (spaces, apostrophes,
        // the leading $ in OData system query options).
        $endpoint = $entity_set . '?' . http_build_query( array(
            '$select' => $singular . 'id',
            '$filter' => $filter,
            '$top'    => 1,
        ) );

        $response = $this->dynamics365_request( $endpoint, 'GET', array(), $record );
        if ( is_wp_error( $response ) ) {
            return array();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['value'][0] ) ) {
            return $body['value'][0];
        }
        return array();
    }

    public function find_account_by_name( $name, $record = array() ) {
        if ( ! $name ) {
            return array();
        }
        $filter   = "name eq '" . str_replace( "'", "''", $name ) . "'";
        $endpoint = 'accounts?' . http_build_query( array(
            '$select' => 'accountid',
            '$filter' => $filter,
            '$top'    => 1,
        ) );

        $response = $this->dynamics365_request( $endpoint, 'GET', array(), $record );
        if ( is_wp_error( $response ) ) {
            return array();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['value'][0]['accountid'] ) ) {
            return $body['value'][0];
        }
        return array();
    }
}

$adfoin_dynamics365 = ADFOIN_Dynamics365::get_instance();

/* ---------- Job queue + dispatch ---------- */

function adfoin_dynamics365_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'dynamics365' ) {
        // Older AS jobs were dispatched on adfoin_dynamics365_job_queue
        // directly without the provider key. Fall through for those.
        if ( ! isset( $data['record'] ) ) {
            return;
        }
    }
    adfoin_dynamics365_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_dynamics365_job_queue', 'adfoin_dynamics365_job_queue', 10, 1 );
add_action( 'adfoin_job_queue', 'adfoin_dynamics365_job_queue', 10, 1 );

function adfoin_dynamics365_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id    = $field_data['credId'] ?? ( $record['cred_id'] ?? '' );
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || ! $task ) {
        return;
    }

    $d = ADFOIN_Dynamics365::get_instance();
    $d->set_credentials( $cred_id );

    if ( empty( $d->access_token ) ) {
        $d->request_token();
    }
    if ( empty( $d->access_token ) ) {
        return; // logged via mark_connection_failed
    }

    // Resolve tag-templates in the mapped values.
    $resolved = array();
    foreach ( $field_data as $key => $value ) {
        if ( in_array( $key, array( 'credId', 'cl' ), true ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) {
            $resolved[ $key ] = $parsed;
        }
    }

    switch ( $task ) {
        case 'create_contact':
            adfoin_dynamics365_handle_contact( $d, $resolved, $record );
            break;

        case 'create_lead':
            adfoin_dynamics365_handle_lead( $d, $resolved, $record );
            break;

        case 'create_account':
            adfoin_dynamics365_handle_account( $d, $resolved, $record );
            break;
    }
}

function adfoin_dynamics365_handle_contact( $d, $values, $record ) {
    $payload = $d->finalize_payload( 'contact', $values );

    // Optional account linkage: if the user mapped a free-text "Account
    // Name", look it up and attach. We don't auto-create accounts here —
    // that's a separate task.
    if ( ! empty( $values['parentcustomerid_account'] ) ) {
        $account = $d->find_account_by_name( $values['parentcustomerid_account'], $record );
        if ( ! empty( $account['accountid'] ) ) {
            $payload['parentcustomerid_account@odata.bind'] = '/accounts(' . $account['accountid'] . ')';
        }
    }

    $email = $values['emailaddress1'] ?? '';
    if ( $email ) {
        $existing = $d->find_record_by_email( 'contacts', $email, $record );
        if ( ! empty( $existing['contactid'] ) ) {
            // PATCH the existing contact to update fields without
            // creating a duplicate.
            $d->dynamics365_request( "contacts({$existing['contactid']})", 'PATCH', $payload, $record );
            return;
        }
    }

    $d->dynamics365_request( 'contacts', 'POST', $payload, $record );
}

function adfoin_dynamics365_handle_lead( $d, $values, $record ) {
    $payload = $d->finalize_payload( 'lead', $values );

    // Dynamics requires subject (Topic) on Lead. Synthesize a default
    // from "<First> <Last>" or company if missing — easier than failing.
    if ( empty( $payload['subject'] ) ) {
        $name = trim( ( $values['firstname'] ?? '' ) . ' ' . ( $values['lastname'] ?? '' ) );
        if ( '' === $name ) {
            $name = $values['companyname'] ?? __( 'Web Lead', 'advanced-form-integration' );
        }
        $payload['subject'] = $name;
    }

    $email = $values['emailaddress1'] ?? '';
    if ( $email ) {
        $existing = $d->find_record_by_email( 'leads', $email, $record );
        if ( ! empty( $existing['leadid'] ) ) {
            $d->dynamics365_request( "leads({$existing['leadid']})", 'PATCH', $payload, $record );
            return;
        }
    }

    $d->dynamics365_request( 'leads', 'POST', $payload, $record );
}

function adfoin_dynamics365_handle_account( $d, $values, $record ) {
    $payload = $d->finalize_payload( 'account', $values );

    if ( empty( $payload['name'] ) ) {
        return; // name is required on Account
    }

    $d->dynamics365_request( 'accounts', 'POST', $payload, $record );
}
