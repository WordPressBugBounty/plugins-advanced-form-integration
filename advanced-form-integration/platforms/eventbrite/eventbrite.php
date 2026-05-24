<?php

/**
 * Eventbrite — Register attendees for an event via the Orders endpoint.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <private_token>
 *
 * Eventbrite's attendee model is awkward: attendees are created indirectly
 * via "orders". For programmatic registration we POST a free order against
 * the user's selected event_id + ticket_class_id, with a single attendee
 * profile embedded inside it.
 *
 * After saving the token the settings flow also discovers the user's
 * organization_id via GET /users/me/organizations/ — most subsequent
 * helpers scope by organization, even though the order-creation endpoint
 * itself is event-scoped.
 *
 * @link https://www.eventbrite.com/platform/api
 */

add_filter( 'adfoin_action_providers', 'adfoin_eventbrite_actions', 10, 1 );

function adfoin_eventbrite_actions( $actions ) {
    $actions['eventbrite'] = array(
        'title' => __( 'Eventbrite', 'advanced-form-integration' ),
        'tasks' => array(
            'create_attendee' => __( 'Create Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_eventbrite_settings_tab', 10, 1 );

function adfoin_eventbrite_settings_tab( $providers ) {
    $providers['eventbrite'] = __( 'Eventbrite', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_eventbrite_settings_view', 10, 1 );

function adfoin_eventbrite_settings_view( $current_tab ) {
    if ( 'eventbrite' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'privateToken',
            'label'         => __( 'Private Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'e.g. ABCDEFGHIJKLMNOPQRST', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Eventbrite and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://www.eventbrite.com/platform/api-keys">API Keys</a>' ),
        esc_html__( 'Copy the "Private Token" listed under your personal account (no OAuth app required).', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI sends it to https://www.eventbriteapi.com/v3/ as an Authorization: Bearer header.', 'advanced-form-integration' ),
        esc_html__( 'In your action you will be asked for an Event ID and a Ticket Class ID — both are numeric strings found in your Eventbrite event admin (Event ID is in the event URL; Ticket Class IDs are visible under Tickets > each ticket type).', 'advanced-form-integration' ),
        esc_html__( 'Important: this integration creates a FREE order containing one attendee. The ticket class you supply must be a free tier — paid tickets require checkout/payment fields that AFI does not collect.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'eventbrite', __( 'Eventbrite', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_eventbrite_credentials', 'adfoin_get_eventbrite_credentials', 10, 0 );

function adfoin_get_eventbrite_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'eventbrite' );
}

add_action( 'wp_ajax_adfoin_save_eventbrite_credentials', 'adfoin_save_eventbrite_credentials', 10, 0 );

function adfoin_save_eventbrite_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // After save, discover and persist the user's organization_id so
    // subsequent helpers (event listing, etc.) can scope correctly.
    ADFOIN_Account_Manager::ajax_save_credentials( 'eventbrite', array( 'privateToken' ) );

    // Note: ajax_save_credentials calls wp_send_json_* and exits, so any
    // post-save org discovery happens on the next request via the helper
    // adfoin_eventbrite_ensure_organization_id() below.
}

/**
 * Resolve & cache the org id for a given credential. Lazily populated on
 * first use so we don't block the credential-save AJAX round-trip.
 */
function adfoin_eventbrite_ensure_organization_id( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'eventbrite', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['privateToken'] ) ) {
        return '';
    }

    if ( ! empty( $credentials['organizationId'] ) ) {
        return $credentials['organizationId'];
    }

    $response = adfoin_eventbrite_request( 'users/me/organizations/', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['organizations'][0]['id'] ) ) {
        return '';
    }

    $org_id = (string) $body['organizations'][0]['id'];

    // Persist the discovered organization id back onto the credential row so
    // it is only fetched once. Writes to the canonical adfoin_credentials store
    // (the former ADFOIN_Account_Manager::update_credential_field path never
    // existed, so this previously wrote to a dead per-platform option).
    $records = adfoin_read_credentials( 'eventbrite' );
    if ( is_array( $records ) ) {
        foreach ( $records as &$row ) {
            if ( is_array( $row ) && isset( $row['id'] ) && (string) $row['id'] === (string) $cred_id ) {
                $row['organizationId'] = $org_id;
                break;
            }
        }
        unset( $row );
        adfoin_save_credentials( 'eventbrite', $records );
    }

    return $org_id;
}

function adfoin_eventbrite_credentials_list() {
    foreach ( adfoin_read_credentials( 'eventbrite' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_eventbrite_action_fields' );

function adfoin_eventbrite_action_fields() {
    ?>
    <script type="text/template" id="eventbrite-action-template">
        <table class="form-table" v-if="action.task == 'create_attendee'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Eventbrite Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=eventbrite' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_eventbrite_fields', 'adfoin_get_eventbrite_fields' );

function adfoin_get_eventbrite_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        // Event + ticket class identifiers — both supplied by the user as
        // plain text, copied from their Eventbrite admin URL/tickets page.
        array( 'key' => 'event_id',        'value' => __( 'Event ID (required, numeric — from Eventbrite event URL)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'ticket_class_id', 'value' => __( 'Ticket Class ID (required, numeric — free ticket tier)', 'advanced-form-integration' ), 'required' => true ),

        // Attendee profile
        array( 'key' => 'email',           'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name',      'value' => __( 'First Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'last_name',       'value' => __( 'Last Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'cell_phone',      'value' => __( 'Cell Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'work_phone',      'value' => __( 'Work Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'company',         'value' => __( 'Company', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_eventbrite_job_queue', 'adfoin_eventbrite_job_queue', 10, 1 );

function adfoin_eventbrite_job_queue( $data ) {
    adfoin_eventbrite_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_eventbrite_send_data( $record, $posted_data ) {
    if ( 'create_attendee' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat values up-front. The order envelope is assembled
    // below — the form just hands us key=>value pairs.
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

    // Hard requirements for the orders endpoint.
    $event_id        = isset( $values['event_id'] ) ? trim( (string) $values['event_id'] ) : '';
    $ticket_class_id = isset( $values['ticket_class_id'] ) ? trim( (string) $values['ticket_class_id'] ) : '';

    if ( '' === $event_id || '' === $ticket_class_id ) {
        return;
    }
    if ( empty( $values['email'] ) || empty( $values['first_name'] ) || empty( $values['last_name'] ) ) {
        return;
    }

    $profile = array(
        'email'      => $values['email'],
        'first_name' => $values['first_name'],
        'last_name'  => $values['last_name'],
    );

    if ( ! empty( $values['cell_phone'] ) ) {
        $profile['cell_phone'] = $values['cell_phone'];
    }
    if ( ! empty( $values['work_phone'] ) ) {
        $profile['work_phone'] = $values['work_phone'];
    }
    if ( ! empty( $values['company'] ) ) {
        $profile['company'] = $values['company'];
    }

    $payload = array(
        'order' => array(
            'ticket_class_id' => $ticket_class_id,
            'attendees'       => array(
                array(
                    'ticket_class_id' => $ticket_class_id,
                    'profile'         => $profile,
                ),
            ),
        ),
    );

    adfoin_eventbrite_request( 'events/' . rawurlencode( $event_id ) . '/orders/', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_eventbrite_request' ) ) :
function adfoin_eventbrite_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'eventbrite', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['privateToken'] ) ) {
        return new WP_Error( 'eventbrite_missing_credentials', __( 'Eventbrite private token not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://www.eventbriteapi.com/v3/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['privateToken'],
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
