<?php

add_filter( 'adfoin_action_providers', 'adfoin_emailfig_actions', 10, 1 );

function adfoin_emailfig_actions( $actions ) {

    $actions['emailfig'] = array(
        'title' => __( 'EmailFig', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact To List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_emailfig_settings_tab', 10, 1 );

function adfoin_emailfig_settings_tab( $providers ) {
    $providers['emailfig'] = __( 'EmailFig', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_emailfig_settings_view', 10, 1 );

function adfoin_emailfig_settings_view( $current_tab ) {
    if( $current_tab != 'emailfig' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your EmailFig API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __( 'Go to EmailFig Settings > API Keys and create a new key', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'emailfig', __( 'EmailFig', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_emailfig_credentials', 'adfoin_get_emailfig_credentials', 10, 0 );

function adfoin_get_emailfig_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'emailfig' );
}

add_action( 'wp_ajax_adfoin_save_emailfig_credentials', 'adfoin_save_emailfig_credentials', 10, 0 );

function adfoin_save_emailfig_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'emailfig', array( 'apiKey' ) );
}

add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'emailfig', array(
            'apiKey' => 'adfoin_emailfig_api_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_add_js_fields', 'adfoin_emailfig_js_fields', 10, 1 );

function adfoin_emailfig_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_emailfig_action_fields' );

function adfoin_emailfig_action_fields() {
?>
    <script type="text/template" id="emailfig-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="item in credentialsList" :key="item.id" :value="item.id">{{ item.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=emailfig' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
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
                    <label><?php esc_attr_e( 'List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php _e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, key) in fielddata.lists" :key="key" :value="key">{{ item }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" :key="field.value" :field="field" :trigger="trigger" :action="action" :fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'EmailFig', 'custom fields' ); ?>
        </table>
    </script>
<?php
}

add_action( 'wp_ajax_adfoin_get_emailfig_lists', 'adfoin_get_emailfig_lists', 10, 0 );

function adfoin_get_emailfig_lists() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_emailfig_request( 'lists', 'GET', array(), array(), $cred_id );

    if( !is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $lists = array();

        if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            foreach ( $body['data'] as $list ) {
                if ( isset( $list['id'] ) && isset( $list['name'] ) ) {
                    $lists[ $list['id'] ] = $list['name'];
                }
            }
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_emailfig_job_queue', 'adfoin_emailfig_job_queue', 10, 1 );

function adfoin_emailfig_job_queue( $data ) {
    adfoin_emailfig_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_emailfig_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = $data['listId'];
    $task    = $record['task'];

    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'emailfig' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if ( $task == 'subscribe' ) {
        $email      = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
        $tags       = empty( $data['tags'] ) ? array() : adfoin_get_parsed_values( $data['tags'], $posted_data );

        if ( is_string( $tags ) ) {
            $tags = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
        } elseif ( ! is_array( $tags ) ) {
            $tags = array();
        }

        $payload = array(
            'email_address' => $email,
        );

        if ( ! empty( $first_name ) ) {
            $payload['first_name'] = $first_name;
        }

        if ( ! empty( $last_name ) ) {
            $payload['last_name'] = $last_name;
        }

        if ( ! empty( $tags ) ) {
            $payload['tags'] = $tags;
        }

        $endpoint = "lists/{$list_id}/contacts";
        $response = adfoin_emailfig_request( $endpoint, 'POST', $payload, $record, $cred_id );

        if ( ! is_wp_error( $response ) ) {
            // Logging handled in adfoin_emailfig_request()
        }
    }
}

function adfoin_emailfig_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'emailfig', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_emailfig_api_key' );
    }

    if ( empty( $api_key ) ) {
        return new WP_Error( 'missing_credentials', __( 'EmailFig API Key is not configured.', 'advanced-form-integration' ) );
    }

    $base_url = 'https://emailfig.com/api/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
    );

    if ( 'POST' == $method || 'PATCH' == $method ) {
        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
