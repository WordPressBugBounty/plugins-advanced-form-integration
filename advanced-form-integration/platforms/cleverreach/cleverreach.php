<?php

class ADFOIN_CleverReach extends Advanced_Form_Integration_OAuth2 {

    const authorization_endpoint = 'https://rest.cleverreach.com/oauth/authorize.php';
    const token_endpoint         = 'https://rest.cleverreach.com/oauth/token.php';

    private static $instance;

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $option = (array) maybe_unserialize(get_option('adfoin_cleverreach_keys'));

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

        $this->refresh_token_endpoint = self::token_endpoint;

        add_filter('adfoin_action_providers', array($this, 'adfoin_cleverreach_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_cleverreach_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_cleverreach_settings_view'), 10, 1);
        add_action('admin_post_adfoin_save_cleverreach_keys', array($this, 'adfoin_save_cleverreach_keys'), 10, 0);
        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('wp_ajax_adfoin_get_cleverreach_groups', array($this, 'get_groups'), 10, 0);
        add_action('wp_ajax_adfoin_get_cleverreach_fields', array($this, 'get_fields'), 10, 0);
        add_action('admin_init', array($this, 'auth_redirect'));
        add_action('rest_api_init', array($this, 'create_webhook_route'));
    }

    public function create_webhook_route() {
        register_rest_route('advancedformintegration', '/cleverreach', array(
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
                    'action' => 'adfoin_cleverreach_auth_redirect',
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

        if ('adfoin_cleverreach_auth_redirect' == $action) {
            $code = isset($_GET['code']) ? $_GET['code'] : '';

            if ($code) {
                $this->request_token($code);
            }

            if (!empty($this->access_token)) {
                $message = 'success';
            } else {
                $message = 'failed';
            }

            wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=cleverreach'));
            exit();
        }
    }

    public function adfoin_cleverreach_actions($actions) {
        $actions['cleverreach'] = array(
            'title' => __('CleverReach', 'advanced-form-integration'),
            'tasks' => array(
                'subscribe' => __('Subscribe to List', 'advanced-form-integration'),
            )
        );
        return $actions;
    }

    public function adfoin_cleverreach_settings_tab($providers) {
        $providers['cleverreach'] = __('CleverReach', 'advanced-form-integration');
        return $providers;
    }

    public function adfoin_cleverreach_settings_view($current_tab) {
        if ($current_tab != 'cleverreach') {
            return;
        }

        $option = (array) maybe_unserialize(get_option('adfoin_cleverreach_keys'));
        $nonce = wp_create_nonce("adfoin_cleverreach_settings");
        $client_id = isset($option['client_id']) ? $option['client_id'] : '';
        $client_secret = isset($option['client_secret']) ? $option['client_secret'] : '';
        $redirect_uri = $this->get_redirect_uri();
        ?>

        <form name="cleverreach_save_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="container">
            <input type="hidden" name="action" value="adfoin_save_cleverreach_keys">
            <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"> <?php _e('Instructions', 'advanced-form-integration'); ?></th>
                    <td>
                        <p>
                            <ol>
                                <li>Go to Settings > API.</li>
                                <li>Generate and copy the key</li>
                            </ol>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Redirect URI', 'advanced-form-integration'); ?></th>
                    <td>
                        <code><?php echo esc_url($redirect_uri); ?></code>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Client ID', 'advanced-form-integration'); ?></th>
                    <td>
                        <input type="text" name="adfoin_cleverreach_client_id" value="<?php echo esc_attr($client_id); ?>" placeholder="<?php _e('Enter Client ID', 'advanced-form-integration'); ?>" class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Client Secret', 'advanced-form-integration'); ?></th>
                    <td>
                        <input type="text" name="adfoin_cleverreach_client_secret" value="<?php echo esc_attr($client_secret); ?>" placeholder="<?php _e('Enter Client Secret', 'advanced-form-integration'); ?>" class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Status', 'advanced-form-integration'); ?></th>
                    <td>
                        <?php
                        if ($this->is_active()) {
                            _e('Connected', 'advanced-form-integration');
                        } else {
                            _e('Not Connected', 'advanced-form-integration');
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Authorize', 'advanced-form-integration')); ?>
        </form>

        <?php
    }

    public function adfoin_save_cleverreach_keys() {
        if (!wp_verify_nonce($_POST['_nonce'], 'adfoin_cleverreach_settings')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $client_id = isset($_POST["adfoin_cleverreach_client_id"]) ? sanitize_text_field($_POST["adfoin_cleverreach_client_id"]) : "";
        $client_secret = isset($_POST["adfoin_cleverreach_client_secret"]) ? sanitize_text_field($_POST["adfoin_cleverreach_client_secret"]) : "";

        if (!$client_id || !$client_secret) {
            $this->reset_data();
        } else {
            $this->client_id = trim($client_id);
            $this->client_secret = trim($client_secret);

            $this->save_data();
            $this->authorize();
        }

        advanced_form_integration_redirect("admin.php?page=advanced-form-integration-settings&tab=cleverreach");
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="cleverreach-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e('List', 'advanced-form-integration'); ?>
                    </th>
                    <td>
                        <select name="fieldData[groupId]" v-model="fielddata.groupId">
                            <option value=""> <?php _e('Select List...', 'advanced-form-integration'); ?> </option>
                            <option v-for="(list, index) in fielddata.groups" :value="index"> {{ list }} </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    public function get_groups() {
        if (!adfoin_verify_nonce()) return;

        $response = $this->request('groups');

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $lists = array();

        if (is_array($body)) {
            foreach ($body as $list) {
                $lists[$list['id']] = $list['name'];
            }
        }

        wp_send_json_success($lists);
    }

    public function get_fields() {
        if (!adfoin_verify_nonce()) return;

        $fields = array(
            array('key' => 'email', 'value' => 'Email')
        );

        $response = $this->request('attributes');

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (is_array($body)) {
            foreach ($body as $field) {
                $fields[] = ['key' => $field['name'], 'value' => $field['description']];
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

        $endpoint = add_query_arg($data, self::authorization_endpoint);

        if (wp_redirect(esc_url_raw($endpoint))) {
            exit();
        }
    }

    protected function request_token($authorization_code) {
        $body = array(
            'code' => $authorization_code,
            'redirect_uri' => $this->get_redirect_uri(),
            'grant_type' => 'authorization_code'
        );

        $request = [
            'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
            ),
            'body' => $body
        ];

        $response = wp_remote_post(esc_url_raw(self::token_endpoint), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (401 == $response_code) {
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            if (isset($response_body['access_token'])) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            if (isset($response_body['refresh_token'])) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                $this->refresh_token = null;
            }
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        $option = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token
        );

        update_option('adfoin_cleverreach_keys', maybe_serialize($option));
    }

    protected function reset_data() {
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/cleverreach');
    }

    public function request($endpoint, $method = 'GET', $data = array(), $record = array()) {
        $base_url = "https://rest.cleverreach.com/v3/";
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
            $args['body'] = json_encode($data);
        }

        $response = $this->remote_request($url, $args, $record);

        return $response;
    }

    protected function remote_request($url, $request = array(), $record = array()) {
        static $refreshed = false;

        $response = wp_remote_request(esc_url_raw($url), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

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
}

$cleverreach = ADFOIN_CleverReach::get_instance();

add_action('adfoin_cleverreach_job_queue', 'adfoin_cleverreach_job_queue', 10, 1);

function adfoin_cleverreach_job_queue($data) {
    adfoin_cleverreach_send_data($data['record'], $data['posted_data']);
}

function adfoin_cleverreach_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $group_id = isset($data['groupId']) ? $data['groupId'] : '';
    $task = $record['task'];

    unset($data['groupId']);

    if ($task == 'subscribe') {
        $subscriber_data = array();

        foreach ($data as $key => $value) {
            $value = adfoin_get_parsed_values($value, $posted_data);

            if ($value) {
                $subscriber_data[$key] = $value;
            }
        }

        $receiver_data = array(
            'email' => $subscriber_data['email'],
            'global_attributes' => $subscriber_data
        );

        unset($receiver_data['global_attributes']['email']);

        $cleverreach = ADFOIN_CleverReach::get_instance();
        $response = $cleverreach->request("groups.json/{$group_id}/receivers/upsert", 'POST', $receiver_data, $record);
    }

    return;
}
