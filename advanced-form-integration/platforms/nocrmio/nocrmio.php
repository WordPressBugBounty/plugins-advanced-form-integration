<?php

/**
 * noCRM.io — Create Lead via POST /api/v2/leads.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: X-API-KEY: <api_token> header.
 *
 * noCRM.io is a per-account subdomain API — every account has its own
 * base host like https://{subdomain}.nocrm.io/api/v2/, so we store both
 * the subdomain and the API token on each credential record.
 *
 * Confirmed via https://www.nocrm.io/api — the create-lead endpoint only
 * accepts `title`, `description`, `user_id`, `tags`, `created_at`, `step`.
 * There is NO nested `contact{}` object and NO `amount`/`currency` params —
 * noCRM's lead model isn't a generic CRM contact; per their own docs,
 * contact details are embedded as free text inside `description` (their
 * own example: "Firstname: John\nLastname: Doe\nEmail: john.doe@..."), and
 * assignment uses `user_id` (which accepts either a numeric ID or an email
 * address as its value), not `user_email`. An earlier version of this file
 * sent a `contact` object and `user_email`/`amount`/`currency` — none of
 * those are real parameters, so that data was being silently dropped.
 *
 * @link https://www.nocrm.io/api
 */

add_filter( 'adfoin_action_providers', 'adfoin_nocrmio_actions', 10, 1 );

