<?php

add_filter( 'adfoin_action_providers', 'adfoin_maileon_actions', 10, 1 );

function adfoin_maileon_actions( $actions ) {

    $actions['maileon'] = [
        'title' => __( 'Maileon', 'advanced-form-integration' ),
        'tasks' => [
            'subscribe'   => __( 'Add Contact', 'advanced-form-integration' ),
        ]
    ];

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_maileon_settings_tab', 10, 1 );

function adfoin_maileon_settings_tab( $providers ) {
    $providers['maileon'] = __( 'Maileon', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_maileon_settings_view', 10, 1 );

function adfoin_maileon_settings_view( $current_tab ) {
    if( $current_tab != 'maileon' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Go to Settings > API Keys in Maileon.', 'advanced-form-integration' ),
        __( 'Create a new API Key (v1).', 'advanced-form-integration' ),
        __( 'Paste the API Key below and save.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'maileon', __( 'Maileon', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_maileon_credentials', 'adfoin_get_maileon_credentials', 10, 0 );
function adfoin_get_maileon_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'maileon' );
}

add_action( 'wp_ajax_adfoin_save_maileon_credentials', 'adfoin_save_maileon_credentials', 10, 0 );
function adfoin_save_maileon_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'maileon', array( 'apiKey' ) );
}

add_filter( 'adfoin_get_credentials', 'adfoin_maileon_modify_credentials', 10, 2 );
function adfoin_maileon_modify_credentials( $credentials, $platform ) {
    if ( 'maileon' === $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_maileon_api_key' );
        if ( $api_key ) {
            $credentials[] = array(
                'id'      => 'legacy',
                'title'   => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey'  => $api_key,
            );
        }
    }

    return $credentials;
}

add_action( 'adfoin_action_fields', 'adfoin_maileon_action_fields' );

function adfoin_maileon_action_fields() {
    ?>
    <script type="text/template" id="maileon-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :key="cred.id" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=maileon' ) ); ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
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
                        <?php esc_attr_e( 'Permission', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[permission]" v-model="fielddata.permission" required="required">
                        <option value="1"> <?php _e( 'No Permission', 'advanced-form-integration' ); ?> </option>
                        <option value="2"> <?php _e( 'Single opt-in', 'advanced-form-integration' ); ?> </option>
                        <option value="3"> <?php _e( 'Confirmed opt-in', 'advanced-form-integration' ); ?> </option>
                        <option value="4"> <?php _e( 'Double opt-in', 'advanced-form-integration' ); ?> </option>
                        <option value="5"> <?php _e( 'Double opt-in plus', 'advanced-form-integration' ); ?> </option>
                        <option value="6"> <?php _e( 'Other', 'advanced-form-integration' ); ?> </option>                        
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Double opt-in', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doi]" value="true" v-model="fielddata.doi">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Double opt-in plus', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doiplus]" value="true" v-model="fielddata.doiplus">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Update existing contact', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[update]" value="true" v-model="fielddata.update">
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

/*
 * maileon API Request
 */
function adfoin_maileon_request( $endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'maileon', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fall back to old option
    if ( ! $api_key ) {
        $api_key = get_option( 'adfoin_maileon_api_key' ) ? get_option( 'adfoin_maileon_api_key' ) : '';
    }

    if ( ! $api_key ) {
        return new WP_Error( 'missing_api_key', __( 'Maileon API key not found', 'advanced-form-integration' ) );
    }

    $url     = 'https://api.maileon.com/1.0/' . $endpoint;

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type'    => 'application/vnd.maileon.api+json',
            'Accept'          => 'application/vnd.maileon.api+xml; charset=utf-8',
            'Authorization' => 'Basic ' . base64_encode( $api_key ),
        ]
    ];

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_maileon_fields', 'adfoin_get_maileon_fields' );

function adfoin_get_maileon_fields() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    
    $standard_fields = [
        [ 'key' => 'email', 'value' => 'Email', 'description' => ''],
        [ 'key' => 'firstName', 'value' => 'First Name', 'description' => ''],
        [ 'key' => 'lastName', 'value' => 'Last Name', 'description' => ''],
        [ 'key' => 'title', 'value' => 'Title', 'description' => ''],
        [ 'key' => 'organization', 'value' => 'Organization', 'description' => ''],
        [ 'key' => 'gender', 'value' => 'Gender', 'description' => ''],
        [ 'key' => 'birthday', 'value' => 'Birthday', 'description' => ''],
        [ 'key' => 'address', 'value' => 'Address', 'description' => ''],
        [ 'key' => 'city', 'value' => 'City', 'description' => ''],
        [ 'key' => 'zip', 'value' => 'Zip', 'description' => ''],
        [ 'key' => 'region', 'value' => 'Region', 'description' => ''],
        [ 'key' => 'state', 'value' => 'State', 'description' => ''],
        [ 'key' => 'country', 'value' => 'Country', 'description' => ''],
        [ 'key' => 'src', 'value' => 'src', 'description' => '']
    ];

    $custom_fields = adfoin_get_maileon_custom_fields( $cred_id );
    $all_fields = array_merge( $standard_fields, $custom_fields );

    wp_send_json_success( $all_fields );
}

