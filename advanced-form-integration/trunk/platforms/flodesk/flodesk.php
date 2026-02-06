<?php

add_filter( 'adfoin_action_providers', 'adfoin_flodesk_actions', 10 );
function adfoin_flodesk_actions(  $actions  ) {
    $actions['flodesk'] = [
        'title' => __( 'Flodesk', 'advanced-form-integration' ),
        'tasks' => [
            'subscribe' => __( 'Subscribe', 'advanced-form-integration' ),
        ],
    ];
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_flodesk_settings_tab', 10 );
function adfoin_flodesk_settings_tab(  $providers  ) {
    $providers['flodesk'] = __( 'Flodesk', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_flodesk_settings_view', 10 );
function adfoin_flodesk_settings_view(  $current_tab  ) {
    if ( $current_tab != 'flodesk' ) {
        return;
    }
    $title = __( 'Flodesk', 'advanced-form-integration' );
    $key = 'flodesk';
    $arguments = json_encode( [
        'platform' => 'flodesk',
        'fields'   => [[
            'key'    => 'apiKey',
            'label'  => __( 'API Key', 'advanced-form-integration' ),
            'hidden' => true,
        ]],
    ] );
    $instructions = sprintf( '<ol><li>%s</li><li>%s</li></ol>', __( 'Go to Profile > Integrations > API Keys.', 'advanced-form-integration' ), __( 'Crate API Key and copy.', 'advanced-form-integration' ) );
    echo adfoin_platform_settings_template(
        $title,
        $key,
        $arguments,
        $instructions
    );
}

function adfoin_flodesk_credentials_list() {
    $credentials = adfoin_read_credentials( 'flodesk' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_flodesk_action_fields' );
function adfoin_flodesk_action_fields() {
    ?>
    <script type="text/template" id="flodesk-action-template">
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
    esc_attr_e( 'Flodesk Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getSegments">
                    <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <?php 
    adfoin_flodesk_credentials_list();
    ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Flodesk Segment', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[segmentId]" v-model="fielddata.segmentId">
                        <option value=""> <?php 
    _e( 'Select Segment...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.segments" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': segmentsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Double Opt-In', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doptin]" value="true" v-model="fielddata.doptin">
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
 * flodesk API Request
 */
function adfoin_flodesk_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'flodesk', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $base_url = 'https://api.flodesk.com/v1/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ),
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
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
    'wp_ajax_adfoin_get_flodesk_credentials',
    'adfoin_get_flodesk_credentials',
    10,
    0
);
/*
 * Get Flodesk credentials
 */
function adfoin_get_flodesk_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $all_credentials = adfoin_read_credentials( 'flodesk' );
    wp_send_json_success( $all_credentials );
}

add_action(
    'wp_ajax_adfoin_save_flodesk_credentials',
    'adfoin_save_flodesk_credentials',
    10,
    0
);
/*
 * Save Flodesk credentials
 */
function adfoin_save_flodesk_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $platform = sanitize_text_field( $_POST['platform'] );
    if ( 'flodesk' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

add_action(
    'wp_ajax_adfoin_get_flodesk_segments',
    'adfoin_get_flodesk_segments',
    10,
    0
);
/*
 * Get Flodesk subscriber lists
 */
function adfoin_get_flodesk_segments() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $cred_id = sanitize_text_field( $_POST['credId'] );
    $page = 1;
    $per_page = 100;
    $all_segments = [];
    do {
        $data = adfoin_flodesk_request(
            'segments?page=' . $page . '&per_page=' . $per_page,
            'GET',
            array(),
            array(),
            $cred_id
        );
        if ( is_wp_error( $data ) ) {
            wp_send_json_error();
        }
        $body = json_decode( wp_remote_retrieve_body( $data ), true );
        $segments = wp_list_pluck( $body['data'], 'name', 'id' );
        $all_segments = array_merge( $all_segments, $segments );
        $page++;
    } while ( count( $body['data'] ) == $per_page );
    wp_send_json_success( $all_segments );
}

add_action(
    'adfoin_flodesk_job_queue',
    'adfoin_flodesk_job_queue',
    10,
    1
);
function adfoin_flodesk_job_queue(  $data  ) {
    adfoin_flodesk_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to flodesk API
 */
function adfoin_flodesk_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data["action_data"]["cl"] ) && adfoin_check_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
        return;
    }
    $data = $record_data['field_data'];
    $segment_id = ( isset( $data['segmentId'] ) ? $data['segmentId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $doptin = ( isset( $data['doptin'] ) && $data['doptin'] ? true : false );
    $task = $record['task'];
    if ( $task == 'subscribe' ) {
        $subscriber_data = array_filter( array(
            'email'        => ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) ),
            'first_name'   => ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) ),
            'last_name'    => ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) ),
            'double_optin' => $doptin,
        ) );
        $return = adfoin_flodesk_request(
            'subscribers',
            'POST',
            $subscriber_data,
            $record,
            $cred_id
        );
        if ( !is_wp_error( $return ) && !empty( $segment_id ) ) {
            adfoin_flodesk_request(
                "subscribers/{$subscriber_data['email']}/segments",
                'POST',
                array(
                    'segment_ids' => array($segment_id),
                ),
                $record,
                $cred_id
            );
        }
    }
    return;
}
