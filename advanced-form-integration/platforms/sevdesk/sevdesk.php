<?php

/**
 * sevDesk — Create Contact via POST /api/v1/Contact.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: <api_token>   (no "Bearer" prefix per sevDesk docs)
 *
 * sevDesk's data model splits contact details across three endpoints:
 *   1. Contact          — name + category (customer/supplier)
 *   2. CommunicationWay — email/phone attached to a contact
 *   3. ContactAddress   — postal address attached to a contact
 *
 * This dispatcher creates the contact first, then attaches optional
 * channels in follow-up calls. Country defaults to Germany (StaticCountry
 * id 1) when an address is supplied — that's the AFI/sevDesk audience.
 *
 * @link https://api.sevdesk.de/
 */

add_filter( 'adfoin_action_providers', 'adfoin_sevdesk_actions', 10, 1 );

function adfoin_sevdesk_actions( $actions ) {
    $actions['sevdesk'] = array(
        'title' => __( 'sevDesk', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sevdesk_settings_tab', 10, 1 );

function adfoin_sevdesk_settings_tab( $providers ) {
    $providers['sevdesk'] = __( 'sevDesk', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sevdesk_settings_view', 10, 1 );

function adfoin_sevdesk_settings_view( $current_tab ) {
    if ( 'sevdesk' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiToken',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your sevDesk API token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In sevDesk, open Settings → User → API Token.', 'advanced-form-integration' ),
        esc_html__( 'Click "Show token" and copy the value.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://my.sevdesk.de/api/v1/ with this token in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'sevdesk', __( 'sevDesk', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_sevdesk_credentials', 'adfoin_get_sevdesk_credentials', 10, 0 );

function adfoin_get_sevdesk_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sevdesk' );
}

add_action( 'wp_ajax_adfoin_save_sevdesk_credentials', 'adfoin_save_sevdesk_credentials', 10, 0 );

function adfoin_save_sevdesk_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sevdesk', array( 'apiToken' ) );
}

function adfoin_sevdesk_credentials_list() {
    foreach ( adfoin_read_credentials( 'sevdesk' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_sevdesk_action_fields' );

function adfoin_sevdesk_action_fields() {
    ?>
    <script type="text/template" id="sevdesk-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'sevDesk Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sevdesk' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_sevdesk_fields', 'adfoin_get_sevdesk_fields' );

function adfoin_get_sevdesk_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        // Company shape (name) OR person (surename + familyname). The
        // dispatcher decides which shape to use based on which fields are
        // populated — company name wins if both are present.
        array( 'key' => 'companyName',  'value' => __( 'Company Name (use for company contacts)', 'advanced-form-integration' ) ),
        array( 'key' => 'surename',     'value' => __( 'First Name (person)', 'advanced-form-integration' ) ),
        array( 'key' => 'familyname',   'value' => __( 'Last Name (person)', 'advanced-form-integration' ) ),

        array( 'key' => 'email',        'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',        'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile',       'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),

        array( 'key' => 'street',       'value' => __( 'Street + Number', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',          'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'city',         'value' => __( 'City', 'advanced-form-integration' ) ),

        array( 'key' => 'description',  'value' => __( 'Description / Note', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_sevdesk_job_queue', 'adfoin_sevdesk_job_queue', 10, 1 );

function adfoin_sevdesk_job_queue( $data ) {
    adfoin_sevdesk_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sevdesk_send_data( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
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

    // Decide between company-shape and person-shape contact.
    // sevDesk requires either "name" (company) or "familyname" (person);
    // populating both creates a company with the person as a free-text
    // family name, which is not what we want.
    $contact = array(
        'category' => array(
            'id'         => 3, // 3 = Customer in sevDesk's seed data
            'objectName' => 'Category',
        ),
    );

    if ( ! empty( $values['companyName'] ) ) {
        $contact['name'] = $values['companyName'];
    } elseif ( ! empty( $values['familyname'] ) ) {
        $contact['familyname'] = $values['familyname'];
        if ( ! empty( $values['surename'] ) ) {
            $contact['surename'] = $values['surename'];
        }
    } else {
        return; // need at least a company name or last name
    }

    if ( ! empty( $values['description'] ) ) {
        $contact['description'] = $values['description'];
    }

    $response = adfoin_sevdesk_request( 'Contact', 'POST', $contact, $record, $cred_id );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $body       = json_decode( wp_remote_retrieve_body( $response ), true );
    $contact_id = is_array( $body ) && isset( $body['objects']['id'] ) ? $body['objects']['id'] : '';

    if ( ! $contact_id ) {
        return;
    }

    // Follow-up calls: attach email/phone via CommunicationWay, address via
    // ContactAddress. Each is optional — only sent when the form provided
    // the value. Failures here don't roll back the contact (sevDesk has no
    // transaction primitive over multiple endpoints).
    if ( ! empty( $values['email'] ) ) {
        adfoin_sevdesk_request( 'CommunicationWay', 'POST', array(
            'contact'    => array( 'id' => $contact_id, 'objectName' => 'Contact' ),
            'type'       => 'EMAIL',
            'value'      => $values['email'],
            'main'       => true,
            'objectName' => 'CommunicationWay',
        ), $record, $cred_id );
    }

    if ( ! empty( $values['phone'] ) ) {
        adfoin_sevdesk_request( 'CommunicationWay', 'POST', array(
            'contact'    => array( 'id' => $contact_id, 'objectName' => 'Contact' ),
            'type'       => 'PHONE',
            'value'      => $values['phone'],
            'main'       => true,
            'objectName' => 'CommunicationWay',
        ), $record, $cred_id );
    }

    if ( ! empty( $values['mobile'] ) ) {
        adfoin_sevdesk_request( 'CommunicationWay', 'POST', array(
            'contact'    => array( 'id' => $contact_id, 'objectName' => 'Contact' ),
            'type'       => 'MOBILE',
            'value'      => $values['mobile'],
            'main'       => false,
            'objectName' => 'CommunicationWay',
        ), $record, $cred_id );
    }

    $address_keys = array( 'street', 'zip', 'city' );
    $address      = array();
    foreach ( $address_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $address[ $key ] = $values[ $key ];
        }
    }
    if ( ! empty( $address ) ) {
        // StaticCountry id 1 = Germany. AFI's sevDesk audience is overwhelm-
        // ingly DACH; other countries need a different id and can be set
        // by editing the contact in sevDesk afterwards.
        $address['country']    = array( 'id' => 1, 'objectName' => 'StaticCountry' );
        $address['contact']    = array( 'id' => $contact_id, 'objectName' => 'Contact' );
        $address['objectName'] = 'ContactAddress';

        adfoin_sevdesk_request( 'ContactAddress', 'POST', $address, $record, $cred_id );
    }
}

if ( ! function_exists( 'adfoin_sevdesk_request' ) ) :
function adfoin_sevdesk_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'sevdesk', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiToken'] ) ) {
        return new WP_Error( 'sevdesk_missing_credentials', __( 'sevDesk API token not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://my.sevdesk.de/api/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            // sevDesk's Authorization header is just the raw token — no
            // Bearer prefix. This is per their published API spec.
            'Authorization' => $credentials['apiToken'],
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
