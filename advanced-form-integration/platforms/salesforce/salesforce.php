<?php

class ADFOIN_Salesforce extends Advanced_Form_Integration_OAuth2 {

    const authorization_endpoint = 'https://login.salesforce.com/services/oauth2/authorize';
    const token_endpoint         = 'https://login.salesforce.com/services/oauth2/token';
    const refresh_token_endpoint = 'https://login.salesforce.com/services/oauth2/token';

    private static $instance;
    protected $client_id = '';
    protected $client_secret = '';
    protected $access_token = '';
    protected $refresh_token = '';

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        $option = (array) maybe_unserialize( get_option( 'adfoin_salesforce_keys' ) );

        if ( isset( $option['client_id'] ) ) {
            $this->client_id = $option['client_id'];
        }

        if ( isset( $option['client_secret'] ) ) {
            $this->client_secret = $option['client_secret'];
        }

        if ( isset( $option['access_token'] ) ) {
            $this->access_token = $option['access_token'];
        }

        if ( isset( $option['refresh_token'] ) ) {
            $this->refresh_token = $option['refresh_token'];
        }

        add_action('admin_init', array($this, 'auth_redirect'));
        add_filter('adfoin_action_providers', array($this, 'adfoin_salesforce_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_salesforce_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_salesforce_settings_view'), 10, 1);
        add_action('admin_post_adfoin_save_salesforce_keys', array($this, 'adfoin_save_salesforce_keys'));
        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('rest_api_init', array($this, 'create_webhook_route'));
        add_action('wp_ajax_adfoin_get_salesforce_credentials', array($this, 'get_credentials'));
        add_action('wp_ajax_adfoin_save_salesforce_credentials', array($this, 'save_credentials'));
    }

    public function create_webhook_route() {
        register_rest_route('advancedformintegration', '/salesforce', [
            'methods' => 'GET',
            'callback' => [$this, 'get_webhook_data'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_webhook_data($request) {
        $params = $request->get_params();
        $code = isset($params['code']) ? urlencode($params['code']) : '';

        if ($code) {

            $redirect_to = add_query_arg(
            [
                'service' => 'authorize',
                'action' => 'adfoin_salesforce_auth_redirect',
                'code' => $code,
            ], admin_url('admin.php?page=advanced-form-integration'));

            wp_safe_redirect($redirect_to);
            exit();
        }
    }

    public function auth_redirect() {

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

        if ('adfoin_salesforce_auth_redirect' == $action) {
            $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';

            if ($code) {
                $this->request_token($code);
            }

            wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=salesforce'));

            exit();
        }
    }

    public function adfoin_salesforce_actions($actions) {
        $actions['salesforce'] = [
            'title' => __('Salesforce', 'advanced-form-integration'),
            'tasks' => ['subscribe' => __('Add new record', 'advanced-form-integration')],
        ];
        return $actions;
    }

    public function adfoin_salesforce_settings_tab($providers) {
        $providers['salesforce'] = __('Salesforce', 'advanced-form-integration');
        return $providers;
    }

    public function adfoin_salesforce_settings_view($current_tab) {
        if ($current_tab !== 'salesforce') return;

        $option = (array) maybe_unserialize(get_option('adfoin_salesforce_keys'));
        $nonce = wp_create_nonce("adfoin_salesforce_settings");
        $client_id = isset($option['client_id']) ? $option['client_id'] : "";
        $client_secret = isset($option['client_secret']) ? $option['client_secret'] : "";
        $redirect_uri = $this->get_redirect_uri();
        $domain = parse_url(get_site_url());
        $host = $domain['host'];
        ?>

        <form name="salesforce_save_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              method="post" class="container">

            <input type="hidden" name="action" value="adfoin_save_salesforce_keys">
            <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"> <?php _e('Instructions', 'advanced-form-integration'); ?></th>
                    <td>
                        <p>
                            <ol>
                                <li>Log in to your Salesforce account.</li>
                                <li>Click the <strong>Settings</strong> icon (gear) in the top-right corner and select <strong>Setup</strong>.</li>
                                <li>In the left-hand menu, go to <strong>Platform Tools</strong>, then click <strong>Apps</strong>, and select <strong>App Manager</strong>.</li>
                                <li>Click on <strong>New Connected App</strong> to create a new app.</li>
                                <li>Enter the following details:</li>
                                <ul>
                                    <li><strong>Connected App Name</strong></li>
                                    <li><strong>API Name</strong></li>
                                    <li><strong>Contact Email</strong></li>
                                </ul>
                                <li>Check <strong>Enable OAuth Settings</strong> under the API section.</li>
                                <li>In the <strong>Callback URL</strong> field, copy <code><?php echo $redirect_uri; ?></code></li>
                                <li>Add the following OAuth scopes:</li>
                                <ul>
                                    <li><strong>Full Access</strong></li>
                                    <li><strong>Perform requests anytime (including refresh_token and offline_access)</strong>
                                </ul>
                                <li>Uncheck the option <strong>Require Proof Key for Code Exchange (PKCE)</strong>.</li>
                                <li>Click <strong>Save and Continue</strong> to proceed.</li>
                                <li>After saving, go back to the API section and click on <strong>Manage Consumer Details</strong>.</li>
                                <li>Verify your account using the OTP sent to your email.</li>
                                <li>Copy the <strong>Consumer Key</strong> and paste it into the <strong>Client ID</strong> field.</li>
                                <li>Copy the <strong>Consumer Secret</strong> and paste it into the <strong>Client Secret</strong> field.</li>
                                <li>Go to <strong>Identify</strong> > <strong>OAuth and OpenID Connect Settings</strong> and turn on <strong>Allow OAuth Username-Password Flows</strong>.</li>
                            </ol>
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
                        <input type="text" name="adfoin_salesforce_client_id"
                               value="<?php echo esc_attr($client_id); ?>" placeholder="<?php _e('Enter Client ID', 'advanced-form-integration'); ?>"
                               class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Client Secret', 'advanced-form-integration'); ?></th>
                    <td>
                        <input type="text" name="adfoin_salesforce_client_secret"
                               value="<?php echo esc_attr($client_secret); ?>" placeholder="<?php _e('Enter Client Secret', 'advanced-form-integration'); ?>"
                               class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php _e('Re-Authorize', 'advanced-form-integration'); ?></th>
                    <td>
                        <?php
                        _e('Try re-authorizing if you face issues. Go to <a target="_blank" rel="noopener noreferrer" href="https://login.salesforce.com/permissions" ><b>Salesforce App Permissions</b></a> and hit <b>REMOVE ACCESS</b> on any previous authorization of this app. Now click on the <b>Save & Authorize</b> button below and finish the authorization process again.', 'advanced-form-integration');
                        ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save & Authorize', 'advanced-form-integration')); ?>
        </form>

        <?php
    }

    public function adfoin_save_salesforce_keys() {
        // Security Check
        if (!wp_verify_nonce($_POST['_nonce'], 'adfoin_salesforce_settings')) {
            die(__('Security check failed', 'advanced-form-integration'));
        }

        $client_id = isset($_POST["adfoin_salesforce_client_id"]) ? sanitize_text_field($_POST["adfoin_salesforce_client_id"]) : "";
        $client_secret = isset($_POST["adfoin_salesforce_client_secret"]) ? sanitize_text_field($_POST["adfoin_salesforce_client_secret"]) : "";

        $this->client_id = trim($client_id);
        $this->client_secret = trim($client_secret);

        $this->save_data();
        $this->authorize();

        wp_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=salesforce'));
        exit;
    }

    protected function authorize($scope = '') {
        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id' => $this->client_id,
                'redirect_uri' => urlencode($this->get_redirect_uri()),
                'prompt' => 'login consent'
            ),
            $this->authorization_endpoint
        );

        if (wp_redirect(esc_url_raw($endpoint))) {
            exit();
        }
    }

    

    protected function request_token($code) {
        $url = $this->token_endpoint . '?' . http_build_query(array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->get_redirect_uri(),
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ));

        $url = 'https://login.salesforce.com/services/oauth2/token?grant_type=authorization_code&client_id=' . $this->client_id . '&client_secret=' . $this->client_secret . '&redirect_uri=' . $this->get_redirect_uri() . '&code=' . $code;

        $args = array(
            'headers' => array(
                'user-agent' => 'wordpress/advanced-form-integration'
            ),
            'timeout' => 30,
            'method' => 'POST',
            'body' => array(),
        );

        $response      = wp_remote_request($url, $args);
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

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/salesforce');
    }

    protected function save_data() {
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ];
        update_option('adfoin_salesforce_keys', maybe_serialize( $data ));
    }

    protected function reset_data() {
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';
        $this->save_data();
    }

}

// $salesforce = ADFOIN_Salesforce::get_instance();
