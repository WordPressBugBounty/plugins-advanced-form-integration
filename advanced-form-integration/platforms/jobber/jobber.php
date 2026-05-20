<?php

/**
 * Jobber — Create Client & Job via the GraphQL API.
 *
 * Endpoint: POST https://api.getjobber.com/api/graphql
 * Headers:  Authorization: Bearer {token}, X-JOBBER-GRAPHQL-VERSION: YYYY-MM-DD
 *
 * @link https://developer.getjobber.com/docs
 */

/**
 * Pinned Jobber GraphQL API version.
 *
 * @link https://developer.getjobber.com/docs/changelog
 */
if ( ! defined( 'ADFOIN_JOBBER_DEFAULT_API_VERSION' ) ) {
    define( 'ADFOIN_JOBBER_DEFAULT_API_VERSION', '2025-04-16' );
}

add_filter( 'adfoin_action_providers', 'adfoin_jobber_actions', 10, 1 );

function adfoin_jobber_actions( $actions ) {
    $actions['jobber'] = array(
        'title' => __( 'Jobber', 'advanced-form-integration' ),
        'tasks' => array(
            'create_job' => __( 'Create Client & Job', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_jobber_settings_tab', 10, 1 );

function adfoin_jobber_settings_tab( $providers ) {
    $providers['jobber'] = __( 'Jobber', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_jobber_settings_view', 10, 1 );

function adfoin_jobber_settings_view( $current_tab ) {
    if ( 'jobber' !== $current_tab ) {
        return;
    }

    $title = __( 'Jobber', 'advanced-form-integration' );
    $key   = 'jobber';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'    => 'accessToken',
                    'label'  => __( 'Access Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'         => 'apiVersion',
                    'label'       => __( 'GraphQL Version (optional)', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => ADFOIN_JOBBER_DEFAULT_API_VERSION,
                ),
            ),
        )
    );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
            <li>%4$s</li>
        </ol>',
        esc_html__( 'Open the Jobber Developer Center and create (or open) a developer app.', 'advanced-form-integration' ),
        esc_html__( 'Generate an OAuth2 access token for that app — Jobber requires OAuth2 and the access token must come from a registered developer app.', 'advanced-form-integration' ),
        esc_html__( 'Paste the access token below. Leave the GraphQL version blank to use the bundled default, or pin to a specific YYYY-MM-DD version from the Jobber changelog.', 'advanced-form-integration' ),
        sprintf(
            /* translators: %s: Jobber GraphQL endpoint URL */
            esc_html__( 'AFI will then send requests to %s using Bearer authentication and the X-JOBBER-GRAPHQL-VERSION header.', 'advanced-form-integration' ),
            '<code>https://api.getjobber.com/api/graphql</code>'
        ));

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_jobber_action_fields' );

function adfoin_jobber_action_fields() {
    ?>
    <script type="text/template" id="jobber-action-template">
        <table class="form-table" v-if="action.task == 'create_job'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Jobber Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_jobber_credentials_list(); ?>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=jobber' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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
            <?php adfoin_pro_feature_notice( 'create_job', 'Jobber [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_jobber_credentials', 'adfoin_get_jobber_credentials' );

function adfoin_get_jobber_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'jobber' ) );
}

add_action( 'wp_ajax_adfoin_save_jobber_credentials', 'adfoin_save_jobber_credentials' );

function adfoin_save_jobber_credentials() {

    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'jobber' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'jobber', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_jobber_fields', 'adfoin_get_jobber_fields' );

function adfoin_get_jobber_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    // Basic field set the free plugin pushes through clientCreate /
    // clientEdit / jobCreate. Tags + external IDs + custom-JSON merges
    // are gated to Pro.
    $fields = array(
        array( 'key' => 'client_id',        'value' => __( 'Existing Client ID (skips clientCreate)', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name',       'value' => __( 'Client First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',        'value' => __( 'Client Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name',     'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',            'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile_phone',     'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',            'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line1',    'value' => __( 'Street Address', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line2',    'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'city',             'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'province',         'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code',      'value' => __( 'Postal / Zip Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country',          'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title',        'value' => __( 'Job Title', 'advanced-form-integration' ), 'description' => __( 'Required for jobCreate.', 'advanced-form-integration' ) ),
        array( 'key' => 'job_description',  'value' => __( 'Job Description', 'advanced-form-integration' ) ),
        array( 'key' => 'job_instructions', 'value' => __( 'Job Instructions / Notes', 'advanced-form-integration' ) ),
        array( 'key' => 'job_start_at',     'value' => __( 'Job Start (ISO 8601)', 'advanced-form-integration' ), 'description' => __( 'Example: 2026-05-15T09:00:00Z', 'advanced-form-integration' ) ),
        array( 'key' => 'job_end_at',       'value' => __( 'Job End (ISO 8601)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_jobber_job_queue', 'adfoin_jobber_job_queue', 10, 1 );

function adfoin_jobber_job_queue( $data ) {
    adfoin_jobber_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_jobber_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_jobber_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    // Step 1: resolve the client. If the user mapped a Client ID we edit
    // that record; otherwise we create a new client and capture the ID.
    $client_id = '';
    if ( isset( $field_data['client_id'] ) ) {
        $client_id = trim( adfoin_get_parsed_values( $field_data['client_id'], $posted_data ) );
    }

    $client_input = adfoin_jobber_collect_client_fields( $field_data, $posted_data );

    if ( $client_id ) {
        // clientEdit(clientId: EncodedId!, input: ClientEditInput!)
        if ( ! empty( $client_input ) ) {
            adfoin_jobber_run_mutation(
                'clientEdit',
                'mutation clientEdit($clientId: EncodedId!, $input: ClientEditInput!) { clientEdit(clientId: $clientId, input: $input) { client { id } userErrors { message } } }',
                array(
                    'clientId' => $client_id,
                    'input'    => $client_input,
                ),
                $record,
                $credentials
            );
        }
    } elseif ( ! empty( $client_input ) ) {
        // clientCreate(input: ClientCreateInput!)
        $create_response = adfoin_jobber_run_mutation(
            'clientCreate',
            'mutation clientCreate($input: ClientCreateInput!) { clientCreate(input: $input) { client { id } userErrors { message } } }',
            array( 'input' => $client_input ),
            $record,
            $credentials
        );

        if ( ! is_wp_error( $create_response ) ) {
            $client_id = adfoin_jobber_extract_nested_value(
                $create_response,
                array( 'data', 'clientCreate', 'client', 'id' )
            );
        }
    }

    if ( ! $client_id ) {
        return;
    }

    // Step 2: create the job under that client.
    // jobCreate(clientId: EncodedId!, attributes: JobCreateAttributes!)
    $job_attributes = adfoin_jobber_collect_job_attributes( $field_data, $posted_data );

    if ( empty( $job_attributes['title'] ) ) {
        // jobCreate requires a non-empty title — bail rather than send a
        // request the API will reject.
        return;
    }

    adfoin_jobber_run_mutation(
        'jobCreate',
        'mutation jobCreate($clientId: EncodedId!, $attributes: JobCreateAttributes!) { jobCreate(clientId: $clientId, attributes: $attributes) { job { id } userErrors { message } } }',
        array(
            'clientId'   => $client_id,
            'attributes' => $job_attributes,
        ),
        $record,
        $credentials
    );
}

function adfoin_jobber_credentials_list() {
    $credentials = adfoin_read_credentials( 'jobber' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_jobber_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'jobber', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'jobber_missing_credentials', __( 'Jobber credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

/**
 * Execute an arbitrary GraphQL mutation against Jobber.
 *
 * Centralised so both free + pro share the same userErrors / GraphQL
 * errors handling. Pass the mutation document verbatim plus its
 * variable map; we don't construct mutations dynamically anymore
 * because each Jobber mutation has its own argument shape (some take
 * `input`, some take `attributes`, some take `clientId` alongside).
 *
 * @param string $operation   Top-level mutation field name (e.g. "clientCreate").
 * @param string $query       Full GraphQL mutation document.
 * @param array  $variables   Variables payload.
 * @param array  $record      Submission record (for logging).
 * @param array  $credentials Jobber credential row.
 * @return array|WP_Error Decoded response, or WP_Error on transport / GraphQL / userErrors.
 */
if ( ! function_exists( 'adfoin_jobber_run_mutation' ) ) :
function adfoin_jobber_run_mutation( $operation, $query, $variables, $record, $credentials ) {
    $response = adfoin_jobber_request( $query, $variables, $record, $credentials );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );

    if ( ! $body ) {
        return new WP_Error( 'jobber_empty_response', __( 'Empty response returned from Jobber.', 'advanced-form-integration' ) );
    }

    $decoded = json_decode( $body, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'jobber_invalid_json', __( 'Invalid JSON received from Jobber.', 'advanced-form-integration' ) );
    }

    // Top-level GraphQL errors (auth, schema, etc.).
    if ( isset( $decoded['errors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_graphql_error', $decoded['errors'][0]['message'] );
    }

    // userErrors live under data.<operation>.userErrors per Jobber's
    // mutation convention.
    $root = $decoded['data'][ $operation ] ?? null;

    if ( isset( $root['userErrors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_user_error', $root['userErrors'][0]['message'] );
    }

    return $decoded;
}
endif;

if ( ! function_exists( 'adfoin_jobber_request' ) ) :
function adfoin_jobber_request( $query, $variables = array(), $record = array(), $credentials = array() ) {
    $access_token = isset( $credentials['accessToken'] ) ? trim( $credentials['accessToken'] ) : '';
    $api_version  = isset( $credentials['apiVersion'] ) ? trim( $credentials['apiVersion'] ) : '';

    if ( '' === $access_token ) {
        return new WP_Error( 'jobber_missing_token', __( 'Jobber access token is missing.', 'advanced-form-integration' ) );
    }

    // Jobber requires the version header on every request. Fall back to
    // the bundled default when the credential row doesn't pin one.
    if ( '' === $api_version ) {
        $api_version = ADFOIN_JOBBER_DEFAULT_API_VERSION;
    }

    $url = 'https://api.getjobber.com/api/graphql';

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Authorization'             => 'Bearer ' . $access_token,
            'X-JOBBER-GRAPHQL-VERSION'  => $api_version,
            'Content-Type'              => 'application/json',
            'Accept'                    => 'application/json',
        ),
        'body'    => wp_json_encode(
            array(
                'query'     => $query,
                'variables' => $variables,
            )
        ),
        'method'  => 'POST',
    );

    $response = wp_remote_post( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

/**
 * Build a ClientCreateInput / ClientEditInput payload from the mapped
 * form fields. Both Create and Edit accept the same set of top-level
 * fields per Jobber's schema convention, so we share the collector.
 */
function adfoin_jobber_collect_client_fields( $field_data, $posted_data ) {
    $payload = array();

    $map = array(
        'first_name'   => 'firstName',
        'last_name'    => 'lastName',
        'company_name' => 'companyName',
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $payload[ $api_key ] = $value;
    }

    if ( isset( $field_data['email'] ) ) {
        $email = adfoin_get_parsed_values( $field_data['email'], $posted_data );
        if ( '' !== $email && null !== $email ) {
            $payload['emails'] = array(
                array(
                    'address'     => $email,
                    'primary'     => true,
                    'description' => 'MAIN',
                ),
            );
        }
    }

    $phones = array();
    if ( isset( $field_data['mobile_phone'] ) ) {
        $value = adfoin_get_parsed_values( $field_data['mobile_phone'], $posted_data );
        if ( '' !== $value && null !== $value ) {
            $phones[] = array(
                'number'      => $value,
                'description' => 'MOBILE',
            );
        }
    }
    if ( isset( $field_data['phone'] ) ) {
        $value = adfoin_get_parsed_values( $field_data['phone'], $posted_data );
        if ( '' !== $value && null !== $value ) {
            $phones[] = array(
                'number'      => $value,
                'description' => 'MAIN',
                'primary'     => empty( $phones ),
            );
        }
    }
    if ( ! empty( $phones ) ) {
        $payload['phones'] = $phones;
    }

    $address_fields = array(
        'address_line1' => 'street1',
        'address_line2' => 'street2',
        'city'          => 'city',
        'province'      => 'province',
        'postal_code'   => 'postalCode',
        'country'       => 'country',
    );

    $address_payload = array();

    foreach ( $address_fields as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $address_payload[ $api_key ] = $value;
    }

    if ( ! empty( $address_payload ) ) {
        $payload['billingAddress'] = $address_payload;
    }

    return $payload;
}

/**
 * Build a JobCreateAttributes payload from the mapped form fields.
 *
 * IMPORTANT: clientId is NOT included here — it's a separate top-level
 * argument on the jobCreate mutation, not a member of
 * JobCreateAttributes.
 */
function adfoin_jobber_collect_job_attributes( $field_data, $posted_data ) {
    $payload = array();

    $map = array(
        'job_title'        => 'title',
        'job_description'  => 'description',
        'job_instructions' => 'instructions',
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $payload[ $api_key ] = $value;
    }

    $start_at = adfoin_jobber_normalise_iso8601( $field_data, $posted_data, 'job_start_at' );
    $end_at   = adfoin_jobber_normalise_iso8601( $field_data, $posted_data, 'job_end_at' );

    if ( $start_at ) {
        $payload['startAt'] = $start_at;
    }
    if ( $end_at ) {
        $payload['endAt'] = $end_at;
    }

    return $payload;
}

/**
 * Best-effort conversion of a mapped date/time field to an ISO 8601
 * UTC timestamp. Accepts already-formatted ISO strings unchanged.
 * Returns '' when the field is missing or unparseable.
 */
function adfoin_jobber_normalise_iso8601( $field_data, $posted_data, $field_key ) {
    if ( ! isset( $field_data[ $field_key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
    if ( '' === $value || null === $value ) {
        return '';
    }

    $value = trim( (string) $value );

    // Pass through anything that already looks like an ISO 8601 datetime.
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value ) ) {
        return $value;
    }

    $ts = strtotime( $value );
    if ( false === $ts ) {
        return '';
    }

    return gmdate( 'Y-m-d\TH:i:s\Z', $ts );
}

function adfoin_jobber_extract_nested_value( $source, $path ) {
    if ( is_wp_error( $source ) ) {
        return '';
    }

    if ( is_array( $source ) && isset( $source[0] ) ) {
        $source = $source[0];
    }

    if ( is_array( $source ) || is_object( $source ) ) {
        foreach ( $path as $key ) {
            if ( is_array( $source ) && isset( $source[ $key ] ) ) {
                $source = $source[ $key ];
            } elseif ( is_object( $source ) && isset( $source->{$key} ) ) {
                $source = $source->{$key};
            } else {
                return '';
            }
        }
    }

    if ( is_array( $source ) || is_object( $source ) ) {
        return '';
    }

    return $source;
}

function adfoin_jobber_prepare_list_field( $value ) {
    if ( is_array( $value ) ) {
        $items = $value;
    } else {
        $items = explode( ',', (string) $value );
    }

    $items = array_map( 'trim', $items );
    $items = array_filter( $items, 'strlen' );

    return array_values( array_unique( $items ) );
}

if ( ! function_exists( 'adfoin_jobber_merge_recursive' ) ) :
function adfoin_jobber_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
            $base[ $key ] = adfoin_jobber_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }

    return $base;
}
endif;
