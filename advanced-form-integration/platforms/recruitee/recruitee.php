<?php

/**
 * Recruitee — Create Candidate via POST /c/{company_id}/candidates.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager. Each account
 * holds a numeric company_id plus a Personal API token. Auth header:
 * Authorization: Bearer <api_token>.
 *
 * Recruitee wraps the candidate body in a top-level "candidate" envelope and
 * expects emails/phones/sources/tags/offers as arrays even when only a single
 * value is supplied — the dispatcher normalizes the flat form-field map
 * below. offer_id is optional; when present we attach the candidate to that
 * job offer via the offers[] array.
 *
 * @link https://docs.recruitee.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_recruitee_actions', 10, 1 );

function adfoin_recruitee_actions( $actions ) {
    $actions['recruitee'] = array(
        'title' => __( 'Recruitee', 'advanced-form-integration' ),
        'tasks' => array(
            'create_candidate' => __( 'Create Candidate', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_recruitee_settings_tab', 10, 1 );

function adfoin_recruitee_settings_tab( $providers ) {
    $providers['recruitee'] = __( 'Recruitee', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_recruitee_settings_view', 10, 1 );

function adfoin_recruitee_settings_view( $current_tab ) {
    if ( 'recruitee' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'company_id',
            'label'         => __( 'Company ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'e.g. 123456', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_token',
            'label'         => __( 'Personal API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Recruitee Personal API token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Recruitee, go to Settings → Apps & integrations → Personal API tokens.', 'advanced-form-integration' ),
        esc_html__( 'Click "New token" and create one with appropriate scopes.', 'advanced-form-integration' ),
        esc_html__( 'Copy the token. Also note your Company ID (in the URL when logged in: https://<company>.recruitee.com/... — the ID is shown in Settings → Company).', 'advanced-form-integration' ),
        esc_html__( 'Paste both here. AFI calls https://api.recruitee.com/c/{company_id}/ with the token in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'recruitee', __( 'Recruitee', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_recruitee_credentials', 'adfoin_get_recruitee_credentials', 10, 0 );

function adfoin_get_recruitee_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'recruitee' );
}

add_action( 'wp_ajax_adfoin_save_recruitee_credentials', 'adfoin_save_recruitee_credentials', 10, 0 );

function adfoin_save_recruitee_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'recruitee', array( 'company_id', 'api_token' ) );
}

function adfoin_recruitee_credentials_list() {
    foreach ( adfoin_read_credentials( 'recruitee' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_recruitee_action_fields' );

function adfoin_recruitee_action_fields() {
    ?>
    <script type="text/template" id="recruitee-action-template">
        <table class="form-table" v-if="action.task == 'create_candidate'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Recruitee Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=recruitee' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_recruitee_fields', 'adfoin_get_recruitee_fields' );

function adfoin_get_recruitee_fields() {
    adfoin_verify_nonce();

    $fields = array(
        // "name" wins when supplied directly; otherwise the dispatcher
        // synthesizes it from first_name + last_name below.
        array( 'key' => 'name',         'value' => __( 'Full Name (required if first/last name not provided)', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name',   'value' => __( 'First Name (combined with Last Name into "name")', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',    'value' => __( 'Last Name (combined with First Name into "name")', 'advanced-form-integration' ) ),
        array( 'key' => 'email',        'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',        'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'cover_letter', 'value' => __( 'Cover Letter', 'advanced-form-integration' ) ),
        array( 'key' => 'source',       'value' => __( 'Source (defaults to "WordPress Form")', 'advanced-form-integration' ) ),
        array( 'key' => 'tags',         'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'offer_id',     'value' => __( 'Offer ID (integer job posting ID to attach the candidate to)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_recruitee_job_queue', 'adfoin_recruitee_job_queue', 10, 1 );

function adfoin_recruitee_job_queue( $data ) {
    adfoin_recruitee_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_recruitee_send_data( $record, $posted_data ) {
    if ( 'create_candidate' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat form-field tokens up-front. Recruitee's "candidate"
    // payload is assembled below from the flat key=>value map.
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

    // Synthesize "name" from first/last when not supplied directly. Recruitee
    // requires a non-empty name on every candidate; bail silently if we have
    // neither a name nor any first/last so the queue worker doesn't keep
    // retrying a doomed payload.
    $name = isset( $values['name'] ) ? trim( (string) $values['name'] ) : '';
    if ( '' === $name ) {
        $first = isset( $values['first_name'] ) ? trim( (string) $values['first_name'] ) : '';
        $last  = isset( $values['last_name'] ) ? trim( (string) $values['last_name'] ) : '';
        $name  = trim( $first . ' ' . $last );
    }

    if ( '' === $name ) {
        return;
    }

    $candidate = array(
        'name'         => $name,
        'emails'       => array(),
        'phones'       => array(),
        'social_links' => array(),
        'links'        => array(),
    );

    if ( ! empty( $values['email'] ) ) {
        $candidate['emails'] = array( $values['email'] );
    }

    if ( ! empty( $values['phone'] ) ) {
        $candidate['phones'] = array( $values['phone'] );
    }

    if ( ! empty( $values['cover_letter'] ) ) {
        $candidate['cover_letter'] = $values['cover_letter'];
    }

    // sources[] — single string wrapped in array; default to "WordPress Form".
    $source              = ! empty( $values['source'] ) ? (string) $values['source'] : 'WordPress Form';
    $candidate['sources'] = array( $source );

    // tags[] — split "website, vip, hot" into ["website","vip","hot"].
    if ( ! empty( $values['tags'] ) ) {
        $tag_parts = array_map( 'trim', explode( ',', (string) $values['tags'] ) );
        $tag_parts = array_values( array_filter( $tag_parts, 'strlen' ) );
        if ( ! empty( $tag_parts ) ) {
            $candidate['tags'] = $tag_parts;
        }
    }

    // offers[] — Recruitee expects integer offer IDs. Cast and wrap.
    if ( ! empty( $values['offer_id'] ) ) {
        $offer_id = (int) $values['offer_id'];
        if ( $offer_id > 0 ) {
            $candidate['offers'] = array( $offer_id );
        }
    }

    $payload = array(
        'candidate' => $candidate,
    );

    adfoin_recruitee_request( 'candidates', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_recruitee_request' ) ) :
function adfoin_recruitee_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'recruitee', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['api_token'] ) || empty( $credentials['company_id'] ) ) {
        return new WP_Error( 'recruitee_missing_credentials', __( 'Recruitee Company ID and API token must both be configured.', 'advanced-form-integration' ) );
    }

    // Strip anything users might paste — trailing slash, whitespace. The
    // company_id segment is typically a bare integer but rawurlencode keeps
    // us safe if Recruitee ever issues string slugs.
    $company_id = trim( (string) $credentials['company_id'] );
    $company_id = trim( $company_id, "/ \t\n\r\0\x0B" );

    if ( '' === $company_id ) {
        return new WP_Error( 'recruitee_bad_company_id', __( 'Recruitee Company ID is invalid.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.recruitee.com/c/' . rawurlencode( $company_id ) . '/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['api_token'],
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