function adfoin_get_maileon_custom_fields( $cred_id = '' ) {
    $response = adfoin_maileon_request( 'contacts/fields/custom', 'GET', array(), array(), $cred_id );
    $xml = wp_remote_retrieve_body( $response );

    if ( !is_wp_error( $response ) ) {
        $xmlObject = simplexml_load_string( $xml );
        $fields = [];

        foreach ( $xmlObject->custom_field as $field ) {
            $fields[] = [
                'key' => 'custom__' . ( string ) $field->type. '__' . ( string ) $field->name,
                'value' => ( string ) $field->name
            ];
        }
    }

    return $fields;
}

add_action( 'adfoin_maileon_job_queue', 'adfoin_maileon_job_queue', 10, 1 );

function adfoin_maileon_job_queue( $data ) {
    adfoin_maileon_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to maileon API
 */
function adfoin_maileon_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    // Backward compatibility: if no credId, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'maileon' );
        if ( ! empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'subscribe' ) {
        $email = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );

        $request_data = array(
            'standard_fields' => array_filter( array(
                'FIRSTNAME' => isset( $data['firstName'] ) ? adfoin_get_parsed_values( $data['firstName'], $posted_data ) : '',
                'LASTNAME'  => isset( $data['lastName'] ) ? adfoin_get_parsed_values( $data['lastName'], $posted_data ) : '',
                'TITLE' => isset( $data['title'] ) ? adfoin_get_parsed_values( $data['title'], $posted_data ) : '',
                'ORGANIZATION' => isset( $data['organization'] ) ? adfoin_get_parsed_values( $data['organization'], $posted_data ) : '',
                'GENDER' => isset( $data['gender'] ) ? adfoin_get_parsed_values( $data['gender'], $posted_data ) : '',
                'BIRTHDAY' => isset( $data['birthday'] ) ? adfoin_get_parsed_values( $data['birthday'], $posted_data ) : '',
                'ADDRESS' => isset( $data['address'] ) ? adfoin_get_parsed_values( $data['address'], $posted_data ) : '',
                'CITY' => isset( $data['city'] ) ? adfoin_get_parsed_values( $data['city'], $posted_data ) : '',
                'ZIP' => isset( $data['zip'] ) ? adfoin_get_parsed_values( $data['zip'], $posted_data ) : '',
                'REGION' => isset( $data['region'] ) ? adfoin_get_parsed_values( $data['region'], $posted_data ) : '',
                'STATE' => isset( $data['state'] ) ? adfoin_get_parsed_values( $data['state'], $posted_data ) : '',
                'COUNTRY' => isset( $data['country'] ) ? adfoin_get_parsed_values( $data['country'], $posted_data ) : '',
            ) ),
        );

        $url_attributes = array_filter( array(
            'permission' => isset( $data['permission'] ) ? $data['permission'] : 1,
            'doi'        => isset( $data['doi'] ) ? $data['doi'] : false,
            'doiplus'    => isset( $data['doiplus'] ) ? $data['doiplus'] : false,
            'src'        => isset( $data['src'] ) ? $data['src'] : '',
        ) );

        if( isset( $data['update'] ) && $data['update'] == 'true' ) {
            $url_attributes['sync_mode'] = 1;
        }

        $url_attributes = array_filter( $url_attributes );
        $url_param = add_query_arg( $url_attributes, $email );

        $custom_fields = [];

        foreach ( $data as $key => $value ) {
            if ( strpos( $key, 'custom__' ) !== false && !empty( $value ) ) {
                $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

                if( empty( $parsed_value ) ) continue;
                
                $field = explode( '__', str_replace( 'custom__', '', $key ) );

                if ( $field[0] == 'boolean' ) {
                    $parsed_value = ( strtolower( $parsed_value ) == 'yes' || $parsed_value == 'true' || $parsed_value == 1 ) ? true : false;
                }

                $custom_fields[$field[1]] = $parsed_value;
            }
        }

        if ( !empty( $custom_fields ) ) {
            $request_data['custom_fields'] = $custom_fields;
        }

        if ( adfoin_maileon_contact_exists( $email, $cred_id ) ) {
            $return = adfoin_maileon_request( 'contacts/email/' . $url_param, 'PUT', $request_data, $record, $cred_id );
        } else {
            $return = adfoin_maileon_request( 'contacts/email/' . $url_param, 'POST', $request_data, $record, $cred_id );
        }
    }

    return;
}

function adfoin_maileon_contact_exists( $email, $cred_id = '' ) {
    $response = adfoin_maileon_request( 'contacts/email/' . $email, 'GET', array(), array(), $cred_id );
    $xml = wp_remote_retrieve_body( $response );

    if ( !is_wp_error( $response ) ) {
        $xmlObject = simplexml_load_string( $xml );

        if ( isset( $xmlObject->email ) && $xmlObject->email == $email ) {
            return true;
        }
    }

    return false;
}
