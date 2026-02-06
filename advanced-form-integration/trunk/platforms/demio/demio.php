<?php

add_filter( 'adfoin_action_providers', 'adfoin_demio_actions', 10, 1 );

function adfoin_demio_actions( $actions ) {

    $actions['demio'] = array(
        'title' => __( 'Demio', 'advanced-form-integration' ),
        'tasks' => array(
            'reg_people'   => __( 'Register people to Webinar', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_demio_settings_tab', 10, 1 );

function adfoin_demio_settings_tab( $providers ) {
    $providers['demio'] = __( 'Demio', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_demio_settings_view', 10, 1 );

function adfoin_demio_settings_view( $current_tab ) {
    if( $current_tab != 'demio' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        ),
        array( 
            'name' => 'apiSecret', 
            'label' => __( 'API Secret', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Secret', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Settings > API > copy API Key and API Secret', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'demio', __( 'Demio', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_demio_credentials', 'adfoin_get_demio_credentials', 10, 0 );
/*
 * Get Demio credentials
 */
function adfoin_get_demio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'demio' );
}

add_action( 'wp_ajax_adfoin_save_demio_credentials', 'adfoin_save_demio_credentials', 10, 0 );
/*
 * Save Demio credentials
 */
function adfoin_save_demio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'demio', array( 'apiKey', 'apiSecret' ) );
}

/*
 * Demio Credentials List
 */
function adfoin_demio_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'demio' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_demio_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_demio_modify_credentials( $credentials, $platform ) {
    if ( 'demio' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_demio_api_key' );
        $api_secret = get_option( 'adfoin_demio_api_secret' );

        if( $api_key && $api_secret ) {
            $credentials = array(
                array(
                    'id'        => 'legacy',
                    'title'     => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiKey'    => $api_key,
                    'apiSecret' => $api_secret
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_demio_save_api_key', 'adfoin_save_demio_api_key', 10, 0 );

function adfoin_save_demio_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_demio_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key    = sanitize_text_field( $_POST["adfoin_demio_api_key"] );
    $api_secret = sanitize_text_field( $_POST["adfoin_demio_api_secret"] );

    // Save keys
    update_option( "adfoin_demio_api_key", $api_key );
    update_option( "adfoin_demio_api_secret", $api_secret );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=demio" );
}

add_action( 'adfoin_action_fields', 'adfoin_demio_action_fields' );

function adfoin_demio_action_fields() {
    ?>
    <script type="text/template" id="demio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'reg_people'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'reg_people'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Demio Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=demio' ); ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'reg_people'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Event', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[eventId]" v-model="fielddata.eventId" @change="getSessions">
                            <option value=""> <?php _e( 'Select Event...', 'advanced-form-integration' ); ?> </option>
                            <option v-for="(item, index) in fielddata.events" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': eventLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>
 
                <tr valign="top" class="alternate" v-if="action.task == 'reg_people'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Session', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[sessionId]" v-model="fielddata.sessionId">
                            <option value=""> <?php _e( 'Select Session...', 'advanced-form-integration' ); ?> </option>
                            <option v-for="(item, index) in fielddata.sessions" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': sessionLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_demio_events', 'adfoin_get_demio_events', 10, 1 );
 
/*
 * Get demio Event list
 */
function adfoin_get_demio_events()
{
    // Security Check
    if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) {
        die(__('Security check Failed', 'advanced-form-integration'));
    }
 
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_demio_request('events', 'GET', array(), array(), $cred_id);
 
    if (is_wp_error($data)) {
        wp_send_json_error();
    }
  
    $body  = json_decode( wp_remote_retrieve_body( $data ) );
    $events = wp_list_pluck( $body, 'name', 'id' );

 
    wp_send_json_success($events);
}

add_action( 'wp_ajax_adfoin_get_demio_sessions', 'adfoin_get_demio_sessions', 10, 1 );
 
/*
 * Get demio Session list
 */
function adfoin_get_demio_sessions()
{
    // Security Check
    if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) {
        die(__('Security check Failed', 'advanced-form-integration'));
    }
 
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $event_id = isset( $_POST['eventId'] ) ? $_POST['eventId'] : '';
 
    $data = adfoin_demio_request('event/' . $event_id . '?active=active', 'GET', array(), array(), $cred_id);
 
    if (is_wp_error($data)) {
        wp_send_json_error();
    }
 
    $body   = json_decode(wp_remote_retrieve_body( $data ), true );
    $sessions = array();
 
    foreach( $body['dates'] as $session ) {
        $sessions[$session['date_id']] = $session['datetime'];
    }
 
    wp_send_json_success($sessions);
}

function adfoin_demio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'demio', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $api_secret = isset( $credentials['apiSecret'] ) ? $credentials['apiSecret'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_demio_api_key' ) ? get_option( 'adfoin_demio_api_key' ) : '';
    }

    if( empty( $api_secret ) ) {
        $api_secret = get_option( 'adfoin_demio_api_secret' ) ? get_option( 'adfoin_demio_api_secret' ) : '';
    }

    if( !$api_key || !$api_secret ) {
        return array();
    }

    $base_url = 'https://my.demio.com/api/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Api-Key'      => $api_key,
            'Api-Secret'   => $api_secret,

        )
    );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_demio_job_queue', 'adfoin_demio_job_queue', 10, 1 );

function adfoin_demio_job_queue( $data ) {
    adfoin_demio_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to demio API
 */
function adfoin_demio_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( 'cl', $record_data['action_data']) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data       = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task       = $record['task'];
    $event_id   = isset( $data['eventId'] ) ? $data['eventId'] : '';
    $session_id = isset( $data['sessionId'] ) ? $data['sessionId'] : '';

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'demio' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'reg_people' ) {
        $email        = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $name         = empty( $data['name'] ) ? '' : adfoin_get_parsed_values( $data['name'], $posted_data );
        // $last_name    = empty( $data['last_name'] ) ? '' : adfoin_get_parsed_values( $data['last_name'], $posted_data );
        // $company      = empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data );
        // $website      = empty( $data['website'] ) ? '' : adfoin_get_parsed_values( $data['website'], $posted_data );
        // $phone_number = empty( $data['phone_number'] ) ? '' : adfoin_get_parsed_values( $data['phone_number'], $posted_data );
        // $gdpr         = empty( $data['gdpr'] ) ? '' : adfoin_get_parsed_values( $data['gdpr'], $posted_data );
        // $refUrl       = empty( $data['refUrl'] ) ? '' : adfoin_get_parsed_values( $data['refUrl'], $posted_data );
        
        $data = array(
            'id'           => $event_id,
            'date_id'      => $session_id,
            'name'         => $name,
            'email'        => $email,
            // 'last_name'    => $last_name,
            // 'company'      => $company,
            // 'website'      => $website,
            // 'phone_number' => $phone_number,
            // 'gdpr'         => $gdpr,
            // 'ref_url'      => $refUrl

        );

        $data = array_filter( $data );

        $return = adfoin_demio_request( 'event/register', 'POST', $data, $record, $cred_id );

    }

    return;
}