<?php

/**
 * Crisp — Create/Update People Profile via POST /v1/website/{website_id}/people/profile.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: HTTP Basic — base64(identifier:key) — with X-Crisp-Tier: plugin header.
 *
 * Crisp scopes every endpoint to a "website" (workspace) — the website_id is
 * stored on the credential record alongside the plugin token identifier/key
 * so the dispatcher can resolve all three from one credential lookup.
 *
 * @link https://docs.crisp.chat/references/rest-api/v1/
 */

add_filter( 'adfoin_action_providers', 'adfoin_crisp_actions', 10, 1 );

function adfoin_crisp_actions( $actions ) {
    $actions['crisp'] = array(
        'title' => __( 'Crisp', 'advanced-form-integration' ),
        'tasks' => array(
            'create_profile' => __( 'Create/Update People Profile', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_crisp_settings_tab', 10, 1 );

function adfoin_crisp_settings_tab( $providers ) {
    $providers['crisp'] = __( 'Crisp', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_crisp_settings_view', 10, 1 );

function adfoin_crisp_settings_view( $current_tab ) {
    if ( 'crisp' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'identifier',
            'label'         => __( 'Plugin Identifier', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Crisp plugin token identifier', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'key',
            'label'         => __( 'Plugin Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Crisp plugin token key', 'advanced-form-integration' ),
            'show_in_table' => false,
        ),
        array(
            'name'          => 'websiteId',
            'label'         => __( 'Website ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => false,
            'placeholder'   => __( 'e.g. 00000000-0000-0000-0000-000000000000', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to %s and open Plugins → New Plugin.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://marketplace.crisp.chat/">Crisp Marketplace</a>' ),
        esc_html__( 'Create a private plugin (or open an existing one) and grant it at least the "people:write" scope.', 'advanced-form-integration' ),
        esc_html__( 'Open Token Information → Plugin Information and copy the Plugin Identifier and Plugin Key — these are the HTTP Basic Auth username/password.', 'advanced-form-integration' ),
        esc_html__( 'Install the plugin to the Crisp website you want to push contacts into (Marketplace → your plugin → Install).', 'advanced-form-integration' ),
        esc_html__( 'Grab the destination Website ID from the Crisp dashboard URL (the UUID after /website/) or under Settings → Website Settings.', 'advanced-form-integration' ),
        esc_html__( 'Paste all three values below. AFI calls https://api.crisp.chat/v1/ with X-Crisp-Tier: plugin.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'crisp', __( 'Crisp', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_crisp_credentials', 'adfoin_get_crisp_credentials', 10, 0 );

function adfoin_get_crisp_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'crisp' );
}

add_action( 'wp_ajax_adfoin_save_crisp_credentials', 'adfoin_save_crisp_credentials', 10, 0 );

function adfoin_save_crisp_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'crisp', array( 'identifier', 'key', 'websiteId' ) );
}

function adfoin_crisp_credentials_list() {
    foreach ( adfoin_read_credentials( 'crisp' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_crisp_action_fields' );

function adfoin_crisp_action_fields() {
    ?>
    <script type="text/template" id="crisp-action-template">
        <table class="form-table" v-if="action.task == 'create_profile'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Crisp Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=crisp' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <p class="description"><?php esc_html_e( 'Each Crisp account is bound to a single Website ID — the destination workspace is set when you save the credential.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_crisp_fields', 'adfoin_get_crisp_fields' );

function adfoin_get_crisp_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email',        'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'nickname',     'value' => __( 'Nickname / Full Display Name', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name',   'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',    'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',        'value' => __( 'Phone (E.164, e.g. +15551234567)', 'advanced-form-integration' ) ),
        array( 'key' => 'country',      'value' => __( 'Country (ISO-2, e.g. US)', 'advanced-form-integration' ) ),
        array( 'key' => 'city',         'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company_url',  'value' => __( 'Company URL', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title',    'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'segments_csv', 'value' => __( 'Segments (comma separated tags)', 'advanced-form-integration' ) ),
        array( 'key' => 'notepad',      'value' => __( 'Notepad (internal note)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_crisp_job_queue', 'adfoin_crisp_job_queue', 10, 1 );

function adfoin_crisp_job_queue( $data ) {
    adfoin_crisp_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_crisp_send_data( $record, $posted_data ) {
    if ( 'create_profile' !== ( $record['task'] ?? '' ) ) {
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

    // Flatten the editable-field map into simple key=>value pairs. Crisp's
    // nested person/geolocation/employment/company payload is assembled
    // below from these.
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

    // Crisp uses email as the primary people identifier — bail silently if
    // it's not mapped so we don't pollute the log with guaranteed 4xx
    // responses.
    if ( empty( $values['email'] ) ) {
        return;
    }

    $payload = array(
        'email' => (string) $values['email'],
    );

    // person.* — only emit keys we actually have so we don't blank out
    // fields Crisp already holds on an existing profile.
    $person = array();

    if ( ! empty( $values['nickname'] ) ) {
        $person['nickname'] = (string) $values['nickname'];
    } elseif ( ! empty( $values['first_name'] ) || ! empty( $values['last_name'] ) ) {
        // Crisp's main display field is `nickname`; synthesize one from
        // first + last so the contact card isn't shown as the bare email.
        $person['nickname'] = trim( ( $values['first_name'] ?? '' ) . ' ' . ( $values['last_name'] ?? '' ) );
    }
    if ( ! empty( $values['first_name'] ) ) {
        $person['first_name'] = (string) $values['first_name'];
    }
    if ( ! empty( $values['last_name'] ) ) {
        $person['last_name'] = (string) $values['last_name'];
    }
    if ( ! empty( $values['phone'] ) ) {
        $person['phone'] = (string) $values['phone'];
    }

    // person.geolocation — Crisp expects an ISO-2 country code.
    $geo = array();
    if ( ! empty( $values['country'] ) ) {
        $geo['country'] = strtoupper( substr( (string) $values['country'], 0, 2 ) );
    }
    if ( ! empty( $values['city'] ) ) {
        $geo['city'] = (string) $values['city'];
    }
    if ( ! empty( $geo ) ) {
        $person['geolocation'] = $geo;
    }

    // person.employment — company-as-employer view on the people record.
    $employment = array();
    if ( ! empty( $values['company_name'] ) ) {
        $employment['name'] = (string) $values['company_name'];
    }
    if ( ! empty( $values['job_title'] ) ) {
        $employment['title'] = (string) $values['job_title'];
    }
    if ( ! empty( $employment ) ) {
        $person['employment'] = $employment;
    }

    if ( ! empty( $person ) ) {
        $payload['person'] = $person;
    }

    // Top-level company{} — used by Crisp for the company card. Derive the
    // domain from the URL when only a URL was supplied.
    $company = array();
    if ( ! empty( $values['company_name'] ) ) {
        $company['name'] = (string) $values['company_name'];
    }
    if ( ! empty( $values['company_url'] ) ) {
        $company['url'] = (string) $values['company_url'];
        $host           = wp_parse_url( (string) $values['company_url'], PHP_URL_HOST );
        if ( $host ) {
            $company['domain'] = preg_replace( '/^www\./i', '', $host );
        }
    }
    if ( ! empty( $company ) ) {
        $payload['company'] = $company;
    }

    // segments_csv — flat array of tag-like strings. Split, trim, dedupe.
    if ( ! empty( $values['segments_csv'] ) ) {
        $segments = array_filter( array_map( 'trim', explode( ',', (string) $values['segments_csv'] ) ), 'strlen' );
        if ( ! empty( $segments ) ) {
            $payload['segments'] = array_values( array_unique( $segments ) );
        }
    }

    if ( ! empty( $values['notepad'] ) ) {
        $payload['notepad'] = (string) $values['notepad'];
    }

    adfoin_crisp_request( 'people/profile', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_crisp_request' ) ) :
function adfoin_crisp_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'crisp', $cred_id );

    if (
        ! is_array( $credentials )
        || empty( $credentials['identifier'] )
        || empty( $credentials['key'] )
        || empty( $credentials['websiteId'] )
    ) {
        return new WP_Error( 'crisp_missing_credentials', __( 'Crisp identifier, key or website ID not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.crisp.chat/v1/website/' . rawurlencode( $credentials['websiteId'] ) . '/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $credentials['identifier'] . ':' . $credentials['key'] ),
            'X-Crisp-Tier'  => 'plugin',
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
