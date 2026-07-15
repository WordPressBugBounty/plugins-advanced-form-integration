<?php

/**
 * Dotloop — Create Loop via POST /profile/{profileId}/loop, Add Person to
 * Loop via POST /profile/{profileId}/loop/{loopId}/participant.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow. Auth: Bearer
 * {access_token}; access tokens expire ~12h and MUST be refreshed via
 * refresh_token — the previous version of this file asked users to paste a
 * static "OAuth Access Token" with no refresh path, which would silently
 * stop working within a day.
 *
 * @link https://dotloop.github.io/public-api/
 * @link https://info.dotloop.com/developers
 */

class ADFOIN_Dotloop extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'dotloop';

    protected $profile_id = '';

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

        add_action( 'wp_ajax_adfoin_get_dotloop_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_dotloop_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_dotloop_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dotloop'] = array(
            'title' => __( 'Dotloop', 'advanced-form-integration' ),
            'tasks' => array(
                'create_loop'   => __( 'Create Loop', 'advanced-form-integration' ),
                'create_person' => __( 'Add Person to Loop', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dotloop'] = __( 'Dotloop', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'dotloop' !== $current_tab ) {
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
                'name'          => 'profile_id',
                'label'         => __( 'Profile ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'show_in_table' => true,
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Register an app at %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://info.dotloop.com/developers">Dotloop Developer Center</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into the application\'s Redirect URI field.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Enter the Profile ID that should receive new loops — visible in the Dotloop web app URL bar while viewing that profile\'s loops.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Dotloop', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'dotloop',
            __( 'Dotloop', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="dotloop-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'create_loop' || action.task == 'create_person'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'create_loop' || action.task == 'create_person'">
                    <td scope="row-title"><label><?php esc_attr_e( 'Dotloop Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=dotloop' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_loop', 'Dotloop [PRO]', 'tags and extra fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/dotloop', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Dotloop with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, and closes the popup with
     * a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'dotloop' );
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
        foreach ( adfoin_read_credentials( 'dotloop' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
                $this->profile_id    = $entry['profile_id']    ?? '';
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

        $platform    = 'dotloop';
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
        $profile_id    = isset( $_POST['profile_id'] )    ? sanitize_text_field( wp_unslash( $_POST['profile_id'] ) )    : '';

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

        if ( '' === $profile_id ) {
            wp_send_json_error( array( 'message' => __( 'Profile ID is required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'profile_id'    => $profile_id,
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
            'state'         => self::issue_oauth_state( 'dotloop', $id ),
        ), 'https://auth.dotloop.com/oauth/authorize' );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->dotloop_request( 'profile', 'GET' );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/dotloop' );
    }

    protected function get_apis_base_url() {
        return 'https://api-gateway.dotloop.com/public/v2/';
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( 'https://auth.dotloop.com/oauth/token', array(
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

        $response = wp_remote_post( 'https://auth.dotloop.com/oauth/token', array(
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
        $this->client_id     = $credentials['client_id']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? '';
        $this->profile_id    = $credentials['profile_id']    ?? '';
        $this->token_expires = isset( $credentials['token_expires'] ) ? (int) $credentials['token_expires'] : 0;

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'dotloop' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'dotloop' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function get_profile_id() {
        return $this->profile_id;
    }

    public function dotloop_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
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
}

ADFOIN_Dotloop::get_instance();

add_action( 'wp_ajax_adfoin_get_dotloop_fields', 'adfoin_get_dotloop_fields' );
function adfoin_get_dotloop_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_loop';

    if ( $task === 'create_person' ) {
        $fields = array(
            array( 'key' => 'loopId',    'value' => 'Loop ID',    'description' => 'Existing loop to attach this person to' ),
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'role',      'value' => 'Role',       'description' => 'BUYER / SELLER / LISTING_AGENT / BUYING_AGENT / etc.' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'name',           'value' => 'Loop Name',       'description' => '' ),
            array( 'key' => 'transactionType','value' => 'Transaction Type','description' => 'PURCHASE_OFFER / LISTING_FOR_SALE / etc.' ),
            array( 'key' => 'status',         'value' => 'Status',          'description' => 'e.g. PRE_LISTING / UNDER_CONTRACT / SOLD — statuses are configurable per Dotloop profile' ),
            array( 'key' => 'street',         'value' => 'Property Street', 'description' => 'Best-effort: sent via a follow-up loop detail update; field availability depends on the profile\'s loop template' ),
            array( 'key' => 'city',           'value' => 'Property City',   'description' => '' ),
            array( 'key' => 'state',          'value' => 'Property State',  'description' => '' ),
            array( 'key' => 'zip',            'value' => 'Property Zip',    'description' => '' ),
            array( 'key' => 'price',          'value' => 'Sale Price',      'description' => '' ),
            array( 'key' => 'firstName',      'value' => 'Client First Name', 'description' => '' ),
            array( 'key' => 'lastName',       'value' => 'Client Last Name','description' => '' ),
            array( 'key' => 'email',          'value' => 'Client Email',    'description' => '' ),
            array( 'key' => 'phone',          'value' => 'Client Phone',    'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

/**
 * Best-effort follow-up PATCH to set property address / sale price on a
 * just-created loop. Dotloop's detail schema is a "sections" object keyed
 * by section/field label (not a flat body), and the exact field set is
 * customizable per brokerage template, so this is sent opportunistically —
 * failure here does not undo the already-created loop.
 * @link https://dotloop.github.io/public-api/
 */
function adfoin_dotloop_update_loop_detail( $profile_id, $loop_id, $fields, $record, $cred_id ) {
    $dotloop = ADFOIN_Dotloop::get_instance();

    $address = array();
    if ( ! empty( $fields['street'] ) ) $address['Street Name'] = $fields['street'];
    if ( ! empty( $fields['city'] ) )   $address['City']        = $fields['city'];
    if ( ! empty( $fields['state'] ) )  $address['State']       = $fields['state'];
    if ( ! empty( $fields['zip'] ) )    $address['Zip Code']    = $fields['zip'];

    $detail = array();
    if ( $address ) $detail['Property Address'] = $address;
    if ( ! empty( $fields['price'] ) ) $detail['Financials'] = array( 'Purchase/Sale Price' => $fields['price'] );

    if ( ! $detail ) {
        return;
    }

    $dotloop->dotloop_request( "profile/{$profile_id}/loop/{$loop_id}/detail", 'PATCH', $detail, $record );
}

add_action( 'adfoin_dotloop_job_queue', 'adfoin_dotloop_job_queue', 10, 1 );
function adfoin_dotloop_job_queue( $data ) {
    adfoin_dotloop_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_dotloop_send_data( $record, $posted_data ) {
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

    $dotloop = ADFOIN_Dotloop::get_instance();
    if ( ! $dotloop->set_credentials( $cred_id ) ) {
        return;
    }
    $profile_id = $dotloop->get_profile_id();
    if ( ! $profile_id ) return;

    if ( $record['task'] === 'create_loop' ) {
        $body = array(
            'name'            => isset( $fields['name'] )            ? $fields['name']            : '',
            'transactionType' => isset( $fields['transactionType'] ) ? $fields['transactionType'] : 'PURCHASE_OFFER',
            'status'          => isset( $fields['status'] )          ? $fields['status']          : 'PRE_LISTING',
        );

        $response = $dotloop->dotloop_request( "profile/{$profile_id}/loop", 'POST', $body, $record );

        if ( is_wp_error( $response ) ) {
            return;
        }
        $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
        $loop_id   = isset( $resp_body['id'] ) ? $resp_body['id'] : '';
        if ( ! $loop_id ) {
            return;
        }

        adfoin_dotloop_update_loop_detail( $profile_id, $loop_id, $fields, $record, $cred_id );

        if ( ! empty( $fields['email'] ) ) {
            $full_name = trim( ( isset( $fields['firstName'] ) ? $fields['firstName'] : '' ) . ' ' . ( isset( $fields['lastName'] ) ? $fields['lastName'] : '' ) );
            $dotloop->dotloop_request( "profile/{$profile_id}/loop/{$loop_id}/participant", 'POST', array_filter( array(
                'fullName' => $full_name,
                'email'    => $fields['email'],
                'phone'    => isset( $fields['phone'] ) ? $fields['phone'] : '',
                'role'     => 'BUYER',
            ) ), $record );
        }
    } elseif ( $record['task'] === 'create_person' ) {
        $loop_id = isset( $fields['loopId'] ) ? $fields['loopId'] : '';
        if ( ! $loop_id ) return;

        $full_name = trim( ( isset( $fields['firstName'] ) ? $fields['firstName'] : '' ) . ' ' . ( isset( $fields['lastName'] ) ? $fields['lastName'] : '' ) );

        $body = array_filter( array(
            'fullName' => $full_name,
            'email'    => isset( $fields['email'] ) ? $fields['email'] : '',
            'phone'    => isset( $fields['phone'] ) ? $fields['phone'] : '',
            'role'     => isset( $fields['role'] )  ? $fields['role']  : '',
        ) );
        $dotloop->dotloop_request( "profile/{$profile_id}/loop/{$loop_id}/participant", 'POST', $body, $record );
    }
}
