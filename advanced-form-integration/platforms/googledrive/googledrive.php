<?php

class ADFOIN_GoogleDrive extends Advanced_Form_Integration_OAuth2 {

    const service_name           = 'googledrive';
    const authorization_endpoint = 'https://accounts.google.com/o/oauth2/auth';
    const token_endpoint         = 'https://www.googleapis.com/oauth2/v3/token';

    private static $instance;
    protected $client_id          = '';
    protected $client_secret      = '';
    protected $google_access_code = '';
    protected $folder_list        = array();

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {
        $this->token_endpoint         = self::token_endpoint;
        $this->authorization_endpoint = self::authorization_endpoint;

        $option = (array) maybe_unserialize(get_option('adfoin_googledrive_keys'));

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

        if ($this->is_active()) {
            if (isset($option['folder_list'])) {
                $this->folder_list = $option['folder_list'];
            }
        }

        add_action('admin_init', array($this, 'auth_redirect'));
        add_filter('adfoin_action_providers', array($this, 'adfoin_googledrive_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_googledrive_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_googledrive_settings_view'), 10, 1);
        add_action('admin_post_adfoin_save_googledrive_keys', array($this, 'adfoin_save_googledrive_keys'), 10, 0);
        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('wp_ajax_adfoin_get_googledrive_folders', array($this, 'get_drive_folder_list'), 10, 0);
        add_action('adfoin_job_queue', array($this, 'adfoin_googledrive_job_queue'), 10, 1);
        add_action("rest_api_init", array($this, "create_webhook_route"));
    }

    public function create_webhook_route() {
        register_rest_route('advancedformintegration', '/googledrive', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_webhook_data'),
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
                    'action'  => 'adfoin_googledrive_auth_redirect',
                    'code'    => $code,
                ],
                admin_url('admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect($redirect_to);
            exit();
        }
    }

    public function auth_redirect() {
        $action = isset($_GET['action']) ? sanitize_text_field(trim($_GET['action'])) : '';

        if ('adfoin_googledrive_auth_redirect' == $action) {
            $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';

            if ($code) {
                $this->request_token($code);
            }

            wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=googledrive'));
            exit();
        }
    }

    public function adfoin_googledrive_actions($actions) {
        $actions['googledrive'] = array(
            'title' => __('Google Drive', 'advanced-form-integration'),
            'tasks' => array(
                'upload_file' => __('Upload File', 'advanced-form-integration'),
            )
        );

        return $actions;
    }

    public function adfoin_googledrive_settings_tab($providers) {
        $providers['googledrive'] = __('Google Drive', 'advanced-form-integration');

        return $providers;
    }

