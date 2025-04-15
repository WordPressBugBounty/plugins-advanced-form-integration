<?php

add_filter( 'adfoin_action_providers', 'adfoin_ragic_actions', 10, 1 );

function adfoin_ragic_actions( $actions ) {
    $actions['ragic'] = array(
        'title' => __( 'Ragic (Beta)', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Record', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ragic_settings_tab', 10, 1 );

function adfoin_ragic_settings_tab( $providers ) {
    $providers['ragic'] = __( 'Ragic', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ragic_settings_view', 10, 1 );

function adfoin_ragic_settings_view( $current_tab ) {
    if( $current_tab != 'ragic' ) return;

    $nonce     = wp_create_nonce( 'adfoin_ragic_settings' );
    $api_token = get_option( 'adfoin_ragic_api_token', '' );
    $base_url  = get_option( 'adfoin_ragic_base_url', '' );
    ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="container">
        <input type="hidden" name="action" value="adfoin_save_ragic_api_token">
        <input type="hidden" name="_nonce" value="<?php echo $nonce; ?>"/>

        <table class="form-table">
            <tr>
                <th><?php _e( 'API Key', 'advanced-form-integration' ); ?></th>
                <td><input type="text" name="adfoin_ragic_api_token" value="<?php echo esc_attr( $api_token ); ?>" class="regular-text"/></td>
            </tr>
            <tr>
                <th><?php _e( 'Base URL', 'advanced-form-integration' ); ?></th>
                <td><input type="text" name="adfoin_ragic_base_url" value="<?php echo esc_attr( $base_url ); ?>" class="regular-text"/></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}

add_action( 'admin_post_adfoin_save_ragic_api_token', 'adfoin_save_ragic_api_token' );

function adfoin_save_ragic_api_token() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_ragic_settings' ) ) {
        die( __( 'Security check failed', 'advanced-form-integration' ) );
    }

    update_option( 'adfoin_ragic_api_token', sanitize_text_field( $_POST['adfoin_ragic_api_token'] ) );
    update_option( 'adfoin_ragic_base_url', sanitize_text_field( $_POST['adfoin_ragic_base_url'] ) );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=ragic' );
}


add_action( 'adfoin_action_fields', 'adfoin_ragic_action_fields', 10, 1 );

function adfoin_ragic_action_fields() {
    ?>
    <script type="text/template" id="ragic-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}


function adfoin_ragic_request( $endpoint, $method = 'POST', $data = array(), $record = array() ) {
    $api_token = get_option( 'adfoin_ragic_api_token' );
    $base_url  = get_option( 'adfoin_ragic_base_url' );
    $base_url = preg_replace( '/^http:/i', 'https:', $base_url );
    if ( strpos( $base_url, 'https://' ) !== 0 ) {
        $base_url = 'https://' . ltrim( $base_url, '/' );
    }
    $url = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );
    $url = $url . '?api&v=3&APIKey=' . $api_token;

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            // 'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_ragic_job_queue', 'adfoin_ragic_job_queue', 10, 1 );

function adfoin_ragic_job_queue( $data ) {
    adfoin_ragic_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ragic_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    
    if ( isset( $record_data["action_data"]["cl"] ) && adfoin_check_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) return;

    $data       = $record_data['field_data'];
    $account_name = isset( $data['account_name'] ) ? $data['account_name'] : '';
    $tab    = isset( $data['tab'] ) ? $data['tab'] : '';
    $sheet_id     = isset( $data['sheet_id'] ) ? $data['sheet_id'] : '';
    $task       = $record['task'];

    unset( $data['account_name'], $data['tab'], $data['sheet_id'] );

    if( $task == 'subscribe' ) {
        $endpoint = $account_name . '/' . $tab . '/' . $sheet_id;

        $subscription_data = array();

        foreach ( $data as $key => $value ) {
            if( $value ) {
                $pairs = explode( '||', $value );
                foreach ( $pairs as $pair ) {
                    $exploded = explode( '=', $pair, 2 );
                    $key   = trim( $exploded[0] );
                    $parsed_value = isset( $exploded[1] ) && $exploded[1] ? adfoin_get_parsed_values( $exploded[1], $posted_data ) : '';

                    if ( $parsed_value ) {
                        $subscription_data[ $key ] = $parsed_value;
                    }
                }
            }
        }

        adfoin_ragic_request( $endpoint, 'POST', $subscription_data, $record );
    }
}


