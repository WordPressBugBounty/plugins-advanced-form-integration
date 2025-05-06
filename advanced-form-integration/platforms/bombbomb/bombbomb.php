<?php

class ADFOIN_BombBomb extends Advanced_Form_Integration_OAuth2 {

    const authorization_endpoint     = 'https://app.bombbomb.com/auth/authorize';
    const token_endpoint             = 'https://app.bombbomb.com/auth/access_token';
    const refresh_token_endpoint     = 'https://app.bombbomb.com/auth/access_token';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        $option = (array) maybe_unserialize( get_option( 'adfoin_bombbomb_keys' ) );

        $this->client_id     = isset($option['client_id']) ? $option['client_id'] : '';
        $this->client_secret = isset($option['client_secret']) ? $option['client_secret'] : '';
        $this->access_token  = isset($option['access_token']) ? $option['access_token'] : '';
        $this->refresh_token = isset($option['refresh_token']) ? $option['refresh_token'] : '';

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_bombbomb_keys', array( $this, 'save_keys' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_bombbomb_lists', array( $this, 'get_lists' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/bombbomb',
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
                    'action'  => 'adfoin_bombbomb_auth_redirect',
                    'code'    => $code,
                ],
                admin_url( 'admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function actions( $actions ) {
        $actions['bombbomb'] = array(
            'title' => __( 'BombBomb', 'advanced-form-integration' ),
            'tasks' => array( 'add_contact' => __( 'Add Contact', 'advanced-form-integration' ) )
        );
        return $actions;
    }

    public function settings_tab( $providers ) {
        $providers['bombbomb'] = __( 'BombBomb', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( $current_tab !== 'bombbomb' ) return;

        $option = (array) maybe_unserialize( get_option( 'adfoin_bombbomb_keys' ) );
        $nonce = wp_create_nonce( 'adfoin_bombbomb_settings' );
        $client_id = isset($option['client_id']) ? $option['client_id'] : '';
        $client_secret = isset($option['client_secret']) ? $option['client_secret'] : '';
        $redirect_uri = $this->get_redirect_uri();
        ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="adfoin_save_bombbomb_keys" />
            <input type="hidden" name="_nonce" value="<?php echo esc_attr($nonce); ?>" />
            <table class="form-table">
                <tr>
                    <th><?php _e('Redirect URI', 'advanced-form-integration'); ?></th>
                    <td><code><?php echo esc_url( $redirect_uri ); ?></code></td>
                </tr>
                <tr>
                    <th><?php _e('Client ID', 'advanced-form-integration'); ?></th>
                    <td><input type="text" name="client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php _e('Client Secret', 'advanced-form-integration'); ?></th>
                    <td><input type="text" name="client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'advanced-form-integration'); ?></th>
                    <td><?php echo $this->is_active() ? 'Connected' : 'Not Connected'; ?></td>
                </tr>
            </table>
            <?php submit_button( __( 'Authorize', 'advanced-form-integration' ) ); ?>
        </form>
        <?php
    }

    public function save_keys() {
        if (! wp_verify_nonce($_POST['_nonce'], 'adfoin_bombbomb_settings')) {
            die(__('Security check failed.', 'advanced-form-integration'));
        }

        $this->client_id     = sanitize_text_field(isset($_POST['client_id']) ? $_POST['client_id'] : '');
        $this->client_secret = sanitize_text_field(isset($_POST['client_secret']) ? $_POST['client_secret'] : '');
        $this->save_data();

        $this->authorize('all:manage');
    }

    public function auth_redirect() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'adfoin_bombbomb_auth_redirect') return;

        $code = isset($_GET['code']) ? $_GET['code'] : '';
        if ($code) {
            $this->request_token($code);
        }

