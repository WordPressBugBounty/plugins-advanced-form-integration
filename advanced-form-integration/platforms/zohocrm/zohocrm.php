<?php

class ADFOIN_ZohoCRM extends Advanced_Form_Integration_OAuth2 {
    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';

    const token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    public $data_center;

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;
        add_action( 'admin_init', array($this, 'auth_redirect') );
        add_filter(
            'adfoin_action_providers',
            array($this, 'adfoin_zohocrm_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_zohocrm_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_zohocrm_settings_view'),
            10,
            1
        );
        add_action(
            'admin_post_adfoin_save_zohocrm_keys',
            array($this, 'adfoin_save_zohocrm_keys'),
            10,
            0
        );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action(
            'wp_ajax_adfoin_get_zohocrm_users',
            array($this, 'get_users'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_get_zohocrm_modules',
            array($this, 'get_modules'),
            10,
            0
        );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_zohocrm_module_fields', array($this, 'get_fields') );
        add_action(
            'wp_ajax_adfoin_get_zohocrm_credentials',
            array($this, 'get_credentials'),
            10,
            0
        );
        add_filter(
            'adfoin_get_credentials',
            array($this, 'modify_credentials'),
            10,
            2
        );
        add_action(
            'wp_ajax_adfoin_save_zohocrm_credentials',
            array($this, 'save_credentials'),
            10,
            0
        );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohocrm', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code = ( isset( $params['code'] ) ? trim( $params['code'] ) : '' );
        $state = ( isset( $params['state'] ) ? trim( $params['state'] ) : '' );
        $this->cred_id = $state;
        if ( $code ) {
            $redirect_to = add_query_arg( [
                'service' => 'authorize',
                'action'  => 'adfoin_zohocrm_auth_redirect',
                'state'   => $state,
                'code'    => $code,
            ], admin_url( 'admin.php?page=advanced-form-integration' ) );
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }

    public function adfoin_zohocrm_actions( $actions ) {
        $actions['zohocrm'] = array(
            'title' => __( 'Zoho CRM', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Add new record', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    function get_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $all_credentials = adfoin_read_credentials( 'zohocrm' );
        wp_send_json_success( $all_credentials );
    }

    function modify_credentials( $credentials, $platform ) {
        if ( 'zohocrm' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_zohocrm_keys' ) );
            if ( isset( $option['data_center'] ) && isset( $option['client_id'] ) && isset( $option['client_secret'] ) ) {
                $credentials[] = array(
                    'id'           => '123456',
                    'title'        => __( 'Untitled', 'advanced-form-integration' ),
                    'dataCenter'   => $option['data_center'],
                    'clientId'     => $option['client_id'],
                    'clientSecret' => $option['client_secret'],
                    'accessToken'  => $option['access_token'],
                    'refreshToken' => $option['refresh_token'],
                );
            }
        }
        return $credentials;
    }

    /*
     * Save Zoho CRM credentials
     */
    function save_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = sanitize_text_field( $_POST['platform'] );
        if ( 'zohocrm' == $platform ) {
            $data = $_POST['data'];
            adfoin_save_credentials( $platform, $data );
        }
        wp_send_json_success();
    }

    public function adfoin_zohocrm_settings_tab( $providers ) {
        $providers['zohocrm'] = __( 'Zoho CRM', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_zohocrm_settings_view( $current_tab ) {
        if ( $current_tab != 'zohocrm' ) {
            return;
        }
        $redirect_uri = $this->get_redirect_uri();
        ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- main content -->
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        
                            <h2 class="hndle"><span><?php 
        esc_attr_e( 'Zoho Accounts', 'advanced-form-integration' );
        ?></span></h2>
                            <div class="inside">
                                <div id="zohocrm-auth">


                                    <table v-if="tableData.length > 0" class="wp-list-table widefat striped">
                                        <thead>
                                            <tr>
                                                <th><?php 
        _e( 'Title', 'advanced-form-integration' );
        ?></th>
                                                <th><?php 
        _e( 'Client ID', 'advanced-form-integration' );
        ?></th>
                                                <th><?php 
        _e( 'Client Secret', 'advanced-form-integration' );
        ?></th>
                                                <th><?php 
        _e( 'Status', 'advanced-form-integration' );
        ?></th>
                                                <th><?php 
        _e( 'Actions', 'advanced-form-integration' );
        ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, index) in tableData" :key="index">
                                                <td>{{ row.title }}</td>
                                                <td>{{ formatApiKey(row.clientId) }}</td>
                                                <td>{{ formatApiKey(row.clientSecret) }}</td>
                                                <td>{{row.accessToken ? 'Connected' : 'Not Connected'}}</td>
                                                <td>
                                                    <button @click="editRow(index)"><span class="dashicons dashicons-edit"></span></button>
                                                    <button @click="confirmDelete(index)"><span class="dashicons dashicons-trash"></span></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <br>
                                    <form @submit.prevent="addOrUpdateRow">
                                        <table class="form-table">
                                            <tr valign="top">
                                                <th scope="row"> <?php 
        _e( 'Title', 'advanced-form-integration' );
        ?></th>
                                                <td>
                                                    <input type="text" class="regular-text"v-model="rowData.title" placeholder="Add any title here" required />
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"> <?php 
        _e( 'Client ID', 'advanced-form-integration' );
        ?></th>
                                                <td>
                                                    <input type="text" class="regular-text"v-model="rowData.clientId" placeholder="Client ID" />
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"> <?php 
        _e( 'Client Secret', 'advanced-form-integration' );
        ?></th>
                                                <td>
                                                    <input type="text" class="regular-text"v-model="rowData.clientSecret" placeholder="Client Secret" required />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row"> <?php 
        _e( 'Data Center', 'advanced-form-integration' );
        ?></th>
                                                <td>
                                                    <select v-model="rowData.dataCenter">
                                                        <option value="com">zoho.com</option>
                                                        <option value="eu">zoho.eu</option>
                                                        <option value="in">zoho.in</option>
                                                        <option value="com.cn">zoho.com.cn</option>
                                                        <option value="com.au">zoho.com.au</option>
                                                        <option value="jp">zoho.jp</option>
                                                        <option value="com.cn">zoho.com.cn</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"> <?php 
        _e( 'Redirect URI', 'advanced-form-integration' );
        ?></th>
                                                <td>
                                                    <code><?php 
        echo $redirect_uri;
        ?></code>
                                                </td>
                                            </tr>
                                            
                                        </table>
                                        <button class="button button-primary" type="submit">{{ isEditing ? 'Update & Authorize' : 'Add & Authorize' }}</button>
                                        <button class="button" type="button" @click="cancelEdit" v-if="isEditing">Cancel</button>
                                    </form>


                                </div>
                            </div>
                            <!-- .inside -->
                        
                    </div>
                    <!-- .meta-box-sortables .ui-sortable -->
                </div>
                <!-- post-body-content -->

                <!-- sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="meta-box-sortables">
                            <h2 class="hndle"><span><?php 
        esc_attr_e( 'Instructions', 'advanced-form-integration' );
        ?></span></h2>
                            <div class="inside">
                            <div class="card" style="margin-top: 0px;">
                                <p>
                                    <ol>
                                        <li>Go to <a target='_blank' rel='noopener noreferrer' href='https://api-console.zoho.com/'>Zoho CRM API Console</a>.</li>
                                        <li>Click Add Client, Choose Server-based Applications.</li>
                                        <li>Insert a suitable Client Name.</li>
                                        <li>Insert URL of your website as Homepage URL.</li>
                                        <li>Copy the Redirect URI and paste in <b>Authorized Redirect URIs</b> input box.</li>
                                        <li>Click CREATE.</li>
                                        <li>You will receive Client ID and Client Secret copy and paste here.</li>
                                        <li>Click <b>Authorize</b> button.</li>
                                    <ol>
                                </p>
                            </div>
                            
                            </div>
                            <!-- .inside -->
                    </div>
                    <!-- .meta-box-sortables -->
                </div>
                <!-- #postbox-container-1 .postbox-container -->
            </div>
            <!-- #post-body .metabox-holder .columns-2 -->
            <br class="clear">
        </div>

        <?php 
    }

    public function adfoin_save_zohocrm_keys() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_zohocrm_settings' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $data_center = ( isset( $_POST['zoho_data_center'] ) ? sanitize_text_field( $_POST['zoho_data_center'] ) : 'com' );
        $client_id = ( isset( $_POST['adfoin_zohocrm_client_id'] ) ? sanitize_text_field( $_POST['adfoin_zohocrm_client_id'] ) : '' );
        $client_secret = ( isset( $_POST['adfoin_zohocrm_client_secret'] ) ? sanitize_text_field( $_POST['adfoin_zohocrm_client_secret'] ) : '' );
        if ( !$client_id || !$client_secret ) {
            $this->reset_data();
        } else {
            $this->data_center = trim( $data_center );
            $this->client_id = trim( $client_id );
            $this->client_secret = trim( $client_secret );
            $this->save_data();
            $this->authorize( 'ZohoCRM.Files.READ,ZohoCRM.Files.CREATE,ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.users.ALL,ZohoCRM.coql.READ,ZohoCRM.settings.tags.ALL,ZohoCRM.change_owner.CREATE' );
        }
        advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=zohocrm' );
    }

    protected function authorize( $scope = '' ) {
        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
        );
        if ( $scope ) {
            $data['scope'] = $scope;
        }
        $auth_endpoint = $this->authorization_endpoint;
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $auth_endpoint = str_replace( 'com', $this->data_center, $this->authorization_endpoint );
        }
        $endpoint = add_query_arg( $data, $auth_endpoint );
        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $authorization_code ) {
        $tok_endpoint = $this->token_endpoint;
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $tok_endpoint = str_replace( 'com', $this->data_center, $this->token_endpoint );
        }
        $endpoint = add_query_arg( array(
            'code'          => $authorization_code,
            'redirect_uri'  => $this->get_redirect_uri(),
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ), $tok_endpoint );
        $request = [
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ];
        $response = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 401 === $response_code ) {
            // Unauthorized
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            $this->access_token = $response_body['access_token'] ?? null;
            $this->refresh_token = $response_body['refresh_token'] ?? null;
        }
        $this->save_data();
        return $response;
    }

    protected function refresh_token() {
        $ref_endpoint = $this->refresh_token_endpoint;
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $ref_endpoint = str_replace( 'com', $this->data_center, $this->refresh_token_endpoint );
        }
        $endpoint = add_query_arg( array(
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
        ), $ref_endpoint );
        $request = [
            'headers' => array(
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ),
        ];
        $response = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if ( 401 == $response_code ) {
            // Unauthorized
            $this->access_token = null;
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

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'zohocrm' );
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials = array();
        $all_credentials = adfoin_read_credentials( 'zohocrm' );
        if ( is_array( $all_credentials ) ) {
            $credentials = $all_credentials[0];
            foreach ( $all_credentials as $single ) {
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                }
            }
        }
        return $credentials;
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='zohocrm-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'subscribe'">
                    <th scope='row'>
                        <?php 
        esc_attr_e( 'Map Fields', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope='row'>

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
        esc_attr_e( 'Zoho Account', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getUsers">
                        <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <?php 
        $this->get_credentials_list();
        ?>
                        </select>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Zoho User', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[userId]" v-model="fielddata.userId" @change="getModules">
                            <option value=''> <?php 
        _e( 'Select User...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.users' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': userLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Module', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[moduleId]" v-model="fielddata.moduleId" @change=getFields>
                            <option value=''> <?php 
        _e( 'Select Module...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.modules' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': moduleLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>


                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>

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
            printf( __( 'To unlock custom fields and tags consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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

    public function auth_redirect() {
        $action = ( isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '' );
        if ( 'adfoin_zohocrm_auth_redirect' == $action ) {
            $code = ( isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' );
            $state = ( isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '' );
            if ( $state ) {
                $this->cred_id = $state;
                //read zohocrm credentials by 'id'
                $zoho_credentials = adfoin_read_credentials( 'zohocrm' );
                foreach ( $zoho_credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->data_center = $value['dataCenter'];
                        $this->client_id = $value['clientId'];
                        $this->client_secret = $value['clientSecret'];
                    }
                }
            }
            if ( $code ) {
                $this->request_token( $code );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohocrm' ) );
            exit;
        }
    }

    protected function save_data() {
        $data = array(
            'data_center'   => $this->data_center,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'id'            => $this->cred_id,
        );
        $zoho_credentials = adfoin_read_credentials( 'zohocrm' );
        foreach ( $zoho_credentials as &$value ) {
            if ( $value['id'] == $this->cred_id ) {
                if ( $this->access_token ) {
                    $value['accessToken'] = $this->access_token;
                }
                if ( $this->refresh_token ) {
                    $value['refreshToken'] = $this->refresh_token;
                }
            }
        }
        adfoin_save_credentials( 'zohocrm', $zoho_credentials );
    }

    protected function reset_data() {
        $this->data_center = 'com';
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';
        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohocrm' );
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return;
        }
        $this->data_center = $credentials['dataCenter'];
        $this->client_id = $credentials['clientId'];
        $this->client_secret = $credentials['clientSecret'];
        $this->access_token = $credentials['accessToken'];
        $this->refresh_token = $credentials['refreshToken'];
        $this->cred_id = $credentials['id'];
    }

    public function zohocrm_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        $base_url = 'https://www.zohoapis.com/crm/v3/';
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $base_url = str_replace( 'com', $this->data_center, $base_url );
        }
        $url = $base_url . $endpoint;
        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );
        if ( 'POST' == $method || 'PUT' == $method ) {
            if ( $data ) {
                $args['body'] = json_encode( $data );
            }
        }
        $response = $this->remote_request( $url, $args, $record );
        return $response;
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;
        $request = wp_parse_args( $request, [] );
        $request['headers'] = array_merge( $request['headers'], array(
            'Authorization' => $this->get_http_authorization_header( 'bearer' ),
        ) );
        $response = wp_remote_request( esc_url_raw( $url ), $request );
        if ( 401 === wp_remote_retrieve_response_code( $response ) and !$refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $response = $this->remote_request( $url, $request, $record );
        }
        if ( $record ) {
            adfoin_add_to_log(
                $response,
                $url,
                $request,
                $record
            );
        }
        return $response;
    }

    /*
     * Get Owners
     */
    public function get_users() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohocrm_request( 'users?type=AdminUsers' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['users'] ) && is_array( $response_body['users'] ) ) {
            $users = array();
            foreach ( $response_body['users'] as $value ) {
                $users[$value['id']] = $value['full_name'];
            }
            wp_send_json_success( $users );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Modules
     */
    public function get_modules() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohocrm_request( 'settings/modules' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['modules'] ) && is_array( $response_body['modules'] ) ) {
            $skip_list = array(
                'Quotes',
                'Sales_Orders',
                'Purchase_Orders',
                'Invoices',
                'Projects',
                'Notes'
            );
            $modules = array();
            foreach ( $response_body['modules'] as $single ) {
                if ( in_array( $single['api_name'], $skip_list ) ) {
                    continue;
                }
                if ( isset( $single['editable'] ) && true == $single['editable'] && 'Associated_Products' != $single['api_name'] ) {
                    $modules[$single['api_name']] = $single['plural_label'];
                }
            }
            wp_send_json_success( $modules );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Module Fields
     */
    function get_fields() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $final_data = array();
        $module = ( isset( $_POST['module'] ) ? sanitize_text_field( $_POST['module'] ) : '' );
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        if ( $module ) {
            $response = $this->zohocrm_request( "settings/fields?module={$module}&type=all" );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $body['fields'] ) && is_array( $body['fields'] ) ) {
                $suppression_list = array(
                    'Created_By',
                    'Modified_By',
                    'Created_Time',
                    'Modified_Time',
                    'Layout',
                    'Tag',
                    'Recurring_Activity',
                    'BEST_TIME',
                    'What_Id',
                    'Record_Image'
                );
                foreach ( $body['fields'] as $field ) {
                    $helptext = '';
                    $data_type = $field['data_type'];
                    $api_name = $field['api_name'];
                    $display_label = $field['display_label'];
                    if ( isset( $field['field_read_only'] ) && $field['field_read_only'] == true ) {
                        continue;
                    }
                    if ( in_array( $api_name, $suppression_list ) ) {
                        continue;
                    }
                    if ( $field['custom_field'] == true ) {
                        continue;
                    }
                    if ( 'Contact_Name' == $api_name || 'Who_Id' == $api_name ) {
                        $display_label = 'Contact Email';
                    }
                    if ( 'bigint' == $data_type && 'Participants' == $api_name ) {
                        $helptext = 'Example: lead--john@example.com,contact--david@example.com';
                    }
                    if ( 'multiselectpicklist' == $data_type && 'Tax' == $api_name ) {
                        $items = array();
                        if ( isset( $field['pick_list_values'] ) && is_array( $field['pick_list_values'] ) ) {
                            foreach ( $field['pick_list_values'] as $pick ) {
                                $items[] = $pick['display_value'] . ': ' . $pick['id'];
                            }
                        }
                        $helptext = implode( ', ', $items );
                    }
                    if ( 'picklist' == $data_type && is_array( $field['pick_list_values'] ) ) {
                        $picklist = wp_list_pluck( $field['pick_list_values'], 'actual_value' );
                        $helptext = implode( ' | ', $picklist );
                    }
                    array_push( $final_data, array(
                        'key'         => $data_type . '__' . $api_name,
                        'value'       => $display_label,
                        'description' => $helptext,
                    ) );
                }
                if ( 'Tasks' == $module || 'Events' == $module ) {
                    array_push( $final_data, array(
                        'key'         => 'text__$se_module',
                        'value'       => 'Module Name',
                        'description' => 'Accounts | Deals',
                    ) );
                    array_push( $final_data, array(
                        'key'         => 'text__What_Id',
                        'value'       => 'Module Record',
                        'description' => 'Account Name | Deal Name',
                    ) );
                }
            }
        }
        wp_send_json_success( $final_data );
    }

    public function search_record(
        $module,
        $search_key,
        $search_value,
        $record
    ) {
        sleep( 5 );
        $result = array(
            'id'   => '',
            'data' => array(),
        );
        $body = array(
            'select_query' => "select {$search_key}, id, Tag from {$module} where {$search_key} = '{$search_value}'",
        );
        $response = $this->zohocrm_request(
            'coql',
            'POST',
            $body,
            $record
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['data'], $body['data'][0], $body['data'][0]['id'] ) ) {
            $result['id'] = $body['data'][0]['id'];
            $result['data'] = $body['data'][0];
        }
        return $result;
    }

    public function is_duplicate( $module, $holder, $record ) {
        if ( 'Leads' == $module ) {
            $lead_id = $this->search_record(
                'Leads',
                'Email',
                $holder['Email'],
                $record
            )['id'];
            if ( $lead_id ) {
                return $lead_id;
            }
        }
        if ( 'Contacts' == $module ) {
            $contact_id = $this->search_record(
                'Contacts',
                'Email',
                $holder['Email'],
                $record
            )['id'];
            if ( $contact_id ) {
                return $contact_id;
            }
        }
        if ( 'Accounts' == $module ) {
            $account_id = $this->search_record(
                'Accounts',
                'Account_Name',
                $holder['Account_Name'],
                $record
            )['id'];
            if ( $account_id ) {
                return $account_id;
            }
        }
        // if( 'Leads' == $module ) {
        //     $lead_id = $this->search_record( 'Leads', 'Email', $holder['Email'], $record )['id];
        //     if( $lead_id ) {
        //         return $lead_id;
        //     }
        // }
        // if( 'Leads' == $module ) {
        //     $lead_id = $this->search_record( 'Leads', 'Email', $holder['Email'], $record )['id];
        //     if( $lead_id ) {
        //         return $lead_id;
        //     }
        // }
        return false;
    }

}

