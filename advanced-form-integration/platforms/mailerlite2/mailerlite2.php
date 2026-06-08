<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailerlite2_actions', 10, 1 );

function adfoin_mailerlite2_actions( $actions ) {

    $actions['mailerlite2'] = array(
        'title' => __( 'MailerLite', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To Group', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailerlite2_settings_tab', 10, 1 );

function adfoin_mailerlite2_settings_tab( $providers ) {
    $providers['mailerlite2'] = __( 'MailerLite', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailerlite2_settings_view', 10, 1 );

function adfoin_mailerlite2_settings_view( $current_tab ) {
    if( $current_tab != 'mailerlite2' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'MailerLite API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Enter API Token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Go to Integrations > API in MailerLite.', 'advanced-form-integration' ),
        __( 'Generate an API token with required scopes.', 'advanced-form-integration' ),
        __( 'Paste the token here and save.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'mailerlite2', __( 'MailerLite', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailerlite2_credentials', 'adfoin_get_mailerlite2_credentials', 10, 0 );
function adfoin_get_mailerlite2_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailerlite2' );
}

add_action( 'wp_ajax_adfoin_save_mailerlite2_credentials', 'adfoin_save_mailerlite2_credentials', 10, 0 );
function adfoin_save_mailerlite2_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailerlite2', array( 'apiKey' ) );
}

// Legacy single-account import: surfaces old `adfoin_mailerlite2_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'mailerlite2', array(
            'apiKey' => 'adfoin_mailerlite2_api_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_mailerlite2_action_fields' );

function adfoin_mailerlite2_action_fields() {
    ?>
    <script type="text/template" id="mailerlite2-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'subscribe_to_group'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :key="cred.id" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailerlite2' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'subscribe_to_group'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'MailerLite Group', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select Group...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'MailerLite [PRO]', 'custom fields' ); ?>
            
        </table>
    </script>


    <?php
}

function adfoin_mailerlite2_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {

    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '' );
    if ( isset( $data['credId'] ) ) {
        unset( $data['credId'] );
    }

    $credentials = adfoin_get_credentials_by_id( 'mailerlite2', $cred_id );
    $api_token   = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if( !$api_token ) {
        $api_token = get_option( 'adfoin_mailerlite2_api_key' ) ? get_option( 'adfoin_mailerlite2_api_key' ) : '';
    }

    if( !$api_token ) {
        return new WP_Error( 'missing_api_key', __( 'MailerLite API key not found', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'User-Agent' => 'advanced-form-integration',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $api_token
        )
    );

    $base_url = 'https://connect.mailerlite.com/api/';
    $url      = $base_url . $endpoint;

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    // MailerLite returns HTTP 429 with a Retry-After header when rate limited.
    // Back off once (bounded) and retry rather than failing the submission.
    if( 429 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
        $wait        = is_numeric( $retry_after ) ? max( 1, min( (int) $retry_after, 30 ) ) : 2;
        sleep( $wait );
        $response = wp_remote_request( $url, $args );
    }

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

/*
 * Build a readable message from a MailerLite API error response so AJAX
 * handlers can tell the user WHY a fetch failed (bad/expired token, etc.)
 * instead of leaving an empty dropdown with no explanation.
 */
function adfoin_mailerlite2_error_message( $response, $code = 0 ) {
    if( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $msg  = '';

    if( is_array( $body ) ) {
        if( ! empty( $body['message'] ) ) {
            $msg = $body['message'];
        }
        if( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
            $first = reset( $body['errors'] );
            if( is_array( $first ) ) {
                $first = reset( $first );
            }
            if( $first ) {
                $msg = $msg ? $msg . ' ' . $first : $first;
            }
        }
    }

    if( 401 === (int) $code ) {
        $msg = trim( $msg . ' ' . __( 'Check your MailerLite API token in Settings.', 'advanced-form-integration' ) );
    }

    if( '' === $msg && $code ) {
        $msg = sprintf( __( 'MailerLite API returned HTTP %d.', 'advanced-form-integration' ), $code );
    }

    return $msg ? $msg : __( 'Could not load data from MailerLite.', 'advanced-form-integration' );
}

/*
 * Fetch ALL pages of a MailerLite collection (groups, fields). The endpoints
 * default to 25 items per page, so without this groups/fields dropdowns were
 * silently truncated on larger accounts — the recurring "groups don't load /
 * don't show" complaint. Returns the merged data array, or a WP_Error so the
 * caller can surface the reason.
 */
function adfoin_mailerlite2_fetch_all( $endpoint, $cred_id = '' ) {
    $items = array();
    $page  = 1;
    $sep   = ( false === strpos( $endpoint, '?' ) ) ? '?' : '&';

    do {
        $url  = $endpoint . $sep . 'limit=100&page=' . $page;
        $data = adfoin_mailerlite2_request( $url, 'GET', array( 'credId' => $cred_id ) );

        if( is_wp_error( $data ) ) {
            return $data;
        }

        $code = (int) wp_remote_retrieve_response_code( $data );
        if( 200 !== $code ) {
            return new WP_Error( 'mailerlite2_http_error', adfoin_mailerlite2_error_message( $data, $code ) );
        }

        $body  = json_decode( wp_remote_retrieve_body( $data ), true );
        $batch = ( isset( $body['data'] ) && is_array( $body['data'] ) ) ? $body['data'] : array();
        $items = array_merge( $items, $batch );
        $page++;
    } while( count( $batch ) === 100 && $page <= 50 );

    return $items;
}

add_action( 'wp_ajax_adfoin_get_mailerlite2_list', 'adfoin_get_mailerlite2_list', 10, 0 );

/*
 * Get MailerLite subscriber lists
 */
function adfoin_get_mailerlite2_list() {
    // Security Check
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $groups = adfoin_mailerlite2_fetch_all( 'groups', $cred_id );

    if( is_wp_error( $groups ) ) {
        wp_send_json_error( $groups->get_error_message() );
    }

    $lists = array();
    foreach( $groups as $group ) {
        if( isset( $group['id'], $group['name'] ) ) {
            $lists[ $group['id'] ] = $group['name'];
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'wp_ajax_adfoin_get_mailerlite2_custom_fields', 'adfoin_get_mailerlite2_custom_fields', 10, 0 );

/*
 * Get MailerLite fields
 */
function adfoin_get_mailerlite2_custom_fields() {
    // Security Check
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $all = adfoin_mailerlite2_fetch_all( 'fields', $cred_id );

    if( is_wp_error( $all ) ) {
        wp_send_json_error( $all->get_error_message() );
    }

    $fields = array();

    foreach( $all as $single ) {
        // Free edition: standard (default) fields only; custom fields are Pro.
        if( ! empty( $single['is_default'] ) ) {
            array_push( $fields, array( 'key' => $single['key'], 'value' => $single['name'] ) );
        }
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_mailerlite2_job_queue', 'adfoin_mailerlite2_job_queue', 10, 1 );

function adfoin_mailerlite2_job_queue( $data ) {
    adfoin_mailerlite2_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to MailerLite API
 */
function adfoin_mailerlite2_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $task    = $record['task'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( empty( $cred_id ) ) {
        $creds = adfoin_read_credentials( 'mailerlite2' );
        if ( ! empty( $creds ) ) {
            $cred_id = $creds[0]['id'];
        }
    }

    if( $task == 'subscribe' ) {
        $holder  = array();

        foreach( $data as $key => $value ) {
            $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
        }

        $email        = isset( $holder['email'] ) ? $holder['email'] : '';
        $status       = isset( $holder['status'] ) ? $holder['status'] : '';
        $ip_address   = isset( $holder['ip_address'] ) ? $holder['ip_address'] : '';
        $opted_in_at  = isset( $holder['opted_in_at'] ) ? $holder['opted_in_at'] : '';
        $optin_ip     = isset( $holder['optin_ip'] ) ? $holder['optin_ip'] : '';
        $resubscribe  = isset( $holder['resubscribe'] ) ? $holder['resubscribe'] : '';

        unset( $holder['list'] );
        unset( $holder['listId'] );
        unset( $holder['credId'] );
        unset( $holder['email'] );
        unset( $holder['status'] );
        unset( $holder['ip_address'] );
        unset( $holder['opted_in_at'] );
        unset( $holder['optin_ip'] );
        unset( $holder['resubscribe'] );

        $holder = array_filter( $holder );

        $subscriber_data = array(
            'email'  => $email
        );

        if( $holder ) {
            $subscriber_data['fields'] = $holder;
        }

        if( $ip_address ) {
            $subscriber_data['ip_address'] = $ip_address;
        }

        if( $status ) {
            $subscriber_data['status'] = $status;
        }

        if( $opted_in_at ) {
            $subscriber_data['opted_in_at'] = $opted_in_at;
        }

        if( $optin_ip ) {
            $subscriber_data['optin_ip'] = $optin_ip;
        }

        if( 'true' === $resubscribe ) {
            $subscriber_data['resubscribe'] = true;
        }

        if( $list_id ) {
            $subscriber_data['groups'] = array( $list_id );
        }

        $subscriber_data['credId'] = $cred_id;

        adfoin_mailerlite2_request( 'subscribers', 'POST', $subscriber_data, $record );

        return;
    }
}
