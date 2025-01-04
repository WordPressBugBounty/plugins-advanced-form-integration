<?php
add_filter( 'adfoin_action_providers', 'adfoin_sendpulse_actions', 10, 1 );

function adfoin_sendpulse_actions( $actions ) {

    $actions['sendpulse'] = array(
        'title' => __( 'SendPulse', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To Email List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sendpulse_settings_tab', 10, 1 );

function adfoin_sendpulse_settings_tab( $providers ) {
    $providers['sendpulse'] = __( 'SendPulse', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendpulse_settings_view', 10, 1 );

function adfoin_sendpulse_settings_view( $current_tab ) {
    if( $current_tab != 'sendpulse' ) {
        return;
    }

    $nonce  = wp_create_nonce( "adfoin_sendpulse_settings" );
    $id     = get_option( 'adfoin_sendpulse_id' ) ? get_option( 'adfoin_sendpulse_id' ) : "";
    $secret = get_option( 'adfoin_sendpulse_secret' ) ? get_option( 'adfoin_sendpulse_secret' ) : "";
    ?>

    <form name="sendpulse_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_sendpulse_api_key">
        <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php _e( 'ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sendpulse_id"
                           value="<?php echo esc_attr( $id ); ?>" placeholder="<?php _e( 'Please enter ID', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p>
                        Go to Account Settings > API
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php _e( 'Secret', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sendpulse_secret"
                           value="<?php echo esc_attr( $secret ); ?>" placeholder="<?php esc_attr_e( 'Please enter Secret', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_sendpulse_api_key', 'adfoin_save_sendpulse_api_key', 10, 0 );

function adfoin_save_sendpulse_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_sendpulse_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $id     = sanitize_text_field( $_POST['adfoin_sendpulse_id'] );
    $secret = sanitize_text_field( $_POST['adfoin_sendpulse_secret'] );

    // Save tokens
    update_option( "adfoin_sendpulse_id", $id );
    update_option( "adfoin_sendpulse_secret", $secret );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=sendpulse" );
}

add_action( 'adfoin_action_fields', 'adfoin_sendpulse_action_fields', 10, 1 );

function adfoin_sendpulse_action_fields() {
    ?>
    <script type="text/template" id="sendpulse-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Contact Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Email List', 'advanced-form-integration' ); ?>
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

add_action( 'wp_ajax_adfoin_get_sendpulse_list', 'adfoin_get_sendpulse_list', 10 );

function adfoin_get_sendpulse_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $response = adfoin_sendpulse_request('addressbooks');
    $addressbooks = json_decode(wp_remote_retrieve_body($response));
    $lists = wp_list_pluck($addressbooks, 'name', 'id');

    wp_send_json_success($lists);
}

add_action( 'adfoin_sendpulse_job_queue', 'adfoin_sendpulse_job_queue', 10, 1 );

function adfoin_sendpulse_job_queue( $data ) {
    adfoin_sendpulse_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to SendPulse API
 */
function adfoin_sendpulse_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data['field_data'];
    $list_id = $data['listId'];
    $task    = $record['task'];
    $email   = empty( $data['email'] ) ? '' : adfoin_get_parsed_values($data['email'], $posted_data);
    $name    = empty( $data['name'] ) ? '' : adfoin_get_parsed_values($data['name'], $posted_data);
    $phone   = empty( $data['phone'] ) ? '' : adfoin_get_parsed_values($data['phone'], $posted_data);

    if( $task == 'subscribe' ) {
        $emails = array(
            'emails' => array(
                array(
                    'email' => $email,
                    'variables' => array_filter( array(
                        'name'  => $name ? $name : '',
                        'Phone' => $phone ? $phone : ''
                    ))
                )
            )
        );

        $response = adfoin_sendpulse_request('addressbooks/' . $list_id . '/emails', 'POST', $emails, $record, '');
        $return = json_decode(wp_remote_retrieve_body($response));
    }
}

function adfoin_sendpulse_request($endpoint, $method = 'GET', $data = array(), $record = array()) {

    $user_id = get_option( 'adfoin_sendpulse_id' ) ? get_option( 'adfoin_sendpulse_id' ) : '';
    $secret  = get_option( 'adfoin_sendpulse_secret' ) ? get_option( 'adfoin_sendpulse_secret' ) : '';

    // Get token
    $token = get_transient('sendpulse_access_token');
    if (!$token) {
        $token_response = wp_remote_post('https://api.sendpulse.com/oauth/access_token', array(
            'body' => json_encode(array(
                'grant_type'    => 'client_credentials',
                'client_id'     => $user_id,
                'client_secret' => $secret,
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ));

        $token_body = json_decode(wp_remote_retrieve_body($token_response));
        $token = isset($token_body->access_token) ? $token_body->access_token : '';

        // Save token in transient for 1 hour
        set_transient('sendpulse_access_token', $token, 3500);
    }

    $url = 'https://api.sendpulse.com/' . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ),
    );

    if ('POST' == $method || 'PUT' == $method || 'PATCH' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}