$zohocrm = ADFOIN_ZohoCRM::get_instance();
add_action(
    'adfoin_zohocrm_job_queue',
    'adfoin_zohocrm_job_queue',
    10,
    1
);
function adfoin_zohocrm_job_queue(  $data  ) {
    adfoin_zohocrm_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zoho API
 */
function adfoin_zohocrm_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $owner = ( isset( $data['userId'] ) ? $data['userId'] : '' );
    $module = ( isset( $data['moduleId'] ) ? $data['moduleId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    unset($data['credId']);
    unset($data['userId']);
    unset($data['moduleId']);
    if ( $task == 'subscribe' ) {
        $zohocrm = ADFOIN_ZohoCRM::get_instance();
        $holder = array();
        $account_id = '';
        $contact_id = '';
        $vendor_id = '';
        $campaign_id = '';
        $task_module = '';
        $account_lookups = array('Parent_Account', 'Account_Name');
        $contact_lookups = array('Contact_Name', 'Who_Id', 'Related_To');
        $campaign_lookups = array('Parent_Campaign', 'Campaign_Source');
        $zohocrm->set_credentials( $cred_id );
        foreach ( $data as $key => $value ) {
            list( $data_type, $original_key ) = explode( '__', $key );
            $value = adfoin_get_parsed_values( $value, $posted_data );
            if ( 'datetime' == $data_type && $value ) {
                // if( 'Start_DateTime' == $original_key || 'End_DateTime' == $original_key ) {
                $timezone = wp_timezone();
                $date = date_create( $value, $timezone );
                if ( $date ) {
                    $value = date_format( $date, 'c' );
                }
                // }
            }
            if ( 'date' == $data_type && $value ) {
                $date = date_create( $value );
                if ( $date ) {
                    $value = date_format( $date, 'Y-m-d' );
                }
            }
            if ( 'multiselectpicklist' == $data_type && $value ) {
                if ( 'Tax' == $original_key ) {
                    $formatted_tax_ids = array();
                    $tax_ids = explode( ',', $value );
                    foreach ( $tax_ids as $tax_id ) {
                        array_push( $formatted_tax_ids, array(
                            'id' => $tax_id,
                        ) );
                    }
                    $value = $formatted_tax_ids;
                }
            }
            if ( 'bigint' == $data_type && $value ) {
                if ( 'Participants' == $original_key ) {
                    $participants = array();
                    $raw_participants = explode( ',', $value );
                    foreach ( $raw_participants as $single ) {
                        list( $type, $email ) = explode( '--', $single );
                        if ( 'lead' == $type ) {
                            $participant_id = $zohocrm->search_record(
                                'Leads',
                                'Email',
                                $email,
                                $record
                            )['id'];
                            if ( $participant_id ) {
                                array_push( $participants, array(
                                    'type'        => 'lead',
                                    'participant' => $participant_id,
                                ) );
                            }
                        }
                        if ( 'contact' == $type ) {
                            $participant_id = $zohocrm->search_record(
                                'Contacts',
                                'Email',
                                $email,
                                $record
                            )['id'];
                            if ( $participant_id ) {
                                array_push( $participants, array(
                                    'type'        => 'contact',
                                    'participant' => $participant_id,
                                ) );
                            }
                        }
                    }
                    $value = $participants;
                }
            }
            if ( 'lookup' == $data_type && $value ) {
                if ( in_array( $original_key, $account_lookups ) ) {
                    $account_id = $zohocrm->search_record(
                        'Accounts',
                        'Account_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $account_id ) {
                        $value = $account_id;
                    }
                }
                if ( in_array( $original_key, $contact_lookups ) ) {
                    $contact_id = $zohocrm->search_record(
                        'Contacts',
                        'Email',
                        $value,
                        $record
                    )['id'];
                    if ( $contact_id ) {
                        $value = $contact_id;
                    }
                }
                if ( in_array( $original_key, $campaign_lookups ) ) {
                    $campaign_id = $zohocrm->search_record(
                        'Campaigns',
                        'Campaign_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $campaign_id ) {
                        $value = $campaign_id;
                    }
                }
                if ( 'Vendor_Name' == $original_key ) {
                    $vendor_id = $zohocrm->search_record(
                        'Vendors',
                        'Vendor_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $vendor_id ) {
                        $value = $vendor_id;
                    }
                }
                if ( 'Product_Name' == $original_key ) {
                    $product_id = $zohocrm->search_record(
                        'Products',
                        'Product_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $product_id ) {
                        $value = $product_id;
                    }
                }
                if ( 'Deal_Name' == $original_key ) {
                    $deal_id = $zohocrm->search_record(
                        'Deals',
                        'Deal_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $deal_id ) {
                        $value = $deal_id;
                    }
                }
                if ( 'Reporting_To' == $original_key && $account_id ) {
                    $contacts_response = $zohocrm->zohocrm_request( 'Accounts/' . $account_id . '/Contacts?fields=id,First_Name,Last_Name' );
                    $contacts_body = json_decode( wp_remote_retrieve_body( $contacts_response ), true );
                    if ( isset( $contacts_body['data'] ) && is_array( $contacts_body['data'] ) ) {
                        foreach ( $contacts_body['data'] as $contact ) {
                            $contact_name = $contact['First_Name'] . ' ' . $contact['Last_Name'];
                            $contact_id = ( $contact_name == $value ? $contact['id'] : '' );
                            if ( $contact_id ) {
                                $value = $contact_id;
                            }
                        }
                    }
                }
            }
            if ( 'boolean' == $data_type ) {
                if ( strtolower( $value ) == 'true' ) {
                    $value = true;
                } else {
                    $value = false;
                }
            }
            if ( '$se_module' == $original_key ) {
                $task_module = $value;
            }
            if ( 'What_Id' == $original_key ) {
                if ( 'Accounts' == $task_module ) {
                    $account_id = $zohocrm->search_record(
                        'Accounts',
                        'Account_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $account_id ) {
                        $value = $account_id;
                    }
                }
            }
            $holder[$original_key] = $value;
        }
        if ( $owner ) {
            $holder['Owner'] = "{$owner}";
        }
        if ( $module && $holder ) {
            $request_data = array(
                'data' => array(array_filter( $holder )),
            );
            $return = $zohocrm->zohocrm_request(
                $module . '/upsert',
                'POST',
                $request_data,
                $record
            );
            // $id           = $zohocrm->is_duplicate( $module, $holder, $record );
            // if( $id ) {
            //     $return = $zohocrm->zohocrm_request( $module . '/' . $id, 'PUT', $request_data, $record );
            // } else {
            //     $return = $zohocrm->zohocrm_request( $module, 'POST', $request_data, $record );
            // }
        }
    }
    return;
}
