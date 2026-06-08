<?php

add_filter( 'adfoin_action_providers', 'adfoin_klaviyo_actions', 10, 1 );

function adfoin_klaviyo_actions( $actions ) {

    $actions['klaviyo'] = array(
        'title' => __( 'Klaviyo', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_klaviyo_settings_tab', 10, 1 );

function adfoin_klaviyo_settings_tab( $providers ) {
    $providers['klaviyo'] = __( 'Klaviyo', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_klaviyo_settings_view', 10, 1 );

function adfoin_klaviyo_settings_view( $current_tab ) {
    if( $current_tab != 'klaviyo' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'privateKey', 
            'label' => __( 'Private API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your Private API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>
                    <li>Generate a Private API Key with full access.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        ),
        'https://www.klaviyo.com/account#api-keys-tab',
        __('Click here to get the API Keys', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'klaviyo', __( 'Klaviyo', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_klaviyo_credentials', 'adfoin_get_klaviyo_credentials', 10, 0 );

function adfoin_get_klaviyo_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'klaviyo' );
}

add_action( 'wp_ajax_adfoin_save_klaviyo_credentials', 'adfoin_save_klaviyo_credentials', 10, 0 );
/*
 * Get Kalviyo credentials
 */
function adfoin_save_klaviyo_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'klaviyo', array( 'privateKey' ) );
}

// Legacy single-account import: surfaces old `adfoin_klaviyo_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'klaviyo', array(
            'privateKey' => 'adfoin_klaviyo_api_token',
        ), array(
            'id' => 'imported_legacy',
        ) );
    }
}, 20 );

function adfoin_klaviyo_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'klaviyo' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_klaviyo_action_fields' );

function adfoin_klaviyo_action_fields() {
    ?>
    <script type="text/template" id="klaviyo-action-template">
        <div>
        <table class="form-table">
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
                        <?php esc_attr_e( 'Klaviyo Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=klaviyo' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Klaviyo List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Klaviyo [PRO]', 'custom fields' ); ?>
            
        </table>
        </div>
    </script>
    <?php
}

/*
 * Klaviyo API revision date sent with every request.
 * Filterable so the pinned revision can be updated without touching callers.
 * @see https://developers.klaviyo.com/en/reference/api_overview
 */
function adfoin_klaviyo_api_revision() {
    return apply_filters( 'adfoin_klaviyo_api_revision', '2025-10-15' );
}

/*
 * Klaviyo API request (Private API Key authentication).
 * Retries on HTTP 429 (rate limit), honouring the Retry-After header.
 */
function adfoin_klaviyo_private_request_20240215( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $retry_count = 0 ) {

    $credentials = adfoin_get_credentials_by_id( 'klaviyo', $cred_id );
    $api_key     = isset( $credentials['privateKey'] ) ? $credentials['privateKey'] : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'missing_api_key', __( 'Private API Key not found', 'advanced-form-integration' ) );
    }

    $base_url = 'https://a.klaviyo.com/api/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'revision'      => adfoin_klaviyo_api_revision(),
            'Authorization' => 'Klaviyo-API-Key ' . $api_key,
        ),
    );

    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    // Retry once or twice on rate limiting, respecting Retry-After.
    if ( ! is_wp_error( $response ) && 429 == wp_remote_retrieve_response_code( $response ) && $retry_count < 2 ) {
        $retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
        $retry_after = ( $retry_after > 0 && $retry_after <= 10 ) ? $retry_after : 3;
        sleep( $retry_after );

        return adfoin_klaviyo_private_request_20240215( $endpoint, $method, $data, $record, $cred_id, $retry_count + 1 );
    }

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_klaviyo_list', 'adfoin_get_klaviyo_list', 10, 0 );
/*
 * Get Kalviyo subscriber lists
 */
