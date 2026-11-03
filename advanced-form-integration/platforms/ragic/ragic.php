<?php

add_filter( 'adfoin_action_providers', 'adfoin_ragic_actions', 10, 1 );

function adfoin_ragic_actions( $actions ) {
    $actions['ragic'] = array(
        'title' => __( 'Ragic', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Record', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get Ragic credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_token' and 'base_url' keys, or empty strings if not found
 */
function adfoin_ragic_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( wp_unslash( $_POST['credId'] ) );
    }

    $api_token = '';
    $base_url = '';
    $credentials = adfoin_read_credentials( 'ragic' );

    // 'legacy_123456' is the placeholder older editors used for "no account picked".
    if ( $cred_id && 'legacy_123456' !== $cred_id ) {
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_token = $single['api_token'];
                $base_url = $single['base_url'];
                break;
            }
        }
    } else {
        // No account picked: use the old single-account settings, as before,
        // or the first saved account when those settings don't exist.
        $api_token = get_option( 'adfoin_ragic_api_token' ) ? get_option( 'adfoin_ragic_api_token' ) : '';
        $base_url = get_option( 'adfoin_ragic_base_url' ) ? get_option( 'adfoin_ragic_base_url' ) : '';

        if ( ( ! $api_token || ! $base_url ) && ! empty( $credentials[0] ) ) {
            $api_token = isset( $credentials[0]['api_token'] ) ? $credentials[0]['api_token'] : '';
            $base_url  = isset( $credentials[0]['base_url'] ) ? $credentials[0]['base_url'] : '';
        }
    }

    return array(
        'api_token' => $api_token,
        'base_url' => $base_url
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ragic_settings_tab', 10, 1 );

function adfoin_ragic_settings_tab( $providers ) {
    $providers['ragic'] = __( 'Ragic', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ragic_settings_view', 10, 1 );

function adfoin_ragic_settings_view( $current_tab ) {
    if( $current_tab != 'ragic' ) return;

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_ragic_api_token' ) ? get_option( 'adfoin_ragic_api_token' ) : '';
    $old_base_url = get_option( 'adfoin_ragic_base_url' ) ? get_option( 'adfoin_ragic_base_url' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'ragic' );

    if ( ( $old_api_token || $old_base_url ) && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => wp_generate_uuid4(),
            'title' => 'Default Account (Legacy)',
            'api_token' => $old_api_token,
            'base_url' => $old_base_url
        );
        adfoin_save_credentials( 'ragic', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'base_url',
            'label'         => __( 'Base URL', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter Base URL', 'advanced-form-integration' ),
            'mask'          => false,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Ragic account settings.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to API settings and generate an API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your Base URL (e.g., https://yourcompany.ragic.com).', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter both API Key and Base URL in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'ragic', 'Ragic', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_ragic_credentials', 'adfoin_get_ragic_credentials' );
function adfoin_get_ragic_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'ragic' );
}

add_action( 'wp_ajax_adfoin_save_ragic_credentials', 'adfoin_save_ragic_credentials' );
function adfoin_save_ragic_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'ragic', array( 'api_token', 'base_url' ) );
}

add_action( 'wp_ajax_adfoin_get_ragic_credentials_list', 'adfoin_ragic_get_credentials_list_ajax' );
function adfoin_ragic_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_token', 'mask' => true ),
        array( 'name' => 'base_url', 'mask' => false ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'ragic', $fields );
}

add_action( 'admin_post_adfoin_save_ragic_api_token', 'adfoin_save_ragic_api_token' );

function adfoin_save_ragic_api_token() {
    // Authorization check
    adfoin_require_manage_options();

    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_ragic_settings' ) ) {
        die( __( 'Security check failed', 'advanced-form-integration' ) );
    }

    update_option( 'adfoin_ragic_api_token', sanitize_text_field( wp_unslash( $_POST['adfoin_ragic_api_token'] ) ) );
    update_option( 'adfoin_ragic_base_url', sanitize_text_field( wp_unslash( $_POST['adfoin_ragic_base_url'] ) ) );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=ragic' );
}


add_action( 'adfoin_action_fields', 'adfoin_ragic_action_fields', 10, 1 );

function adfoin_ragic_action_fields() {
    ?>
    <script type="text/template" id="ragic-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td class="afi-label" scope="row"><?php esc_html_e( 'Ragic Account', 'advanced-form-integration' ); ?></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="onAccountChange">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=ragic' ); ?>"
                       target="_blank"
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td class="afi-label" scope="row"><?php esc_html_e( 'Sheet', 'advanced-form-integration' ); ?></td>
                <td>
                    <select name="fieldData[sheetKey]" v-model="fielddata.sheetKey" @change="onSheetChange">
                        <option value=""> <?php _e( 'Enter sheet manually...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="sheet in sheets" :value="sheet.key">{{ sheet.label }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': sheetLoading || fieldsLoading}"></div>
                    <p class="description" v-if="fieldsMessage">{{ fieldsMessage }}</p>
                    <template v-if="fielddata.sheetKey">
                        <input type="hidden" name="fieldData[account_name]" :value="fielddata.account_name">
                        <input type="hidden" name="fieldData[tab]" :value="fielddata.tab">
                        <input type="hidden" name="fieldData[sheet_id]" :value="fielddata.sheet_id">
                    </template>
                </td>
            </tr>
            <editable-field v-for="field in visibleFields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}


function adfoin_ragic_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '', $query = array() ) {
    $credentials = adfoin_ragic_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];
    $base_url = $credentials['base_url'];

    if ( ! $api_token || ! $base_url ) {
        $response = new WP_Error( 'adfoin_ragic_no_account', __( 'No Ragic account is set up. Add one under AFI Settings > Ragic.', 'advanced-form-integration' ) );

        if ( $record ) {
            adfoin_add_to_log( $response, $endpoint, array( 'method' => $method, 'body' => $data ), $record );
        }

        return $response;
    }
    
    $base_url = preg_replace( '/^http:/i', 'https:', $base_url );
    if ( strpos( $base_url, 'https://' ) !== 0 ) {
        $base_url = 'https://' . ltrim( $base_url, '/' );
    }
    $url = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );
    $url = $url . '?api&v=3&APIKey=' . $api_token;

    if ( $query ) {
        $url .= '&' . http_build_query( $query );
    }

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            // 'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( adfoin_ragic_response_for_log( $response ), $url, $args, $record );
    }

    return $response;
}

