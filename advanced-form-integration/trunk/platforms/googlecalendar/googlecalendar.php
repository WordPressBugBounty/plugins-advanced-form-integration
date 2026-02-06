<?php

class ADFOIN_GoogleCalendar extends Advanced_Form_Integration_OAuth2 {

    const service_name           = 'googlecalendar';
    const authorization_endpoint = 'https://accounts.google.com/o/oauth2/auth';
    const token_endpoint         = 'https://www.googleapis.com/oauth2/v3/token';

    private static $instance;
    protected $client_id          = '';
    protected $client_secret      = '';
    protected $google_access_code = '';
    protected $calendar_lists     = array();
    protected $token_expires      = 0;
    protected $cred_id            = '';

    public static function get_instance() {

        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {
        $this->token_endpoint         = self::token_endpoint;
        $this->authorization_endpoint = self::authorization_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'adfoin_googlecalendar_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'adfoin_googlecalendar_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'adfoin_googlecalendar_settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_googlecalendar_list', array( $this, 'get_calendar_list' ), 10, 0 );
        add_action( "rest_api_init", array( $this, "create_webhook_route" ) );
        add_action( 'wp_ajax_adfoin_get_googlecalendar_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_googlecalendar_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_googlecalendar_keys' ) );

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
        if ( isset( $option['token_expires'] ) ) {
            $this->token_expires = $option['token_expires'];
        }
        if ( $this->is_active() && isset( $option['calendar_lists'] ) ) {
            $this->calendar_lists = $option['calendar_lists'];
        }
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/googlecalendar',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_webhook_data' ),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        if ( $code ) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                $this->cred_id = $state;
                $credentials = adfoin_read_credentials( 'googlecalendar' );
                
                foreach ( $credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                        $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
                    }
                }

                $response = $this->request_token( $code );
                
                $success = false;
                $message = 'Unknown error';
                
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['access_token'] ) ) {
                        $success = true;
                        $message = 'Connected successfully!';
                    } else {
                        $message = isset( $body['error'] ) ? $body['error'] : 'Token exchange failed.';
                    }
                } else {
                    $message = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error';
                }

                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
                exit;
            }
            
            // Legacy flow - redirect to admin
            $redirect_to = add_query_arg(
                [
                    'service' => 'authorize',
                    'action'  => 'adfoin_googlecalendar_auth_redirect',
                    'code'    => $code,
                ],
                admin_url( 'admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function auth_redirect() {

        $action = isset( $_GET['action'] ) ? sanitize_text_field( trim( $_GET['action'] ) ) : '';

        if ( 'adfoin_googlecalendar_auth_redirect' == $action ) {
            $code  = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
            $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

            if ( $code ) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->cred_id = $state;
                    $credentials = adfoin_read_credentials( 'googlecalendar' );
                    
                    foreach ( $credentials as $value ) {
                        if ( $value['id'] == $state ) {
                            $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                            $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
                        }
                    }
                }
                
                $this->request_token( $code );
                
                // For popup flow
                if ( $state ) {
                    require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                    ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
                    exit;
                }
            }

            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=googlecalendar' ) );

            exit();
        }
    }

    public function adfoin_googlecalendar_actions( $actions ) {

        $actions['googlecalendar'] = array(
            'title' => __( 'Google Calendar', 'advanced-form-integration' ),
            'tasks' => array(
                'addEvent'   => __( 'Add New Event', 'advanced-form-integration' )
            )
        );

        return $actions;
    }

    public function adfoin_googlecalendar_settings_tab( $providers ) {
        $providers['googlecalendar'] = __( 'Google Calendar', 'advanced-form-integration' );

        return $providers;
    }

    public function adfoin_googlecalendar_settings_view( $current_tab ) {
        if( $current_tab != 'googlecalendar' ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
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
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
        );

        // Instructions
        $domain = parse_url( get_site_url() );
        $host   = $domain['host'];

        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s and create a New Project.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://console.cloud.google.com/project">Google Developer Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Go to Library and search for Google Calendar API, open it and click ENABLE.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . sprintf( __( 'Go to OAuth consent screen, select External, click Create. Enter %s in Authorized domains. Set publishing status to in production.', 'advanced-form-integration' ), '<code>' . esc_html( $host ) . '</code>' ) . '</li>';
        $instructions .= '<li>' . __( 'Go to Credentials, click CREATE CREDENTIALS, select OAuth client ID, select Web application.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Authorized redirect URIs:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Check this video instruction: ', 'advanced-form-integration' ) . '<a target="_blank" rel="noopener noreferrer" href="https://youtu.be/omYFbXN0ECw">https://youtu.be/omYFbXN0ECw</a></li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Google Calendar', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'googlecalendar', 'Google Calendar', $fields, $instructions, $config );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $all_credentials = adfoin_read_credentials( 'googlecalendar' );
        wp_send_json_success( $all_credentials );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'googlecalendar' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_googlecalendar_keys' ) );

            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && ! empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
                    'token_expires' => isset( $option['token_expires'] ) ? $option['token_expires'] : 0,
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'googlecalendar';
        $credentials = adfoin_read_credentials( $platform );
        
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && strpos( $credentials[ $index ]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_googlecalendar_keys' );
                }
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( 'Invalid index' );
        }

        // Handle Save/Update
        $id            = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '';

        if ( empty( $id ) ) {
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
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials haven't changed
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id &&
                     isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['token_expires'] = isset( $cred['token_expires'] ) ? $cred['token_expires'] : 0;
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Generate Auth URL
        $scope    = 'https://www.googleapis.com/auth/calendar';
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

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    protected function request_token( $authorization_code ) {

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

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                $this->refresh_token = null;
            }
        }

        $this->save_data();

        return $response;
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="googlecalendar-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'addEvent'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Google Calendar Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getCalendars">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in fielddata.credId" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                        <br/>
                        <a id="googlecalendar-auth-btn" target="_blank" href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=googlecalendar' ); ?>"><?php _e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Documentation', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <?php _e( 'Need help? See <a target="_blank" rel="noopener noreferrer" href="https://advancedformintegration.com/docs/receiver-platforms/google-calendar/">Google Calendar Documentation</a>.', 'advanced-form-integration' ); ?>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Calendar', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[calendarId]" v-model="fielddata.calendarId" required="required">
                            <option value=""> <?php _e( 'Select Calendar...', 'advanced-form-integration' ); ?> </option>
                            <option v-for="(item, index) in fielddata.calendarList" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent' && trigger.formProviderId == 'woocommerce'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Single task for each WooCommerce order item', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="checkbox" name="fieldData[wcMultipleRow]" value="true" v-model="fielddata.wcMultipleRow">
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Title', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="text" class="regular-text" v-model="fielddata.title" name="fieldData[title]" required="required">
                        <select @change="updateFieldValue('title')" v-model="title">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Title of the event.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Description', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <textarea class="regular-text" v-model="fielddata.description" name="fieldData[description]" rows="8"></textarea>
                        <select @change="updateFieldValue('description')" v-model="description">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Description of the event. Can contain HTML.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'All Day Event', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="checkbox" name="fieldData[allDayEvent]" value="true" v-model="fielddata.allDayEvent">
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Start Date Time', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="text" class="regular-text" v-model="fielddata.start" name="fieldData[start]" required="required">
                        <select @change="updateFieldValue('start')" v-model="start">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Required, use a valid Date or DateTime format', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'End Date Time', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="text" class="regular-text" v-model="fielddata.end" name="fieldData[end]">
                        <select @change="updateFieldValue('end')" v-model="end">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Required, use a valid Date or DateTime format', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Timezone', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="text" class="regular-text" v-model="fielddata.timezone" name="fieldData[timezone]">
                        <select @change="updateFieldValue('timezone')" v-model="timezone">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Optional, overrides default WordPress timezone. (Formatted as an <a target="_blank" rel="noopener noreferrer" href="https://en.wikipedia.org/wiki/List_of_tz_database_time_zones">IANA Time Zone</a> Database name, e.g. "Europe/Zurich".)', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Location', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="text" class="regular-text" v-model="fielddata.location" name="fieldData[location]">
                        <select @change="updateFieldValue('location')" v-model="location">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Geographic location of the event as free-form text. Optional.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'addEvent'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Attendees', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="text" class="regular-text" v-model="fielddata.attendees" name="fieldData[attendees]">
                        <select @change="updateFieldValue('attendees')" v-model="attendees">
                            <option value=""><?php _e( 'Form Fields...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                        </select>
                        <p class="description"><?php _e( 'Accepts attendee\'s email. Use comma for multiple attendees.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    /**
     * Load credentials for a specific account
     */
    protected function load_credentials( $cred_id ) {
        if ( empty( $cred_id ) ) {
            return false;
        }

        // Handle legacy credential ID
        if ( strpos( $cred_id, 'legacy_' ) === 0 ) {
            $this->load_legacy_credentials();
            return true;
        }

        // Load from OAuth Manager
        $credentials = adfoin_read_credentials( 'googlecalendar' );
        
        foreach ( $credentials as $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] == $cred_id ) {
                $this->cred_id       = $cred_id;
                $this->client_id     = isset( $credential['client_id'] ) ? $credential['client_id'] : '';
                $this->client_secret = isset( $credential['client_secret'] ) ? $credential['client_secret'] : '';
                $this->access_token  = isset( $credential['access_token'] ) ? $credential['access_token'] : '';
                $this->refresh_token = isset( $credential['refresh_token'] ) ? $credential['refresh_token'] : '';
                $this->token_expires = isset( $credential['token_expires'] ) ? $credential['token_expires'] : 0;
                return true;
            }
        }

        return false;
    }

    protected function save_data() {
        // If using OAuth Manager (has cred_id), save to credentials
        if ( ! empty( $this->cred_id ) && strpos( $this->cred_id, 'legacy_' ) !== 0 ) {
            $credentials = adfoin_read_credentials( 'googlecalendar' );
            
            foreach ( $credentials as &$credential ) {
                if ( isset( $credential['id'] ) && $credential['id'] == $this->cred_id ) {
                    $credential['access_token']  = $this->access_token;
                    $credential['refresh_token'] = $this->refresh_token;
                    $credential['token_expires'] = $this->token_expires;
                    break;
                }
            }
            
            adfoin_save_credentials( 'googlecalendar', $credentials );
            return;
        }

        // Legacy save method for backward compatibility
        $data = (array) maybe_unserialize( get_option( 'adfoin_googlecalendar_keys' ) );

        $option = array_merge(
            $data,
            array(
                'client_id'      => $this->client_id,
                'client_secret'  => $this->client_secret,
                'access_token'   => $this->access_token,
                'refresh_token'  => $this->refresh_token,
                'calendar_lists' => $this->calendar_lists
            )
        );

        update_option( 'adfoin_googlecalendar_keys', maybe_serialize( $option ) );
    }

    protected function reset_data() {

        $this->client_id          = '';
        $this->client_secret      = '';
        $this->google_access_code = '';
        $this->access_token       = '';
        $this->refresh_token      = '';
        $this->calendar_lists     = array();

        $this->save_data();
    }

    protected function get_redirect_uri() {

        return site_url( '/wp-json/advancedformintegration/googlecalendar' );
    }

    public function get_calendar_list() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
        
        if ( empty( $cred_id ) ) {
            wp_send_json_error( __( 'No account selected', 'advanced-form-integration' ) );
        }

        // Load credentials for the selected account
        $this->load_credentials( $cred_id );

        if ( empty( $this->access_token ) ) {
            wp_send_json_error( __( 'Account not connected', 'advanced-form-integration' ) );
        }

        $endpoint = "https://www.googleapis.com/calendar/v3/users/me/calendarList";

        $request = array(
            'method'  => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token
            ),
        );

        $response      = $this->remote_request( $endpoint, $request );
        $response_body = wp_remote_retrieve_body( $response );

        if ( empty( $response_body ) ) {
            wp_send_json_error( __( 'Failed to fetch calendars', 'advanced-form-integration' ) );
        }

        $body = json_decode( $response_body, true );

        if ( ! isset( $body['items'] ) ) {
            wp_send_json_error( __( 'No calendars found', 'advanced-form-integration' ) );
        }

        $calendar_list = $body['items'];
        $list          = wp_list_pluck( $calendar_list, 'summary', 'id' );

        wp_send_json_success( $list );
    }

    protected function remote_request( $url, $request = array() ) {

        if( !$this->check_token_expiry( $this->access_token ) ) {
            $this->refresh_token();
        }

        $request = wp_parse_args( $request, array() );

        $request['headers'] = array_merge(
            $request['headers'],
            array(
                'Authorization' => $this->get_http_authorization_header( 'bearer' ),
            )
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        return $response;
    }

    public function check_token_expiry( $token ='' ) {
        $response = array();

        if ( empty( $token ) ) {
            return;
        }

        $return = wp_remote_get('https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $token );

        if( is_wp_error( $return ) ) {
            return false;
        }

        $body = json_decode( $return['body'], true );

        if ( $return['response']['code'] == 200 ) {
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

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            }
        }

        $this->save_data();

        return $response;
    }

    public function create_event( $calendar_id, $calendar_data, $record, $cred_id = '' ) {

        if ( !$calendar_id || empty( $calendar_data ) ) {
            return false;
        }

        // Load credentials if cred_id is provided
        if ( ! empty( $cred_id ) ) {
            $this->load_credentials( $cred_id );
        }

        if( !$this->check_token_expiry( $this->access_token ) ) {
            $this->refresh_token();
        }

        $endpoint = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events";

        $request = array(
            'method'  => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token
            ),
            'body' => json_encode( $calendar_data )
        );

        $response = $this->remote_request( $endpoint, $request );

        adfoin_add_to_log( $response, $endpoint, $request, $record );
    }
}

