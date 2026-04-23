<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_notion_actions',
    10,
    1
);
function adfoin_notion_actions(  $actions  ) {
    $actions['notion'] = array(
        'title' => __( 'Notion', 'advanced-form-integration' ),
        'tasks' => array(
            'add_item' => __( 'Add Database Item', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_notion_settings_tab',
    10,
    1
);
function adfoin_notion_settings_tab(  $providers  ) {
    $providers['notion'] = __( 'Notion', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_notion_settings_view',
    10,
    1
);
function adfoin_notion_settings_view(  $current_tab  ) {
    if ( $current_tab != 'notion' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiToken',
        'label'         => __( 'Internal Integration Token', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your Notion integration token', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Go to %s and click "New integration".', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://www.notion.so/my-integrations">https://www.notion.so/my-integrations</a>' ),
        __( 'Give your integration a name and select the workspace.', 'advanced-form-integration' ),
        __( 'Click "Submit" to create the integration.', 'advanced-form-integration' ),
        __( 'Copy the "Internal Integration Token" and paste it here.', 'advanced-form-integration' ),
        __( 'Important: Share your databases with the integration by clicking "..." > "Connections" > select your integration.', 'advanced-form-integration' )
    );
    ADFOIN_Account_Manager::render_settings_view(
        'notion',
        __( 'Notion', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_notion_credentials',
    'adfoin_get_notion_credentials',
    10,
    0
);
/*
 * Get Notion credentials
 */
function adfoin_get_notion_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'notion' );
}

add_action(
    'wp_ajax_adfoin_save_notion_credentials',
    'adfoin_save_notion_credentials',
    10,
    0
);
/*
 * Save Notion credentials
 */
function adfoin_save_notion_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'notion', array('apiToken') );
}

/*
 * Notion Credentials List
 */
function adfoin_notion_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'notion' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_notion_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_notion_modify_credentials(  $credentials, $platform  ) {
    if ( 'notion' == $platform && empty( $credentials ) ) {
        $api_token = get_option( 'adfoin_notion_api_token' );
        if ( $api_token ) {
            $credentials = array(array(
                'id'       => 'legacy',
                'title'    => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiToken' => $api_token,
            ));
        }
    }
    return $credentials;
}

add_action( 'adfoin_action_fields', 'adfoin_notion_action_fields' );
function adfoin_notion_action_fields() {
    ?>
    <script type="text/template" id="notion-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_item'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_item'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Notion Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getDatabases">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=notion' );
    ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_item'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Database', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[databaseId]" v-model="fielddata.databaseId" required="required" @change="getFields">
                        <option value=""> <?php 
    _e( 'Select Database...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.databases" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': databaseLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'add_item'">
                        <th scope="row">
                            <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                        </th>
                        <td scope="row">
                            <span><?php 
        printf( __( 'To unlock advanced features like update existing items, multi-select, relations, and page content, consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
 * Notion API Request
 */
function adfoin_notion_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'notion', $cred_id );
    $api_token = ( isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '' );
    // Backward compatibility: fallback to old option if credentials not found
    if ( empty( $api_token ) ) {
        $api_token = ( get_option( 'adfoin_notion_api_token' ) ? get_option( 'adfoin_notion_api_token' ) : '' );
    }
    $base_url = 'https://api.notion.com/v1/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Bearer ' . $api_token,
            'Notion-Version' => '2022-06-28',
        ),
    );
    if ( 'POST' == $method || 'PATCH' == $method || 'PUT' == $method ) {
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
    'wp_ajax_adfoin_get_notion_databases',
    'adfoin_get_notion_databases',
    10,
    0
);
/*
 * Get Notion Databases
 */
function adfoin_get_notion_databases() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $search_data = array(
        'filter' => array(
            'value'    => 'database',
            'property' => 'object',
        ),
        'sort'   => array(
            'direction' => 'descending',
            'timestamp' => 'last_edited_time',
        ),
    );
    $all_databases = array();
    $has_more = true;
    $start_cursor = null;
    while ( $has_more ) {
        if ( $start_cursor ) {
            $search_data['start_cursor'] = $start_cursor;
        }
        $response = adfoin_notion_request(
            'search',
            'POST',
            $search_data,
            array(),
            $cred_id
        );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['results'] ) && is_array( $body['results'] ) ) {
            foreach ( $body['results'] as $database ) {
                if ( isset( $database['id'] ) ) {
                    $title = '';
                    if ( isset( $database['title'] ) && is_array( $database['title'] ) ) {
                        foreach ( $database['title'] as $title_part ) {
                            if ( isset( $title_part['plain_text'] ) ) {
                                $title .= $title_part['plain_text'];
                            }
                        }
                    }
                    $all_databases[$database['id']] = ( $title ? $title : __( 'Untitled', 'advanced-form-integration' ) );
                }
            }
        }
        $has_more = ( isset( $body['has_more'] ) ? $body['has_more'] : false );
        $start_cursor = ( isset( $body['next_cursor'] ) ? $body['next_cursor'] : null );
        // Limit to prevent infinite loops
        if ( count( $all_databases ) > 500 ) {
            break;
        }
    }
    wp_send_json_success( $all_databases );
}

add_action(
    'wp_ajax_adfoin_get_notion_fields',
    'adfoin_get_notion_fields',
    10,
    0
);
/*
 * Get Notion Database Fields/Properties
 */
function adfoin_get_notion_fields() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $database_id = ( isset( $_POST['databaseId'] ) ? sanitize_text_field( $_POST['databaseId'] ) : '' );
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    if ( empty( $database_id ) ) {
        wp_send_json_error();
    }
    $response = adfoin_notion_request(
        'databases/' . $database_id,
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $fields = array();
    // Supported property types in basic version
    $supported_types = array(
        'title',
        'rich_text',
        'number',
        'select',
        'checkbox',
        'date',
        'email',
        'phone_number',
        'url'
    );
    if ( isset( $body['properties'] ) && is_array( $body['properties'] ) ) {
        foreach ( $body['properties'] as $name => $property ) {
            $type = ( isset( $property['type'] ) ? $property['type'] : '' );
            if ( !in_array( $type, $supported_types ) ) {
                continue;
            }
            $description = adfoin_notion_get_field_description( $property );
            $fields[] = array(
                'key'         => $type . '__' . $name,
                'value'       => $name,
                'description' => $description,
            );
        }
    }
    wp_send_json_success( $fields );
}

/*
 * Get field description based on property type
 */
function adfoin_notion_get_field_description(  $property  ) {
    $type = ( isset( $property['type'] ) ? $property['type'] : '' );
    $description = '';
    switch ( $type ) {
        case 'title':
            $description = __( 'Required. The title of the database item.', 'advanced-form-integration' );
            break;
        case 'rich_text':
            $description = __( 'Text content.', 'advanced-form-integration' );
            break;
        case 'number':
            $description = __( 'Numeric value.', 'advanced-form-integration' );
            break;
        case 'select':
            if ( isset( $property['select']['options'] ) && is_array( $property['select']['options'] ) ) {
                $options = wp_list_pluck( $property['select']['options'], 'name' );
                if ( !empty( $options ) ) {
                    $description = sprintf( __( 'Options: %s', 'advanced-form-integration' ), implode( ', ', $options ) );
                }
            }
            break;
        case 'checkbox':
            $description = __( 'Use "true", "yes", or "1" to check. Anything else unchecks.', 'advanced-form-integration' );
            break;
        case 'date':
            $description = __( 'Date in ISO 8601 format (e.g., 2024-01-15 or 2024-01-15T10:30:00).', 'advanced-form-integration' );
            break;
        case 'email':
            $description = __( 'Email address.', 'advanced-form-integration' );
            break;
        case 'phone_number':
            $description = __( 'Phone number.', 'advanced-form-integration' );
            break;
        case 'url':
            $description = __( 'URL/Link.', 'advanced-form-integration' );
            break;
    }
    return $description;
}

add_action(
    'adfoin_notion_job_queue',
    'adfoin_notion_job_queue',
    10,
    1
);
function adfoin_notion_job_queue(  $data  ) {
    adfoin_notion_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Notion API
 */
function adfoin_notion_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $database_id = ( isset( $data['databaseId'] ) ? $data['databaseId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'notion' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( empty( $database_id ) ) {
        return;
    }
    if ( $task == 'add_item' ) {
        // Remove non-field data
        unset($data['databaseId']);
        unset($data['databases']);
        unset($data['credId']);
        $properties = array();
        foreach ( $data as $key => $value ) {
            if ( empty( $value ) ) {
                continue;
            }
            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
            $parsed_value_trimmed = ( is_string( $parsed_value ) ? trim( $parsed_value ) : $parsed_value );
            if ( '' === $parsed_value_trimmed ) {
                continue;
            }
            $field_parts = explode( '__', $key, 2 );
            if ( count( $field_parts ) < 2 ) {
                continue;
            }
            list( $field_type, $field_name ) = $field_parts;
            $formatted_property = adfoin_notion_format_property( $field_type, $parsed_value );
            if ( null !== $formatted_property ) {
                $properties[$field_name] = $formatted_property;
            }
        }
        if ( empty( $properties ) ) {
            return;
        }
        $page_data = array(
            'parent'     => array(
                'database_id' => $database_id,
            ),
            'properties' => $properties,
        );
        $response = adfoin_notion_request(
            'pages',
            'POST',
            $page_data,
            $record,
            $cred_id
        );
    }
    return;
}

/*
 * Format property value for Notion API
 */
function adfoin_notion_format_property(  $type, $value  ) {
    if ( is_string( $value ) ) {
        $value = trim( $value );
    }
    switch ( $type ) {
        case 'title':
            return array(
                'title' => array(array(
                    'text' => array(
                        'content' => $value,
                    ),
                )),
            );
        case 'rich_text':
            return array(
                'rich_text' => array(array(
                    'text' => array(
                        'content' => $value,
                    ),
                )),
            );
        case 'number':
            if ( !is_numeric( $value ) ) {
                return null;
            }
            return array(
                'number' => floatval( $value ),
            );
        case 'select':
            return array(
                'select' => array(
                    'name' => $value,
                ),
            );
        case 'checkbox':
            $bool_value = in_array( strtolower( $value ), array(
                'true',
                'yes',
                '1',
                'on'
            ), true );
            return array(
                'checkbox' => $bool_value,
            );
        case 'date':
            // Try to parse the date
            $timestamp = strtotime( $value );
            if ( false === $timestamp ) {
                return null;
            }
            return array(
                'date' => array(
                    'start' => date( 'Y-m-d', $timestamp ),
                ),
            );
        case 'email':
            return array(
                'email' => $value,
            );
        case 'phone_number':
            return array(
                'phone_number' => $value,
            );
        case 'url':
            return array(
                'url' => $value,
            );
    }
    return null;
}
