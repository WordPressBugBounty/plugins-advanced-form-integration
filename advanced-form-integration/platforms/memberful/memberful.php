<?php

/**
 * Memberful — Create Member via GraphQL `memberCreate` mutation.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <apiKey>
 *
 * Each Memberful customer has their own subdomain (e.g. mysite.memberful.com),
 * so we store both `apiKey` and `subdomain` on the credential record. The
 * GraphQL endpoint is https://{subdomain}.memberful.com/api/graphql.
 *
 * @link https://memberful.com/help/integrate/advanced/memberful-api/
 */

add_filter( 'adfoin_action_providers', 'adfoin_memberful_actions', 10, 1 );

function adfoin_memberful_actions( $actions ) {
    $actions['memberful'] = array(
        'title' => __( 'Memberful', 'advanced-form-integration' ),
        'tasks' => array(
            'create_member' => __( 'Create Member', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_memberful_settings_tab', 10, 1 );

function adfoin_memberful_settings_tab( $providers ) {
    $providers['memberful'] = __( 'Memberful', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_memberful_settings_view', 10, 1 );

function adfoin_memberful_settings_view( $current_tab ) {
    if ( 'memberful' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Memberful API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'subdomain',
            'label'         => __( 'Site Subdomain', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => false,
            'placeholder'   => 'mysite',
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Sign in to your Memberful dashboard.', 'advanced-form-integration' ),
        esc_html__( 'Go to Settings → Custom apps → API and click "Generate token".', 'advanced-form-integration' ),
        esc_html__( 'Copy the generated API key and paste it above.', 'advanced-form-integration' ),
        esc_html__( 'Enter your site subdomain (the part before .memberful.com — e.g. "mysite" if your dashboard is at mysite.memberful.com).', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'memberful', __( 'Memberful', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_memberful_credentials', 'adfoin_get_memberful_credentials', 10, 0 );

function adfoin_get_memberful_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'memberful' );
}

add_action( 'wp_ajax_adfoin_save_memberful_credentials', 'adfoin_save_memberful_credentials', 10, 0 );

function adfoin_save_memberful_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'memberful', array( 'apiKey', 'subdomain' ) );
}

function adfoin_memberful_credentials_list() {
    foreach ( adfoin_read_credentials( 'memberful' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Normalize a subdomain input by stripping protocol, trailing
 * `.memberful.com`, slashes, and surrounding whitespace.
 */
function adfoin_memberful_sanitize_subdomain( $raw ) {
    $sub = is_string( $raw ) ? trim( $raw ) : '';
    if ( '' === $sub ) {
        return '';
    }

    // Strip protocol.
    $sub = preg_replace( '#^https?://#i', '', $sub );
    // Strip slashes.
    $sub = trim( $sub, "/ \t\n\r\0\x0B" );
    // Strip .memberful.com suffix if user pasted full host.
    $sub = preg_replace( '/\.memberful\.com$/i', '', $sub );
    // Collapse any path remnants — keep only the leading host label.
    if ( false !== strpos( $sub, '/' ) ) {
        $sub = substr( $sub, 0, strpos( $sub, '/' ) );
    }

    return strtolower( $sub );
}

add_action( 'adfoin_action_fields', 'adfoin_memberful_action_fields' );

function adfoin_memberful_action_fields() {
    ?>
    <script type="text/template" id="memberful-action-template">
        <table class="form-table" v-if="action.task == 'create_member'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Memberful Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=memberful' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_memberful_fields', 'adfoin_get_memberful_fields' );

function adfoin_get_memberful_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email',       'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'fullName',    'value' => __( 'Full Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phoneNumber', 'value' => __( 'Phone Number', 'advanced-form-integration' ) ),
        array( 'key' => 'customField', 'value' => __( 'Custom Field', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_memberful_job_queue', 'adfoin_memberful_job_queue', 10, 1 );

function adfoin_memberful_job_queue( $data ) {
    adfoin_memberful_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_memberful_send_data( $record, $posted_data ) {
    if ( 'create_member' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat values up-front, skipping reserved keys.
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

    // Memberful requires both email + fullName on memberCreate.
    if ( empty( $values['email'] ) || empty( $values['fullName'] ) ) {
        return;
    }

    $input = array(
        'email'          => $values['email'],
        'fullName'       => $values['fullName'],
        'trackingParams' => array(
            'source' => 'wordpress_form',
        ),
    );

    if ( ! empty( $values['phoneNumber'] ) ) {
        $input['phoneNumber'] = $values['phoneNumber'];
    }

    if ( ! empty( $values['customField'] ) ) {
        $input['customField'] = $values['customField'];
    }

    $query = 'mutation memberCreate($input: MemberCreateInput!) {
  memberCreate(input: $input) {
    member {
      id
      email
      fullName
    }
    errors {
      field
      messages
    }
  }
}';

    $variables = array(
        'input' => $input,
    );

    adfoin_memberful_request( $query, $variables, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_memberful_request' ) ) :
function adfoin_memberful_request( $query, $variables = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'memberful', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) || empty( $credentials['subdomain'] ) ) {
        return new WP_Error( 'memberful_missing_credentials', __( 'Memberful API key or subdomain not configured.', 'advanced-form-integration' ) );
    }

    $subdomain = adfoin_memberful_sanitize_subdomain( $credentials['subdomain'] );

    if ( '' === $subdomain ) {
        return new WP_Error( 'memberful_invalid_subdomain', __( 'Memberful subdomain is invalid.', 'advanced-form-integration' ) );
    }

    $url = 'https://' . $subdomain . '.memberful.com/api/graphql';

    $body = array(
        'query'     => $query,
        'variables' => is_array( $variables ) ? $variables : array(),
    );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiKey'],
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
