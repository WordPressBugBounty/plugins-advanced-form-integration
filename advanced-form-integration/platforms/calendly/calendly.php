<?php

/**
 * Calendly — Create Single-Use Scheduling Link via POST /scheduling_links.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <personal_access_token>
 *
 * Calendly's PAT model requires two lookups before most endpoints work:
 * the authenticated user URI and the user's current organization URI.
 * Both are discovered lazily via GET /users/me on first request and
 * persisted back to the credential record so subsequent calls skip the
 * round-trip.
 *
 * @link https://developer.calendly.com/api-docs/
 */

add_filter( 'adfoin_action_providers', 'adfoin_calendly_actions', 10, 1 );

function adfoin_calendly_actions( $actions ) {
    $actions['calendly'] = array(
        'title' => __( 'Calendly', 'advanced-form-integration' ),
        'tasks' => array(
            'create_scheduling_link' => __( 'Create Single-Use Scheduling Link', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_calendly_settings_tab', 10, 1 );

function adfoin_calendly_settings_tab( $providers ) {
    $providers['calendly'] = __( 'Calendly', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_calendly_settings_view', 10, 1 );

function adfoin_calendly_settings_view( $current_tab ) {
    if ( 'calendly' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'personalAccessToken',
            'label'         => __( 'Personal Access Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Calendly Personal Access Token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Sign in to Calendly, go to Integrations & apps > Manage > API and webhooks.', 'advanced-form-integration' ),
        esc_html__( 'Under "Personal Access Tokens" click "Get a token now" and give it a descriptive name (e.g. WordPress).', 'advanced-form-integration' ),
        esc_html__( 'Select all scopes, copy the token immediately, Calendly only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.calendly.com/ with this token in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'calendly', __( 'Calendly', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_calendly_credentials', 'adfoin_get_calendly_credentials', 10, 0 );

function adfoin_get_calendly_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'calendly' );
}

add_action( 'wp_ajax_adfoin_save_calendly_credentials', 'adfoin_save_calendly_credentials', 10, 0 );

function adfoin_save_calendly_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'calendly', array( 'personalAccessToken' ) );
}

function adfoin_calendly_credentials_list() {
    foreach ( adfoin_read_credentials( 'calendly' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_calendly_action_fields' );

function adfoin_calendly_action_fields() {
    ?>
    <script type="text/template" id="calendly-action-template">
        <table class="form-table" v-if="action.task == 'create_scheduling_link'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Scheduling Link', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Calendly Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=calendly' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Event Type', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[event_type_uri]" v-model="fielddata.event_type_uri" required="required">
                        <option value=""><?php esc_html_e( 'Select Event Type...', 'advanced-form-integration' ); ?></option>
                        <option v-for="ev in eventTypesList" :value="ev.uri">{{ ev.name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': eventTypesLoading}"></div>
                    <p class="description"><?php esc_html_e( 'The event type the generated one-time scheduling link will book against.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Max Event Count', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="number" min="1" name="fieldData[max_event_count]" v-model="fielddata.max_event_count" />
                    <p class="description"><?php esc_html_e( 'How many times the generated link can be used. Defaults to 1 (single-use).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

/**
 * AJAX: Fetch the user's active event types so the action dropdown can be populated.
 *
 * Calls GET /event_types?user={user_uri}&active=true&count=100. user_uri is
 * resolved (and cached on the credential record) by adfoin_calendly_resolve_user().
 */
add_action( 'wp_ajax_adfoin_get_calendly_event_types', 'adfoin_get_calendly_event_types', 10, 0 );

function adfoin_get_calendly_event_types() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'Missing credential id.', 'advanced-form-integration' ) ) );
    }

    $user_uri = adfoin_calendly_resolve_user( $cred_id );

    if ( is_wp_error( $user_uri ) || ! $user_uri ) {
        wp_send_json_error( array( 'message' => __( 'Could not resolve Calendly user. Check your Personal Access Token.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_calendly_request(
        'event_types',
        'GET',
        array(
            'user'   => $user_uri,
            'active' => 'true',
            'count'  => 100,
        ),
        array(),
        $cred_id
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $items = array();

    if ( is_array( $body ) && isset( $body['collection'] ) && is_array( $body['collection'] ) ) {
        foreach ( $body['collection'] as $ev ) {
            if ( ! is_array( $ev ) || empty( $ev['uri'] ) ) {
                continue;
            }
            $items[] = array(
                'uri'            => $ev['uri'],
                'name'           => isset( $ev['name'] ) ? $ev['name'] : $ev['uri'],
                'scheduling_url' => isset( $ev['scheduling_url'] ) ? $ev['scheduling_url'] : '',
            );
        }
    }

    wp_send_json_success( $items );
}

/**
 * Resolve and cache the authenticated user's URI + organization URI on the
 * credential record. Returns the user_uri (or WP_Error on failure).
 *
 * Persists user_uri + organization_uri back onto the credential so we only
 * pay the GET /users/me cost once per account.
 */
function adfoin_calendly_resolve_user( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'calendly', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['personalAccessToken'] ) ) {
        return new WP_Error( 'calendly_missing_token', __( 'Calendly Personal Access Token not configured.', 'advanced-form-integration' ) );
    }

    if ( ! empty( $credentials['user_uri'] ) ) {
        return $credentials['user_uri'];
    }

    $response = adfoin_calendly_request( 'users/me', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! is_array( $body ) || empty( $body['resource']['uri'] ) ) {
        return new WP_Error( 'calendly_users_me_failed', __( 'Could not parse /users/me response.', 'advanced-form-integration' ) );
    }

    $user_uri         = $body['resource']['uri'];
    $organization_uri = isset( $body['resource']['current_organization'] ) ? $body['resource']['current_organization'] : '';

    // Write back to the credential record so we skip the lookup next time.
    $all = adfoin_read_credentials( 'calendly' );
    if ( is_array( $all ) ) {
        foreach ( $all as &$cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $cred_id ) {
                $cred['user_uri']         = $user_uri;
                $cred['organization_uri'] = $organization_uri;
                break;
            }
        }
        unset( $cred );
        adfoin_save_credentials( 'calendly', $all );
    }

    return $user_uri;
}

/**
 * Resolve the current organization URI for a credential (resolving + caching
 * user/org via /users/me if needed). Returns the org URI or a WP_Error.
 */
function adfoin_calendly_resolve_org( $cred_id ) {
    $user = adfoin_calendly_resolve_user( $cred_id );

    if ( is_wp_error( $user ) ) {
        return $user;
    }

    $credentials = adfoin_get_credentials_by_id( 'calendly', $cred_id );

    if ( is_array( $credentials ) && ! empty( $credentials['organization_uri'] ) ) {
        return $credentials['organization_uri'];
    }

    return new WP_Error( 'calendly_no_org', __( 'Could not resolve the Calendly organization for this account.', 'advanced-form-integration' ) );
}

if ( ! function_exists( 'adfoin_calendly_request' ) ) :
function adfoin_calendly_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'calendly', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['personalAccessToken'] ) ) {
        return new WP_Error( 'calendly_missing_credentials', __( 'Calendly Personal Access Token not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.calendly.com/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['personalAccessToken'],
            'Accept'        => 'application/json',
            // Calendly's API sits behind Cloudflare, which 403s (error 1010)
            // requests without a User-Agent. WordPress sets one by default, but
            // be explicit so it can't be stripped.
            'User-Agent'    => 'advanced-form-integration',
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

add_action( 'adfoin_calendly_job_queue', 'adfoin_calendly_job_queue', 10, 1 );

function adfoin_calendly_job_queue( $data ) {
    adfoin_calendly_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_calendly_send_data( $record, $posted_data ) {
    if ( 'create_scheduling_link' !== ( $record['task'] ?? '' ) ) {
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

    $event_type_uri = isset( $field_data['event_type_uri'] ) ? trim( (string) $field_data['event_type_uri'] ) : '';

    if ( '' === $event_type_uri ) {
        // event_type_uri is required — Calendly will reject the request
        // without it and there's no sane default to fall back on.
        return;
    }

    $max = isset( $field_data['max_event_count'] ) ? (int) $field_data['max_event_count'] : 1;
    if ( $max < 1 ) {
        $max = 1;
    }

    $payload = array(
        'max_event_count' => $max,
        'owner'           => $event_type_uri,
        'owner_type'      => 'EventType',
    );

    adfoin_calendly_request( 'scheduling_links', 'POST', $payload, $record, $cred_id );
}
