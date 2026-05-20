<?php

/**
 * Knack — Create Record via POST /v1/objects/{object_key}/records.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: X-Knack-Application-Id + X-Knack-REST-API-Key headers.
 *
 * @link https://docs.knack.com/reference/using-the-api
 */

add_filter( 'adfoin_action_providers', 'adfoin_knack_actions', 10, 1 );

function adfoin_knack_actions( $actions ) {
    $actions['knack'] = array(
        'title' => __( 'Knack', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_knack_settings_tab', 10, 1 );

function adfoin_knack_settings_tab( $providers ) {
    $providers['knack'] = __( 'Knack', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_knack_settings_view', 10, 1 );

function adfoin_knack_settings_view( $current_tab ) {
    if ( 'knack' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'appId',
            'label'         => __( 'App ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiKey',
            'label'         => __( 'REST API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Knack, open Settings → API & Code and copy the App ID.', 'advanced-form-integration' ),
        esc_html__( 'Click "API Keys" and create (or reuse) a REST API key.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI calls https://api.knack.com/v1/ with these headers.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'knack', __( 'Knack', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_knack_credentials', 'adfoin_get_knack_credentials', 10, 0 );

function adfoin_get_knack_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'knack' );
}

add_action( 'wp_ajax_adfoin_save_knack_credentials', 'adfoin_save_knack_credentials', 10, 0 );

function adfoin_save_knack_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'knack', array( 'appId', 'apiKey' ) );
}

function adfoin_knack_credentials_list() {
    foreach ( adfoin_read_credentials( 'knack' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_knack_action_fields' );

function adfoin_knack_action_fields() {
    ?>
    <script type="text/template" id="knack-action-template">
        <table class="form-table" v-if="action.task == 'create_record'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Knack Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=knack' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Object', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[objectKey]" v-model="fielddata.objectKey">
                        <option value=""><?php esc_html_e( 'Select Object...', 'advanced-form-integration' ); ?></option>
                        <option v-for="obj in fielddata.objects" :value="obj.key">{{ obj.name }} ({{ obj.key }})</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': objectLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_record', 'Knack [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_knack_objects', 'adfoin_get_knack_objects', 10, 0 );

function adfoin_get_knack_objects() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'No Knack account selected.', 'advanced-form-integration' ) ) );
    }

    $credentials = adfoin_get_credentials_by_id( 'knack', $cred_id );
    $app_id      = isset( $credentials['appId'] ) ? trim( (string) $credentials['appId'] ) : '';

    if ( ! $app_id ) {
        wp_send_json_error( array( 'message' => __( 'Knack App ID missing.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_knack_request( 'applications/' . rawurlencode( $app_id ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $objects = adfoin_knack_extract_objects( $body );

    wp_send_json_success( $objects );
}

add_action( 'wp_ajax_adfoin_get_knack_fields', 'adfoin_get_knack_fields', 10, 0 );

function adfoin_get_knack_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id    = isset( $_POST['credId'] )    ? sanitize_text_field( wp_unslash( $_POST['credId'] ) )    : '';
    $object_key = isset( $_POST['objectKey'] ) ? sanitize_text_field( wp_unslash( $_POST['objectKey'] ) ) : '';

    if ( ! $cred_id || ! $object_key ) {
        wp_send_json_error( array( 'message' => __( 'Knack account and object are required.', 'advanced-form-integration' ) ) );
    }

    $credentials = adfoin_get_credentials_by_id( 'knack', $cred_id );
    $app_id      = isset( $credentials['appId'] ) ? trim( (string) $credentials['appId'] ) : '';

    if ( ! $app_id ) {
        wp_send_json_error( array( 'message' => __( 'Knack App ID missing.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_knack_request( 'applications/' . rawurlencode( $app_id ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $objects = adfoin_knack_extract_objects( $body, true );

    $fields = array();
    foreach ( $objects as $object ) {
        if ( ( $object['key'] ?? '' ) !== $object_key ) {
            continue;
        }
        foreach ( $object['fields'] as $field ) {
            $key  = $field['key']  ?? '';
            $name = $field['name'] ?? $key;
            if ( '' === $key ) {
                continue;
            }
            $fields[] = array( 'key' => $key, 'value' => $name );
        }
        break;
    }

    wp_send_json_success( $fields );
}

if ( ! function_exists( 'adfoin_knack_extract_objects' ) ) :
/**
 * Pull the objects array out of a /v1/applications/{id} payload.
 *
 * @param array $body
 * @param bool  $with_fields When true, include the fields array on each object.
 *
 * @return array<int, array{key:string,name:string,fields?:array}>
 */
function adfoin_knack_extract_objects( $body, $with_fields = false ) {
    $raw = array();
    if ( isset( $body['application']['objects'] ) && is_array( $body['application']['objects'] ) ) {
        $raw = $body['application']['objects'];
    } elseif ( isset( $body['objects'] ) && is_array( $body['objects'] ) ) {
        $raw = $body['objects'];
    }

    $out = array();
    foreach ( $raw as $object ) {
        $key  = isset( $object['key'] )  ? (string) $object['key']  : '';
        $name = isset( $object['name'] ) ? (string) $object['name'] : $key;
        if ( '' === $key ) {
            continue;
        }
        $entry = array( 'key' => $key, 'name' => $name );
        if ( $with_fields ) {
            $entry['fields'] = isset( $object['fields'] ) && is_array( $object['fields'] ) ? $object['fields'] : array();
        }
        $out[] = $entry;
    }
    return $out;
}
endif;

add_action( 'adfoin_knack_job_queue', 'adfoin_knack_job_queue', 10, 1 );

function adfoin_knack_job_queue( $data ) {
    adfoin_knack_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_knack_send_data( $record, $posted_data ) {
    if ( 'create_record' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] )    ? $field_data['credId']    : '';
    $object_key = isset( $field_data['objectKey'] ) ? trim( (string) $field_data['objectKey'] ) : '';

    if ( ! $cred_id || ! $object_key ) {
        return;
    }

    $reserved = array( 'credId' => 1, 'objectKey' => 1 );

    $payload = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        if ( 0 !== strpos( $key, 'field_' ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $payload[ $key ] = $parsed;
        }
    }

    $payload = apply_filters( 'adfoin_knack_record_payload', $payload, $field_data, $posted_data );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_knack_request( 'objects/' . rawurlencode( $object_key ) . '/records', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_knack_request' ) ) :
/**
 * Call the Knack API.
 *
 * @param string $endpoint Path under /v1/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_knack_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $app_id  = '';
    $api_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'knack', $cred_id );
        if ( is_array( $credentials ) ) {
            $app_id  = isset( $credentials['appId'] )  ? trim( (string) $credentials['appId'] )  : '';
            $api_key = isset( $credentials['apiKey'] ) ? trim( (string) $credentials['apiKey'] ) : '';
        }
    }

    if ( ! $app_id || ! $api_key ) {
        return new WP_Error( 'knack_missing_credentials', __( 'Knack credentials are not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.knack.com/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-Knack-Application-Id' => $app_id,
            'X-Knack-REST-API-Key'   => $api_key,
            'Accept'                 => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }
    } else {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
