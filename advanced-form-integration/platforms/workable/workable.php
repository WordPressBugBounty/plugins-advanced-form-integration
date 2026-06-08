<?php

/**
 * Workable — Create Candidate via POST /spi/v3/jobs/{shortcode}/candidates.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_token> (Workable API access token)
 *
 * Workable's API base is per-account: https://{subdomain}.workable.com/spi/v3/
 * The candidate is created against a specific job, identified by its
 * shortcode (a 9-char alphanumeric ID) — supplied as a form field.
 *
 * @link https://workable.readme.io/
 */

add_filter( 'adfoin_action_providers', 'adfoin_workable_actions', 10, 1 );

function adfoin_workable_actions( $actions ) {
    $actions['workable'] = array(
        'title' => __( 'Workable', 'advanced-form-integration' ),
        'tasks' => array(
            'create_candidate' => __( 'Create Candidate', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_workable_settings_tab', 10, 1 );

function adfoin_workable_settings_tab( $providers ) {
    $providers['workable'] = __( 'Workable', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_workable_settings_view', 10, 1 );

function adfoin_workable_settings_view( $current_tab ) {
    if ( 'workable' !== $current_tab ) {
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
            'placeholder'   => __( 'e.g. mycompany (from mycompany.workable.com)', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Access Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Workable API access token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Workable, click your profile avatar (top right) and choose "API Access", then click "Generate New Token".', 'advanced-form-integration' ),
        esc_html__( 'Choose an appropriate role (Admin or HR Admin) and generate the token.', 'advanced-form-integration' ),
        esc_html__( 'Copy the generated token. Note your account subdomain — the prefix of your Workable URL (e.g. for https://mycompany.workable.com the subdomain is "mycompany").', 'advanced-form-integration' ),
        esc_html__( 'Paste both values below. AFI calls https://{subdomain}.workable.com/spi/v3/ with the token in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'workable', __( 'Workable', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_workable_credentials', 'adfoin_get_workable_credentials', 10, 0 );

function adfoin_get_workable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'workable' );
}

add_action( 'wp_ajax_adfoin_save_workable_credentials', 'adfoin_save_workable_credentials', 10, 0 );

function adfoin_save_workable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'workable', array( 'subdomain', 'api_token' ) );
}

function adfoin_workable_credentials_list() {
    foreach ( adfoin_read_credentials( 'workable' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Normalize whatever the user pasted into the bare Workable subdomain label.
 *
 * Accepts "mycompany", "mycompany.workable.com",
 * "https://mycompany.workable.com/", etc. — returns "mycompany" (lowercased,
 * alnum + dash only). Returns '' if nothing usable was supplied.
 */
function adfoin_workable_normalize_subdomain( $input ) {
    $subdomain = strtolower( trim( (string) $input ) );
    $subdomain = preg_replace( '#^https?://#i', '', $subdomain );
    $subdomain = preg_replace( '#\.workable\.com.*$#i', '', $subdomain );
    $subdomain = trim( (string) $subdomain, "/ \t\n\r\0\x0B" );
    $subdomain = preg_replace( '/[^a-z0-9\-]/', '', (string) $subdomain );

    return (string) $subdomain;
}

add_action( 'adfoin_action_fields', 'adfoin_workable_action_fields' );

function adfoin_workable_action_fields() {
    ?>
    <script type="text/template" id="workable-action-template">
        <table class="form-table" v-if="action.task == 'create_candidate'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Workable Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=workable' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_workable_fields', 'adfoin_get_workable_fields' );

function adfoin_get_workable_fields() {
    adfoin_verify_nonce();

    $fields = array(
        // Job — shortcode is part of the URL, not the candidate payload.
        array( 'key' => 'job_shortcode', 'value' => __( 'Job Shortcode (required, 9-char job ID)', 'advanced-form-integration' ), 'required' => true ),

        // Candidate identity — supply either "name" or firstname/lastname.
        array( 'key' => 'name',          'value' => __( 'Full Name (or use First + Last below)', 'advanced-form-integration' ) ),
        array( 'key' => 'firstname',     'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastname',      'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',         'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),

        // Profile / contact
        array( 'key' => 'headline',      'value' => __( 'Headline (e.g. Senior Engineer)', 'advanced-form-integration' ) ),
        array( 'key' => 'summary',       'value' => __( 'Summary', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',         'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'address',       'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'cover_letter',  'value' => __( 'Cover Letter', 'advanced-form-integration' ) ),

        // Misc
        array( 'key' => 'tags',          'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_workable_job_queue', 'adfoin_workable_job_queue', 10, 1 );

function adfoin_workable_job_queue( $data ) {
    adfoin_workable_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_workable_send_data( $record, $posted_data ) {
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

    // Resolve all flat values up-front. Workable's candidate envelope is
    // assembled below — the form just feeds us flat key=>value pairs.
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

    // job_shortcode + email are hard requirements for Workable's
    // POST /jobs/{shortcode}/candidates endpoint.
    if ( empty( $values['job_shortcode'] ) || empty( $values['email'] ) ) {
        return;
    }

    $job_shortcode = $values['job_shortcode'];
    unset( $values['job_shortcode'] );

    // Synthesize "name" from firstname + lastname when the user didn't
    // map a single full-name field on the form. Workable accepts either
    // form, but the docs encourage providing "name" plus the split parts.
    if ( empty( $values['name'] ) ) {
        $first  = isset( $values['firstname'] ) ? trim( (string) $values['firstname'] ) : '';
        $last   = isset( $values['lastname'] )  ? trim( (string) $values['lastname'] )  : '';
        $joined = trim( $first . ' ' . $last );
        if ( '' !== $joined ) {
            $values['name'] = $joined;
        }
    }

    $candidate = array(
        'email' => $values['email'],
    );

    // Optional, top-level candidate fields — only included if mapped/non-empty.
    $optional_keys = array(
        'name',
        'firstname',
        'lastname',
        'headline',
        'summary',
        'address',
        'phone',
        'cover_letter',
    );

    foreach ( $optional_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $candidate[ $key ] = $values[ $key ];
        }
    }

    // Tags: form gives us "website, applicants, vip" — API wants an array.
    if ( ! empty( $values['tags'] ) ) {
        $tag_parts = array_map( 'trim', explode( ',', (string) $values['tags'] ) );
        $tag_parts = array_values( array_filter( $tag_parts, 'strlen' ) );
        if ( ! empty( $tag_parts ) ) {
            $candidate['tags'] = $tag_parts;
        }
    }

    $payload = array(
        'sourced'   => true,
        'candidate' => $candidate,
    );

    $endpoint = 'jobs/' . rawurlencode( $job_shortcode ) . '/candidates';

    adfoin_workable_request( $endpoint, 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_workable_request' ) ) :
function adfoin_workable_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'workable', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['api_token'] ) || empty( $credentials['subdomain'] ) ) {
        return new WP_Error( 'workable_missing_credentials', __( 'Workable subdomain and API token must both be configured.', 'advanced-form-integration' ) );
    }

    $subdomain = adfoin_workable_normalize_subdomain( $credentials['subdomain'] );

    if ( '' === $subdomain ) {
        return new WP_Error( 'workable_bad_subdomain', __( 'Workable subdomain is invalid.', 'advanced-form-integration' ) );
    }

    $url    = 'https://' . $subdomain . '.workable.com/spi/v3/' . ltrim( $endpoint, '/' );
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
