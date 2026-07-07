<?php

class ADFOIN_MailUp extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'mailup';

    const service_name           = 'mailup';
    const authorization_endpoint = 'https://services.mailup.com/Authorization/OAuth/LogOn';
    const token_endpoint         = 'https://services.mailup.com/Authorization/OAuth/Token';

    private static $instance;

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Load legacy credentials for backward compatibility
     */
    private function load_legacy_credentials() {
        $option = (array) maybe_unserialize(get_option('adfoin_mailup_keys'));

        if (isset($option['client_id'])) {
            $this->client_id = $option['client_id'];
        }

        if (isset($option['client_secret'])) {
            $this->client_secret = $option['client_secret'];
        }

        if (isset($option['access_token'])) {
            $this->access_token = $option['access_token'];
        }

        if (isset($option['refresh_token'])) {
            $this->refresh_token = $option['refresh_token'];
        }
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        $this->refresh_token_endpoint = self::token_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter('adfoin_action_providers', array($this, 'adfoin_mailup_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_mailup_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_mailup_settings_view'), 10, 1);
        add_action('admin_post_adfoin_save_mailup_keys', array($this, 'adfoin_save_mailup_keys'), 10, 0);

        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('wp_ajax_adfoin_get_mailup_lists', array($this, 'get_lists'), 10, 0);
        add_action('wp_ajax_adfoin_get_mailup_groups', array($this, 'get_groups'), 10, 0);
        add_action('wp_ajax_adfoin_get_mailup_fields', array($this, 'get_fields'), 10, 0);

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_mailup_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_mailup_credentials', array( $this, 'save_credentials' ) );
        add_action( 'wp_ajax_adfoin_get_mailup_fields', array( $this, 'ajax_get_fields' ) );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
    }

    public function create_webhook_route() {
        register_rest_route('advancedformintegration', '/mailup', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true'
        ));
    }

    public function get_webhook_data($request) {
        $params = $request->get_params();
        $code = isset($params['code']) ? trim($params['code']) : '';

        if ($code) {
            $redirect_to = add_query_arg(
                [
                    'service' => 'authorize',
                    'action' => 'adfoin_mailup_auth_redirect',
                    'code' => $code,
                ],
                admin_url('admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect($redirect_to);
            exit();
        }
    }

    public function auth_redirect() {
        $auth = isset($_GET['auth']) ? trim($_GET['auth']) : '';
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $action = isset($_GET['action']) ? trim($_GET['action']) : '';
        $state = isset($_GET['state']) ? trim($_GET['state']) : '';

        if ('adfoin_mailup_auth_redirect' == $action) {
            $code = isset($_GET['code']) ? $_GET['code'] : '';

            if ($code) {
                // Legacy callback handling. This platform's credential-save
                // flow never issues an "oauth_manager_<cred_id>" state (no
                // code path here generates one), so the prior branch that
                // special-cased that prefix by trusting it verbatim was
                // removed. It had no legitimate caller and only served as
                // an unauthenticated way to overwrite an arbitrary stored
                // credential's tokens.
                $this->request_token($code);
            }

            if (!empty($this->access_token)) {
                $message = 'success';
            } else {
                $message = 'failed';
            }

            wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=mailup'));
            exit();
        }
    }

    public function adfoin_mailup_actions($actions) {
        $actions['mailup'] = array(
            'title' => __('MailUp', 'advanced-form-integration'),
            'tasks' => array(
                'subscribe' => __('Subscribe to List', 'advanced-form-integration'),
            )
        );
        return $actions;
    }

    public function adfoin_mailup_settings_tab($providers) {
        $providers['mailup'] = __('MailUp', 'advanced-form-integration');
        return $providers;
    }

    public function adfoin_mailup_settings_view($current_tab) {
        if ($current_tab != 'mailup') {
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
            '<ol><li>%s</li><li>%s</li></ol><p><strong>%s:</strong> %s</p>',
            __( 'Go to Settings > Advanced options > Developer Options and create new app', 'advanced-form-integration' ),
            __( 'Copy the Client ID and Client Secret', 'advanced-form-integration' ),
            __( 'Redirect URI', 'advanced-form-integration' ),
            $redirect_uri
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'mailup', __( 'MailUp', 'advanced-form-integration' ), $fields, $instructions );
    }

    public function adfoin_save_mailup_keys() {
        adfoin_require_manage_options();
        if (!wp_verify_nonce($_POST['_nonce'], 'adfoin_mailup_settings')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $client_id = isset($_POST["adfoin_mailup_client_id"]) ? sanitize_text_field( wp_unslash( $_POST["adfoin_mailup_client_id"] ) ) : "";
        $client_secret = isset($_POST["adfoin_mailup_client_secret"]) ? sanitize_text_field( wp_unslash( $_POST["adfoin_mailup_client_secret"] ) ) : "";

        if (!$client_id || !$client_secret) {
            $this->reset_data();
        } else {
            $this->client_id = trim($client_id);
            $this->client_secret = trim($client_secret);

            $this->save_data();
            $this->authorize('MailUp');
        }

        advanced_form_integration_redirect("admin.php?page=advanced-form-integration-settings&tab=mailup");
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="mailup-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'MailUp Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadFields">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailup' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e('List', 'advanced-form-integration'); ?>
                    </th>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId" @change="getGroups">
                            <option value=""> <?php _e('Select List...', 'advanced-form-integration'); ?> </option>
                            <option v-for="(list, index) in fielddata.lists" :value="index"> {{ list }} </option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                    </td>
                </tr>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e('Group', 'advanced-form-integration'); ?>
                    </th>
                    <td>
                        <select name="fieldData[groupId]" v-model="fielddata.groupId" @change="getGroups">
                            <option value=""> <?php _e('Select Group...', 'advanced-form-integration'); ?> </option>
                            <option v-for="(group, index) in fielddata.groups" :value="index"> {{ group }} </option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    public function get_lists() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $response = $this->request('List?PageSize=500', 'GET');

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $lists = array();

        if (isset($body['Items']) && !empty($body['Items'])) {
            foreach ($body['Items'] as $list) {
                $lists[$list['IdList']] = $list['Name'];
            }
        }

        wp_send_json_success($lists);
    }
    public function get_groups() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        $list_id = isset($_POST['listId']) ? sanitize_text_field( wp_unslash( $_POST['listId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        if (empty($list_id)) {
            wp_send_json_error(__('List ID is required', 'advanced-form-integration'));
        }

        $response = $this->request("List/{$list_id}/Groups", 'GET');

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $groups = array();

        if (isset($body['Items']) && !empty($body['Items'])) {
            foreach ($body['Items'] as $group) {
                $groups[$group['idGroup']] = $group['Name'];
            }
        }

        wp_send_json_success($groups);
    }

    public function get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $response = $this->request('Recipient/DynamicFields?PageSize=500&orderby="Id+asc"', 'GET');

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $fields = array();

        if (isset($body['Items']) && !empty($body['Items'])) {
            foreach ($body['Items'] as $field) {
                $fields[] = [ 'key' => 'field_'. $field['Id'], 'value' => $field['Description']];
            }
        }

        wp_send_json_success($fields);
    }

    protected function authorize($scope = '') {
        $data = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => urlencode($this->get_redirect_uri())
        );

        // if ($scope) {
        //     $data["scope"] = $scope;
        // }

        $endpoint = add_query_arg($data, self::authorization_endpoint);

        if (wp_redirect(esc_url_raw($endpoint))) {
            exit();
        }
    }

    protected function request_token($authorization_code) {
        $endpoint = add_query_arg(
            array(
                'code' => $authorization_code,
                'redirect_uri' => urlencode($this->get_redirect_uri()),
                'grant_type' => 'authorization_code'
            ),
            self::token_endpoint
        );

        $request = [
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
            )
        ];

        $response = wp_remote_post(esc_url_raw($endpoint), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (401 == $response_code) {
            $this->access_token = null;
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
        update_option( 'adfoin_mailup_keys', maybe_serialize( array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ) ) );
    }

    protected function reset_data() {
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/mailup');
    }

    public function request($endpoint, $method = 'GET', $data = array(), $record = array()) {
        $base_url = "https://services.mailup.com/API/v1.1/Rest/ConsoleService.svc/Console/";
        $url = $base_url . $endpoint;

        $args = array(
            'timeout' => 30,
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token
            ),
        );

        if ('POST' == $method || 'PUT' == $method) {
            $args['body'] = wp_json_encode($data);
        }

        $response = $this->remote_request($url, $args, $record);

        return $response;
    }

    protected function remote_request($url, $request = array(), $record = array()) {
        static $refreshed = false;

        $response = wp_remote_request(esc_url_raw($url), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

        // if resonse code 400 or 401, refresh token
        if (401 === $response_code && !$refreshed) {
            $this->refresh_token();
            $refreshed = true;

            $response = $this->remote_request($url, $request, $record);
        }

        if ($record) {
            adfoin_add_to_log($response, $url, $request, $record);
        }

        return $response;
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

    public function save_credentials() {
        adfoin_verify_nonce();

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );
        $credentials = isset( $_POST['credentials'] ) ? $_POST['credentials'] : array();

        if ( 'mailup' === $platform ) {
            ADFOIN_OAuth_Manager::save_credentials( $platform, $credentials );
        }

        wp_send_json_success();
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = array(
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'name', 'value' => __( 'Name', 'advanced-form-integration' ) ),
            array( 'key' => 'mobile_number', 'value' => __( 'Mobile Number', 'advanced-form-integration' ) ),
            array( 'key' => 'mobile_prefix', 'value' => __( 'Mobile Prefix', 'advanced-form-integration' ) ),
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

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'mailup', $cred_id );

        if ( $credentials ) {
            $this->cred_id       = $cred_id;
            $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
            $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
            $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
            $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        }
    }

    /**
     * Surface the legacy single-account credential set as a `legacy_*` record
     * when the multi-account store is otherwise empty. Lets users see and
     * manage their existing connection in the OAuth Manager UI.
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'mailup' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }
        $option = (array) maybe_unserialize( get_option( 'adfoin_mailup_keys' ) );
        if ( empty( $option['client_id'] ) || empty( $option['client_secret'] ) ) {
            return $credentials;
        }
        $credentials[] = array(
            'id'            => 'legacy_123456',
            'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
            'clientId'     => $option['client_id'],
            'client_secret' => $option['client_secret'],
            'access_token'  => isset( $option['access_token'] )  ? $option['access_token']  : '',
            'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
        );
        return $credentials;
    }

}

$mailup = ADFOIN_MailUp::get_instance();

add_action('adfoin_mailup_job_queue', 'adfoin_mailup_job_queue', 10, 1);

function adfoin_mailup_job_queue($data) {
    adfoin_mailup_send_data($data['record'], $data['posted_data']);
}

function adfoin_mailup_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $list_id = isset($data['listId']) ? $data['listId'] : '';
    $group_id = isset($data['groupId']) ? $data['groupId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    if ($task == 'subscribe') {
        $email = empty($data['email']) ? '' : trim(adfoin_get_parsed_values($data['email'], $posted_data));

        $subscriber_data = array(
            'Email' => $email,
        );

        unset($data['listId']);
        unset($data['groupId']);
        unset($data['credId']);
        unset($data['email']);

        if (!empty($data)) {
            $subscriber_data['Fields'] = [];
            foreach ($data as $key => $value) {
                $id = str_replace('field_', '', $key);
                $parsed_value = adfoin_get_parsed_values($value, $posted_data);
            
                if($parsed_value) {
                    $subscriber_data['Fields'][] = array(
                        'Id' => $id,
                        'Value' => $parsed_value
                    );
                }
            }
        }

        $mailup = ADFOIN_MailUp::get_instance();

        // Set credentials if provided
        if ( $cred_id ) {
            $mailup->set_credentials( $cred_id );
        }
        
        if( $group_id ) {
            $result = $mailup->request("Group/{$group_id}/Recipient", 'POST', $subscriber_data, $record);
        } else {
            $result = $mailup->request("List/{$list_id}/Recipient", 'POST', $subscriber_data, $record);
        }
    }
}
?>
