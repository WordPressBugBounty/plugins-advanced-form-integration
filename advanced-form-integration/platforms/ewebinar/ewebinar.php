<?php

/**
 * eWebinar — Register Attendee via POST /api/registrations.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_key>
 *
 * eWebinar is a Canadian on-demand/automated webinar platform. webinar_id is a
 * UUID string (not an integer). session_id is optional — when omitted,
 * eWebinar auto-picks the next available session: "watch instantly" for
 * on-demand webinars, or the next scheduled slot for scheduled webinars.
 *
 * @link https://app.ewebinar.com/help/articles/api-overview
 */

add_filter( 'adfoin_action_providers', 'adfoin_ewebinar_actions', 10, 1 );

function adfoin_ewebinar_actions( $actions ) {
    $actions['ewebinar'] = array(
        'title' => __( 'eWebinar', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ewebinar_settings_tab', 10, 1 );

function adfoin_ewebinar_settings_tab( $providers ) {
    $providers['ewebinar'] = __( 'eWebinar', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ewebinar_settings_view', 10, 1 );

function adfoin_ewebinar_settings_view( $current_tab ) {
    if ( 'ewebinar' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your eWebinar API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In eWebinar, go to your account avatar -> Account Settings -> API.', 'advanced-form-integration' ),
        esc_html__( 'Copy or generate an API key.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.ewebinar.com/api/ with this token as Bearer.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'ewebinar', __( 'eWebinar', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_ewebinar_credentials', 'adfoin_get_ewebinar_credentials', 10, 0 );

function adfoin_get_ewebinar_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'ewebinar' );
}

add_action( 'wp_ajax_adfoin_save_ewebinar_credentials', 'adfoin_save_ewebinar_credentials', 10, 0 );

function adfoin_save_ewebinar_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'ewebinar', array( 'api_key' ) );
}

function adfoin_ewebinar_credentials_list() {
    foreach ( adfoin_read_credentials( 'ewebinar' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_ewebinar_action_fields' );

function adfoin_ewebinar_action_fields() {
    ?>
    <script type="text/template" id="ewebinar-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'eWebinar Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=ewebinar' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_ewebinar_fields', 'adfoin_get_ewebinar_fields' );

function adfoin_get_ewebinar_fields() {
    adfoin_verify_nonce();

    $fields = array(
        // Webinar selector — eWebinar's webinar_id is a UUID string copied from
        // the eWebinar dashboard URL.
        array( 'key' => 'webinar_id',         'value' => __( 'Webinar ID (required — UUID from eWebinar dashboard)', 'advanced-form-integration' ), 'required' => true ),

        // Session — optional. Leave blank to let eWebinar auto-assign the next
        // available session (watch-instantly for on-demand, next slot for scheduled).
        array( 'key' => 'session_id',         'value' => __( 'Session ID (optional — UUID; leave blank for auto-assign)', 'advanced-form-integration' ) ),

        // Core attendee identity
        array( 'key' => 'first_name',         'value' => __( 'First Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'last_name',          'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',              'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone',              'value' => __( 'Phone (E.164, e.g. +14155551234)', 'advanced-form-integration' ) ),
        array( 'key' => 'company',            'value' => __( 'Company', 'advanced-form-integration' ) ),

        // Arbitrary additional fields — accepted as a raw JSON object string,
        // e.g. {"source":"WordPress form","plan":"pro"}.
        array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields JSON (optional — e.g. {"source":"WordPress form"})', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_ewebinar_job_queue', 'adfoin_ewebinar_job_queue', 10, 1 );

function adfoin_ewebinar_job_queue( $data ) {
    adfoin_ewebinar_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ewebinar_send_data( $record, $posted_data ) {
    if ( 'register_attendee' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat values up-front. eWebinar's payload is mostly flat —
    // custom_fields_json gets parsed and merged into a custom_fields object.
    $values   = array();
    $reserved = array( 'credId' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // Required: webinar_id + first_name + email. Abort silently if any are missing.
    $webinar_id = isset( $values['webinar_id'] ) ? trim( (string) $values['webinar_id'] ) : '';
    if ( '' === $webinar_id || empty( $values['first_name'] ) || empty( $values['email'] ) ) {
        return;
    }

    $payload = array(
        'webinar_id' => $webinar_id,
        'first_name' => (string) $values['first_name'],
        'email'      => (string) $values['email'],
    );

    // Optional flat fields — only include when non-empty.
    foreach ( array( 'last_name', 'phone', 'company' ) as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $payload[ $key ] = (string) $values[ $key ];
        }
    }

    // session_id is optional — when blank, omit it so eWebinar auto-assigns.
    if ( ! empty( $values['session_id'] ) ) {
        $session_id = trim( (string) $values['session_id'] );
        if ( '' !== $session_id ) {
            $payload['session_id'] = $session_id;
        }
    }

    // custom_fields_json — decode to an object. Silently skip if malformed.
    if ( ! empty( $values['custom_fields_json'] ) ) {
        $decoded = json_decode( (string) $values['custom_fields_json'], true );
        if ( is_array( $decoded ) && ! empty( $decoded ) ) {
            // Force JSON object (not array) shape on the wire.
            $payload['custom_fields'] = (object) $decoded;
        }
    }

    adfoin_ewebinar_request( 'registrations', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_ewebinar_request' ) ) :
function adfoin_ewebinar_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'ewebinar', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['api_key'] ) ) {
        return new WP_Error( 'ewebinar_missing_credentials', __( 'eWebinar API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.ewebinar.com/api/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['api_key'],
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
