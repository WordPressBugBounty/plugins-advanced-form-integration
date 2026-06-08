<?php

/**
 * Re:amaze — Helpdesk API v1 integration.
 *
 *   - create_contact      → POST /api/v1/contacts
 *   - create_conversation → POST /api/v1/conversations
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: HTTP Basic Auth — username=login_email, password=api_token.
 * Base URL is per-brand: https://{brand_subdomain}.reamaze.io/api/v1/
 *
 * @link https://www.reamaze.com/api
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_reamaze_actions', 10, 1 );

function adfoin_reamaze_actions( $actions ) {
    $actions['reamaze'] = array(
        'title' => __( 'Re:amaze', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact'      => __( 'Create Contact', 'advanced-form-integration' ),
            'create_conversation' => __( 'Create Conversation', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_reamaze_settings_tab', 10, 1 );

function adfoin_reamaze_settings_tab( $providers ) {
    $providers['reamaze'] = __( 'Re:amaze', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_reamaze_settings_view', 10, 1 );

function adfoin_reamaze_settings_view( $current_tab ) {
    if ( 'reamaze' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'brand',
            'label'         => __( 'Brand Subdomain', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'mycompany', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'loginEmail',
            'label'       => __( 'Login Email', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'placeholder' => __( 'you@example.com', 'advanced-form-integration' ),
        ),
        array(
            'name'        => 'apiToken',
            'label'       => __( 'API Token', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Paste your Re:amaze API token', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Re:amaze and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://www.reamaze.com/admin/api">Settings &rarr; APIs &amp; Apps</a>' ),
        esc_html__( 'Copy your API Token. The Login Email is the email address you use to sign in to Re:amaze.', 'advanced-form-integration' ),
        esc_html__( 'Your Brand Subdomain is the prefix before .reamaze.io — e.g. if your brand URL is mycompany.reamaze.io, enter "mycompany". A full URL is fine too; AFI will normalize it.', 'advanced-form-integration' ),
        esc_html__( 'AFI authenticates each request as HTTP Basic Auth (login email + API token) against https://{brand}.reamaze.io/api/v1/.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'reamaze', __( 'Re:amaze', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_reamaze_credentials', 'adfoin_get_reamaze_credentials', 10, 0 );

function adfoin_get_reamaze_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'reamaze' );
}

add_action( 'wp_ajax_adfoin_save_reamaze_credentials', 'adfoin_save_reamaze_credentials', 10, 0 );

function adfoin_save_reamaze_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'reamaze', array( 'brand', 'loginEmail', 'apiToken' ) );
}

function adfoin_reamaze_credentials_list() {
    foreach ( adfoin_read_credentials( 'reamaze' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_reamaze_action_fields' );

function adfoin_reamaze_action_fields() {
    ?>
    <script type="text/template" id="reamaze-action-template">
        <table class="form-table" v-if="action.task == 'create_contact' || action.task == 'create_conversation'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Re:amaze Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=reamaze' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_reamaze_fields', 'adfoin_get_reamaze_fields' );

function adfoin_get_reamaze_fields() {
    adfoin_verify_nonce();

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( 'create_conversation' === $task ) {
        $fields = array(
            array( 'key' => 'subject',        'value' => __( 'Subject (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'body',           'value' => __( 'Message Body (required)', 'advanced-form-integration' ), 'required' => true, 'type' => 'textarea' ),
            array( 'key' => 'customer_email', 'value' => __( 'Customer Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'customer_name',  'value' => __( 'Customer Name', 'advanced-form-integration' ) ),
            array( 'key' => 'category',       'value' => __( 'Category (slug, defaults to "support")', 'advanced-form-integration' ) ),
        );
    } else {
        // create_contact (default)
        $fields = array(
            array( 'key' => 'name',    'value' => __( 'Name (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'email',   'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'mobile',  'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'notes',   'value' => __( 'Notes', 'advanced-form-integration' ) ),
            array( 'key' => 'company', 'value' => __( 'Company (stored as custom data.company)', 'advanced-form-integration' ) ),
            array( 'key' => 'source',  'value' => __( 'Source (stored as custom data.source)', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_reamaze_job_queue', 'adfoin_reamaze_job_queue', 10, 1 );

function adfoin_reamaze_job_queue( $data ) {
    adfoin_reamaze_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_reamaze_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'create_contact', 'create_conversation' ), true ) ) {
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

    // Resolve all field-mapped values up-front.
    $reserved = array( 'credId' => 1 );
    $values   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    if ( 'create_contact' === $task ) {
        // Required: name + email.
        if ( empty( $values['name'] ) || empty( $values['email'] ) ) {
            return;
        }

        $contact = array(
            'name'  => (string) $values['name'],
            'email' => (string) $values['email'],
        );

        if ( ! empty( $values['mobile'] ) ) {
            $contact['mobile'] = (string) $values['mobile'];
        }
        if ( ! empty( $values['notes'] ) ) {
            $contact['notes'] = (string) $values['notes'];
        }

        // Custom data bag — only added when at least one mapped key has a value.
        $data_bag = array();
        if ( ! empty( $values['company'] ) ) {
            $data_bag['company'] = (string) $values['company'];
        }
        if ( ! empty( $values['source'] ) ) {
            $data_bag['source'] = (string) $values['source'];
        }
        if ( ! empty( $data_bag ) ) {
            $contact['data'] = $data_bag;
        }

        $payload = array( 'contact' => $contact );

        adfoin_reamaze_request( 'contacts', 'POST', $payload, $record, $cred_id );
        return;
    }

    // create_conversation
    // Required: subject, customer_email, body.
    if ( empty( $values['subject'] ) || empty( $values['customer_email'] ) || empty( $values['body'] ) ) {
        return;
    }

    $user = array(
        'email' => (string) $values['customer_email'],
    );
    if ( ! empty( $values['customer_name'] ) ) {
        $user['name'] = (string) $values['customer_name'];
    }

    $payload = array(
        'conversation' => array(
            'subject'  => (string) $values['subject'],
            'category' => ! empty( $values['category'] ) ? (string) $values['category'] : 'support',
            'message'  => array(
                'body'   => (string) $values['body'],
                'sender' => 'customer',
            ),
            'user'     => $user,
        ),
    );

    adfoin_reamaze_request( 'conversations', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_reamaze_sanitize_subdomain' ) ) :
/**
 * Normalize whatever the user pasted into the Brand Subdomain field down to
 * the bare subdomain token. Accepts "mycompany", "mycompany.reamaze.io",
 * "https://mycompany.reamaze.io/", etc.
 */
