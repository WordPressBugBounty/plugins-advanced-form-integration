<?php

add_filter( 'adfoin_action_providers', 'adfoin_iterable_actions', 10, 1 );
function adfoin_iterable_actions( $actions ) {
    $actions['iterable'] = [
        'title' => __( 'Iterable', 'advanced-form-integration' ),
        'tasks' => [ 'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ) ]
    ];
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_iterable_settings_tab', 10, 1 );
function adfoin_iterable_settings_tab( $providers ) {
    $providers['iterable'] = __( 'Iterable', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_iterable_settings_view', 10, 1 );
function adfoin_iterable_settings_view( $current_tab ) {
    if ( $current_tab !== 'iterable' ) return;

    $title = __( 'Iterable', 'advanced-form-integration' );
    $key   = 'iterable';
    $arguments = json_encode([
        'platform' => $key,
        'fields'   => [
            ['key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true]
        ]
    ]);
    $instructions = __( '<p>Log in to your Iterable account and generate an API Key under Integrations > API Keys.</p>', 'advanced-form-integration' );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_iterable_credentials', 'adfoin_get_iterable_credentials', 10 );
function adfoin_get_iterable_credentials() {
    if ( !adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'iterable' ) );
}

add_action( 'wp_ajax_adfoin_save_iterable_credentials', 'adfoin_save_iterable_credentials', 10 );
function adfoin_save_iterable_credentials() {
    if ( !adfoin_verify_nonce() ) return;

    $platform = sanitize_text_field( $_POST['platform'] );
    if ( $platform === 'iterable' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

function adfoin_iterable_request( $endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'iterable', $cred_id );
    $api_key     = $credentials['apiKey'] ?? '';
    $url         = 'https://api.iterable.com/api/' . ltrim( $endpoint, '/' );

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Api-Key'       => $api_key,
        ]
    ];

    if ( in_array( $method, ['POST', 'PUT'] ) ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'wp_ajax_adfoin_get_iterable_lists', 'adfoin_get_iterable_lists' );
function adfoin_get_iterable_lists() {
    if ( !adfoin_verify_nonce() ) return;

    $cred_id  = sanitize_text_field( $_POST['credId'] );
    $response = adfoin_iterable_request( 'lists', 'GET', [], [], $cred_id );

    if ( is_wp_error( $response ) ) wp_send_json_error();

    $body = json_decode( wp_remote_retrieve_body( $response ) );
    $lists = isset($body->lists) ? wp_list_pluck( $body->lists, 'name', 'id' ) : [];

    wp_send_json_success( $lists );
}

add_action( 'adfoin_iterable_job_queue', 'adfoin_iterable_job_queue', 10 );
function adfoin_iterable_job_queue( $data ) {
    adfoin_iterable_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_iterable_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;

    $data    = $record_data['field_data'];
    $cred_id = $data['credId'] ?? '';
    $list_id = $data['listId'] ?? '';

    $subscriber = [
        'email' => adfoin_get_parsed_values( $data['email'] ?? '', $posted_data ),
        'listId' => (int) $list_id,
        'dataFields' => []
    ];

    foreach ( $data as $key => $value ) {
        if ( ! in_array( $key, ['credId', 'listId', 'email'] ) ) {
            $parsed = adfoin_get_parsed_values( $value, $posted_data );
            if ( $parsed !== '' ) $subscriber['dataFields'][$key] = $parsed;
        }
    }

    adfoin_iterable_request( 'lists/subscribe', 'POST', [ 'subscribers' => [ $subscriber ] ], $record, $cred_id );
}

add_action( 'adfoin_action_fields', 'adfoin_iterable_action_fields' );
function adfoin_iterable_action_fields() {
    ?>
    <script type="text/template" id="iterable-action-template">
        <table class="form-table">
            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td>
                    <label><?php esc_html_e( 'Iterable Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php
                        $credentials = adfoin_read_credentials('iterable');
                        foreach ($credentials as $option) {
                            printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td>
                    <label><?php esc_html_e( 'Iterable List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php _e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index">{{ item }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" :key="field.value" :field="field" :trigger="trigger" :action="action" :fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}