$googlecalendar = ADFOIN_GoogleCalendar::get_instance();

add_action( 'adfoin_googlecalendar_job_queue', 'adfoin_googlecalendar_job_queue', 10, 1 );

function adfoin_googlecalendar_job_queue( $data ) {
    adfoin_googlecalendar_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Google Calendar API
 */
function adfoin_googlecalendar_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = isset( $record_data['action_data']['credId'] ) ? $record_data['action_data']['credId'] : '';

    // Default to legacy credential if no credId is set (backward compatibility)
    if ( empty( $cred_id ) ) {
        $cred_id = 'legacy_123456';
    }

    if( $task == 'addEvent' ) {
        $calendar_id   = isset( $data['calendarId'] ) ? $data['calendarId'] : '';
        $all_day_event = isset( $data['allDayEvent'] ) ? $data['allDayEvent'] : '';
        $summary       = empty( $data['title'] ) ? '' : adfoin_get_parsed_values( $data['title'], $posted_data );
        $description   = empty( $data['description'] ) ? '' : adfoin_get_parsed_values( $data['description'], $posted_data );
        $start         = empty( $data['start'] ) ? '' : adfoin_get_parsed_values( $data['start'], $posted_data );
        $end           = empty( $data['end'] ) ? '' : adfoin_get_parsed_values( $data['end'], $posted_data );
        $timezone      = empty( $data['timezone'] ) ? '' : adfoin_get_parsed_values( $data['timezone'], $posted_data );
        $location      = empty( $data['location'] ) ? '' : adfoin_get_parsed_values( $data['location'], $posted_data );
        $attendees     = empty( $data['attendees'] ) ? '' : adfoin_get_parsed_values( $data['attendees'], $posted_data );
        $startdatetime = '';
        $enddatetime   = '';

        $calendar_data = array(
            'summary'     => $summary,
            'description' => $description,
            'start'       => array()
        );

        if( 'true' == $all_day_event ) {
            $startdatetime = adfoin_googlecalendar_get_formatted_datetime( $start, $timezone, 'Y-m-d' );
            $enddatetime   = adfoin_googlecalendar_get_formatted_datetime( $end, $timezone, 'Y-m-d' );

            if( $startdatetime ) {
                $calendar_data['start']['date'] = $startdatetime;
            }

            if( $enddatetime ) {
                $calendar_data['end']['date'] = $enddatetime;
            }
        } else {
            $startdatetime = adfoin_googlecalendar_get_formatted_datetime( $start, $timezone );
            $enddatetime   = adfoin_googlecalendar_get_formatted_datetime( $end, $timezone );

            if( $startdatetime ) {
                $calendar_data['start']['dateTime'] = $startdatetime;
            }

            if( $enddatetime ) {
                $calendar_data['end']['dateTime'] = $enddatetime;
            }
        }

        if( $timezone ) {
            if( isset( $calendar_data['start'] ) ) {
                $calendar_data['start']['timezone'] = $timezone;
            }

            if( isset( $calendar_data['end'] ) ) {
                $calendar_data['end']['timezone'] = $timezone;
            }
        }

        if( $location ) {
            $calendar_data['location'] = $location;
        }

        if( $attendees ) {
            $attendees = explode( ',', $attendees );
            $formatted = array();

            if( is_array( $attendees ) ) {
                foreach( $attendees as $attendee ) {
                    array_push( $formatted, array( 'email' => trim( $attendee ) ) );
                }
            }

            if( !empty( $formatted ) ) {
                $calendar_data['attendees'] = $formatted;
            }
        }

        if ( $calendar_id ) {
            $googlecalendar = ADFOIN_GoogleCalendar::get_instance();
            $googlecalendar->create_event( $calendar_id, $calendar_data, $record, $cred_id );
        }
    }

    return;
}

function adfoin_googlecalendar_get_formatted_datetime( $data, $timezone, $format = '' ) {
    if( false === strtotime( $data ) ) {
        return false;
    }

    if( empty( $timezone ) ) {
        $timezone = wp_timezone();
    } else {
        $timezone = new DateTimeZone( $timezone );
    }

    $dt                 = date_create( $data, $timezone );
    $formatted_datetime = '';

    if( 'Y-m-d' == $format ) {
        $formatted_datetime = date_format( $dt, 'Y-m-d' );
    } else {
        $formatted_datetime = date_format( $dt, DateTime::RFC3339 );
    }

    return $formatted_datetime;
}