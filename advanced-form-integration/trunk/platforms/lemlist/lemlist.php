<?php

add_filter( 'adfoin_action_providers', 'adfoin_lemlist_actions', 10, 1 );

function adfoin_lemlist_actions( $actions ) {

    $actions['lemlist'] = array(
        'title' => __( 'lemlist', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact To Campaign', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_lemlist_settings_tab', 10, 1 );

function adfoin_lemlist_settings_tab( $providers ) {
    $providers['lemlist'] = __( 'lemlist', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_lemlist_settings_view', 10, 1 );

function adfoin_lemlist_settings_view( $current_tab ) {
    if( $current_tab != 'lemlist' ) {
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
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Settings > Integrations and generate an API Key', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'lemlist', __( 'lemlist', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_lemlist_credentials', 'adfoin_get_lemlist_credentials', 10, 0 );
/*
 * Get lemlist credentials
 */
function adfoin_get_lemlist_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'lemlist' );
}

add_action( 'wp_ajax_adfoin_save_lemlist_credentials', 'adfoin_save_lemlist_credentials', 10, 0 );
/*
 * Save lemlist credentials
 */
function adfoin_save_lemlist_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'lemlist', array( 'apiKey' ) );
}

/*
 * lemlist Credentials List
 */
function adfoin_lemlist_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'lemlist' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_lemlist_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_lemlist_modify_credentials( $credentials, $platform ) {
    if ( 'lemlist' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_lemlist_api_key' );

        if( $api_key ) {
            $credentials = array(
                array(
                    'id' => 'legacy',
                    'title' => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiKey' => $api_key
                )
            );
        }
    }

    return $credentials;
}

add_action( 'adfoin_add_js_fields', 'adfoin_lemlist_js_fields', 10, 1 );

function adfoin_lemlist_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_lemlist_action_fields' );

function adfoin_lemlist_action_fields() {
?>
    <script type="text/template" id="lemlist-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.credentialsList" :value="index" > {{item}}  </option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=lemlist' ); ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Campaign', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

        </table>
    </script>


<?php
}

add_action( 'wp_ajax_adfoin_get_lemlist_list', 'adfoin_get_lemlist_list', 10, 0 );

/*
 * Get lemlist subscriber lists
 */
function adfoin_get_lemlist_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $response = adfoin_lemlist_request('campaigns?version=v2&limit=100', 'GET', array(), array(), $cred_id);

    if( !is_wp_error( $response ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $response ) );
        $lists = wp_list_pluck( $body->campaigns, 'name', '_id' );

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_lemlist_job_queue', 'adfoin_lemlist_job_queue', 10, 1 );

function adfoin_lemlist_job_queue( $data ) {
    adfoin_lemlist_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to lemlist API
 */
function adfoin_lemlist_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data    = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = $data['listId'];
    $task    = $record['task'];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'lemlist' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if ($task == 'subscribe') {

        $email         = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name    = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name     = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
        $picture       = empty( $data['picture'] ) ? '' : adfoin_get_parsed_values( $data['picture'], $posted_data );
        $phone         = empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data );
        $linkedin_url  = empty( $data['linkedinUrl'] ) ? '' : adfoin_get_parsed_values( $data['linkedinUrl'], $posted_data );
        $company_name  = empty( $data['companyName'] ) ? '' : adfoin_get_parsed_values( $data['companyName'], $posted_data );
        $company_domain = empty( $data['companyDomain'] ) ? '' : adfoin_get_parsed_values( $data['companyDomain'], $posted_data );
        $icebreaker    = empty( $data['icebreaker'] ) ? '' : adfoin_get_parsed_values( $data['icebreaker'], $posted_data );

        $data = array_filter(array(
            'firstName'     => $first_name,
            'lastName'      => $last_name,
            'picture'       => $picture,
            'phone'         => $phone,
            'linkedinUrl'   => $linkedin_url,
            'companyName'   => $company_name,
            'companyDomain' => $company_domain,
            'icebreaker'    => $icebreaker
        ));

        $endpoint = "campaigns/{$list_id}/leads/{$email}";

        if (adfoin_lemlist_find_lead_by_email($email, $list_id, $cred_id)) {
            $response = adfoin_lemlist_request($endpoint, 'PATCH', $data, $record, $cred_id);
        } else {
            $response = adfoin_lemlist_request($endpoint, 'POST', $data, $record, $cred_id);
        }


        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        $lead_id = isset( $response_body['_id'] ) ? $response_body['_id'] : '';
    }
}

/*
 * lemlist API Request
 */
function adfoin_lemlist_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'lemlist', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Fallback to old option for backward compatibility
    if( !$api_key ) {
        $api_key = get_option( 'adfoin_lemlist_api_key' ) ? get_option( 'adfoin_lemlist_api_key' ) : '';
    }

    $base_url = 'https://api.lemlist.com/api/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( ':' . $api_key )
        ),
    );

    if ('POST' == $method || 'PATCH' == $method) {
        if ( ! empty( $data ) ) {
            $args['body'] = json_encode( $data );
        }
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

function adfoin_lemlist_find_lead_by_email($email, $list_id, $cred_id = '') {
    $endpoint = "leads/{$email}?version=v2&campaignId={$list_id}";
    $response = adfoin_lemlist_request($endpoint, 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['0'], $body['0']['variables'], $body['0']['variables']['email']) && $body['0']['variables']['email'] === $email) {
        return true;
    }

    return false;
}