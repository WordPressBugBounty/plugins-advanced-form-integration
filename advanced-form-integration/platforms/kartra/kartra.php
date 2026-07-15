<?php

/**
 * Kartra — Create/Subscribe Lead via POST https://app.kartra.com/api.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: per-account app_id + api_key + api_password — all three supplied
 * by the user, NOT hardcoded.
 *
 * Kartra requires every integration to be a registered "app" (My
 * Integrations > Developers > API on Kartra's side), which itself requires
 * an active Kartra subscription to create and get approved. This plugin
 * does not ship with its own pre-approved app_id — instead, each user with
 * an active Kartra subscription registers their OWN app in their OWN
 * account, gets it approved by Kartra, and enters the resulting App ID
 * here alongside their API Key/Password. This avoids depending on a
 * single shared app_id owned by the plugin author (which would require the
 * author to maintain an active Kartra subscription indefinitely just to
 * keep everyone else's integration working).
 *
 * Confirmed against Kartra's current API docs (support.kartra.com — see
 * "Connecting to the API", "Connecting a custom App with Kartra API",
 * "Lead: Searching, Creating, Editing", "Action: Subscribe a lead to a
 * list", "Action: Retrieve all the lists in your account", "PHP sample:
 * creating a lead"):
 *   - Endpoint is still POST https://app.kartra.com/api, no OAuth/versioning.
 *   - `retrieve_account_lists`, `create_lead`, `subscribe_lead_to_list`
 *     (param `list_name` — a name string, not a numeric id) are current.
 *   - The `lead` payload key is a flat object, e.g. `{"email":"...", ...}` —
 *     NOT an array of one object. The previous (pre-2026) version of this
 *     file sent `'lead' => array( array( 'email' => ... ) )`, i.e. an
 *     array-wrapped lead, which doesn't match the documented shape.
 *   - `custom_fields` is a real, separate array of
 *     `{"field_identifier":"...", "field_value":"..."}` pairs — see the Pro
 *     tier for that.
 *   - New apps start in "Test Mode", which Kartra's docs say is "fully
 *     functional for the developer" — since each user here registers their
 *     own app against their own account, Test Mode alone may already be
 *     enough without waiting on Kartra's live-review queue. Worth trying
 *     before submitting for full approval.
 *
 * @link https://support.kartra.com/support/solutions/articles/153000169502-connecting-to-the-api
 * @link https://support.kartra.com/support/solutions/articles/153000169500-lead-searching-creating-editing
 */

add_filter( 'adfoin_action_providers', 'adfoin_kartra_actions', 10, 1 );

