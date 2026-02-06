<?php

add_filter( 'adfoin_action_providers', 'adfoin_airtable_actions', 10, 1 );

function adfoin_airtable_actions( $actions ) {

    $actions['airtable'] = array(
        'title' => __( 'Airtable', 'advanced-form-integration' ),
        'tasks' => array(
            'add_row'   => __( 'Add New Row', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_airtable_settings_tab', 10, 1 );

function adfoin_airtable_settings_tab( $providers ) {
    $providers['airtable'] = __( 'Airtable', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_airtable_settings_view', 10, 1 );

function adfoin_airtable_settings_view( $current_tab ) {
    if( $current_tab != 'airtable' ) {
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
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __('Go to %s and click on Create new token.', 'advanced-form-integration'), '<a target="_blank" rel="noopener noreferrer" href="https://airtable.com/create/tokens">https://airtable.com/create/tokens</a>' ),
        __('Insert a name for the token.', 'advanced-form-integration'),
        __('Select the scopes: data.records:read, data.record:write and schema.bases:read.', 'advanced-form-integration'),
        __('Select the bases you want to integrate or select all workspaces.', 'advanced-form-integration'),
        __('Click Create token and add it here.', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'airtable', __( 'Airtable', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_airtable_credentials', 'adfoin_get_airtable_credentials', 10, 0 );
/*
 * Get Airtable credentials
 */
function adfoin_get_airtable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'airtable' );
}

add_action( 'wp_ajax_adfoin_save_airtable_credentials', 'adfoin_save_airtable_credentials', 10, 0 );
/*
 * Save Airtable credentials
 */
function adfoin_save_airtable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'airtable', array( 'apiToken' ) );
}

/*
 * Airtable Credentials List
 */
function adfoin_airtable_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'airtable' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_airtable_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_airtable_modify_credentials( $credentials, $platform ) {
    if ( 'airtable' == $platform && empty( $credentials ) ) {
        $api_token = get_option( 'adfoin_airtable_api_token' );

        if( $api_token ) {
            $credentials = array(
                array(
                    'id'       => 'legacy',
                    'title'    => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiToken' => $api_token
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_airtable_save_api_token', 'adfoin_save_airtable_api_token', 10, 0 );

function adfoin_save_airtable_api_token() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_airtable_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_token = sanitize_text_field( $_POST["adfoin_airtable_api_token"] );

    // Save tokens
    update_option( "adfoin_airtable_api_token", $api_token );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=airtable" );
}

add_action( 'adfoin_action_fields', 'adfoin_airtable_action_fields' );

function adfoin_airtable_action_fields() {
    ?>
    <script type="text/template" id="airtable-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_row'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Airtable Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=airtable' ); ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Base', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[baseId]" v-model="fielddata.baseId" required="required" @change="getTables">
                        <option value=""> <?php _e( 'Select Base...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.bases" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': baseLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Table', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[tableId]" v-model="fielddata.tableId" required="required" @change="getFields">
                        <option value=""> <?php _e( 'Select Table...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.tables" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': tableLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

        </table>
    </script>
    <?php
}

/*
 * Airtable API Request
 */
function adfoin_airtable_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '')
{
    $credentials = adfoin_get_credentials_by_id( 'airtable', $cred_id );
    $api_token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    // Backward compatibility: fallback to old option if credentials not found
    if( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_airtable_api_token' ) ? get_option( 'adfoin_airtable_api_token' ) : '';
    }

    $base_url  = 'https://api.airtable.com/v0/';
    $url       = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_token,
        ),
    );

    if ( 'POST' == $method || 'PUT' == $method|| 'PATCH' == $method ) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_airable_bases', 'adfoin_get_airable_bases', 10, 0 );
/*
 * Get Airtable Base List
 */
function adfoin_get_airable_bases() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_airtable_request( 'meta/bases', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( wp_remote_retrieve_body( $data ), true );
    $bases = array();

    if( isset( $body['bases'] ) && is_array( $body['bases'] ) ) {
        foreach( $body['bases'] as $base ) {
            if( 'create' == $base['permissionLevel'] ) {
                $bases[$base['id']] = $base['name'];
            }
        }
    }

    wp_send_json_success( $bases );
}

add_action( 'wp_ajax_adfoin_get_airtable_tables', 'adfoin_get_airtable_tables', 10, 0 );
/*
 * Get Airtable Base List
 */
function adfoin_get_airtable_tables() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $base_id = isset( $_POST['baseId'] ) ? $_POST['baseId'] : '';
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_airtable_request( 'meta/bases/' . $base_id . '/tables', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body   = json_decode( wp_remote_retrieve_body( $data ), true );
    $tables = array();

    if( isset( $body['tables'] ) && is_array( $body['tables'] ) ) {
        foreach( $body['tables'] as $table ) {
            
            $tables[$table['id']] = $table['name'];
        }
    }

    wp_send_json_success( $tables );
}

add_action( 'wp_ajax_adfoin_get_airtable_fields', 'adfoin_get_airtable_fields', 10, 0 );
/*
 * Get Airtable Fields
 */
function adfoin_get_airtable_fields() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $base_id  = isset( $_POST['baseId'] ) ? $_POST['baseId'] : '';
    $table_id = isset( $_POST['tableId'] ) ? $_POST['tableId'] : '';
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_airtable_request( 'meta/bases/' . $base_id . '/tables', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body   = json_decode( wp_remote_retrieve_body( $data ), true );
    $fields = array();

    if( isset( $body['tables'] ) && is_array( $body['tables'] ) ) {
        foreach( $body['tables'] as $table ) {
            if( $table_id == $table['id'] ) {
                if( isset( $table['fields'] ) && is_array( $table['fields'] ) ) {
                    foreach( $table['fields'] as $field ) {
                        $prepared_field = adfoin_airtable_prepare_field_for_response( $field );

                        if( ! empty( $prepared_field ) ) {
                            $fields[] = $prepared_field;
                        }
                    }
                }
            }
        }
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_airtable_job_queue', 'adfoin_airtable_job_queue', 10, 1 );

function adfoin_airtable_job_queue( $data ) {
    adfoin_airtable_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Airtable API
 */
function adfoin_airtable_send_data( $record, $posted_data ) {

    $record_data    = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data     = $record_data['field_data'];
    $base_id  = $data['baseId'];
    $table_id = $data['tableId'];
    $cred_id  = isset( $data['credId'] ) ? $data['credId'] : '';
    $task     = $record['task'];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'airtable' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'add_row' ) {

        unset( $data['baseId'] );
        unset( $data['tableId'] );
        unset( $data['bases'] );
        unset( $data['tables'] );
        unset( $data['credId'] );

        $holder = array();

        foreach ( $data as $key => $value ) {
            if( ! $value ) {
                continue;
            }

            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
            $parsed_value_trimmed = is_string( $parsed_value ) ? trim( $parsed_value ) : $parsed_value;

            if( '' === $parsed_value_trimmed && '0' !== $parsed_value_trimmed ) {
                continue;
            }

            $field_parts = explode( '__', $key, 2 );

            if( count( $field_parts ) < 2 ) {
                continue;
            }

            list( $field_type, $field_key ) = $field_parts;
            $field_type = adfoin_airtable_normalize_field_type( $field_type );

            if( is_string( $parsed_value_trimmed ) && 'null' === strtolower( $parsed_value_trimmed ) ) {
                $holder[ $field_key ] = null;
                continue;
            }

            $formatted_value = adfoin_airtable_format_field_value( $field_type, $parsed_value );

            if( null === $formatted_value ) {
                continue;
            }

            $holder[ $field_key ] = $formatted_value;
        }

        $row_data = array(
            'records' => array(
                array(
                    'fields' => $holder
                )
            )
        );

        $return = adfoin_airtable_request( $base_id . '/' . $table_id, 'POST', $row_data, $record, $cred_id );
    }

    return;
}

/**
 * Build field data array for the UI.
 *
 * @param array $field
 *
 * @return array
 */
function adfoin_airtable_prepare_field_for_response( $field ) {
    if ( empty( $field['id'] ) || empty( $field['name'] ) || empty( $field['type'] ) ) {
        return array();
    }

    return array(
        'key'         => $field['type'] . '__' . $field['id'],
        'value'       => $field['name'],
        'description' => adfoin_airtable_get_field_description( $field ),
    );
}

/**
 * Produce a simple description for Airtable fields.
 *
 * @param array $field
 *
 * @return string
 */
function adfoin_airtable_get_field_description( $field ) {
    $description = adfoin_airtable_get_field_help_text_by_type( isset( $field['type'] ) ? $field['type'] : '' );

    if ( isset( $field['options']['choices'] ) && is_array( $field['options']['choices'] ) ) {
        $choices = wp_list_pluck( $field['options']['choices'], 'name' );
        $choices = array_filter( array_map( 'trim', $choices ) );

        if ( ! empty( $choices ) ) {
            $description .= ( $description ? ' ' : '' ) . sprintf(
                __( 'Choices: %s', 'advanced-form-integration' ),
                implode( ', ', $choices )
            );
        }
    }

    return trim( $description );
}

/**
 * Prepare a value for Airtable before it is sent to the API.
 *
 * @param string $field_type
 * @param mixed  $value
 *
 * @return mixed|null
 */
function adfoin_airtable_format_field_value( $field_type, $value ) {
    if ( is_string( $value ) ) {
        $value = trim( $value );
    }

    switch ( $field_type ) {
        case 'multipleselects':
        case 'multiplelookupvalues':
            return adfoin_airtable_parse_multi_value( $value );

        case 'multiplerecordlinks':
            return adfoin_airtable_parse_multi_value( $value );

        case 'multiplecollaborators':
        case 'singlecollaborator':
        case 'collaborator':
            return adfoin_airtable_prepare_collaborators( $value );

        case 'multipleattachments':
        case 'multipleattachment':
            return adfoin_airtable_prepare_attachments( $value );

        case 'checkbox':
            return adfoin_airtable_string_to_boolean( $value );

        case 'rating':
        case 'duration':
            return adfoin_airtable_to_int( $value );

        case 'number':
        case 'percent':
        case 'currency':
            return adfoin_airtable_to_number( $value );

        case 'barcode':
            return adfoin_airtable_prepare_barcode_value( $value );
    }

    return $value;
}

/**
 * Parse CSV / JSON string input into an array.
 *
 * @param mixed $value
 *
 * @return array
 */
function adfoin_airtable_parse_multi_value( $value ) {
    if ( is_array( $value ) ) {
        return array_values( array_filter( $value, function( $single ) {
            return '' !== $single && null !== $single;
        } ) );
    }

    if ( ! is_string( $value ) || '' === trim( $value ) ) {
        return array();
    }

    $maybe_json = json_decode( $value, true );

    if ( JSON_ERROR_NONE === json_last_error() && is_array( $maybe_json ) ) {
        return $maybe_json;
    }

    $parts = explode( ',', $value );
    $parts = array_map( 'trim', $parts );
    $parts = array_filter( $parts, 'strlen' );

    return array_values( $parts );
}

/**
 * Turn a string input into a boolean for checkbox fields.
 *
 * @param mixed $value
 *
 * @return bool
 */
function adfoin_airtable_string_to_boolean( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_int( $value ) ) {
        return (bool) $value;
    }

    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

/**
 * Prepare collaborator payloads.
 *
 * @param mixed $value
 *
 * @return array
 */
function adfoin_airtable_prepare_collaborators( $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    $collaborators = array();
    $items         = adfoin_airtable_parse_multi_value( $value );

    foreach ( $items as $item ) {
        if ( filter_var( $item, FILTER_VALIDATE_EMAIL ) ) {
            $collaborators[] = array( 'email' => $item );
        } else {
            $collaborators[] = array( 'id' => $item );
        }
    }

    return $collaborators;
}

/**
 * Prepare attachment payloads.
 *
 * @param mixed $value
 *
 * @return array
 */
function adfoin_airtable_prepare_attachments( $value ) {
    if ( empty( $value ) && '0' !== $value ) {
        return array();
    }

    if ( is_array( $value ) ) {
        return $value;
    }

    $maybe_json = json_decode( $value, true );

    if ( JSON_ERROR_NONE === json_last_error() && is_array( $maybe_json ) ) {
        return $maybe_json;
    }

    $attachments = array();
    $items       = adfoin_airtable_parse_multi_value( $value );

    foreach ( $items as $item ) {
        if ( ! $item ) {
            continue;
        }

        $parts = array_map( 'trim', explode( '|', $item, 2 ) );
        $url   = $parts[0];

        if ( ! $url ) {
            continue;
        }

        $single_attachment = array( 'url' => $url );

        if ( isset( $parts[1] ) && $parts[1] ) {
            $single_attachment['filename'] = $parts[1];
        }

        $attachments[] = $single_attachment;
    }

    return $attachments;
}

/**
 * Convert numeric input to the best matching number.
 *
 * @param mixed $value
 *
 * @return mixed
 */
function adfoin_airtable_to_number( $value ) {
    if ( ! is_numeric( $value ) ) {
        return $value;
    }

    return false === strpos( (string) $value, '.' ) ? (int) $value : (float) $value;
}

/**
 * Force integer type for Airtable fields.
 *
 * @param mixed $value
 *
 * @return mixed
 */
function adfoin_airtable_to_int( $value ) {
    if ( '' === $value || null === $value ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return (int) $value;
    }

    return $value;
}

/**
 * Prepare barcode payloads.
 *
 * @param mixed $value
 *
 * @return mixed
 */
function adfoin_airtable_prepare_barcode_value( $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    $maybe_json = json_decode( $value, true );

    if ( JSON_ERROR_NONE === json_last_error() && is_array( $maybe_json ) ) {
        return $maybe_json;
    }

    if ( ! is_string( $value ) || '' === trim( $value ) ) {
        return null;
    }

	    return array( 'text' => $value );
	}

/**
 * Normalize Airtable field type strings.
 *
 * @param string $field_type
 *
 * @return string
 */
function adfoin_airtable_normalize_field_type( $field_type ) {
    if ( ! is_string( $field_type ) ) {
        return '';
    }

    return strtolower( sanitize_key( $field_type ) );
}
/**
 * Provide contextual help text for Airtable field types.
 *
 * @param string $field_type
 *
 * @return string
 */
function adfoin_airtable_get_field_help_text_by_type( $field_type ) {
    $field_type = adfoin_airtable_normalize_field_type( $field_type );

    switch ( $field_type ) {
        case 'multipleselects':
        case 'multiplelookupvalues':
        case 'multiplerecordlinks':
            return __( 'Use comma-separated values or a JSON array to select multiple items.', 'advanced-form-integration' );

        case 'multipleattachments':
        case 'multipleattachment':
            return __( 'Provide comma-separated URLs or a JSON array. Optionally append "|filename.ext" per URL.', 'advanced-form-integration' );

        case 'multiplecollaborators':
        case 'singlecollaborator':
        case 'collaborator':
            return __( 'Use comma-separated collaborator emails or IDs (JSON array is also supported).', 'advanced-form-integration' );

        case 'checkbox':
            return __( 'Use values like yes/true/1 to check the box, anything else leaves it unchecked.', 'advanced-form-integration' );

        case 'rating':
            return __( 'Provide an integer value for the rating.', 'advanced-form-integration' );

        case 'duration':
            return __( 'Provide total seconds for the duration field.', 'advanced-form-integration' );

        case 'percent':
        case 'currency':
        case 'number':
            return __( 'Provide a numeric value (decimals allowed).', 'advanced-form-integration' );

        case 'barcode':
            return __( 'Supply JSON {"text":"value"} or just the barcode text.', 'advanced-form-integration' );

        default:
            return '';
    }
}
