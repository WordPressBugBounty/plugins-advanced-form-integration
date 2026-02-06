<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailjet_actions', 10, 1 );

function adfoin_mailjet_actions( $actions ) {

    $actions['mailjet'] = array(
        'title' => __( 'Mailjet', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailjet_settings_tab', 10, 1 );

function adfoin_mailjet_settings_tab( $providers ) {
    $providers['mailjet'] = __( 'Mailjet', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailjet_settings_view', 10, 1 );

function adfoin_mailjet_settings_view( $current_tab ) {
    if( $current_tab != 'mailjet' ) {
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
        array(
            'name'          => 'secretKey',
            'label'         => __( 'Secret Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Enter Secret Key', 'advanced-form-integration' ),
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Go to Account Settings > API Key Management.', 'advanced-form-integration' ),
        __( 'Create or copy your Primary API Key and Secret Key.', 'advanced-form-integration' ),
        __( 'Paste them here and save.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'mailjet', __( 'Mailjet', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailjet_credentials', 'adfoin_get_mailjet_credentials', 10, 0 );
function adfoin_get_mailjet_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailjet' );
}

add_action( 'wp_ajax_adfoin_save_mailjet_credentials', 'adfoin_save_mailjet_credentials', 10, 0 );
function adfoin_save_mailjet_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailjet', array( 'apiKey', 'secretKey' ) );
}

add_filter( 'adfoin_get_credentials', 'adfoin_mailjet_modify_credentials', 10, 2 );
function adfoin_mailjet_modify_credentials( $credentials, $platform ) {
    if ( 'mailjet' === $platform && empty( $credentials ) ) {
        $api_key    = get_option( 'adfoin_mailjet_api_key' );
        $secret_key = get_option( 'adfoin_mailjet_secret_key' );
        if ( $api_key && $secret_key ) {
            $credentials[] = array(
                'id'        => 'legacy',
                'title'     => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey'    => $api_key,
                'secretKey' => $secret_key,
            );
        }
    }

    return $credentials;
}
add_action( 'adfoin_add_js_fields', 'adfoin_mailjet_js_fields', 10, 1 );

function adfoin_mailjet_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_mailjet_action_fields' );

function adfoin_mailjet_action_fields() {
    ?>
    <script type="text/template" id="mailjet-action-template">
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
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailjet' ) ); ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
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
                        <?php esc_attr_e( 'Mailjet List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mailjet_list', 'adfoin_get_mailjet_list', 10, 0 );
/*
 * Get Mailjet subscriber lists
 */
function adfoin_get_mailjet_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

    $data = adfoin_mailjet_request( 'contactslist?limit=1000', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( $data['body'] );
    $lists = wp_list_pluck( $body->Data, 'Name', 'ID' );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_mailjet_job_queue', 'adfoin_mailjet_job_queue', 10, 1 );

function adfoin_mailjet_job_queue( $data ) {
    adfoin_mailjet_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Mailjet API
 */
function adfoin_mailjet_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( empty( $cred_id ) ) {
        $creds = adfoin_read_credentials( 'mailjet' );
        if ( ! empty( $creds ) ) {
            $cred_id = $creds[0]['id'];
        }
    }

    if( $task == 'subscribe' ) {
        $list_id = $data['listId'];
        $email   = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $name    = empty( $data['name'] ) ? '' : adfoin_get_parsed_values( $data['name'], $posted_data );

        $subscriber_data = array(
            'Contacts' => array(
                array(
                    'Email' => trim( $email ),
                    'IsExcludedFromCampaigns' => false,
                    'Properties' => array(
                        'Name' => $name
                    )
                )
            ),
            'ContactsLists' => array(
                array(
                    'ListID' => $list_id,
                    'Action' => 'addforce'
                )
            )
        );

        $return = adfoin_mailjet_request( 'contact/managemanycontacts', 'POST', $subscriber_data, $record, $cred_id );
    }

    return;
}

function adfoin_mailjet_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

    $credentials = adfoin_get_credentials_by_id( 'mailjet', $cred_id );
    $api_key    = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $secret_key = isset( $credentials['secretKey'] ) ? $credentials['secretKey'] : '';

    if ( ! $api_key || ! $secret_key ) {
        $api_key    = get_option( 'adfoin_mailjet_api_key' ) ? get_option( 'adfoin_mailjet_api_key' ) : '';
        $secret_key = get_option( 'adfoin_mailjet_secret_key' ) ? get_option( 'adfoin_mailjet_secret_key' ) : '';
    }

    if ( ! $api_key || ! $secret_key ) {
        return new WP_Error( 'missing_api_key', __( 'Mailjet API credentials not found', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $secret_key )
        )
    );

    $base_url = 'https://api.mailjet.com/v3/REST/';
    $url      = $base_url . $endpoint;

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
