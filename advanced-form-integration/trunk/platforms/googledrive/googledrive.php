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
    protected $token_expires      = 0;
    protected $cred_id            = '';

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {
        $this->token_endpoint         = self::token_endpoint;
        $this->authorization_endpoint = self::authorization_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action('admin_init', array($this, 'auth_redirect'));
        add_filter('adfoin_action_providers', array($this, 'adfoin_googledrive_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_googledrive_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_googledrive_settings_view'), 10, 1);
        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('wp_ajax_adfoin_get_googledrive_folders', array($this, 'get_drive_folder_list'), 10, 0);
        add_action('adfoin_job_queue', array($this, 'adfoin_googledrive_job_queue'), 10, 1);
        add_action("rest_api_init", array($this, "create_webhook_route"));
        add_action('wp_ajax_adfoin_get_googledrive_credentials', array($this, 'get_credentials'), 10, 0);
        add_filter('adfoin_get_credentials', array($this, 'modify_credentials'), 10, 2);
        add_action('wp_ajax_adfoin_save_googledrive_credentials', array($this, 'save_credentials'), 10, 0);
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
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
        if (isset($option['token_expires'])) {
            $this->token_expires = $option['token_expires'];
        }
        if ($this->is_active() && isset($option['folder_list'])) {
            $this->folder_list = $option['folder_list'];
        }
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
        $code   = isset($params['code']) ? trim($params['code']) : '';
        $state  = isset($params['state']) ? trim($params['state']) : '';

        if ($code) {
            // New OAuth Manager flow with state parameter
            if ($state) {
                $this->cred_id = $state;
                $credentials = adfoin_read_credentials('googledrive');
                
                foreach ($credentials as $value) {
                    if ($value['id'] == $state) {
                        $this->client_id     = isset($value['client_id']) ? $value['client_id'] : '';
                        $this->client_secret = isset($value['client_secret']) ? $value['client_secret'] : '';
                    }
                }

                $response = $this->request_token($code);
                
                $success = false;
                $message = 'Unknown error';
                
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (isset($body['access_token'])) {
                        $success = true;
                        $message = 'Connected successfully!';
                    } else {
                        $message = isset($body['error']) ? $body['error'] : 'Token exchange failed.';
                    }
                } else {
                    $message = is_wp_error($response) ? $response->get_error_message() : 'HTTP Error';
                }

                require_once plugin_dir_path(__FILE__) . '../../includes/class-adfoin-oauth-manager.php';
                ADFOIN_OAuth_Manager::handle_callback_close_popup($success, $message);
                exit;
            }
            
            // Legacy flow - redirect to admin
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
            $code  = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
            $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';

            if ($code) {
                // If state exists, use new credential system
                if ($state) {
                    $this->cred_id = $state;
                    $credentials = adfoin_read_credentials('googledrive');
                    
                    foreach ($credentials as $value) {
                        if ($value['id'] == $state) {
                            $this->client_id     = isset($value['client_id']) ? $value['client_id'] : '';
                            $this->client_secret = isset($value['client_secret']) ? $value['client_secret'] : '';
                        }
                    }
                }
                
                $this->request_token($code);
                
                // For popup flow
                if ($state) {
                    require_once plugin_dir_path(__FILE__) . '../../includes/class-adfoin-oauth-manager.php';
                    ADFOIN_OAuth_Manager::handle_callback_close_popup(true, 'Connected via Legacy Redirect');
                    exit;
                }
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

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __('Client ID', 'advanced-form-integration'),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __('Client Secret', 'advanced-form-integration'),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
        );

        // Instructions
        $domain = parse_url(get_site_url());
        $host   = $domain['host'];

        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf(__('Go to %s and create a New Project.', 'advanced-form-integration'), '<a target="_blank" rel="noopener noreferrer" href="https://console.developers.google.com/">Google Developer Console</a>') . '</li>';
        $instructions .= '<li>' . __('Go to Library and search for Google Drive API, open it and click ENABLE.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li>' . sprintf(__('Go to OAuth consent screen, select External, click Create. Enter %s in Authorized domains. Set publishing status to in production.', 'advanced-form-integration'), '<code>' . esc_html($host) . '</code>') . '</li>';
        $instructions .= '<li>' . __('Go to Credentials, click CREATE CREDENTIALS, select OAuth client ID, select Web application.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li>' . __('Copy the Redirect URI below and paste in Authorized redirect URIs:', 'advanced-form-integration') . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html($redirect_uri) . '</code></li>';
        $instructions .= '<li>' . __('Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration') . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __('Connect Google Drive', 'advanced-form-integration'),
            'submit_text' => __('Save & Authorize', 'advanced-form-integration'),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path(__FILE__) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view('googledrive', 'Google Drive', $fields, $instructions, $config);
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $all_credentials = adfoin_read_credentials('googledrive');
        wp_send_json_success($all_credentials);
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials($credentials, $platform) {
        if ('googledrive' == $platform && empty($credentials)) {
            $option = (array) maybe_unserialize(get_option('adfoin_googledrive_keys'));

            if (isset($option['client_id']) && isset($option['client_secret']) && !empty($option['client_id'])) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __('Default Account (Legacy)', 'advanced-form-integration'),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset($option['access_token']) ? $option['access_token'] : '',
                    'refresh_token' => isset($option['refresh_token']) ? $option['refresh_token'] : '',
                    'token_expires' => isset($option['token_expires']) ? $option['token_expires'] : 0,
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $platform    = 'googledrive';
        $credentials = adfoin_read_credentials($platform);
        
        if (!is_array($credentials)) {
            $credentials = array();
        }

        // Handle Deletion
        if (isset($_POST['delete_index'])) {
            $index = intval($_POST['delete_index']);
            if (isset($credentials[$index])) {
                // If deleting legacy credential, also clear the old option
                if (isset($credentials[$index]['id']) && strpos($credentials[$index]['id'], 'legacy_') === 0) {
                    delete_option('adfoin_googledrive_keys');
                }
                array_splice($credentials, $index, 1);
                adfoin_save_credentials($platform, $credentials);
                wp_send_json_success(array('message' => 'Deleted'));
            }
            wp_send_json_error('Invalid index');
        }

        // Handle Save/Update
        $id            = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        $title         = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $client_id     = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
        $client_secret = isset($_POST['client_secret']) ? sanitize_text_field($_POST['client_secret']) : '';

        if (empty($id)) {
            $id = uniqid();
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
            'token_expires' => 0,
        );

        // Check if updating existing credential
        $found = false;
        foreach ($credentials as &$cred) {
            if ($cred['id'] == $id) {
                // Preserve tokens if credentials haven't changed
                if (isset($cred['client_id']) && $cred['client_id'] == $client_id &&
                     isset($cred['client_secret']) && $cred['client_secret'] == $client_secret) {
                    $new_data['access_token']  = isset($cred['access_token']) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset($cred['refresh_token']) ? $cred['refresh_token'] : '';
                    $new_data['token_expires'] = isset($cred['token_expires']) ? $cred['token_expires'] : 0;
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials($platform, $credentials);

        // Generate Auth URL
        $scope    = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive.readonly';
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $scope,
                'state'         => $id,
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success(array('auth_url' => $auth_url));
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
            )
        );

        $response      = wp_remote_post(esc_url_raw($this->token_endpoint), $args);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (isset($response_body['access_token'])) {
            $this->access_token = $response_body['access_token'];
        }

        if (isset($response_body['refresh_token'])) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        // Cache token expiry
        if (isset($response_body['expires_in'])) {
            $this->token_expires = time() + (int) $response_body['expires_in'];
        } else {
            $this->token_expires = time() + 3600;
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
                            <?php esc_attr_e('Google Drive Account', 'advanced-form-integration'); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFolders">
                            <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                            <?php $this->get_credentials_list(); ?>
                        </select>
                        <a href="<?php echo admin_url('admin.php?page=advanced-form-integration-settings&tab=googledrive'); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e('Manage Accounts', 'advanced-form-integration'); ?>
                        </a>
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
        // If using new credential system
        if ($this->cred_id) {
            $credentials = adfoin_read_credentials('googledrive');

            foreach ($credentials as &$value) {
                if ($value['id'] == $this->cred_id) {
                    if ($this->access_token) {
                        $value['access_token'] = $this->access_token;
                    }
                    if ($this->refresh_token) {
                        $value['refresh_token'] = $this->refresh_token;
                    }
                    if ($this->token_expires) {
                        $value['token_expires'] = $this->token_expires;
                    }
                }
            }

            adfoin_save_credentials('googledrive', $credentials);
            return;
        }

        // Legacy save for backward compatibility
        $data = (array) maybe_unserialize(get_option('adfoin_googledrive_keys'));

        $option = array_merge(
            $data,
            array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'access_token'  => $this->access_token,
                'refresh_token' => $this->refresh_token,
                'folder_list'   => $this->folder_list,
                'token_expires' => $this->token_expires,
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
        $this->token_expires      = 0;
        $this->folder_list        = array();

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/googledrive');
    }

    /**
     * Set credentials from credential ID
     */
    public function set_credentials($cred_id) {
        $credentials = $this->get_credentials_by_id($cred_id);

        if (empty($credentials)) {
            return;
        }

        $this->client_id     = isset($credentials['client_id']) ? $credentials['client_id'] : '';
        $this->client_secret = isset($credentials['client_secret']) ? $credentials['client_secret'] : '';
        $this->access_token  = isset($credentials['access_token']) ? $credentials['access_token'] : '';
        $this->refresh_token = isset($credentials['refresh_token']) ? $credentials['refresh_token'] : '';
        $this->token_expires = isset($credentials['token_expires']) ? $credentials['token_expires'] : 0;
        $this->cred_id       = $credentials['id'];
    }

    /**
     * Get credentials list for dropdown
     */
    public function get_credentials_list() {
        $html        = '';
        $credentials = adfoin_read_credentials('googledrive');

        foreach ($credentials as $option) {
            $html .= '<option value="' . esc_attr($option['id']) . '">' . esc_html($option['title']) . '</option>';
        }

        echo $html;
    }

    /**
     * Get credentials by ID
     */
    public function get_credentials_by_id($cred_id) {
        $credentials     = array();
        $all_credentials = adfoin_read_credentials('googledrive');

        if (is_array($all_credentials) && !empty($all_credentials)) {
            // Default to first credential
            $credentials = $all_credentials[0];

            foreach ($all_credentials as $single) {
                if ($cred_id && $cred_id == $single['id']) {
                    $credentials = $single;
                    break;
                }
            }
        }

        return $credentials;
    }

    public function get_drive_folder_list() {
        if (!adfoin_verify_nonce()) return;

        $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
        
        if ($cred_id) {
            $this->set_credentials($cred_id);
        }

        $url = "https://www.googleapis.com/drive/v3/files?q=mimeType%20%3D%20'application%2Fvnd.google-apps.folder'&pageSize=1000";
        
        if (adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan('professional', true)) {
            $url .= "&supportsAllDrives=true&includeItemsFromAllDrives=true";
        }
        
        $request = array(
            'method'  => 'GET',
            'headers' => array()
        );

        $response = $this->remote_request($url, $request);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['error'])) {
            wp_send_json_error(isset($result['error']['message']) ? $result['error']['message'] : __('Unknown error', 'advanced-form-integration'));
            return;
        }

        $folder_list = array();

        if (isset($result['files'])) {
            foreach ($result['files'] as $folder) {
                $folder_list[$folder['id']] = $folder['name'];
            }
        }

        wp_send_json_success($folder_list);
    }

    protected function remote_request($url, $request = array(), $record = array(), $retry_count = 0) {
        $max_retries = 2;

        if (!$this->check_token_expiry($this->access_token)) {
            $this->refresh_token();
        }

        $request['headers'] = array(
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf('Bearer %s', $this->access_token)
        );

        $request['timeout'] = 30;

        $response      = wp_remote_request(esc_url_raw($url), $request);
        $response_code = wp_remote_retrieve_response_code($response);

        // Handle 401 Unauthorized with retry limit
        if (401 === $response_code && $retry_count < $max_retries) {
            $this->refresh_token();
            return $this->remote_request($url, $request, $record, $retry_count + 1);
        }

        // Handle 429 Rate Limiting with retry limit
        if (429 === $response_code && $retry_count < $max_retries) {
            $retry_after = wp_remote_retrieve_header($response, 'retry-after');
            $sleep_time  = $retry_after ? min((int) $retry_after, 10) : 5;
            sleep($sleep_time);
            return $this->remote_request($url, $request, $record, $retry_count + 1);
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

        // Use cached expiry time if available (with 60 second buffer)
        if ($this->token_expires > 0) {
            return time() < ($this->token_expires - 60);
        }

        // Fallback to API check for backward compatibility
        $return = wp_remote_get('https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $token);

        if (is_wp_error($return)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($return);

        if ($response_code == 200) {
            $body = json_decode(wp_remote_retrieve_body($return), true);
            if (isset($body['expires_in'])) {
                $this->token_expires = time() + (int) $body['expires_in'];
                $this->save_data();
            }
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
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (isset($response_body['access_token'])) {
            $this->access_token = $response_body['access_token'];
        }

        if (isset($response_body['refresh_token'])) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        // Cache token expiry
        if (isset($response_body['expires_in'])) {
            $this->token_expires = time() + (int) $response_body['expires_in'];
        } else {
            $this->token_expires = time() + 3600;
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

        $file_path = isset($record_data['field_data']['fileField']) ? adfoin_get_parsed_values($record_data['field_data']['fileField'], $posted_data) : '';
        $folder_id = isset($record_data['field_data']['folderId']) ? $record_data['field_data']['folderId'] : '';
        $cred_id   = isset($record_data['field_data']['credId']) ? $record_data['field_data']['credId'] : '';

        // Set credentials if provided
        if ($cred_id) {
            $this->set_credentials($cred_id);
        }

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