    public function adfoin_googledrive_settings_view($current_tab) {
        if ($current_tab != 'googledrive') {
            return;
        }

        $option        = (array) maybe_unserialize(get_option('adfoin_googledrive_keys'));
        $nonce         = wp_create_nonce("adfoin_googledrive_settings");
        $client_id     = isset($option['client_id']) ? $option['client_id'] : "";
        $client_secret = isset($option['client_secret']) ? $option['client_secret'] : "";
        $redirect_uri  = $this->get_redirect_uri();
        $domain        = parse_url($redirect_uri, PHP_URL_HOST);
        $host         = parse_url($redirect_uri, PHP_URL_SCHEME) . '://' . $domain;
        ?>

        <form name="googledrive_save_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="container">
            <input type="hidden" name="action" value="adfoin_save_googledrive_keys">
            <input type="hidden" name="_nonce" value="<?php echo $nonce; ?>"/>

            <table class="form-table">

                <tr valign="top">
                    <th scope="row"> <?php _e('Instructions', 'advanced-form-integration'); ?></th>
                    <td>
                        <p>
                            <?php _e('1. Go to <a target="_blank" rel="noopener noreferrer" href="https://console.developers.google.com/">Google Developer Console</a> and create a <b>New Project</b><br>
                                      2. Go to <b>Library</b> side menu and search for <b>Google Drive API</b>, open it and click <b>ENABLE</b>.<br>', 'advanced-form-integration'); ?>
                                      <?php printf(__('3. Go to <b>OAuth consent screen</b>, select <b>External</b> click <b>Create</b>. Put an <b>Application name</b> as you want, select user support email, enter <code><i>%s</i></code> in <b>Authorized domains</b>, put your email on developer contact email. In scopes add drive.file and drive.readonly scopes. then click <b>Save</b>. Please set the publishing status as <b>in production</b>, otherwise you might get a 403 error.<br>', 'advanced-form-integration'), $host); ?>
                                      <?php printf(__('4. Go to <b>Credentials</b>, click <b>CREATE CREDENTIALS</b>, select <b>OAuth client ID</b>, select application type as <b>Web application</b>, click <b>Create</b>, put anything in <b>Name</b>, save <code><i>%s</i></code> in <b>Authorized redirect URIs</b>, click <b>Create</b>.<br>', 'advanced-form-integration'), $redirect_uri); ?>
                                      <?php _e('5. Copy <b>Client ID</b> and <b>Client Secret</b> from newly created app and save below.<br>', 'advanced-form-integration'); ?>
                                      <?php _e('6. Click <b>Save & Authorize</b>, if appears <b>App is not verified</b> error click <b>show advanced</b> and then <b>Go to App</b>.<br><br>', 'advanced-form-integration'); ?>
                        </p>
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
                <tr valign="top">
                    <th scope="row"> <?php _e('Client ID', 'advanced-form-integration'); ?></th>
                    <td>
                        <input type="text" name="adfoin_googledrive_client_id"
                               value="<?php echo esc_attr($client_id); ?>" placeholder="<?php _e('Enter Client ID', 'advanced-form-integration'); ?>"
                               class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Client Secret', 'advanced-form-integration'); ?></th>
                    <td>
                        <input type="text" name="adfoin_googledrive_client_secret"
                               value="<?php echo esc_attr($client_secret); ?>" placeholder="<?php _e('Enter Client Secret', 'advanced-form-integration'); ?>"
                               class="regular-text"/>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save & Authorize', 'advanced-form-integration')); ?>
        </form>
        <?php
    }

    public function adfoin_save_googledrive_keys() {
        if (!wp_verify_nonce($_POST['_nonce'], 'adfoin_googledrive_settings')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $client_id     = isset($_POST["adfoin_googledrive_client_id"]) ? sanitize_text_field($_POST["adfoin_googledrive_client_id"]) : "";
        $client_secret = isset($_POST["adfoin_googledrive_client_secret"]) ? sanitize_text_field($_POST["adfoin_googledrive_client_secret"]) : "";

        $this->client_id     = trim($client_id);
        $this->client_secret = trim($client_secret);

        $this->save_data();
        $this->authorize('https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive.readonly');
    }

    protected function authorize($scope = '') {
        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'access_type'   => 'offline',
                'client_id'     => $this->client_id,
                'redirect_uri'  => urlencode($this->get_redirect_uri()),
                'scope'         => urlencode($scope),
            ),
            $this->authorization_endpoint
        );

        if (wp_redirect(esc_url_raw($endpoint))) {
            exit();
        }
    }

    protected function request_token($authorization_code) {
        $args = array(
            'headers' => array(),
            'body'    => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
                'access_type'   => 'offline',
                'prompt'        => 'consent'
            )
        );

