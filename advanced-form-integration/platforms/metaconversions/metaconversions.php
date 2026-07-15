<?php

/**
 * Meta Conversions API — send a server-side conversion event to Facebook/
 * Instagram Ads when a form is submitted (Lead/Contact/CompleteRegistration).
 *
 * Auth: long-lived access token generated in Events Manager (Settings >
 * Conversions API), stored per Pixel/Dataset ID. Multi-account via
 * ADFOIN_Account_Manager.
 *
 * Confirmed via developers.facebook.com (Server Event Parameters, Customer
 * Information Parameters, Get Started): endpoint is
 * POST graph.facebook.com/v21.0/{pixel_id}/events?access_token=...,
 * body {"data":[{event_name, event_time, action_source, user_data, ...}]}.
 * `em`/`ph`/`fn`/`ln`/`ct`/`st`/`zp`/`country` in user_data MUST be
 * SHA-256 hashed (lowercase+trim first) — Meta does NOT hash them.
 * `client_ip_address`/`client_user_agent`/`fbc`/`fbp` must NOT be hashed.
 *
 * @link https://developers.facebook.com/docs/marketing-api/conversions-api
 */

if ( ! defined( 'ADFOIN_META_CAPI_VERSION' ) ) {
    define( 'ADFOIN_META_CAPI_VERSION', 'v21.0' );
}

add_filter( 'adfoin_action_providers', 'adfoin_metaconversions_actions', 10, 1 );