function adfoin_reamaze_sanitize_subdomain( $raw ) {
    $sub = strtolower( trim( (string) $raw ) );
    // Strip protocol.
    $sub = preg_replace( '#^https?://#i', '', $sub );
    // Strip trailing path / slash.
    $sub = preg_replace( '#/.*$#', '', $sub );
    // Strip .reamaze.io / .reamaze.com suffix if present.
    $sub = preg_replace( '#\.reamaze\.(io|com)$#i', '', $sub );
    // Defensive: keep only host-safe chars.
    $sub = preg_replace( '/[^a-z0-9\-]/', '', $sub );
    return $sub;
}
endif;

if ( ! function_exists( 'adfoin_reamaze_request' ) ) :
function adfoin_reamaze_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'reamaze', $cred_id );

    if (
        ! is_array( $credentials )
        || empty( $credentials['loginEmail'] )
        || empty( $credentials['apiToken'] )
        || empty( $credentials['brand'] )
    ) {
        return new WP_Error( 'reamaze_missing_credentials', __( 'Re:amaze credentials are incomplete (brand, login email, and API token are all required).', 'advanced-form-integration' ) );
    }

    $sub = adfoin_reamaze_sanitize_subdomain( $credentials['brand'] );

    if ( empty( $sub ) ) {
        return new WP_Error( 'reamaze_bad_subdomain', __( 'Re:amaze brand subdomain is invalid.', 'advanced-form-integration' ) );
    }

    $url    = 'https://' . $sub . '.reamaze.io/api/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $credentials['loginEmail'] . ':' . $credentials['apiToken'] ),
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