        $response      = wp_remote_post(esc_url_raw($this->token_endpoint), $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['access_token'])) {
            $this->access_token = $response_body['access_token'];
        }

        if (isset($response_body['refresh_token'])) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        $this->save_data();

        return $response;
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="googledrive-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'upload_file'">
                    <th scope="row">
                        <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'upload_file'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e('Folder', 'advanced-form-integration'); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[folderId]" v-model="fielddata.folderId">
                            <option value=""><?php _e('Select...', 'advanced-form-integration'); ?></option>
                            <option v-for="(item, id) in fielddata.folderList" :value="id">{{ item }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': folderLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:5px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    protected function save_data() {
        $data = (array) maybe_unserialize(get_option('adfoin_googledrive_keys'));

        $option = array_merge(
            $data,
            array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'access_token'  => $this->access_token,
                'refresh_token' => $this->refresh_token
            )
        );

        update_option('adfoin_googledrive_keys', maybe_serialize($option));
    }

    protected function reset_data() {
        $this->client_id          = '';
        $this->client_secret      = '';
        $this->google_access_code = '';
        $this->access_token       = '';
        $this->refresh_token      = '';
        $this->folder_list        = array();

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/googledrive');
    }

    public function get_drive_folder_list() {
        if (!adfoin_verify_nonce()) return;

        $option = (array) maybe_unserialize(get_option('adfoin_googledrive_keys'));

        $this->access_token = isset($option['access_token']) ? $option['access_token'] : '';

        $url = "https://www.googleapis.com/drive/v3/files?q=mimeType%20%3D%20'application%2Fvnd.google-apps.folder'&pageSize=1000&supportsAllDrives=true&includeItemsFromAllDrives=true&access_token=" . $this->access_token;
        
        $request = array(
            'method'  => 'GET',
            'headers' => array()
        );

        $response = $this->remote_request( $url, $request );

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        $folder_list = array();

        if (isset($result['files'])) {
            foreach ($result['files'] as $folder) {
                $folder_list[$folder['id']] = $folder['name'];
            }
        }

        wp_send_json_success($folder_list);
    }

    protected function remote_request($url, $request = array(), $record = array()) {
        if (!$this->check_token_expiry($this->access_token)) {
            $this->refresh_token();
        }

        $request['headers'] = array(
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf('Bearer %s', $this->access_token)
        );

        $request['timeout'] = 30;

        $response = wp_remote_request(esc_url_raw($url), $request);

        if (401 === wp_remote_retrieve_response_code($response)) {
            $this->refresh_token();
            $response = $this->remote_request($url, $request, $record);
        }

        if ($record) {
            adfoin_add_to_log($response, $url, $request, $record);
        }

        return $response;
    }

    public function check_token_expiry($token = '') {
        if (empty($token)) {
            return false;
        }

        $return = wp_remote_get('https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $token);

        if (is_wp_error($return)) {
            return false;
        }

        $body = json_decode($return['body'], true);

        if ($return['response']['code'] == 200) {
            return true;
        }

        return false;
    }

    protected function refresh_token() {
        $args = array(
            'headers' => array(),
            'body'    => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
            )
        );

        $response      = wp_remote_post(esc_url_raw($this->token_endpoint), $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['access_token'])) {
            $this->access_token = $response_body['access_token'];
        }

        if (isset($response_body['refresh_token'])) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        $this->save_data();

        return $response;
    }

    public function upload_file($record, $posted_data) {
        $record_data = json_decode($record["data"], true);

        if (isset($record_data["action_data"]["cl"]["active"]) && $record_data["action_data"]["cl"]["active"] == "yes") {
            if (!adfoin_match_conditional_logic($record_data["action_data"]["cl"], $posted_data)) {
                return;
            }
        }

        $file_path = isset($record_data['field_data']['fileField']) ? adfoin_get_parsed_values( $record_data['field_data']['fileField'], $posted_data ) : '';
        $folder_id = isset($record_data['field_data']['folderId']) ? $record_data['field_data']['folderId'] : '';

        if (!$file_path || !$folder_id) {
            return;
        }

        $url = "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart";

        $metadata = json_encode([
            'name' => basename($file_path),
            'parents' => [$folder_id]
        ]);

        $file_content = file_get_contents($file_path);

        $boundary = uniqid();
        $body = "--$boundary\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= $metadata . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= $file_content . "\r\n";
        $body .= "--$boundary--";

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'multipart/related; boundary=' . $boundary,
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            adfoin_add_to_log($response, $url, $body, $record);
        } else {
            $result = json_decode(wp_remote_retrieve_body($response), true);
            adfoin_add_to_log($result, $url, $body, $record);
        }
    }
}

$googledrive = ADFOIN_GoogleDrive::get_instance();

add_action('adfoin_googledrive_job_queue', 'adfoin_googledrive_job_queue', 10, 1);

function adfoin_googledrive_job_queue($data) {
    adfoin_googledrive_send_data($data['record'], $data['posted_data']);
}

/*
 * Handles uploading file to Google Drive
 */
function adfoin_googledrive_send_data($record, $submitted_data) {
    $googledrive = ADFOIN_GoogleDrive::get_instance();
    $googledrive->upload_file($record, $submitted_data);
}