function adfoin_nocrmio_actions( $actions ) {
    $actions['nocrmio'] = array(
        'title' => __( 'noCRM.io', 'advanced-form-integration' ),
        'tasks' => array(
            'create_lead' => __( 'Create Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_nocrmio_settings_tab', 10, 1 );

function adfoin_nocrmio_settings_tab( $providers ) {
    $providers['nocrmio'] = __( 'noCRM.io', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_nocrmio_settings_view', 10, 1 );

function adfoin_nocrmio_settings_view( $current_tab ) {
    if ( 'nocrmio' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'subdomain',
            'label'         => __( 'Account Subdomain', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'e.g. mycompany (from mycompany.nocrm.io)', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'apiToken',
            'label'       => __( 'API Token', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Paste your noCRM.io API token', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Sign in to your noCRM.io account. The subdomain is the part before ".nocrm.io" in your URL (e.g. "mycompany" for mycompany.nocrm.io).', 'advanced-form-integration' ),
        sprintf( __( 'Open %s and create a new API key.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.nocrm.io/api">Admin Panel &raquo; API</a>' ),
        esc_html__( 'Copy the generated API token.', 'advanced-form-integration' ),
        esc_html__( 'Paste both values below. AFI calls https://{subdomain}.nocrm.io/api/v2/ with the token in the X-API-KEY header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'nocrmio', __( 'noCRM.io', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_nocrmio_credentials', 'adfoin_get_nocrmio_credentials', 10, 0 );

function adfoin_get_nocrmio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'nocrmio' );
}

add_action( 'wp_ajax_adfoin_save_nocrmio_credentials', 'adfoin_save_nocrmio_credentials', 10, 0 );

function adfoin_save_nocrmio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'nocrmio', array( 'subdomain', 'apiToken' ) );
}

function adfoin_nocrmio_credentials_list() {
    foreach ( adfoin_read_credentials( 'nocrmio' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_nocrmio_action_fields' );

function adfoin_nocrmio_action_fields() {
    ?>
    <script type="text/template" id="nocrmio-action-template">
        <table class="form-table" v-if="action.task == 'create_lead'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'noCRM.io Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=nocrmio' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_nocrmio_fields', 'adfoin_get_nocrmio_fields' );

function adfoin_get_nocrmio_fields() {
    adfoin_verify_nonce();

    $fields = array(
        // Lead — title is required by noCRM.io
        array( 'key' => 'title',       'value' => __( 'Lead Title (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'description' => 'Any contact fields mapped below are appended to this text — noCRM.io has no separate contact object.' ),
        array( 'key' => 'tags',        'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'step',        'value' => __( 'Pipeline Step (ID or name)', 'advanced-form-integration' ) ),

        // noCRM.io has no structured contact sub-resource — these are
        // appended as "Label: value" lines into the description text,
        // matching noCRM's own documented convention for lead descriptions.
        array( 'key' => 'first_name',  'value' => __( 'Contact: First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',   'value' => __( 'Contact: Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',       'value' => __( 'Contact: Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',       'value' => __( 'Contact: Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile',      'value' => __( 'Contact: Mobile', 'advanced-form-integration' ) ),
        array( 'key' => 'company',     'value' => __( 'Contact: Company', 'advanced-form-integration' ) ),

        // Owner assignment — noCRM.io's real field is user_id, which accepts
        // either a numeric user ID or the user's email address.
        array( 'key' => 'user_id',     'value' => __( 'Assign To (user email or ID)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_nocrmio_job_queue', 'adfoin_nocrmio_job_queue', 10, 1 );

function adfoin_nocrmio_job_queue( $data ) {
    adfoin_nocrmio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_nocrmio_send_data( $record, $posted_data ) {
    if ( 'create_lead' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat values up-front; assemble the nested contact{} below.
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

    // Title is the only required field on a noCRM.io lead.
    if ( empty( $values['title'] ) ) {
        return;
    }

    $payload = array(
        'title' => $values['title'],
    );

    if ( ! empty( $values['tags'] ) ) {
        // Form gives us "website, form, vip" — API wants ["website","form","vip"]
        $tag_parts = array_map( 'trim', explode( ',', (string) $values['tags'] ) );
        $tag_parts = array_values( array_filter( $tag_parts, 'strlen' ) );
        if ( ! empty( $tag_parts ) ) {
            $payload['tags'] = $tag_parts;
        }
    }

    if ( ! empty( $values['step'] ) ) {
        $payload['step'] = $values['step'];
    }

    // noCRM.io has no nested contact{} object on the create-lead endpoint —
    // contact fields are conventionally embedded as "Label: value" lines
    // inside description (matches noCRM's own documented example).
    $contact_lines = array();
    $contact_map   = array(
        'first_name' => __( 'Firstname', 'advanced-form-integration' ),
        'last_name'  => __( 'Lastname', 'advanced-form-integration' ),
        'email'      => __( 'Email', 'advanced-form-integration' ),
        'phone'      => __( 'Phone', 'advanced-form-integration' ),
        'mobile'     => __( 'Mobile', 'advanced-form-integration' ),
        'company'    => __( 'Company', 'advanced-form-integration' ),
    );
    foreach ( $contact_map as $key => $label ) {
        if ( ! empty( $values[ $key ] ) ) {
            $contact_lines[] = $label . ': ' . $values[ $key ];
        }
    }

    $description_parts = array();
    if ( ! empty( $values['description'] ) ) {
        $description_parts[] = $values['description'];
    }
    if ( ! empty( $contact_lines ) ) {
        $description_parts[] = implode( "\n", $contact_lines );
    }
    if ( ! empty( $description_parts ) ) {
        $payload['description'] = implode( "\n\n", $description_parts );
    }

    // Real field is user_id — accepts either a numeric user ID or an email.
    if ( ! empty( $values['user_id'] ) ) {
        $payload['user_id'] = $values['user_id'];
    }

    adfoin_nocrmio_request( 'leads', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_nocrmio_request' ) ) :
function adfoin_nocrmio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'nocrmio', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiToken'] ) || empty( $credentials['subdomain'] ) ) {
        return new WP_Error( 'nocrmio_missing_credentials', __( 'noCRM.io subdomain and API token must both be configured.', 'advanced-form-integration' ) );
    }

    // Strip anything users might paste — protocol, trailing ".nocrm.io",
    // slashes, whitespace. We only want the bare subdomain label.
    $subdomain = strtolower( trim( (string) $credentials['subdomain'] ) );
    $subdomain = preg_replace( '#^https?://#i', '', $subdomain );
    $subdomain = preg_replace( '#\.nocrm\.io.*$#i', '', $subdomain );
    $subdomain = trim( $subdomain, "/ \t\n\r\0\x0B" );

    if ( '' === $subdomain ) {
        return new WP_Error( 'nocrmio_bad_subdomain', __( 'noCRM.io subdomain is invalid.', 'advanced-form-integration' ) );
    }

    $url    = 'https://' . $subdomain . '.nocrm.io/api/v2/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-API-KEY' => $credentials['apiToken'],
            'Accept'    => 'application/json',
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