/*
 * Ragic answers a rejected record with HTTP 200 and a body such as
 * {"status":"INVALID","msg":"Field Company contains empty value"}.
 * Log those as failures so they don't show up as "200 OK".
 */
function adfoin_ragic_response_for_log( $response ) {
    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return $response;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $decoded ) && isset( $decoded['status'] ) && 'SUCCESS' !== strtoupper( (string) $decoded['status'] ) ) {
        $response['response'] = array(
            'code'    => 400,
            'message' => 'Ragic ' . $decoded['status'] . ( ! empty( $decoded['msg'] ) ? ': ' . wp_strip_all_tags( $decoded['msg'] ) : '' ),
        );
    }

    return $response;
}

/*
 * Ragic paths look like {account}/{tab}/{sheetIndex}; tab paths from the API
 * start with a slash (e.g. "/ragicsales"), hand-typed ones usually don't.
 */
function adfoin_ragic_sheet_endpoint( $account_name, $tab, $sheet_id ) {
    return trim( $account_name, '/ ' ) . '/' . trim( $tab, '/ ' ) . '/' . trim( $sheet_id, '/ ' );
}

function adfoin_ragic_decode( $response ) {
    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return null;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! is_array( $decoded ) || ( isset( $decoded['status'] ) && 'SUCCESS' !== strtoupper( (string) $decoded['status'] ) ) ) {
        return null;
    }

    return $decoded;
}

add_action( 'wp_ajax_adfoin_get_ragic_sheets', 'adfoin_get_ragic_sheets', 10, 0 );

/*
 * List every sheet the account can see as "account|/tab|sheetIndex" => "Tab › Sheet".
 */
function adfoin_get_ragic_sheets() {
    adfoin_verify_nonce();

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $accounts = adfoin_ragic_decode( adfoin_ragic_request( '', 'GET', array(), array(), $cred_id ) );

    if ( null === $accounts ) {
        wp_send_json_error();
    }

    $sheets = array();

    foreach ( $accounts as $account_name => $account ) {
        if ( ! is_array( $account ) ) {
            continue;
        }

        // The account list can come back without tabs; the account itself has them.
        if ( empty( $account['children'] ) ) {
            $single  = adfoin_ragic_decode( adfoin_ragic_request( rawurlencode( $account_name ), 'GET', array(), array(), $cred_id ) );
            $account = isset( $single[ $account_name ] ) ? $single[ $account_name ] : $account;
        }

        if ( empty( $account['children'] ) || ! is_array( $account['children'] ) ) {
            continue;
        }

        foreach ( $account['children'] as $tab_path => $tab ) {
            if ( empty( $tab['children'] ) || ! is_array( $tab['children'] ) ) {
                continue;
            }

            foreach ( $tab['children'] as $sheet_index => $sheet ) {
                if ( ! isset( $sheet['type'] ) || 'sheet' !== $sheet['type'] ) {
                    continue;
                }

                $label = trim( $tab['name'] ) . ' › ' . trim( $sheet['name'] );

                if ( count( $accounts ) > 1 ) {
                    $label = $account_name . ': ' . $label;
                }

                $sheets[] = array(
                    'key'   => $account_name . '|' . $tab_path . '|' . $sheet_index,
                    'label' => $label,
                );
            }
        }
    }

    wp_send_json_success( $sheets );
}

