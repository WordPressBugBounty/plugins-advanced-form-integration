<?php

add_filter( 'adfoin_action_providers', 'adfoin_encharge_actions', 10, 1 );

function adfoin_encharge_actions( $actions ) {

    $actions['encharge'] = array(
        'title' => __( 'Encharge', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Create new person', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_encharge_settings_tab', 10, 1 );

function adfoin_encharge_settings_tab( $providers ) {
    $providers['encharge'] = __( 'Encharge', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_encharge_settings_view', 10, 1 );

function adfoin_encharge_settings_view( $current_tab ) {
    if( $current_tab != 'encharge' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiKey', 
            'label' => __( 'Encharge API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Settings > Your Account and copy the API Key', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'encharge', __( 'Encharge', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_encharge_credentials', 'adfoin_get_encharge_credentials', 10, 0 );
/*
 * Get Encharge credentials
 */
function adfoin_get_encharge_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'encharge' );
}

add_action( 'wp_ajax_adfoin_save_encharge_credentials', 'adfoin_save_encharge_credentials', 10, 0 );
/*
 * Save Encharge credentials
 */
function adfoin_save_encharge_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'encharge', array( 'apiKey' ) );
}

/*
 * Encharge Credentials List
 */
function adfoin_encharge_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'encharge' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_encharge_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_encharge_modify_credentials( $credentials, $platform ) {
    if ( 'encharge' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_encharge_api_key' );

        if( $api_key ) {
            $credentials = array(
                array(
                    'id'     => 'legacy',
                    'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiKey' => $api_key
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_encharge_api_key', 'adfoin_save_encharge_api_key', 10, 0 );

function adfoin_save_encharge_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_encharge_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = sanitize_text_field( $_POST['adfoin_encharge_api_key'] );

    // Save tokens
    update_option( 'adfoin_encharge_api_key', $api_key );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=encharge' );
}

add_action( 'adfoin_action_fields', 'adfoin_encharge_action_fields' );

function adfoin_encharge_action_fields() {
?>
    <script type="text/template" id="encharge-action-template">
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
                        <?php esc_attr_e( 'Encharge Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=encharge' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>


<?php
}

 /*
 * Encharge API Request
 */
function adfoin_encharge_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'encharge', $cred_id );
    $api_token = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_encharge_api_key' ) ? get_option( 'adfoin_encharge_api_key' ) : '';
    }

    if( !$api_token ) {
        return array();
    }

    $base_url = 'https://api.encharge.io/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'X-Encharge-Token' => $api_token,
        ),
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_encharge_fields', 'adfoin_get_encharge_fields', 10, 0 );

/*
 * Get Encharge fields
 */
function adfoin_get_encharge_fields() {

    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $object_type = isset( $_POST['objectType'] ) ? sanitize_text_field( wp_unslash( $_POST['objectType'] ) ) : 'people';
    $endpoint    = 'fields';

    if ( 'company' === $object_type ) {
        $endpoint = 'schemas/company';
    }

    $data   = adfoin_encharge_request( $endpoint, 'GET', array(), array(), $cred_id );
    $fields = array();

    if ( ! is_wp_error( $data ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $data ) );
        $items = array();

        if ( isset( $body->items ) && is_array( $body->items ) ) {
            $items = $body->items;
        } elseif ( isset( $body->fields ) && is_array( $body->fields ) ) {
            $items = $body->fields;
        } elseif ( isset( $body->object ) && isset( $body->object->fields ) && is_array( $body->object->fields ) ) {
            $items = $body->object->fields;
        }

        foreach ( $items as $single ) {
            $read_only = isset( $single->readOnly ) ? (bool) $single->readOnly : false;

            if ( $read_only ) {
                continue;
            }

            $fields[] = array(
                'key'         => isset( $single->name ) ? $single->name : '',
                'value'       => isset( $single->title ) ? $single->title : ( isset( $single->name ) ? $single->name : '' ),
                'description' => '',
                'objectType'  => $object_type,
            );
        }

        if ( 'people' === $object_type ) {
            $fields[] = array(
                'key'         => 'tags',
                'value'       => 'Tags',
                'description' => 'Use comma to add multiple tags',
                'objectType'  => 'people',
            );
        }

        wp_send_json_success( $fields );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_encharge_job_queue', 'adfoin_encharge_job_queue', 10, 1 );

function adfoin_encharge_job_queue( $data ) {
    adfoin_encharge_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Encharge API
 */
function adfoin_encharge_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = isset( $record['task'] ) ? $record['task'] : '';

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'encharge' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'subscribe' ) {

        $holder = array();
        $tags   = null;

        foreach ( $data as $key => $value ) {
            if( $value && $key !== 'credId' ) {
                $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        $holder = array_filter( $holder );

        if( isset( $holder['tags'] ) && $holder['tags'] ) {
            $tags = $holder['tags'];

            unset( $holder['tags'] );
        }

        adfoin_encharge_request( 'people', 'POST', $holder, $record, $cred_id );

        if( $tags ) {
            $email = isset( $holder['email'] ) ? $holder['email'] : '';

            if( $email ) {

                $tag_data = array(
                    'tag'   => $tags,
                    'email' => $email
                );

                sleep(5);

                adfoin_encharge_request( 'tags', 'POST', $tag_data, $record, $cred_id );
            }
        }
    }

    return;
}
