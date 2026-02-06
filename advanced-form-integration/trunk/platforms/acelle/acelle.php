<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_acelle_actions',
    10,
    1
);
function adfoin_acelle_actions(  $actions  ) {
    $actions['acelle'] = [
        'title' => __( 'Acelle Mail', 'advanced-form-integration' ),
        'tasks' => [
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ],
    ];
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_acelle_settings_tab',
    10,
    1
);
function adfoin_acelle_settings_tab(  $providers  ) {
    $providers['acelle'] = __( 'Acelle Mail', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_acelle_settings_view',
    10,
    1
);
function adfoin_acelle_settings_view(  $current_tab  ) {
    if ( $current_tab != 'acelle' ) {
        return;
    }
    $title = __( 'Acelle Mail', 'advanced-form-integration' );
    $key = 'acelle';
    $arguments = json_encode( [
        'platform' => $key,
        'fields'   => [[
            'key'    => 'apiEndpoint',
            'label'  => __( 'API Endpoint', 'advanced-form-integration' ),
            'hidden' => false,
        ], [
            'key'    => 'apiToken',
            'label'  => __( 'API Token', 'advanced-form-integration' ),
            'hidden' => true,
        ]],
    ] );
    $instructions = sprintf( '<ol><li>%s</li><li>%s</li></ol>', __( 'Go to My Profile > Account > API Token.', 'advanced-form-integration' ), __( 'Copy API Endpoint and API Token here.', 'advanced-form-integration' ) );
    echo adfoin_platform_settings_template(
        $title,
        $key,
        $arguments,
        $instructions
    );
}

function adfoin_acelle_credentials_list() {
    $credentials = adfoin_read_credentials( 'acelle' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_acelle_action_fields' );
function adfoin_acelle_action_fields() {
    ?>
    <script type="text/template" id="acelle-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Acelle Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <?php 
    adfoin_acelle_credentials_list();
    ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'acelle List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'subscribe'">
                        <th scope="row">
                            <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                        </th>
                        <td scope="row">
                            <span><?php 
        printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                        </td>
                    </tr>
                    <?php 
    }
    ?>
            
        </table>
    </script>
    <?php 
}

/*
 * Acelle API Request
 */
function adfoin_acelle_request(
    $endpoint,
    $method = 'GET',
    $data = [],
    $record = [],
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'acelle', $cred_id );
    $api_endpoint = ( isset( $credentials['apiEndpoint'] ) ? $credentials['apiEndpoint'] : '' );
    $api_token = ( isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '' );
    $base_url = $api_endpoint;
    $base_url = rtrim( $base_url, '/' ) . '/';
    $url = $base_url . ltrim( $endpoint, '/' );
    $query_args = [
        'api_token' => $api_token,
    ];
    if ( 'GET' === $method && !empty( $data ) ) {
        $query_args = array_merge( $query_args, $data );
    }
    $url = add_query_arg( $query_args, $url );
    $args = [
        'timeout' => 30,
        'method'  => $method,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];
    if ( in_array( $method, [
        'POST',
        'PUT',
        'PATCH',
        'DELETE'
    ], true ) && !empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}

add_action(
    'wp_ajax_adfoin_get_acelle_credentials',
    'adfoin_get_acelle_credentials',
    10,
    0
);
function adfoin_get_acelle_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $all_credentials = adfoin_read_credentials( 'acelle' );
    wp_send_json_success( $all_credentials );
}

add_action(
    'wp_ajax_adfoin_save_acelle_credentials',
    'adfoin_save_acelle_credentials',
    10,
    0
);
function adfoin_save_acelle_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $platform = sanitize_text_field( $_POST['platform'] );
    if ( 'acelle' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

add_action(
    'wp_ajax_adfoin_get_acelle_list',
    'adfoin_get_acelle_list',
    10,
    0
);
/*
 * Get Acelle subscriber lists
 */
function adfoin_get_acelle_list() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $cred_id = sanitize_text_field( $_POST['credId'] );
    $lists = [];
    $page = 1;
    $per_page = 100;
    do {
        $response = adfoin_acelle_request(
            'lists',
            'GET',
            [
                'per_page' => $per_page,
                'page'     => $page,
            ],
            [],
            $cred_id
        );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body ) || !is_array( $body ) ) {
            break;
        }
        $previous_total = count( $lists );
        foreach ( $body as $list_item ) {
            if ( empty( $list_item['uid'] ) ) {
                continue;
            }
            $lists[$list_item['uid']] = $list_item;
        }
        if ( $previous_total === count( $lists ) || count( $body ) < $per_page ) {
            break;
        }
        $page++;
    } while ( true );
    if ( empty( $lists ) ) {
        wp_send_json_success( [] );
    }
    $list_options = wp_list_pluck( array_values( $lists ), 'name', 'uid' );
    wp_send_json_success( $list_options );
}

add_action(
    'adfoin_acelle_job_queue',
    'adfoin_acelle_job_queue',
    10,
    1
);
function adfoin_acelle_job_queue(  $data  ) {
    adfoin_acelle_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to acelle API
 */
function adfoin_acelle_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( adfoin_check_conditional_logic( $record_data["action_data"]["cl"] ?? [], $posted_data ) ) {
        return;
    }
    $data = $record_data['field_data'];
    $list_id = ( isset( $data['listId'] ) ? $data['listId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    if ( $task === 'subscribe' ) {
        $subscriber_data = [
            'EMAIL'      => trim( adfoin_get_parsed_values( ( isset( $data['EMAIL'] ) ? $data['EMAIL'] : '' ), $posted_data ) ),
            'FIRST_NAME' => adfoin_get_parsed_values( ( isset( $data['FIRST_NAME'] ) ? $data['FIRST_NAME'] : '' ), $posted_data ),
            'LAST_NAME'  => adfoin_get_parsed_values( ( isset( $data['LAST_NAME'] ) ? $data['LAST_NAME'] : '' ), $posted_data ),
            'list_uid'   => $list_id,
        ];
        adfoin_acelle_request(
            'subscribers',
            'POST',
            array_filter( $subscriber_data ),
            $record,
            $cred_id
        );
    }
    return;
}
