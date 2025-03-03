<?php

add_filter( 'adfoin_action_providers', 'adfoin_lemlist_actions', 10, 1 );

function adfoin_lemlist_actions( $actions ) {

    $actions['lemlist'] = array(
        'title' => __( 'lemlist', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact To Campaign', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_lemlist_settings_tab', 10, 1 );

function adfoin_lemlist_settings_tab( $providers ) {
    $providers['lemlist'] = __( 'lemlist', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_lemlist_settings_view', 10, 1 );

function adfoin_lemlist_settings_view( $current_tab ) {
    if( $current_tab != 'lemlist' ) {
        return;
    }

    $nonce     = wp_create_nonce( "adfoin_lemlist_settings" );
    $api_key = get_option( 'adfoin_lemlist_api_key' ) ? get_option( 'adfoin_lemlist_api_key' ) : "";
    ?>

    <form name="lemlist_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_lemlist_api_key">
        <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php _e( 'lemlist API Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_lemlist_api_key"
                           value="<?php echo esc_attr( $api_key ); ?>" placeholder="<?php _e( 'Please enter API Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description"><?php _e( 'Go to Settings > Integrations and generate an API Key', 'advanced-form-integration' ); ?></a></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_lemlist_api_key', 'adfoin_save_lemlist_api_key', 10, 0 );

function adfoin_save_lemlist_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_lemlist_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = sanitize_text_field( $_POST["adfoin_lemlist_api_key"] );

    // Save tokens
    update_option( "adfoin_lemlist_api_key", $api_key );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=lemlist" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_lemlist_js_fields', 10, 1 );

function adfoin_lemlist_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_lemlist_action_fields' );

function adfoin_lemlist_action_fields() {
?>
    <script type="text/template" id="lemlist-action-template">
        <table class="form-table">
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
                        <?php esc_attr_e( 'Campaign', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
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

add_action( 'wp_ajax_adfoin_get_lemlist_list', 'adfoin_get_lemlist_list', 10, 0 );

/*
 * Get lemlist subscriber lists
 */
function adfoin_get_lemlist_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $response = adfoin_lemlist_request('campaigns?version=v2&limit=100');

    if( !is_wp_error( $response ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $response ) );
        $lists = wp_list_pluck( $body->campaigns, 'name', '_id' );

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_lemlist_job_queue', 'adfoin_lemlist_job_queue', 10, 1 );

function adfoin_lemlist_job_queue( $data ) {
    adfoin_lemlist_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to lemlist API
 */
function adfoin_lemlist_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data    = $record_data['field_data'];
    $list_id = $data['listId'];
    $task    = $record['task'];

    if ($task == 'subscribe') {

        $email      = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );

        $data = array_filter(array(
            'firstName' => $first_name,
            'lastName'  => $last_name
        ));

        $endpoint = "campaigns/{$list_id}/leads/{$email}";

        if (adfoin_lemlist_find_lead_by_email($email)) {
            $response = adfoin_lemlist_request($endpoint, 'PATCH', $data, $record);
        } else {
            $response = adfoin_lemlist_request($endpoint, 'POST', $data, $record);
        }
    }
}

/*
 * lemlist API Request
 */
function adfoin_lemlist_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

    $api_key = get_option( 'adfoin_lemlist_api_key' ) ? get_option( 'adfoin_lemlist_api_key' ) : '';

    $base_url = 'https://api.lemlist.com/api/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( ':' . $api_key )
        ),
    );

    if ('POST' == $method || 'PATCH' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

function adfoin_lemlist_find_lead_by_email($email) {
    $endpoint = "leads/{$email}?version=v2";
    $response = adfoin_lemlist_request($endpoint);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['0'], $body['0']['variables'], $body['0']['variables']['email']) && $body['0']['variables']['email'] === $email) {
        return true;
    }

    return false;
}