function adfoin_get_klaviyo_list() {
    // Security Check
    $nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';

    if ( ! wp_verify_nonce( $nonce, 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists = array();
    $next_url = '';

    do {
        // Construct URL for Klaviyo request
        $url = empty( $next_url ) ? 'lists' : str_replace( 'https://a.klaviyo.com/api/', '', $next_url );

        // Fetch data from Klaviyo
        $data = adfoin_klaviyo_private_request_20240215( $url, 'GET', array(), array(), $cred_id );

        if ( is_wp_error( $data ) ) {
            wp_send_json_error();
        }

        $body  = json_decode( wp_remote_retrieve_body( $data ), true );

        if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            foreach ( $body['data'] as $list ) {
                $lists[ $list['id'] ] = $list['attributes']['name'];
            }
        }

        // Check for pagination
        if ( isset( $body['links']['next'] ) && ! empty( $body['links']['next'] ) ) {
            $next_url = $body['links']['next'];
        } else {
            $next_url = '';
        }

    } while ( ! empty( $next_url ) );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_klaviyo_job_queue', 'adfoin_klaviyo_job_queue', 10, 1 );

function adfoin_klaviyo_job_queue( $data ) {
    adfoin_klaviyo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_klaviyo_create_or_update_contact( $subscriber_data, $record, $cred_id ) {
    $result = adfoin_klaviyo_private_request_20240215( "profile-import/", 'POST', $subscriber_data, $record, $cred_id );
    $result = json_decode( wp_remote_retrieve_body( $result ), true );

    return isset( $result['data'], $result['data']['id'] ) ? $result['data']['id'] : false;
}

function adfoin_klaviyo_email_subscribe( $list_id, $email, $record, $cred_id, $source ) {
    $email_data = array(
        'data' => array(
            'type' => 'profile-subscription-bulk-create-job',
            'attributes' => array(
                'custom_source' => $source,
                'profiles' => array(
                    'data' => array(
                        array(
                            'type' => 'profile',
                            'attributes' => array(
                                'email' => $email,
                                'subscriptions' => array(
                                    'email' => array(
                                        'marketing' => array(
                                            'consent' => 'SUBSCRIBED',
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            'relationships' => array(
                'list' => array(
                    'data' => array(
                        'id' => $list_id,
                        'type' => 'list'
                    )
                )
            )
        )
    );

    $result = adfoin_klaviyo_private_request_20240215( 'profile-subscription-bulk-create-jobs/', 'POST', $email_data, $record, $cred_id );

    return $result;
}

/*
 * Handles sending data to Klaviyo API
 */
function adfoin_klaviyo_send_data( $record, $posted_data ) {

    $record_data    = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = $record['task'];

    // Backward compatibility: If no credId, use the first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'klaviyo' );
        if ( ! empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = isset( $first_credential['id'] ) ? $first_credential['id'] : '';
        }
    }

    if( $task == 'subscribe' ) {
        $email = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );

        $profile = array(
            'email' => $email
        );

        if( isset( $data['firstName'] ) && $data['firstName'] ) { $profile['first_name'] = adfoin_get_parsed_values( $data['firstName'], $posted_data ); }
        if( isset( $data['lastName'] ) && $data['lastName'] ) { $profile['last_name'] = adfoin_get_parsed_values( $data['lastName'], $posted_data ); }
        if( isset( $data['title'] ) && $data['title'] ) { $profile['title'] = adfoin_get_parsed_values( $data['title'], $posted_data ); }
        if( isset( $data['organization'] ) && $data['organization'] ) { $profile['organization'] = adfoin_get_parsed_values( $data['organization'], $posted_data ); }
        if( isset( $data['externalId'] ) && $data['externalId'] ) { $profile['external_id'] = adfoin_get_parsed_values( $data['externalId'], $posted_data ); }
        $source = isset( $data['source'] ) && $data['source'] ? adfoin_get_parsed_values( $data['source'], $posted_data ) : '';
        $ip = isset( $data['ip'] ) && $data['ip'] ? adfoin_get_parsed_values( $data['ip'], $posted_data ) : '';
        if( isset( $data['phoneNumber'] ) && $data['phoneNumber'] ) {
            $phone_number = preg_replace('/[^0-9+]/','',adfoin_get_parsed_values( $data['phoneNumber'], $posted_data ) );

            if( strlen( $phone_number ) > 7 ) {
                $profile['phone_number'] = $phone_number;
            }
        }

        $address = array(
            'address1' => adfoin_get_parsed_values( $data['address1'], $posted_data ),
            'address2' => adfoin_get_parsed_values( $data['address2'], $posted_data ),
            'city'     => adfoin_get_parsed_values( $data['city'], $posted_data ),
            'region'   => adfoin_get_parsed_values( $data['region'], $posted_data ),
            'country'  => adfoin_get_parsed_values( $data['country'], $posted_data ),
            'zip'      => adfoin_get_parsed_values( $data['zip'], $posted_data ),
            'latitude' => adfoin_get_parsed_values( $data['latitude'], $posted_data ),
            'longitude' => adfoin_get_parsed_values( $data['longitude'], $posted_data ),
            'timezone' => adfoin_get_parsed_values( $data['timezone'], $posted_data ),
            'ip'       => $ip ? $ip : ''
        );

        $address = array_filter( $address );

        $subscriber_data = array(
            'data' => array(
                'type' => 'profile',
                'attributes' => $profile
            )
        );

        if( !empty( $address ) ) {
            $subscriber_data['data']['attributes']['location'] = $address;
        }

        $contact_id = adfoin_klaviyo_create_or_update_contact( $subscriber_data, $record, $cred_id );

        if( $contact_id && $email ) {
            adfoin_klaviyo_email_subscribe( $list_id, $email, $record, $cred_id, $source );
        }
    }

    return;
}