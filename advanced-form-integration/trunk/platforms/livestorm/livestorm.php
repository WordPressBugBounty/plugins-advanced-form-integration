<?php

add_filter( 'adfoin_action_providers', 'adfoin_livestorm_actions', 10, 1 );

function adfoin_livestorm_actions( $actions ) {

    $actions['livestorm'] = array(
        'title' => __( 'Livestorm', 'advanced-form-integration' ),
        'tasks' => array(
            'add_people'   => __( 'Add people to event session', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_livestorm_settings_tab', 10, 1 );

function adfoin_livestorm_settings_tab( $providers ) {
    $providers['livestorm'] = __( 'Livestorm', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_livestorm_settings_view', 10, 1 );

function adfoin_livestorm_settings_view( $current_tab ) {
    if( $current_tab != 'livestorm' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiToken', 
            'label' => __( 'API Token', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Token', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Account > Account Settings > App marketplace > Zapier. Copy the Zapier Token.', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'livestorm', __( 'Livestorm', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_livestorm_credentials', 'adfoin_get_livestorm_credentials', 10, 0 );
/*
 * Get Livestorm credentials
 */
function adfoin_get_livestorm_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'livestorm' );
}

add_action( 'wp_ajax_adfoin_save_livestorm_credentials', 'adfoin_save_livestorm_credentials', 10, 0 );
/*
 * Save Livestorm credentials
 */
function adfoin_save_livestorm_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'livestorm', array( 'apiToken' ) );
}

/*
 * Livestorm Credentials List
 */
function adfoin_livestorm_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'livestorm' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_livestorm_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_livestorm_modify_credentials( $credentials, $platform ) {
    if ( 'livestorm' == $platform && empty( $credentials ) ) {
        $api_token = get_option( 'adfoin_livestorm_api_token' );

        if( $api_token ) {
            $credentials = array(
                array(
                    'id' => 'legacy',
                    'title' => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiToken' => $api_token
                )
            );
        }
    }

    return $credentials;
}

add_action( 'adfoin_action_fields', 'adfoin_livestorm_action_fields' );

function adfoin_livestorm_action_fields() {
    ?>
    <script type="text/template" id="livestorm-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_people'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.credentialsList" :value="index" > {{item}}  </option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=livestorm' ); ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_people'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_people'">
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

                <tr valign="top" class="alternate" v-if="action.task == 'add_people'">
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

function adfoin_livestorm_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'livestorm', $cred_id );
    $api_token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    // Fallback to old option for backward compatibility
    if( !$api_token ) {
        $api_token = get_option( 'adfoin_livestorm_api_token' ) ? get_option( 'adfoin_livestorm_api_token' ) : '';
    }

    $base_url = 'https://api.livestorm.co/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => $api_token
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

add_action( 'wp_ajax_adfoin_get_livestorm_events', 'adfoin_get_livestorm_events', 10, 1 );

/*
 * Get Livestorm Event list
 */
function adfoin_get_livestorm_events()
{
    // Security Check
    if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) {
        die(__('Security check Failed', 'advanced-form-integration'));
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $page = 0;
    $size = 100;

    $events = array();

    do {
        $data = adfoin_livestorm_request( 'events?page[number]=' . $page . '&page[size]=' . $size, 'GET', array(), array(), $cred_id );
        $body = json_decode(wp_remote_retrieve_body( $data ), true);
        $events = array_merge($events, $body);

        $page++;
    } while ( count($body) == $size );

    $final_events = wp_list_pluck( $events, 'title', 'id' );

    asort( $final_events );

    wp_send_json_success( $final_events );
}

add_action( 'wp_ajax_adfoin_get_livestorm_sessions', 'adfoin_get_livestorm_sessions', 10, 1 );

/*
 * Get Livestorm Session list
 */
function adfoin_get_livestorm_sessions()
{
    // Security Check
    if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) {
        die(__('Security check Failed', 'advanced-form-integration'));
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $event_id = isset( $_POST['eventId'] ) ? $_POST['eventId'] : '';

    if( empty( $event_id ) ) {
        wp_send_json_error();
    }

    $page = 0;
    $size = 100;
    $raw_sessions = array();

    do {
        $data = adfoin_livestorm_request( 'events/' . $event_id . '/sessions?page[number]=' . $page . '&page[size]=' . $size, 'GET', array(), array(), $cred_id );
        $body = json_decode(wp_remote_retrieve_body( $data ), true);
        $raw_sessions = array_merge($raw_sessions, $body);

        $page++;
    } while ( count($body) == $size );

    $sessions = array();

    foreach( $raw_sessions as $session ) {
        if( isset( $session['estimated_started_at_in_timezone'] ) ) {
            $sessions[$session['id']] = $session['estimated_started_at_in_timezone'];
        } else {
            $sessions[$session['id']] = $session['id'];
        }
    }

    wp_send_json_success($sessions);
}

add_action( 'adfoin_livestorm_job_queue', 'adfoin_livestorm_job_queue', 10, 1 );

function adfoin_livestorm_job_queue( $data ) {
    adfoin_livestorm_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to livestorm API
 */
function adfoin_livestorm_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( 'cl', $record_data['action_data']) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data       = $record_data['field_data'];
    $cred_id    = isset( $data['credId'] ) ? $data['credId'] : '';
    $task       = $record['task'];
    $session_id = $data['sessionId'];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'livestorm' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'add_people' ) {
        
        $email      = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );

        $data = array(
            'data' =>array(
                'type' => 'people',
                'attributes' => array(
                    'fields' => array(
                        array(
                            "id"    => "email",
                            "value" => trim( $email )
                        ),
                        array(
                            "id"    => "first_name",
                            "value" => $first_name
                        ),
                        array(
                            "id"    => "last_name",
                            "value" => $last_name
                        ),
                    )
                    ),
            )
        );

        $return = adfoin_livestorm_request( 'sessions/' . $session_id . '/people', 'POST', $data, $record, $cred_id );

    }

    return;
}