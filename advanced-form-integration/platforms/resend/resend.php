<?php
add_filter( 'adfoin_action_providers', 'adfoin_resend_actions', 10, 1 );

function adfoin_resend_actions( $actions ) {
    $actions['resend'] = array(
        'title' => __( 'Resend', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add contact', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_resend_settings_tab', 10, 1 );

function adfoin_resend_settings_tab( $providers ) {
    $providers['resend'] = __( 'Resend', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_resend_settings_view', 10, 1 );

function adfoin_resend_settings_view( $current_tab ) {
    if( $current_tab != 'resend' ) {
        return;
    }

    $title = __( 'Resend', 'advanced-form-integration' );
    $key = 'resend';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'api_key',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ul>
                    <li>Go to API Keys and create a new key with full access</li>
                </ul>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_resend_credentials', 'adfoin_get_resend_credentials', 10, 0 );

function adfoin_get_resend_credentials() {
    adfoin_verify_nonce();

    $all_credentials = adfoin_read_credentials( 'resend' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_resend_credentials', 'adfoin_save_resend_credentials', 10, 0 );

function adfoin_save_resend_credentials() {

    adfoin_verify_nonce();

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if( 'resend' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_resend_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'resend' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_resend_action_fields' );

function adfoin_resend_action_fields() {
    ?>
    <script type="text/template" id="resend-action-template">
        <table class="form-table">
            
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Resend Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_resend_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=resend' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Segment', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select Segment...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(list, index) in fielddata.lists" :value="index"> {{ list }} </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Resend [PRO]', 'custom contact properties' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_resend_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'resend', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';

    $base_url = "https://api.resend.com/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
    );

    if ( 'POST' == $method || 'PATCH' == $method ) {
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_resend_lists', 'adfoin_get_resend_lists', 10, 0 );

function adfoin_get_resend_lists() {
    adfoin_verify_nonce();

    $cred_id = sanitize_text_field( wp_unslash( $_POST['credId'] ) );

    // Resend renamed Audiences to Segments (same IDs). Fall back to the old
    // endpoint in case an account still only answers there.
    $response = adfoin_resend_request( 'segments', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        $response = adfoin_resend_request( 'audiences', 'GET', '', '', $cred_id );
    }

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) && isset( $body['data'] ) ) {
        $lists = array();

        foreach ( $body['data'] as $list ) {
            $lists[ $list['id'] ] = $list['name'];
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_resend_job_queue', 'adfoin_resend_job_queue', 10, 1 );

function adfoin_resend_job_queue( $data ) {
    adfoin_resend_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_resend_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    unset( $data['listId'], $data['credId'] );

    if ( $task == 'subscribe' ) {
        $contact = array();

        foreach ( array( 'email', 'first_name', 'last_name' ) as $key ) {
            $value = isset( $data[ $key ] ) ? trim( (string) adfoin_get_parsed_values( $data[ $key ], $posted_data ) ) : '';

            if ( '' !== $value ) {
                $contact[ $key ] = $value;
            }
        }

        adfoin_resend_upsert_contact( $contact, $list_id, $record, $cred_id );
    }
}

/**
 * Create or update a contact and add it to a segment.
 *
 * POST /contacts is an upsert: for an existing email it updates the
 * contact and adds the given segments. Saved integrations store the old
 * Audience ID as listId, which is the same ID as the segment.
 *
 * @param array  $contact    email, first_name, last_name, and optionally properties.
 * @param string $segment_id Segment (formerly Audience) ID; may be empty.
 * @return array|WP_Error|null Response, or null when there is no email.
 */
function adfoin_resend_upsert_contact( $contact, $segment_id, $record, $cred_id ) {
    if ( empty( $contact['email'] ) ) {
        return null;
    }

    if ( $segment_id ) {
        $contact['segments'] = array( array( 'id' => $segment_id ) );
    }

    return adfoin_resend_request( 'contacts', 'POST', $contact, $record, $cred_id );
}
