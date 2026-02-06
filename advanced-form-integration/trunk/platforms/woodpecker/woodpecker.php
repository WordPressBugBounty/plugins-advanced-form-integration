<?php

class ADFOIN_Woodpecker {
    private static $instance;

    private $api_key;

    private $cred_id;

    /**
     * Retrieve singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter(
            'adfoin_action_providers',
            array($this, 'register_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'register_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'settings_view'),
            10,
            1
        );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        // Account Manager AJAX hooks
        add_action( 'wp_ajax_adfoin_get_woodpecker_credentials', array($this, 'get_credentials') );
        add_action( 'wp_ajax_adfoin_save_woodpecker_credentials', array($this, 'save_credentials') );
        add_filter(
            'adfoin_get_credentials',
            array($this, 'modify_credentials'),
            10,
            2
        );
    }

    /**
     * Register Woodpecker provider/tasks.
     *
     * @param array $actions Existing providers.
     *
     * @return array
     */
    public function register_actions( $actions ) {
        $actions['woodpecker'] = array(
            'title' => __( 'Woodpecker.co', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Add Subscriber', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    /**
     * Add settings tab entry.
     *
     * @param array $providers Current tabs.
     *
     * @return array
     */
    public function register_settings_tab( $providers ) {
        $providers['woodpecker'] = __( 'Woodpecker.co', 'advanced-form-integration' );
        return $providers;
    }

    /**
     * Render Woodpecker settings screen.
     *
     * @param string $current_tab Current tab slug.
     */
    public function settings_view( $current_tab ) {
        if ( 'woodpecker' !== $current_tab ) {
            return;
        }
        // Load Account Manager
        if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }
        $fields = array(array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter your Woodpecker API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ));
        $instructions = '<ol class="afi-instructions-list">
                            <li>' . esc_html__( 'Log in to your Woodpecker.co account.', 'advanced-form-integration' ) . '</li>
                            <li>' . esc_html__( 'Go to Prospects > Integrations > API Keys.', 'advanced-form-integration' ) . '</li>
                            <li>' . esc_html__( 'Generate a new API Key or copy an existing one.', 'advanced-form-integration' ) . '</li>
                            <li>' . esc_html__( 'Click "Add Account" and enter your API Key.', 'advanced-form-integration' ) . '</li>
                        </ol>';
        ADFOIN_Account_Manager::render_settings_view(
            'woodpecker',
            'Woodpecker.co',
            $fields,
            $instructions
        );
    }

    public function get_credentials() {
        if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }
        ADFOIN_Account_Manager::ajax_get_credentials( 'woodpecker' );
    }

    public function save_credentials() {
        if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }
        ADFOIN_Account_Manager::ajax_save_credentials( 'woodpecker', array('api_key') );
    }

    public function modify_credentials( $credentials, $platform ) {
        if ( 'woodpecker' == $platform && empty( $credentials ) ) {
            $api_key = get_option( 'adfoin_woodpecker_api_key' );
            if ( $api_key ) {
                $credentials[] = array(
                    'id'      => 'legacy_123456',
                    'title'   => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'api_key' => $api_key,
                );
            }
        }
        return $credentials;
    }

    public function set_credentials( $cred_id ) {
        $credentials = adfoin_read_credentials( 'woodpecker' );
        // Backward compatibility: If no credId, use the first available credential
        if ( empty( $cred_id ) && !empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = ( isset( $first_credential['id'] ) ? $first_credential['id'] : '' );
        }
        foreach ( $credentials as $single ) {
            if ( $cred_id && $cred_id == $single['id'] ) {
                $this->cred_id = $single['id'];
                $this->api_key = ( isset( $single['api_key'] ) ? $single['api_key'] : '' );
                return;
            }
        }
    }

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'woodpecker' );
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    /**
     * Print action template markup.
     */
    public function action_fields() {
        ?>
        <script type="text/template" id="woodpecker-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php 
        esc_attr_e( 'Woodpecker Account', 'advanced-form-integration' );
        ?>
                    </th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <?php 
        $this->get_credentials_list();
        ?>
                        </select>
                        <a href="<?php 
        echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=woodpecker' );
        ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
        esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
        ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php 
        esc_attr_e( 'Map Fields', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php 
        if ( adfoin_fs()->is_not_paying() ) {
            ?>
                        <tr valign="top" v-if="action.task == 'subscribe'">
                            <th scope="row">
                                <?php 
            esc_attr_e( 'Go Pro', 'advanced-form-integration' );
            ?>
                            </th>
                            <td scope="row">
                                <span><?php 
            printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
            ?></span>
                            </td>
                        </tr>
                        <?php 
        }
        ?>
                
            </table>
        </script>
        <?php 
    }

    /**
     * Woodpecker API Request
     */
    public function api_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        if ( !$this->api_key ) {
            return new WP_Error('missing_api_key', __( 'Woodpecker API Key is missing', 'advanced-form-integration' ));
        }
        $base_url = 'https://api.woodpecker.co/rest/v1/';
        $url = $base_url . $endpoint;
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':' . 'X' ),
            ),
            'timeout' => 30,
        );
        if ( 'POST' == $method || 'PUT' == $method ) {
            $args['body'] = json_encode( $data );
        }
        $response = wp_remote_request( $url, $args );
        if ( $record ) {
            adfoin_add_to_log(
                $response,
                $url,
                $args,
                $record
            );
        }
        return $response;
    }

}

$adfoin_woodpecker = ADFOIN_Woodpecker::get_instance();
add_action(
    'adfoin_add_js_fields',
    'adfoin_woodpecker_js_fields',
    10,
    1
);
function adfoin_woodpecker_js_fields(  $field_data  ) {
}

add_action(
    'adfoin_woodpecker_job_queue',
    'adfoin_woodpecker_job_queue',
    10,
    1
);
function adfoin_woodpecker_job_queue(  $data  ) {
    adfoin_woodpecker_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Woodpecker API
 */
function adfoin_woodpecker_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
    // Backward compatibility: If no credId, use the first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'woodpecker' );
        if ( !empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = ( isset( $first_credential['id'] ) ? $first_credential['id'] : '' );
        }
    }
    if ( $task == 'subscribe' ) {
        $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
        $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
        $subscriber_data = array(
            'prospects' => array(array(
                'email'      => trim( $email ),
                'first_name' => $first_name,
                'last_name'  => $last_name,
            )),
        );
        $woodpecker = ADFOIN_Woodpecker::get_instance();
        $woodpecker->set_credentials( $cred_id );
        $return = $woodpecker->api_request(
            'add_prospects_list',
            'POST',
            $subscriber_data,
            $record
        );
    }
}
