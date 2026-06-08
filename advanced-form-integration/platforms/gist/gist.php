<?php

/**
 * Gist — Create or Update Contact via POST /contacts.
 *
 * @link https://developers.getgist.com/api/
 */

add_filter( 'adfoin_action_providers', 'adfoin_gist_actions', 10, 1 );

function adfoin_gist_actions( $actions ) {

    $actions['gist'] = array(
        'title' => __( 'Gist', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create or Update Contact', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_gist_settings_tab', 10, 1 );

function adfoin_gist_settings_tab( $providers ) {
    $providers['gist'] = __( 'Gist', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_gist_settings_view', 10, 1 );

function adfoin_gist_settings_view( $current_tab ) {
    if( $current_tab != 'gist' ) {
        return;
    }

    $title = __( 'Gist', 'advanced-form-integration' );
    $key = 'gist';
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
                <ol>
                    <li>Log in to your Gist account.</li>
                    <li>Navigate to Settings &gt; API &amp; Installation Code &gt; Secret API Key.</li>
                    <li>Copy the API key and paste it into the field below.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_gist_credentials', 'adfoin_get_gist_credentials', 10, 0 );

function adfoin_get_gist_credentials() {
    adfoin_verify_nonce();

    $all_credentials = adfoin_read_credentials( 'gist' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_gist_credentials', 'adfoin_save_gist_credentials', 10, 0 );

function adfoin_save_gist_credentials() {

    adfoin_verify_nonce();

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if( 'gist' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_gist_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'gist' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. esc_attr( $option['id'] ) .'">' . esc_html( $option['title'] ) . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_gist_action_fields' );

function adfoin_gist_action_fields() {
    ?>
    <script type="text/template" id="gist-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Gist Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php adfoin_gist_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Gist [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

/**
 * Centralized Gist API caller. Reused by gistpro.
 *
 * @param string $endpoint Path under https://api.getgist.com/ (no leading slash).
 * @param string $method   HTTP method.
 * @param array  $data     Request body (encoded as JSON for POST/PUT/PATCH/DELETE).
 * @param array  $record   Submission record — when present, the call is logged.
 * @param string $cred_id  Saved-credential ID.
 */
function adfoin_gist_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'gist', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';

    $base_url = 'https://api.getgist.com/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_gist_job_queue', 'adfoin_gist_job_queue', 10, 1 );

function adfoin_gist_job_queue( $data ) {
    adfoin_gist_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_gist_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = $record['task'];

    if ( 'create_contact' !== $task ) {
        return;
    }

    // Whitelist of Gist API fields supported in the free edition. Per
    // POST /contacts docs, email or user_id is required — the Gist
    // backend identifies/updates existing contacts by id > user_id >
    // email, in that order.
    $allowed_fields = array( 'email', 'name', 'phone', 'user_id' );

    $contact = array();
    foreach ( $allowed_fields as $key ) {
        if ( ! isset( $data[ $key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $data[ $key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $contact[ $key ] = $value;
    }

    // Require at least one identifier — sending neither email nor
    // user_id returns 400 from Gist.
    if ( empty( $contact['email'] ) && empty( $contact['user_id'] ) ) {
        return;
    }

    adfoin_gist_request( 'contacts', 'POST', $contact, $record, $cred_id );
}
