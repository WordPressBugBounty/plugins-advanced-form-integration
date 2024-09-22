<?php

class ADFOIN_ZohoMA extends Advanced_Form_Integration_OAuth2 {
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
            array($this, 'adfoin_zohoma_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_zohoma_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_zohoma_settings_view'),
            10,
            1
        );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action(
            'wp_ajax_adfoin_get_zohoma_lists',
            array($this, 'get_lists'),
            10,
            0
        );
        // add_action( 'wp_ajax_adfoin_get_zohoma_owners', array( $this, 'get_owners' ), 10, 0 );
        // add_action( 'wp_ajax_adfoin_get_zohoma_departments', array( $this, 'get_departments' ), 10, 0 );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_zohoma_fields', array($this, 'get_fields') );
        add_action(
            'wp_ajax_adfoin_get_zohoma_credentials',
            array($this, 'get_credentials'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_save_zohoma_credentials',
            array($this, 'save_credentials'),
            10,
            0
        );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohoma', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code = ( isset( $params['code'] ) ? trim( $params['code'] ) : '' );
        $state = ( isset( $params['state'] ) ? trim( $params['state'] ) : '' );
        if ( $code ) {
            $redirect_to = add_query_arg( [
                'service' => 'authorize',
                'action'  => 'adfoin_zohoma_auth_redirect',
                'state'   => $state,
                'code'    => $code,
            ], admin_url( 'admin.php?page=advanced-form-integration' ) );
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }

    public function adfoin_zohoma_actions( $actions ) {
        $actions['zohoma'] = array(
            'title' => __( 'Zoho Marketing Automation', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Add New Lead', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    function get_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $all_credentials = adfoin_read_credentials( 'zohoma' );
        wp_send_json_success( $all_credentials );
    }

    /*
     * Save Zoho Marketing Automation credentials
     */
    function save_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = sanitize_text_field( $_POST['platform'] );
        if ( 'zohoma' == $platform ) {
            $data = $_POST['data'];
            adfoin_save_credentials( $platform, $data );
        }
        wp_send_json_success();
    }

    public function adfoin_zohoma_settings_tab( $providers ) {
        $providers['zohoma'] = __( 'Zoho Marketing Automation', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_zohoma_settings_view( $current_tab ) {
        if ( $current_tab != 'zohoma' ) {
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
                                <div id="zohoma-auth">


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
                                        <li>Go to <a target='_blank' rel='noopener noreferrer' href='https://api-console.zoho.com/'>Zoho API Console</a>.</li>
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
            'code'         => $authorization_code,
            'redirect_uri' => urlencode( $this->get_redirect_uri() ),
            'grant_type'   => 'authorization_code',
        ), $tok_endpoint );
        $request = [
            'headers' => [
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ],
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
            } else {
                $this->refresh_token = null;
            }
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
        $credentials = adfoin_read_credentials( 'zohoma' );
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials = array();
        $all_credentials = adfoin_read_credentials( 'zohoma' );
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
        <script type='text/template' id='zohoma-action-template'>
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
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
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
        esc_attr_e( 'List', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=''> <?php 
        _e( 'Select List...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.lists' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

    public function auth_redirect() {
        $action = ( isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '' );
        if ( 'adfoin_zohoma_auth_redirect' == $action ) {
            $code = ( isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' );
            $state = ( isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '' );
            if ( $state ) {
                $this->state = $state;
                //read zohoma credentials by 'id'
                $zoho_credentials = adfoin_read_credentials( 'zohoma' );
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
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohoma' ) );
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
            'id'            => $this->state,
        );
        $zoho_credentials = adfoin_read_credentials( 'zohoma' );
        foreach ( $zoho_credentials as &$value ) {
            if ( $value['id'] == $this->state ) {
                if ( $this->access_token ) {
                    $value['accessToken'] = $this->access_token;
                }
                if ( $this->refresh_token ) {
                    $value['refreshToken'] = $this->refresh_token;
                }
            }
        }
        adfoin_save_credentials( 'zohoma', $zoho_credentials );
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
        return site_url( '/wp-json/advancedformintegration/zohoma' );
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
    }

    public function zohoma_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        $base_url = 'https://marketinghub.zoho.com/api/v1/';
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $base_url = str_replace( 'com', $this->data_center, $base_url );
        }
        $url = $base_url . $endpoint;
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        );
        if ( 'POST' == $method || 'PUT' == $method ) {
            $url = add_query_arg( $data, $url );
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
            $response = $this->remote_request( $url, $request );
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
     * Get Organizations
     */
    public function get_lists() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohoma_request( 'getmailinglists?resfmt=JSON&range=100' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( isset( $response_body['list_of_details'] ) && is_array( $response_body['list_of_details'] ) ) {
            $lists = array();
            foreach ( $response_body['list_of_details'] as $value ) {
                $lists[$value['listkey']] = $value['listname'];
            }
            wp_send_json_success( $lists );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Fields
     */
    function get_fields() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohoma_request( 'lead/allfields?type=json' );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $lead_fields = array();
        if ( isset( $body['response'], $body['response']['fieldnames'], $body['response']['fieldnames']['fieldname'] ) && is_array( $body['response']['fieldnames']['fieldname'] ) ) {
            foreach ( $body['response']['fieldnames']['fieldname'] as $field ) {
                if ( in_array( $field['FIELD_NAME'], array(
                    'do_not_call',
                    'do_not_call_reason',
                    'contact_owner',
                    'preferred_language',
                    'total_sales_activity'
                ) ) ) {
                    continue;
                }
                $description = '';
                if ( 'standard' == $field['TYPE'] ) {
                    if ( isset( $field['values'] ) && !empty( $field['values'] ) ) {
                        $description = $field['values'];
                    }
                    array_push( $lead_fields, array(
                        'key'         => $field['UITYPE'] . '__' . $field['DISPLAY_NAME'],
                        'value'       => $field['DISPLAY_NAME'],
                        'description' => $description,
                    ) );
                }
            }
        }
        wp_send_json_success( $lead_fields );
    }

    public function create_lead( $lead_data, $record ) {
        $response = $this->zohoma_request(
            'json/listsubscribe',
            'POST',
            $lead_data,
            $record
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

}

$zohoma = ADFOIN_ZohoMA::get_instance();
add_action(
    'adfoin_zohoma_job_queue',
    'adfoin_zohoma_job_queue',
    10,
    1
);
function adfoin_zohoma_job_queue(  $data  ) {
    adfoin_zohoma_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zoho API
 */
function adfoin_zohoma_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $list_id = ( isset( $data['listId'] ) ? $data['listId'] : '' );
    $task = $record['task'];
    unset($data['credId'], $data['listId']);
    if ( $task == 'subscribe' ) {
        $zohoma = ADFOIN_ZohoMA::get_instance();
        $holder = array();
        $lead_id = '';
        $zohoma->set_credentials( $cred_id );
        foreach ( $data as $key => $value ) {
            list( $type, $field ) = explode( '__', $key );
            if ( $value ) {
                $value = adfoin_get_parsed_values( $value, $posted_data );
                if ( 'datetime' == $type || 'date' == $type ) {
                    $timezone = wp_timezone();
                    $date = date_create( $value, $timezone );
                    if ( $date ) {
                        $value = date_format( $date, 'c' );
                    }
                }
            }
            $holder[$field] = $value;
        }
        $holder = array_filter( $holder );
        $lead_data = array(
            'resfmt'   => 'JSON',
            'listkey'  => $list_id,
            'leadinfo' => urlencode( wp_json_encode( $holder ) ),
        );
        $zohoma->create_lead( $lead_data, $record );
    }
    return;
}
