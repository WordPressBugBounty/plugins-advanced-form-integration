<?php

add_filter( 'adfoin_action_providers', 'adfoin_sendlane_actions', 10, 1 );

function adfoin_sendlane_actions( $actions ) {

    $actions['sendlane'] = array(
        'title' => __( 'Sendlane', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add/Update Contact', 'advanced-form-integration' ),
            'unsubscribe' => __( 'Unsubscribe Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

/**
 * Get Sendlane credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key', 'api_secret', 'subdomain' keys, or empty strings if not found
 */
function adfoin_sendlane_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_key = '';
    $api_secret = '';
    $subdomain = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'sendlane' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                $api_secret = $single['api_secret'];
                $subdomain = $single['subdomain'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = get_option( 'adfoin_sendlane_api_key', '' );
        $api_secret = get_option( 'adfoin_sendlane_api_secret', '' );
        $subdomain = get_option( 'adfoin_sendlane_subdomain', '' );
    }

    return array(
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'subdomain' => $subdomain
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sendlane_settings_tab', 10, 1 );

function adfoin_sendlane_settings_tab( $providers ) {
    $providers['sendlane'] = __( 'Sendlane', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendlane_settings_view', 10, 1 );

function adfoin_sendlane_settings_view( $current_tab ) {
    if ( 'sendlane' !== $current_tab ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = get_option( 'adfoin_sendlane_api_key', '' );
    $old_api_secret = get_option( 'adfoin_sendlane_api_secret', '' );
    $old_subdomain = get_option( 'adfoin_sendlane_subdomain', '' );
    
    $existing_creds = adfoin_read_credentials( 'sendlane' );

    if ( $old_api_key && $old_api_secret && $old_subdomain && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'api_key' => $old_api_key,
            'api_secret' => $old_api_secret,
            'subdomain' => $old_subdomain
        );
        adfoin_save_credentials( 'sendlane', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'subdomain',
            'label'         => __( 'Sendlane Subdomain', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter Subdomain (example: mybrand)', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_key',
            'label'         => __( 'Sendlane API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_secret',
            'label'         => __( 'Sendlane API Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter API Secret', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to <a href="https://app.sendlane.com/integrations/api" target="_blank" rel="noopener noreferrer">Sendlane API</a> page.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Create credentials and copy your API key, secret, and subdomain.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the credentials in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'sendlane', 'Sendlane', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendlane_credentials', 'adfoin_get_sendlane_credentials' );
function adfoin_get_sendlane_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendlane' );
}

add_action( 'wp_ajax_adfoin_save_sendlane_credentials', 'adfoin_save_sendlane_credentials' );
function adfoin_save_sendlane_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendlane', array( 'subdomain', 'api_key', 'api_secret' ) );
}

add_action( 'wp_ajax_adfoin_get_sendlane_credentials_list', 'adfoin_sendlane_get_credentials_list_ajax' );
function adfoin_sendlane_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'subdomain', 'mask' => false ),
        array( 'name' => 'api_key', 'mask' => true ),
        array( 'name' => 'api_secret', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendlane', $fields );
}

add_action( 'adfoin_add_js_fields', 'adfoin_sendlane_js_fields', 10, 1 );

function adfoin_sendlane_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_sendlane_action_fields' );

function adfoin_sendlane_action_fields() {
    ?>
    <script type="text/template" id="sendlane-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row"><?php esc_html_e( 'Sendlane Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendlane' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Sendlane List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""><?php _e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index">{{ item }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            v-bind:key="field.value"
                            v-bind:field="field"
                            v-bind:trigger="trigger"
                            v-bind:action="action"
                            v-bind:fielddata="fielddata"></editable-field>
            <?php
            if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
                ?>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Using Pro Features', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'For tags, automation triggers, and custom fields, create a <a href="%s">new integration</a> and select Sendlane [PRO].', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-new' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }

            if ( adfoin_fs()->is_not_paying() ) {
                ?>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Go Pro', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'Unlock tags and automations by <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_sendlane_lists', 'adfoin_get_sendlane_lists', 10, 0 );

function adfoin_get_sendlane_lists() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_sendlane_get_credentials();
    
    if ( ! $credentials['api_key'] || ! $credentials['api_secret'] || ! $credentials['subdomain'] ) {
        wp_send_json_error( __( 'Sendlane credentials are missing.', 'advanced-form-integration' ) );
        return;
    }

    $lists = array();
    $page  = 1;
    $limit = 50;
    $safe  = 0;

    do {
        $safe++;
        $endpoint = sprintf( 'contacts/lists?page=%d&limit=%d', $page, $limit );
        $response = adfoin_sendlane_request( $endpoint, 'GET', array(), array(), $credentials );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            foreach ( $body['data'] as $list ) {
                if ( isset( $list['id'], $list['name'] ) ) {
                    $lists[ $list['id'] ] = $list['name'];
                }
            }
        }

        if ( isset( $body['meta']['pagination']['next_page'] ) && $body['meta']['pagination']['next_page'] ) {
            $page = (int) $body['meta']['pagination']['next_page'];
        } else {
            $page = 0;
        }

    } while ( $page && $safe < 10 );

    wp_send_json_success( $lists );
}

function adfoin_sendlane_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = null ) {
    if ( ! $credentials ) {
        $credentials = adfoin_sendlane_get_credentials();
    }
    
    $api_key = $credentials['api_key'];
    $api_secret = $credentials['api_secret'];
    $subdomain = $credentials['subdomain'];

    if ( ! $api_key || ! $api_secret || ! $subdomain ) {
        return new WP_Error( 'adfoin_sendlane_missing_credentials', __( 'Sendlane credentials are missing.', 'advanced-form-integration' ) );
    }

    $endpoint = ltrim( $endpoint, '/' );
    $url      = sprintf( 'https://%s.sendlane.com/api/v1/%s', $subdomain, $endpoint );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'API-KEY'       => $api_key,
            'API-SECRET'    => $api_secret,
            'API-ID'        => $subdomain,
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_sendlane_job_queue', 'adfoin_sendlane_job_queue', 10, 1 );

function adfoin_sendlane_job_queue( $data ) {
    adfoin_sendlane_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sendlane_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === $record_data['action_data']['cl']['active'] ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';
    
    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $credentials = adfoin_sendlane_get_credentials( $cred_id );

    $list_id = isset( $field_data['listId'] ) ? $field_data['listId'] : '';
    $email   = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );
    $first   = empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
    $last    = empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data );

    if ( ! $list_id || ! $email ) {
        return;
    }

    if ( 'unsubscribe' === $task ) {
        $payload = array(
            'emails' => array( $email ),
        );

        adfoin_sendlane_request( sprintf( 'contacts/lists/%s/unsubscribe', $list_id ), 'POST', $payload, $record, $credentials );

        return;
    }

    if ( 'subscribe' !== $task ) {
        return;
    }

    $payload = array(
        'emails' => array(
            array_filter( array(
                'email'      => $email,
                'first_name' => $first,
                'last_name'  => $last,
            ) ),
        ),
    );

    adfoin_sendlane_request( sprintf( 'contacts/lists/%s/subscribe', $list_id ), 'POST', $payload, $record, $credentials );
}
