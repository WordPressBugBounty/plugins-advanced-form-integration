<?php

add_filter( 'adfoin_action_providers', 'adfoin_iterable_actions', 10, 1 );

function adfoin_iterable_actions( $actions ) {
    $actions['iterable'] = array(
        'title' => __( 'Iterable', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_iterable_settings_tab', 10, 1 );

function adfoin_iterable_settings_tab( $providers ) {
    $providers['iterable'] = __( 'Iterable', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_iterable_settings_view', 10, 1 );

function adfoin_iterable_settings_view( $current_tab ) {
    if ( 'iterable' !== $current_tab ) {
        return;
    }

    $title      = __( 'Iterable', 'advanced-form-integration' );
    $key        = 'iterable';
    $arguments  = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                </ol>
            </li>
            <li><strong>%5$s</strong>
                <ol>
                    <li>%6$s</li>
                    <li>%7$s</li>
                </ol>
            </li>
        </ol>
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Generate a server-side API key', 'advanced-form-integration' ),
        esc_html__( 'Sign in to Iterable and navigate to Integrations → API Keys.', 'advanced-form-integration' ),
        esc_html__( 'Click “New API Key”, choose the Server-side type, and enable User, Lists, and Event permissions.', 'advanced-form-integration' ),
        esc_html__( 'Give the key a recognizable name (e.g., “AFI Basic”) and copy the value.', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the API key into the field above and save the settings.', 'advanced-form-integration' ),
        esc_html__( 'Repeat to add keys for other Iterable projects or sandboxes.', 'advanced-form-integration' ),
        esc_html__( 'AFI will call https://api.iterable.com/api using that key to subscribe contacts and manage lists.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Iterable [PRO] to track custom events, trigger journeys, and push full profile attributes.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_iterable_credentials', 'adfoin_get_iterable_credentials', 10 );

function adfoin_get_iterable_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'iterable' ) );
}

add_action( 'wp_ajax_adfoin_save_iterable_credentials', 'adfoin_save_iterable_credentials', 10 );

function adfoin_save_iterable_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'iterable' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

if ( ! function_exists( 'adfoin_iterable_request' ) ) :
function adfoin_iterable_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'iterable', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) {
        return new WP_Error( 'missing_credentials', __( 'Iterable API key is missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.iterable.com/api/' . ltrim( $endpoint, '/' );

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Api-Key'      => $api_key,
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

add_action( 'wp_ajax_adfoin_get_iterable_lists', 'adfoin_get_iterable_lists' );

function adfoin_get_iterable_lists() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_iterable_request( 'lists', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $lists = array();

    if ( ! empty( $body['lists'] ) && is_array( $body['lists'] ) ) {
        foreach ( $body['lists'] as $list ) {
            if ( isset( $list['id'], $list['name'] ) ) {
                $lists[ $list['id'] ] = $list['name'];
            }
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_iterable_job_queue', 'adfoin_iterable_job_queue', 10, 1 );

function adfoin_iterable_job_queue( $data ) {
    adfoin_iterable_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_iterable_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = isset( $data['listId'] ) ? (int) $data['listId'] : 0;
    $email   = isset( $data['email'] ) ? adfoin_get_parsed_values( $data['email'], $posted_data ) : '';

    if ( ! $cred_id || ! $list_id || ! $email ) {
        return;
    }

    $subscriber = array(
        'email'     => $email,
        'listId'    => $list_id,
        'dataFields'=> array(),
    );

    foreach ( $data as $key => $value ) {
        if ( in_array( $key, array( 'credId', 'listId', 'email' ), true ) ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' !== $parsed && null !== $parsed ) {
            $subscriber['dataFields'][ $key ] = $parsed;
        }
    }

    adfoin_iterable_request( 'lists/subscribe', 'POST', array( 'subscribers' => array( $subscriber ) ), $record, $cred_id );
}

add_action( 'adfoin_action_fields', 'adfoin_iterable_action_fields' );

function adfoin_iterable_action_fields() {
    ?>
    <script type="text/template" id="iterable-action-template">
        <table class="form-table">
            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Iterable Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select account…', 'advanced-form-integration' ); ?></option>
                        <?php foreach ( adfoin_read_credentials( 'iterable' ) as $option ) : ?>
                            <option value="<?php echo esc_attr( $option['id'] ); ?>"><?php echo esc_html( $option['title'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Iterable List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, value) in fielddata.lists" :value="value">{{ label }}</option>
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

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need automation events?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Iterable [PRO]</a> to trigger journeys, capture custom events, and sync every profile attribute.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}