function adfoin_metaconversions_actions( $actions ) {
    $actions['metaconversions'] = array(
        'title' => __( 'Meta Conversions API', 'advanced-form-integration' ),
        'tasks' => array(
            'send_event' => __( 'Send Conversion Event', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_metaconversions_settings_tab', 10, 1 );

function adfoin_metaconversions_settings_tab( $providers ) {
    $providers['metaconversions'] = __( 'Meta Conversions API', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_metaconversions_settings_view', 10, 1 );

function adfoin_metaconversions_settings_view( $current_tab ) {
    if ( 'metaconversions' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'pixelId',
            'label'         => __( 'Pixel / Dataset ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'     => 'accessToken',
            'label'    => __( 'Conversions API Access Token', 'advanced-form-integration' ),
            'type'     => 'text',
            'required' => true,
            'mask'     => true,
        ),
        array(
            'name'  => 'testEventCode',
            'label' => __( 'Test Event Code (optional)', 'advanced-form-integration' ),
            'type'  => 'text',
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s — Events Manager link. */
            esc_html__( 'In %s, select your Pixel, open Settings > Conversions API, and click "Generate access token".', 'advanced-form-integration' ),
            '<a target="_blank" rel="noopener noreferrer" href="https://business.facebook.com/events_manager2">Events Manager</a>'
        ),
        esc_html__( 'Paste the Pixel/Dataset ID and access token below.', 'advanced-form-integration' ),
        esc_html__( 'Optional: paste a Test Event Code (Events Manager > Test Events tab) while verifying — remove it before going live, or real events will be diverted to test-only processing and won\'t count for ad optimization.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'metaconversions', __( 'Meta Conversions API', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_metaconversions_credentials', 'adfoin_get_metaconversions_credentials', 10, 0 );

function adfoin_get_metaconversions_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'metaconversions' );
}

add_action( 'wp_ajax_adfoin_save_metaconversions_credentials', 'adfoin_save_metaconversions_credentials', 10, 0 );

function adfoin_save_metaconversions_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'metaconversions', array( 'pixelId', 'accessToken', 'testEventCode' ) );
}

function adfoin_metaconversions_credentials_list() {
    foreach ( adfoin_read_credentials( 'metaconversions' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_metaconversions_action_fields' );

function adfoin_metaconversions_action_fields() {
    ?>
    <script type="text/template" id="metaconversions-action-template">
        <table class="form-table" v-if="action.task == 'send_event'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Meta Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=metaconversions' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'send_event', 'Meta Conversions API [PRO]', 'custom data & event dedup' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_metaconversions_fields', 'adfoin_get_metaconversions_fields' );

function adfoin_get_metaconversions_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'eventName',       'value' => __( 'Event Name', 'advanced-form-integration' ), 'required' => true, 'description' => __( 'Lead / Contact / CompleteRegistration / SubmitApplication / Subscribe', 'advanced-form-integration' ) ),
        array( 'key' => 'eventSourceUrl',  'value' => __( 'Event Source URL (page the form was on)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email',           'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',           'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'firstName',       'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',        'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'city',            'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state',           'value' => __( 'State (2-letter)', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',             'value' => __( 'Zip', 'advanced-form-integration' ) ),
        array( 'key' => 'country',         'value' => __( 'Country (2-letter)', 'advanced-form-integration' ) ),
        array( 'key' => 'fbc',             'value' => __( 'fbc Cookie Value (_fbc)', 'advanced-form-integration' ) ),
        array( 'key' => 'fbp',             'value' => __( 'fbp Cookie Value (_fbp)', 'advanced-form-integration' ) ),
        array( 'key' => 'value',           'value' => __( 'Conversion Value', 'advanced-form-integration' ) ),
        array( 'key' => 'currency',        'value' => __( 'Currency (e.g. USD)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

/**
 * Normalize + SHA-256 hash a piece of PII per Meta's matching rules.
 */
function adfoin_metaconversions_hash( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return $value !== '' ? hash( 'sha256', $value ) : '';
}

/**
 * Build the confirmed user_data object. Shared with Pro.
 */
function adfoin_metaconversions_build_user_data( $fields ) {
    $user_data = array();

    if ( ! empty( $fields['email'] ) )    $user_data['em'] = array( adfoin_metaconversions_hash( $fields['email'] ) );
    if ( ! empty( $fields['phone'] ) )    $user_data['ph'] = array( adfoin_metaconversions_hash( preg_replace( '/[^0-9]/', '', $fields['phone'] ) ) );
    if ( ! empty( $fields['firstName'] ) ) $user_data['fn'] = array( adfoin_metaconversions_hash( $fields['firstName'] ) );
    if ( ! empty( $fields['lastName'] ) )  $user_data['ln'] = array( adfoin_metaconversions_hash( $fields['lastName'] ) );
    if ( ! empty( $fields['city'] ) )      $user_data['ct'] = array( adfoin_metaconversions_hash( $fields['city'] ) );
    if ( ! empty( $fields['state'] ) )     $user_data['st'] = array( adfoin_metaconversions_hash( $fields['state'] ) );
    if ( ! empty( $fields['zip'] ) )       $user_data['zp'] = array( adfoin_metaconversions_hash( $fields['zip'] ) );
    if ( ! empty( $fields['country'] ) )   $user_data['country'] = array( adfoin_metaconversions_hash( $fields['country'] ) );

    // Not hashed — raw values per Meta's spec.
    if ( ! empty( $fields['fbc'] ) ) $user_data['fbc'] = $fields['fbc'];
    if ( ! empty( $fields['fbp'] ) ) $user_data['fbp'] = $fields['fbp'];

    if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $user_data['client_user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
    }
    $ip = adfoin_metaconversions_get_client_ip();
    if ( $ip ) {
        $user_data['client_ip_address'] = $ip;
    }

    return $user_data;
}

function adfoin_metaconversions_get_client_ip() {
    foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ) as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }
    return '';
}

add_action( 'adfoin_metaconversions_job_queue', 'adfoin_metaconversions_job_queue', 10, 1 );

function adfoin_metaconversions_job_queue( $data ) {
    adfoin_metaconversions_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_metaconversions_send_data( $record, $posted_data ) {
    if ( 'send_event' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );
    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    if ( ! $cred_id ) {
        return;
    }

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) continue;
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) $fields[ $key ] = $parsed;
    }

    if ( empty( $fields['eventName'] ) ) {
        return;
    }

    $event = array(
        'event_name'  => $fields['eventName'],
        'event_time'  => time(),
        'action_source' => 'website',
        'user_data'   => adfoin_metaconversions_build_user_data( $fields ),
    );
    if ( ! empty( $fields['eventSourceUrl'] ) ) $event['event_source_url'] = $fields['eventSourceUrl'];

    $custom_data = array();
    if ( ! empty( $fields['value'] ) )    $custom_data['value']    = floatval( $fields['value'] );
    if ( ! empty( $fields['currency'] ) ) $custom_data['currency'] = $fields['currency'];
    if ( ! empty( $custom_data ) ) $event['custom_data'] = $custom_data;

    adfoin_metaconversions_request( $event, $cred_id, $record );
}

if ( ! function_exists( 'adfoin_metaconversions_request' ) ) :
function adfoin_metaconversions_request( $event, $cred_id, $record = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'metaconversions', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['pixelId'] ) || empty( $credentials['accessToken'] ) ) {
        return new WP_Error( 'metaconversions_missing_credentials', __( 'Meta Pixel ID / Access Token not configured.', 'advanced-form-integration' ) );
    }

    $body = array( 'data' => array( $event ) );
    if ( ! empty( $credentials['testEventCode'] ) ) {
        $body['test_event_code'] = $credentials['testEventCode'];
    }

    $url = add_query_arg(
        array( 'access_token' => $credentials['accessToken'] ),
        'https://graph.facebook.com/' . ADFOIN_META_CAPI_VERSION . '/' . rawurlencode( $credentials['pixelId'] ) . '/events'
    );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
