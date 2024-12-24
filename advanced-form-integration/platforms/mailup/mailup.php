<?php

class ADFOIN_MailUp extends Advanced_Form_Integration_OAuth2 {

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

    private function __construct() {
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

        $this->refresh_token_endpoint = self::token_endpoint;

        add_filter('adfoin_action_providers', array($this, 'adfoin_mailup_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_mailup_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_mailup_settings_view'), 10, 1);
        add_action('admin_post_adfoin_save_mailup_keys', array($this, 'adfoin_save_mailup_keys'), 10, 0);
        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('wp_ajax_adfoin_get_mailup_lists', array($this, 'get_lists'), 10, 0);
        add_action('wp_ajax_adfoin_get_mailup_groups', array($this, 'get_groups'), 10, 0);
        add_action('wp_ajax_adfoin_get_mailup_fields', array($this, 'get_fields'), 10, 0);
        add_action('admin_init', array($this, 'auth_redirect'));
        add_action('rest_api_init', array($this, 'create_webhook_route'));
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

        if ('adfoin_mailup_auth_redirect' == $action) {
            $code = isset($_GET['code']) ? $_GET['code'] : '';

            if ($code) {
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

        $option = (array) maybe_unserialize(get_option('adfoin_mailup_keys'));
        $nonce = wp_create_nonce("adfoin_mailup_settings");
        $client_id = isset($option['client_id']) ? $option['client_id'] : '';
        $client_secret = isset($option['client_secret']) ? $option['client_secret'] : '';
        $redirect_uri = $this->get_redirect_uri();
        ?>

        <form name="mailup_save_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="container">
            <input type="hidden" name="action" value="adfoin_save_mailup_keys">
            <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"> <?php _e('Instructions', 'advanced-form-integration'); ?></th>
                    <td>
                        <p>
                            <ol>
                                <li>Go to Settings > Advanced options > Developer Options and create new app</li>
                                <li>Copy the Client ID and Client Secret</li>
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
                        <input type="text" name="adfoin_mailup_client_id" value="<?php echo esc_attr($client_id); ?>" placeholder="<?php _e('Enter Client ID', 'advanced-form-integration'); ?>" class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Client Secret', 'advanced-form-integration'); ?></th>
                    <td>
                        <input type="text" name="adfoin_mailup_client_secret" value="<?php echo esc_attr($client_secret); ?>" placeholder="<?php _e('Enter Client Secret', 'advanced-form-integration'); ?>" class="regular-text"/>
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

    public function adfoin_save_mailup_keys() {
        if (!wp_verify_nonce($_POST['_nonce'], 'adfoin_mailup_settings')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $client_id = isset($_POST["adfoin_mailup_client_id"]) ? sanitize_text_field($_POST["adfoin_mailup_client_id"]) : "";
        $client_secret = isset($_POST["adfoin_mailup_client_secret"]) ? sanitize_text_field($_POST["adfoin_mailup_client_secret"]) : "";

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
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
                        <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    public function get_lists() {
        if (!adfoin_verify_nonce()) return;

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
        if (!adfoin_verify_nonce()) return;

        $list_id = isset($_POST['listId']) ? sanitize_text_field($_POST['listId']) : '';

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

        update_option('adfoin_mailup_keys', maybe_serialize($option));
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
            $args['body'] = json_encode($data);
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
    $task = $record['task'];

    if ($task == 'subscribe') {
        $email = empty($data['email']) ? '' : trim(adfoin_get_parsed_values($data['email'], $posted_data));

        $subscriber_data = array(
            'Email' => $email,
        );

        unset($data['listId']);
        unset($data['groupId']);
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
        
        if( $group_id ) {
            $result = $mailup->request("Group/{$group_id}/Recipient", 'POST', $subscriber_data, $record);
        } else {
            $result = $mailup->request("List/{$list_id}/Recipient", 'POST', $subscriber_data, $record);
        }
    }
}
?>