add_action( 'wp_ajax_adfoin_get_ragic_fields', 'adfoin_get_ragic_fields', 10, 0 );

/*
 * List a sheet's fields as ragic__{fieldId} => name. The schema endpoint is
 * only open to account managers, so fall back to reading one record twice:
 * once keyed by field ID and once by field name, which share the same order.
 */
function adfoin_get_ragic_fields() {
    adfoin_verify_nonce();

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $account  = isset( $_POST['account'] ) ? sanitize_text_field( wp_unslash( $_POST['account'] ) ) : '';
    $tab      = isset( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : '';
    $sheet_id = isset( $_POST['sheetId'] ) ? sanitize_text_field( wp_unslash( $_POST['sheetId'] ) ) : '';

    if ( ! $account || ! $tab || '' === $sheet_id ) {
        wp_send_json_error();
    }

    $endpoint = adfoin_ragic_sheet_endpoint( $account, $tab, $sheet_id );
    $fields   = array();
    $schema   = adfoin_ragic_decode( adfoin_ragic_request( $endpoint . '/metadata/schema', 'GET', array(), array(), $cred_id ) );

    if ( isset( $schema['fields'] ) && is_array( $schema['fields'] ) ) {
        foreach ( $schema['fields'] as $field ) {
            if ( empty( $field['fieldId'] ) || ! empty( $field['readOnly'] ) || ! empty( $field['computed'] ) ) {
                continue;
            }

            $fields[] = array(
                'key'      => 'ragic__' . $field['fieldId'],
                'value'    => isset( $field['fieldName'] ) ? $field['fieldName'] : $field['fieldId'],
                'required' => ! empty( $field['required'] ),
            );
        }

        wp_send_json_success( $fields );
    }

    $by_id = adfoin_ragic_decode( adfoin_ragic_request( $endpoint, 'GET', array(), array(), $cred_id, array( 'limit' => 1, 'subtables' => 0, 'naming' => 'EID' ) ) );
    $by_id = $by_id ? reset( $by_id ) : null;

    if ( ! is_array( $by_id ) || ! isset( $by_id['_ragicId'] ) ) {
        wp_send_json_error( array( 'reason' => 'empty' ) );
    }

    $by_name = adfoin_ragic_decode( adfoin_ragic_request( $endpoint . '/' . (int) $by_id['_ragicId'], 'GET', array(), array(), $cred_id, array( 'subtables' => 0 ) ) );
    $by_name = $by_name ? reset( $by_name ) : null;

    $strip = function( $row ) {
        return is_array( $row ) ? array_values( array_filter( array_keys( $row ), function( $key ) {
            return 0 !== strpos( (string) $key, '_' );
        } ) ) : array();
    };

    $ids   = $strip( $by_id );
    $names = $strip( $by_name );

    if ( ! $ids || count( $ids ) !== count( $names ) ) {
        wp_send_json_error();
    }

    foreach ( $ids as $i => $field_id ) {
        $fields[] = array(
            'key'      => 'ragic__' . $field_id,
            'value'    => $names[ $i ],
            'required' => false,
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_ragic_job_queue', 'adfoin_ragic_job_queue', 10, 1 );

function adfoin_ragic_job_queue( $data ) {
    adfoin_ragic_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ragic_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    
    if ( isset( $record_data["action_data"]["cl"] ) && adfoin_check_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) return;

    $data       = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $account_name = isset( $data['account_name'] ) ? $data['account_name'] : '';
    $tab    = isset( $data['tab'] ) ? $data['tab'] : '';
    $sheet_id     = isset( $data['sheet_id'] ) ? $data['sheet_id'] : '';
    $task       = $record['task'];

    if( $task == 'subscribe' ) {
        $endpoint = adfoin_ragic_sheet_endpoint( $account_name, $tab, $sheet_id );

        $subscription_data = array();

        // Fields picked from the sheet: ragic__{fieldId} => value.
        foreach ( $data as $key => $value ) {
            if ( 0 !== strpos( $key, 'ragic__' ) || '' === $value || null === $value ) {
                continue;
            }

            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

            if ( '' !== trim( (string) $parsed_value ) ) {
                $subscription_data[ substr( $key, 7 ) ] = $parsed_value;
            }
        }

        // Extra fields typed as fieldId=value, separated by ||.
        foreach ( $data as $key => $value ) {
            if( preg_match( '/^field\d+$/', $key ) && $value ) {
                $pairs = explode( '||', $value );
                foreach ( $pairs as $pair ) {
                    $exploded = explode( '=', $pair, 2 );
                    $key   = trim( $exploded[0] );
                    $parsed_value = isset( $exploded[1] ) && $exploded[1] ? adfoin_get_parsed_values( $exploded[1], $posted_data ) : '';

                    if ( $parsed_value ) {
                        $subscription_data[ $key ] = $parsed_value;
                    }
                }
            }
        }

        adfoin_ragic_request( $endpoint, 'POST', $subscription_data, $record, $cred_id );
    }
}
