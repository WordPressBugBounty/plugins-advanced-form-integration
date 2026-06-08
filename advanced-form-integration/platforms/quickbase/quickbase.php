<?php

/**
 * Quickbase — Add Record via POST https://api.quickbase.com/v1/records.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: QB-USER-TOKEN header + QB-Realm-Hostname header.
 *
 * @link https://developer.quickbase.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_quickbase_actions', 10, 1 );

function adfoin_quickbase_actions( $actions ) {
    $actions['quickbase'] = array(
        'title' => __( 'Quickbase', 'advanced-form-integration' ),
        'tasks' => array(
            'add_record' => __( 'Add Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_quickbase_settings_tab', 10, 1 );

function adfoin_quickbase_settings_tab( $providers ) {
    $providers['quickbase'] = __( 'Quickbase', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_quickbase_settings_view', 10, 1 );

function adfoin_quickbase_settings_view( $current_tab ) {
    if ( 'quickbase' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'realmHostname',
            'label'         => __( 'Realm Hostname', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'e.g. mycompany.quickbase.com', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'userToken',
            'label'         => __( 'User Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Enter your Quickbase User Token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Quickbase and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://login.quickbase.com/db/main?a=myPreferences">My Preferences</a>' ),
        __( 'Under "My User Information", click "Manage my user tokens".', 'advanced-form-integration' ),
        __( 'Click "+ New user token", name it (e.g. WordPress), and assign it to the apps you want to integrate.', 'advanced-form-integration' ),
        __( 'Copy the User Token value and paste it here.', 'advanced-form-integration' ),
        __( 'Enter your Realm Hostname (the subdomain of your Quickbase URL, e.g. mycompany.quickbase.com).', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'quickbase', __( 'Quickbase', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_quickbase_credentials', 'adfoin_get_quickbase_credentials', 10, 0 );

function adfoin_get_quickbase_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'quickbase' );
}

add_action( 'wp_ajax_adfoin_save_quickbase_credentials', 'adfoin_save_quickbase_credentials', 10, 0 );

function adfoin_save_quickbase_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'quickbase', array( 'realmHostname', 'userToken' ) );
}

// Legacy single-account import: surfaces old credentials store entries
// that used the pre-modern `accessToken` key as a Legacy Account record.
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        return;
    }
    add_filter( 'adfoin_get_credentials', 'adfoin_quickbase_legacy_credentials_migration', 5, 2 );
}, 20 );

function adfoin_quickbase_legacy_credentials_migration( $credentials, $platform ) {
    if ( 'quickbase' !== $platform || ! is_array( $credentials ) ) {
        return $credentials;
    }

    foreach ( $credentials as &$cred ) {
        if ( empty( $cred['userToken'] ) && ! empty( $cred['accessToken'] ) ) {
            $cred['userToken'] = $cred['accessToken'];
        }
    }
    unset( $cred );

    return $credentials;
}

add_action( 'adfoin_action_fields', 'adfoin_quickbase_action_fields' );

function adfoin_quickbase_action_fields() {
    ?>
    <script type="text/template" id="quickbase-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_record'">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldLoading}"></div></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_record'">
                <td><label><?php esc_html_e( 'Quickbase Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getApps">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=quickbase' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_record'">
                <td><label><?php esc_html_e( 'App', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[appId]" v-model="fielddata.appId" @change="getTables">
                        <option value=""><?php esc_html_e( 'Select App...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.apps" :value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': appLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_record'">
                <td><label><?php esc_html_e( 'Table', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[tableId]" v-model="fielddata.tableId" @change="getFields">
                        <option value=""><?php esc_html_e( 'Select Table...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.tables" :value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': tableLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" :key="field.value" :field="field" :trigger="trigger" :action="action" :fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_quickbase_apps', 'adfoin_get_quickbase_apps' );

function adfoin_get_quickbase_apps() {
    adfoin_verify_nonce();

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_quickbase_request( 'apps', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $apps = array();

    if ( is_array( $body ) ) {
        foreach ( $body as $app ) {
            if ( isset( $app['id'], $app['name'] ) ) {
                $apps[ $app['id'] ] = $app['name'];
            }
        }
    }

    wp_send_json_success( $apps );
}

add_action( 'wp_ajax_adfoin_get_quickbase_tables', 'adfoin_get_quickbase_tables' );

function adfoin_get_quickbase_tables() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $app_id  = isset( $_POST['appId'] )  ? sanitize_text_field( wp_unslash( $_POST['appId'] ) )  : '';

    if ( ! $app_id ) {
        wp_send_json_error();
    }

    $response = adfoin_quickbase_request( 'tables?appId=' . rawurlencode( $app_id ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body   = json_decode( wp_remote_retrieve_body( $response ), true );
    $tables = array();

    if ( is_array( $body ) ) {
        foreach ( $body as $table ) {
            if ( isset( $table['id'], $table['name'] ) ) {
                $tables[ $table['id'] ] = $table['name'];
            }
        }
    }

    wp_send_json_success( $tables );
}

add_action( 'wp_ajax_adfoin_get_quickbase_fields', 'adfoin_get_quickbase_fields' );

function adfoin_get_quickbase_fields() {
    adfoin_verify_nonce();

    $cred_id  = isset( $_POST['credId'] )  ? sanitize_text_field( wp_unslash( $_POST['credId'] ) )  : '';
    $table_id = isset( $_POST['tableId'] ) ? sanitize_text_field( wp_unslash( $_POST['tableId'] ) ) : '';

    if ( ! $table_id ) {
        wp_send_json_error();
    }

    $response = adfoin_quickbase_request( 'fields?tableId=' . rawurlencode( $table_id ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body   = json_decode( wp_remote_retrieve_body( $response ), true );
    $fields = array();

    // Quickbase derived field types that can't be set on insert.
    $skip_types = array( 'recordid', 'dblink', 'timestamp', 'address', 'file', 'summary', 'lookup' );

    if ( is_array( $body ) ) {
        foreach ( $body as $field ) {
            if ( empty( $field['id'] ) || empty( $field['label'] ) || empty( $field['fieldType'] ) ) {
                continue;
            }
            if ( in_array( $field['fieldType'], $skip_types, true ) ) {
                continue;
            }
            // Computed fields (formula/summary/lookup) carry a `mode` property.
            if ( ! empty( $field['mode'] ) ) {
                continue;
            }

            $fields[] = array(
                'key'         => (string) $field['id'],
                'value'       => $field['label'],
                'description' => adfoin_quickbase_field_description( $field ),
            );
        }
    }

    wp_send_json_success( $fields );
}

function adfoin_quickbase_field_description( $field ) {
    $type = isset( $field['fieldType'] ) ? $field['fieldType'] : '';

    switch ( $type ) {
        case 'checkbox':
            return __( 'Use yes/true/1 to check the box; anything else leaves it unchecked.', 'advanced-form-integration' );
        case 'multitext':
        case 'text-multiple-choice':
            return __( 'Use comma-separated values for multiple selections.', 'advanced-form-integration' );
        case 'date':
            return __( 'Format: YYYY-MM-DD', 'advanced-form-integration' );
        case 'datetime':
            return __( 'Format: ISO 8601 (e.g. 2025-12-31T14:30:00Z).', 'advanced-form-integration' );
        case 'user':
            return __( 'Provide a Quickbase user email or user ID.', 'advanced-form-integration' );
        case 'numeric':
        case 'currency':
        case 'percent':
        case 'rating':
        case 'duration':
            return __( 'Provide a numeric value.', 'advanced-form-integration' );
        default:
            return '';
    }
}

function adfoin_quickbase_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'quickbase', $cred_id );
    $token       = isset( $credentials['userToken'] )     ? $credentials['userToken']     : ( $credentials['accessToken']   ?? '' );
    $realm       = isset( $credentials['realmHostname'] ) ? $credentials['realmHostname'] : '';

    $url  = 'https://api.quickbase.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'QB-Realm-Hostname' => $realm,
            'User-Agent'        => 'AdvancedFormIntegrationWP',
            'Authorization'     => 'QB-USER-TOKEN ' . $token,
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_quickbase_job_queue', 'adfoin_quickbase_job_queue', 10, 1 );

function adfoin_quickbase_job_queue( $data ) {
    adfoin_quickbase_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_quickbase_send_data( $record, $posted_data ) {
    if ( 'add_record' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId']  ?? '';
    $table_id   = $field_data['tableId'] ?? '';

    if ( ! $cred_id || ! $table_id ) {
        return;
    }

    unset( $field_data['credId'], $field_data['appId'], $field_data['tableId'], $field_data['apps'], $field_data['tables'] );

    $row = array();

    foreach ( $field_data as $field_id => $value ) {
        if ( '' === $value || null === $value ) {
            continue;
        }
        if ( ! ctype_digit( (string) $field_id ) ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        $parsed = is_string( $parsed ) ? trim( $parsed ) : $parsed;

        if ( '' === $parsed && '0' !== $parsed ) {
            continue;
        }

        $row[ (int) $field_id ] = array( 'value' => $parsed );
    }

    if ( empty( $row ) ) {
        return;
    }

    $payload = array(
        'to'   => $table_id,
        'data' => array( $row ),
    );

    adfoin_quickbase_request( 'records', 'POST', $payload, $record, $cred_id );
}