        wp_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=bombbomb'));
        exit;
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/bombbomb');
    }

    protected function authorize($scope = '') {
        $args = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => $scope
        );
        wp_redirect(add_query_arg($args, self::authorization_endpoint));
        exit;
    }

    protected function request_token($authorization_code) {
        $response = wp_remote_post(self::token_endpoint, array(
            'body' => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code'
            )
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->access_token  = isset($body['access_token']) ? $body['access_token'] : '';
        $this->refresh_token = isset($body['refresh_token']) ? $body['refresh_token'] : '';
        $this->save_data();
    }

    protected function refresh_token() {
        $response = wp_remote_post(self::refresh_token_endpoint, array(
            'body' => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token'
            )
        ));
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->access_token = isset($body['access_token']) ? $body['access_token'] : '';
        $this->save_data();
    }

    protected function save_data() {
        update_option('adfoin_bombbomb_keys', maybe_serialize(array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token
        )));
    }

    public function is_active() {
        return !empty($this->access_token);
    }

    public function bombbomb_request($endpoint, $method = 'GET', $data = array(), $record = array()) {
        $base_url = 'https://api.bombbomb.com/v2/';

        $url = $base_url . $endpoint;

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->access_token,
            ),
        );

        if ('POST' === $method || 'PUT' === $method) {
            if ($data) {
                $args['body'] = json_encode($data);
            }
        }

        $response = $this->remote_request_with_refresh($url, $args, $record);

        return $response;
    }

    protected function remote_request_with_refresh($url, $request = array(), $record = array()) {
        static $refreshed = false;

        $response = wp_remote_request(esc_url_raw($url), $request);

        if (401 === wp_remote_retrieve_response_code($response) && !$refreshed) {
            $this->refresh_token();
            $refreshed = true;

            $request['headers']['Authorization'] = 'Bearer ' . $this->access_token;
            $response = $this->remote_request_with_refresh($url, $request, $record);
        }

        if ($record) {
            adfoin_add_to_log($response, $url, $request, $record);
        }

        return $response;
    }

    public function get_lists() {
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $response = $this->bombbomb_request( 'lists/' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( __( 'Error fetching lists', 'advanced-form-integration' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            wp_send_json_error( __( 'Error fetching lists', 'advanced-form-integration' ) );
        }

        $lists = array();

        if(is_array($body) && !empty($body)) {
            foreach ( $body as $list ) {
                $lists[$list['id']] = $list['name'];
            }
        }


        wp_send_json_success( $lists );
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='bombbomb-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'add_contact'">
                    <th scope='row'>
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope='row'>

                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php esc_attr_e( 'List', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=''> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                            <option v-for='(item, index) in fielddata.lists' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>
            </table>
        </script>
        <?php
    }
}

ADFOIN_BombBomb::get_instance();

add_action( 'adfoin_bombbomb_job_queue', 'adfoin_bombbomb_job_queue', 10, 1 );

function adfoin_bombbomb_job_queue( $data ) {
    adfoin_bombbomb_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to BombBomb API
 */
function adfoin_bombbomb_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];

    if( $task == 'add_contact' ) {
        $bombbomb = ADFOIN_BombBomb::get_instance();
        $contact_data = array();

        foreach ( $data as $key => $value ) {
            $value = adfoin_get_parsed_values( $value, $posted_data );
            $contact_data[$key] = $value;
        }

        $list_id = isset( $contact_data['listId']) ? $contact_data['listId'] : '';
        unset( $contact_data['listId'] );

        if (!empty($contact_data['email'])) {
            $contact_email = $contact_data['email'];

            $contact_info = array(
                'formValues' => array(
                    'basicInfo' => array_filter(array(
                        'first_name'     => isset($contact_data['first_name']) ? $contact_data['first_name'] : '',
                        'last_name'      => isset($contact_data['last_name']) ? $contact_data['last_name'] : '',
                        'email'          => $contact_email,
                        'address_line_1' => isset($contact_data['address_line_1']) ? $contact_data['address_line_1'] : '',
                        'address_line_2' => isset($contact_data['address_line_2']) ? $contact_data['address_line_2'] : '',
                        'city'           => isset($contact_data['city']) ? $contact_data['city'] : '',
                        'state'          => isset($contact_data['state']) ? $contact_data['state'] : '',
                        'country'        => isset($contact_data['country']) ? $contact_data['country'] : '',
                        'postal_code'    => isset($contact_data['postal_code']) ? $contact_data['postal_code'] : '',
                        'phone_number'   => isset($contact_data['phone_number']) ? $contact_data['phone_number'] : '',
                        'business_name'  => isset($contact_data['business_name']) ? $contact_data['business_name'] : '',
                        'position'       => isset($contact_data['position']) ? $contact_data['position'] : '',
                        'comments'       => isset($contact_data['comments']) ? $contact_data['comments'] : '',
                    ))
                ),
            );

            $payload = array(
                'contactEmail' => $contact_email,
                'contactInfo'  => json_encode($contact_info),
            );

            $response = $bombbomb->bombbomb_request('contacts/', 'POST', $payload, $record);

            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['success']) && $response_body['success'] === true) {
                $contact_id = isset($response_body['id']) ? $response_body['id'] : '';
                if (!empty($list_id) && !empty($contact_id)) {
                    $list_payload = array(
                        'ids' => array( $contact_id ),
                    );

                    $bombbomb->bombbomb_request("lists/{$list_id}/contacts", 'POST', $list_payload, $record);
                }
            }
        }

    }

    return;
}