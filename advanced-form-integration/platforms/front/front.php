<?php

/**
 * Front — Collaborative Inbox / Helpdesk integration.
 *
 *   - create_contact      → POST /contacts
 *   - create_conversation → POST /channels/{channel_id}/incoming_messages
 *
 * Multi-account credential storage via ADFOIN_Account_Manager. Auth is a
 * single Front API Token (generated in Front under Settings → Developers →
 * API Tokens), passed as a Bearer token. There is no OAuth dance — tokens
 * are long-lived until the operator rotates them in the Front UI.
 *
 * Front splits its outbound surface across two task shapes:
 *  - Contacts get an array of "handles" (one entry per email / phone), so
 *    we collapse the flat form-field map into that nested structure in the
 *    dispatcher. Links and group_names are exposed as comma-separated text
 *    inputs and split here, since the form mapper has no repeater UI.
 *  - Conversations require a channel_id (visible in Front under
 *    Settings → Channels) which the operator pastes per-action. Front's
 *    "incoming_messages" endpoint requires a Custom Channel — this is the
 *    only way to push a message into a channel without already owning a
 *    conversation_id, so it intentionally targets that channel type.
 *
 * @link https://dev.frontapp.com/reference/introduction
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_front_actions', 10, 1 );

function adfoin_front_actions( $actions ) {
    $actions['front'] = array(
        'title' => __( 'Front', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact'      => __( 'Create Contact', 'advanced-form-integration' ),
            'create_conversation' => __( 'Create Conversation', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_front_settings_tab', 10, 1 );

function adfoin_front_settings_tab( $providers ) {
    $providers['front'] = __( 'Front', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_front_settings_view', 10, 1 );

function adfoin_front_settings_view( $current_tab ) {
    if ( 'front' !== $current_tab ) {
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
            'placeholder'   => __( 'Paste your Front API token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Front and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.frontapp.com/settings/developers/tokens">Settings &rarr; Developers &rarr; API Tokens</a>' ),
        esc_html__( 'Click "Create API token", give it a descriptive name (e.g. WordPress AFI), and grant it the scopes you need (Shared Resources is enough for Create Contact / Create Conversation).', 'advanced-form-integration' ),
        esc_html__( 'Copy the token immediately — Front only displays it once.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api2.frontapp.com/ with this token as Authorization: Bearer.', 'advanced-form-integration' ),
        esc_html__( 'For the Create Conversation task you will also need a Channel ID — find it in Front under Settings → Channels (the cha_xxx identifier of a Custom Channel).', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'front', __( 'Front', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_front_credentials', 'adfoin_get_front_credentials', 10, 0 );

function adfoin_get_front_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'front' );
}

add_action( 'wp_ajax_adfoin_save_front_credentials', 'adfoin_save_front_credentials', 10, 0 );

function adfoin_save_front_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'front', array( 'apiToken' ) );
}

function adfoin_front_credentials_list() {
    foreach ( adfoin_read_credentials( 'front' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_front_action_fields' );

function adfoin_front_action_fields() {
    ?>
    <script type="text/template" id="front-action-template">
        <table class="form-table" v-if="action.task == 'create_contact' || action.task == 'create_conversation'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Front Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=front' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_front_fields', 'adfoin_get_front_fields' );

function adfoin_get_front_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( 'create_conversation' === $task ) {
        $fields = array(
            array( 'key' => 'channel_id',   'value' => __( 'Channel ID (required, e.g. cha_abc123)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'sender_email', 'value' => __( 'Sender Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'sender_name',  'value' => __( 'Sender Name', 'advanced-form-integration' ) ),
            array( 'key' => 'subject',      'value' => __( 'Subject (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'body',         'value' => __( 'Body (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'body_format',  'value' => __( 'Body Format (markdown / html — defaults to markdown)', 'advanced-form-integration' ) ),
        );
    } else {
        // create_contact (default)
        $fields = array(
            array( 'key' => 'name',        'value' => __( 'Name (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'email',       'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'phone',       'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ) ),
            array( 'key' => 'links',       'value' => __( 'Links (comma-separated URLs)', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_front_job_queue', 'adfoin_front_job_queue', 10, 1 );

function adfoin_front_job_queue( $data ) {
    adfoin_front_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_front_send_data( $record, $posted_data ) {
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

    // Resolve all field-mapped values up-front. Per-task payload assembly
    // happens below — the form only ever feeds us flat key=>value pairs.
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

    if ( 'create_conversation' === $task ) {
        // Required: channel_id, sender_email, subject, body.
        if (
            empty( $values['channel_id'] )
            || empty( $values['sender_email'] )
            || empty( $values['subject'] )
            || empty( $values['body'] )
        ) {
            return;
        }

        $sender = array(
            'handle' => (string) $values['sender_email'],
        );
        if ( ! empty( $values['sender_name'] ) ) {
            $sender['name'] = (string) $values['sender_name'];
        }

        // body_format accepts markdown or html; anything else falls back to
        // markdown so a bad mapping does not blow up the API call.
        $body_format = ! empty( $values['body_format'] ) ? strtolower( (string) $values['body_format'] ) : 'markdown';
        if ( ! in_array( $body_format, array( 'markdown', 'html' ), true ) ) {
            $body_format = 'markdown';
        }

        $payload = array(
            'sender'      => $sender,
            'subject'     => (string) $values['subject'],
            'body'        => (string) $values['body'],
            'body_format' => $body_format,
        );

        $channel_id = rawurlencode( (string) $values['channel_id'] );
        adfoin_front_request( 'channels/' . $channel_id . '/incoming_messages', 'POST', $payload, $record, $cred_id );
        return;
    }

    // create_contact
    // Required: name + email (email anchors the primary handle).
    if ( empty( $values['name'] ) || empty( $values['email'] ) ) {
        return;
    }

    // Build handles[]: Front's contact payload expects one entry per channel
    // (email, phone). Email is required and always added first; phone is
    // optional. We deliberately do not surface twitter/intercom/etc here —
    // they can be added later if there is demand.
    $handles = array(
        array(
            'handle' => (string) $values['email'],
            'source' => 'email',
        ),
    );
    if ( ! empty( $values['phone'] ) ) {
        $handles[] = array(
            'handle' => (string) $values['phone'],
            'source' => 'phone',
        );
    }

    $payload = array(
        'name'    => (string) $values['name'],
        'handles' => $handles,
    );

    if ( ! empty( $values['description'] ) ) {
        $payload['description'] = (string) $values['description'];
    }

    // links come in as a comma-separated string (the AFI field mapper has
    // no repeater control); split, trim, and drop blanks.
    if ( ! empty( $values['links'] ) ) {
        $links = array_filter( array_map( 'trim', explode( ',', (string) $values['links'] ) ), 'strlen' );
        if ( ! empty( $links ) ) {
            $payload['links'] = array_values( $links );
        }
    }

    adfoin_front_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_front_request' ) ) :
/**
 * Authenticated JSON request against https://api2.frontapp.com/.
 * Front uses a long-lived API Token — no refresh handling needed. A 401
 * here means the operator revoked or rotated the token and must reconnect
 * via Settings → Front.
 */
function adfoin_front_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'front', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiToken'] ) ) {
        return new WP_Error( 'front_missing_credentials', __( 'Front API token not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api2.frontapp.com/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiToken'],
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