function adfoin_kartra_actions( $actions ) {
    $actions['kartra'] = array(
        'title' => __( 'Kartra', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Lead To List', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_kartra_settings_tab', 10, 1 );

function adfoin_kartra_settings_tab( $providers ) {
    $providers['kartra'] = __( 'Kartra', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_kartra_settings_view', 10, 1 );

function adfoin_kartra_settings_view( $current_tab ) {
    if ( 'kartra' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'appId',
            'label'         => __( 'App ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'     => 'apiPassword',
            'label'    => __( 'API Password', 'advanced-form-integration' ),
            'type'     => 'text',
            'required' => true,
            'mask'     => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li><strong>%s</strong></li><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s — Kartra developer API link. */
            esc_html__( 'Requires an active Kartra subscription. Go to %s and register a new app of your own — this issues you an App ID. New apps start in "Test Mode," which Kartra says is fully functional for the developer\'s own account, so try it as-is first; if a call fails as unauthorized, submit the app for Kartra\'s live review/approval from the same page.', 'advanced-form-integration' ),
            '<a target="_blank" rel="noopener noreferrer" href="https://app.kartra.com/integrations/api/developers">My Integrations &raquo; Developers &raquo; API</a>'
        ),
        sprintf(
            /* translators: %s — Kartra API key link. */
            esc_html__( 'Get your API Key and API Password from %s.', 'advanced-form-integration' ),
            '<a target="_blank" rel="noopener noreferrer" href="https://app.kartra.com/integrations/api/key">My Integrations &raquo; Developers &raquo; API</a>'
        ),
        esc_html__( 'Paste the App ID, API Key, and API Password below and save — then pick a Kartra list when mapping fields on your form action.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'kartra', __( 'Kartra', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_kartra_credentials', 'adfoin_get_kartra_credentials', 10, 0 );

function adfoin_get_kartra_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'kartra' );
}

add_action( 'wp_ajax_adfoin_save_kartra_credentials', 'adfoin_save_kartra_credentials', 10, 0 );

function adfoin_save_kartra_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'kartra', array( 'appId', 'apiKey', 'apiPassword' ) );
}

function adfoin_kartra_credentials_list() {
    foreach ( adfoin_read_credentials( 'kartra' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_kartra_action_fields' );

function adfoin_kartra_action_fields() {
    ?>
    <script type="text/template" id="kartra-action-template">
        <table class="form-table" v-if="action.task == 'subscribe'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Kartra Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadList">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=kartra' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Kartra List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""><?php esc_html_e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="item in lists" :value="item">{{ item }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Kartra [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_kartra_list', 'adfoin_get_kartra_list', 10, 0 );

/**
 * Fetch the authenticated account's Kartra lists (retrieve_account_lists).
 */
function adfoin_get_kartra_list() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_success( array() );
    }

    $response = adfoin_kartra_request( array(
        array( 'cmd' => 'retrieve_account_lists' ),
    ), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_success( array() );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    $lists = ( is_array( $body ) && ! empty( $body['account_lists'] ) && is_array( $body['account_lists'] ) )
        ? array_values( $body['account_lists'] )
        : array();

    wp_send_json_success( $lists );
}

add_action( 'wp_ajax_adfoin_get_kartra_fields', 'adfoin_get_kartra_fields' );

function adfoin_get_kartra_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email',            'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'firstName',        'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'middleName',       'value' => __( 'Middle Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',         'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName2',        'value' => __( 'Last Name 2', 'advanced-form-integration' ) ),
        array( 'key' => 'phoneCountryCode', 'value' => __( 'Phone Country Code', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',            'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'ip',               'value' => __( 'IP', 'advanced-form-integration' ) ),
        array( 'key' => 'address',          'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',              'value' => __( 'ZIP', 'advanced-form-integration' ) ),
        array( 'key' => 'city',             'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state',            'value' => __( 'State', 'advanced-form-integration' ) ),
        array( 'key' => 'country',          'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'company',          'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'website',          'value' => __( 'Website', 'advanced-form-integration' ) ),
        array( 'key' => 'facebook',         'value' => __( 'Facebook', 'advanced-form-integration' ) ),
        array( 'key' => 'twitter',          'value' => __( 'Twitter', 'advanced-form-integration' ) ),
        array( 'key' => 'linkedin',         'value' => __( 'LinkedIn', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

/**
 * Build the flat `lead` object from parsed field values. Shared with the
 * Pro tier so both stay in sync with the confirmed API schema.
 */
function adfoin_kartra_build_lead( $fields ) {
    $map = array(
        'email'            => 'email',
        'firstName'        => 'first_name',
        'middleName'       => 'middle_name',
        'lastName'         => 'last_name',
        'lastName2'        => 'last_name2',
        'phoneCountryCode' => 'phone_country_code',
        'phone'            => 'phone',
        'ip'               => 'ip',
        'address'          => 'address',
        'zip'              => 'zip',
        'city'             => 'city',
        'state'            => 'state',
        'country'          => 'country',
        'company'          => 'company',
        'website'          => 'website',
        'facebook'         => 'facebook',
        'twitter'          => 'twitter',
        'linkedin'         => 'linkedin',
    );

    $lead = array();
    foreach ( $map as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) {
            $lead[ $remote ] = $fields[ $local ];
        }
    }

    return $lead;
}

add_action( 'adfoin_kartra_job_queue', 'adfoin_kartra_job_queue', 10, 1 );

function adfoin_kartra_job_queue( $data ) {
    adfoin_kartra_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_kartra_send_data( $record, $posted_data ) {
    if ( 'subscribe' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] )  ? $field_data['credId']  : '';
    $list_id    = isset( $field_data['listId'] )  ? trim( (string) $field_data['listId'] ) : '';

    if ( ! $cred_id || ! $list_id ) {
        return;
    }

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( in_array( $key, array( 'credId', 'listId' ), true ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    $lead = adfoin_kartra_build_lead( $fields );

    if ( empty( $lead['email'] ) ) {
        return;
    }

    $actions = array(
        array( 'cmd' => 'create_lead' ),
        array( 'cmd' => 'subscribe_lead_to_list', 'list_name' => $list_id ),
    );

    adfoin_kartra_request( $actions, $cred_id, $lead, $record );
}

if ( ! function_exists( 'adfoin_kartra_request' ) ) :
/**
 * Call the Kartra API. Every call sends `app_id`/`api_key`/`api_password`
 * both as URL query params (the shape this integration has used since it
 * was first built, confirmed still working) and inside the JSON body
 * (the shape Kartra's own docs describe — "every API call must include an
 * array with the following parameters") so the request succeeds regardless
 * of which one Kartra's endpoint actually reads.
 *
 * @param array  $actions Kartra "actions" cmd array.
 * @param string $cred_id Saved credential id.
 * @param array  $lead    Optional flat lead object (NOT array-wrapped).
 * @param array  $record  Submission record for logging.
 *
 * @return array|WP_Error
 */
function adfoin_kartra_request( $actions, $cred_id, $lead = array(), $record = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'kartra', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['appId'] ) || empty( $credentials['apiKey'] ) || empty( $credentials['apiPassword'] ) ) {
        return new WP_Error( 'kartra_missing_credentials', __( 'Kartra App ID / API Key / API Password not configured.', 'advanced-form-integration' ) );
    }

    $app_id       = $credentials['appId'];
    $api_key      = $credentials['apiKey'];
    $api_password = $credentials['apiPassword'];

    $body = array(
        'app_id'       => $app_id,
        'api_key'      => $api_key,
        'api_password' => $api_password,
        'actions'      => $actions,
    );

    if ( ! empty( $lead ) ) {
        $body['lead'] = $lead;
    }

    $url = add_query_arg( array(
        'app_id'       => $app_id,
        'api_key'      => $api_key,
        'api_password' => $api_password,
    ), 'https://app.kartra.com/api' );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_post( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
