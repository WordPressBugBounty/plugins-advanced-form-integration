<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_mailercloud_actions',
    10,
    1
);
function adfoin_mailercloud_actions(  $actions  ) {
    $actions['mailercloud'] = array(
        'title' => __( 'Mailercloud', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_mailercloud_settings_tab',
    10,
    1
);
function adfoin_mailercloud_settings_tab(  $providers  ) {
    $providers['mailercloud'] = __( 'Mailercloud', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_mailercloud_settings_view',
    10,
    1
);
function adfoin_mailercloud_settings_view(  $current_tab  ) {
    if ( $current_tab != 'mailercloud' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Go to Profile > Account > Integrations > API Integrations.', 'advanced-form-integration' ),
        __( 'Create a new API Key.', 'advanced-form-integration' ),
        __( 'Paste the key here and save.', 'advanced-form-integration' )
    );
    ADFOIN_Account_Manager::render_settings_view(
        'mailercloud',
        __( 'Mailercloud', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_mailercloud_credentials',
    'adfoin_get_mailercloud_credentials',
    10,
    0
);
function adfoin_get_mailercloud_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailercloud' );
}

add_action(
    'wp_ajax_adfoin_save_mailercloud_credentials',
    'adfoin_save_mailercloud_credentials',
    10,
    0
);
function adfoin_save_mailercloud_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailercloud', array('apiKey') );
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_mailercloud_modify_credentials',
    10,
    2
);
function adfoin_mailercloud_modify_credentials(  $credentials, $platform  ) {
    if ( 'mailercloud' === $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_mailercloud_api_key' );
        if ( $api_key ) {
            $credentials[] = array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_key,
            );
        }
    }
    return $credentials;
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_mailercloud_js_fields',
    10,
    1
);
function adfoin_mailercloud_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_mailercloud_action_fields' );
function adfoin_mailercloud_action_fields() {
    ?>
    <script type="text/template" id="mailercloud-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Account', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="cred in credentialsList" :key="cred.id" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailercloud' ) );
    ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Mailercloud List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

/*
 * Mailercloud API Request
 */
function adfoin_mailercloud_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array()
) {
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : (( isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '' )) );
    if ( isset( $data['credId'] ) ) {
        unset($data['credId']);
        // prevent accidental payload contamination
    }
    $credentials = adfoin_get_credentials_by_id( 'mailercloud', $cred_id );
    $api_token = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    if ( !$api_token ) {
        $api_token = get_option( 'adfoin_mailercloud_api_key' );
    }
    if ( !$api_token ) {
        return new WP_Error('missing_api_key', __( 'Mailercloud API key not found', 'advanced-form-integration' ));
    }
    $base_url = 'https://cloudapi.mailercloud.com/v1/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => $api_token,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
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

add_action(
    'wp_ajax_adfoin_get_mailercloud_list',
    'adfoin_get_mailercloud_list',
    10,
    0
);
/*
 * Get Mailercloud subscriber lists
 */
function adfoin_get_mailercloud_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $lists = array();
    $final_list = array();
    $limit = 100;
    $data = adfoin_mailercloud_request( 'lists/search', 'POST', array(
        'limit'  => $limit,
        'page'   => 1,
        'credId' => $cred_id,
    ) );
    if ( !is_wp_error( $data ) ) {
        $body = json_decode( wp_remote_retrieve_body( $data ), true );
        $lists = $body['data'];
        $lists_total = absint( $body['list_count'] );
        $pagination_needed = absint( $lists_total / $limit ) + 1;
        if ( $pagination_needed >= 2 ) {
            $response_pages = array();
            $response_body = array();
            for ($i = 2; $i <= $pagination_needed; $i++) {
                $response_pages[$i] = adfoin_mailercloud_request( 'lists/search', 'POST', array(
                    'limit'  => $limit,
                    'page'   => $i,
                    'credId' => $cred_id,
                ) );
                $response_body[$i] = json_decode( wp_remote_retrieve_body( $response_pages[$i] ), true );
                if ( $response_body[$i]['data'] && is_array( $response_body[$i]['data'] ) ) {
                    $lists = array_merge( $lists, $response_body[$i]['data'] );
                }
            }
        }
        $final_list = wp_list_pluck( $lists, 'name', 'id' );
    }
    wp_send_json_success( $final_list );
}

add_action(
    'wp_ajax_adfoin_get_mailercloud_contact_fields',
    'adfoin_get_mailercloud_contact_fields',
    10,
    0
);
/*
* Get contact fields
*/
function adfoin_get_mailercloud_contact_fields() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $contact_fidlds = array();
    $endpoint = "contact/property/search";
    $params = array(
        'limit'  => 100,
        'page'   => 1,
        'search' => '',
    );
    $params['credId'] = $cred_id;
    $data = adfoin_mailercloud_request( $endpoint, 'POST', $params );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $body = json_decode( wp_remote_retrieve_body( $data ) );
    foreach ( $body->data as $single ) {
        if ( $single->is_default == 1 ) {
            array_push( $contact_fidlds, array(
                'key'   => $single->field_value,
                'value' => $single->field_name,
            ) );
        }
    }
    wp_send_json_success( array_reverse( $contact_fidlds ) );
}

add_action(
    'adfoin_mailercloud_job_queue',
    'adfoin_mailercloud_job_queue',
    10,
    1
);
function adfoin_mailercloud_job_queue(  $data  ) {
    adfoin_mailercloud_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to mailercloud API
 */
function adfoin_mailercloud_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    if ( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if ( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if ( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $list_id = $data['listId'];
    $task = $record['task'];
    $holder = array();
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    // Backward compatibility: default to first credential if none selected
    if ( empty( $cred_id ) ) {
        $creds = adfoin_read_credentials( 'mailercloud' );
        if ( !empty( $creds ) ) {
            $cred_id = $creds[0]['id'];
        }
    }
    unset($data['listId']);
    unset($data['credId']);
    foreach ( $data as $key => $value ) {
        $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
    }
    if ( $task == 'subscribe' ) {
        $holder = array_filter( $holder );
        if ( $list_id ) {
            $holder['list_id'] = $list_id;
        }
        $holder['credId'] = $cred_id;
        $return = adfoin_mailercloud_request(
            'contacts',
            'POST',
            $holder,
            $record
        );
        if ( 400 == wp_remote_retrieve_response_code( $return ) ) {
            $body = json_decode( wp_remote_retrieve_body( $return ), true );
            if ( isset( $body['errors'][0]['message'] ) && $body['errors'][0]['message'] == 'Email already exist' ) {
                $email = $holder['email'];
                unset($holder['list_id']);
                unset($holder['email']);
                $holder['credId'] = $cred_id;
                $return = adfoin_mailercloud_request(
                    'contacts/' . $email,
                    'PUT',
                    $holder,
                    $record
                );
            }
        }
    }
    return;
}
