<?php

/**
 * Teamwork.com — Projects API v3 integration.
 *
 *   - create_task    → POST /projects/api/v3/tasklists/{tasklist_id}/tasks.json
 *   - create_company → POST /projects/api/v3/companies.json
 *
 * Multi-account credential storage via ADFOIN_Account_Manager. Each saved
 * account holds the Teamwork site identifier AND a per-user API token; both
 * are required because the base URL is account-scoped:
 * https://{site}.teamwork.com/projects/api/v3/
 *
 * Auth: HTTP Basic — username = API token, password = literal "X". Teamwork
 * accepts any non-empty password, but "X" is the canonical placeholder used
 * throughout their docs and SDK examples.
 *
 * The dispatcher wraps the flat field map in the v3 envelope expected by
 * each endpoint — {"task": {...}} or {"company": {...}}.
 *
 * @link https://apidocs.teamwork.com/docs/teamwork/v3/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_teamwork_actions', 10, 1 );

function adfoin_teamwork_actions( $actions ) {
    $actions['teamwork'] = array(
        'title' => __( 'Teamwork', 'advanced-form-integration' ),
        'tasks' => array(
            'create_task'    => __( 'Create Task', 'advanced-form-integration' ),
            'create_company' => __( 'Create Company', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_teamwork_settings_tab', 10, 1 );

function adfoin_teamwork_settings_tab( $providers ) {
    $providers['teamwork'] = __( 'Teamwork', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_teamwork_settings_view', 10, 1 );

function adfoin_teamwork_settings_view( $current_tab ) {
    if ( 'teamwork' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'site',
            'label'         => __( 'Site', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'mycompany (from mycompany.teamwork.com)', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Teamwork API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Teamwork, click your avatar (top right) and choose "My profile".', 'advanced-form-integration' ),
        esc_html__( 'Open the "API & Mobile" tab.', 'advanced-form-integration' ),
        esc_html__( 'Copy your API key. Note your site name (the prefix of your teamwork.com URL — e.g. "mycompany" for https://mycompany.teamwork.com).', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI calls https://{site}.teamwork.com/projects/api/v3/ with HTTP Basic auth.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'teamwork', __( 'Teamwork', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_teamwork_credentials', 'adfoin_get_teamwork_credentials', 10, 0 );

function adfoin_get_teamwork_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'teamwork' );
}

add_action( 'wp_ajax_adfoin_save_teamwork_credentials', 'adfoin_save_teamwork_credentials', 10, 0 );

function adfoin_save_teamwork_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'teamwork', array( 'site', 'api_token' ) );
}

function adfoin_teamwork_credentials_list() {
    foreach ( adfoin_read_credentials( 'teamwork' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Normalize a Teamwork site identifier to its bare label.
 *
 * Users tend to paste any of:
 *   "mycompany"
 *   "mycompany.teamwork.com"
 *   "https://mycompany.teamwork.com/"
 *   "https://mycompany.teamwork.com/projects/api/v3/"
 *
 * All of those should collapse to "mycompany".
 *
 * @param string $input Raw value from the credential field.
 * @return string Bare site label, or '' if it cannot be parsed.
 */
function adfoin_teamwork_normalize_site( $input ) {
    $site = strtolower( trim( (string) $input ) );

    if ( '' === $site ) {
        return '';
    }

    // Strip protocol.
    $site = preg_replace( '#^https?://#', '', $site );
    // Drop anything after the host (path, query, fragment).
    $site = preg_replace( '#[/?#].*$#', '', $site );
    // Strip ".teamwork.com" suffix if pasted.
    $site = preg_replace( '#\.teamwork\.com$#', '', $site );
    // Final character whitelist — Teamwork site labels are DNS labels.
    $site = preg_replace( '#[^a-z0-9\-]#', '', $site );

    return (string) $site;
}

add_action( 'adfoin_action_fields', 'adfoin_teamwork_action_fields' );

function adfoin_teamwork_action_fields() {
    ?>
    <script type="text/template" id="teamwork-action-template">
        <table class="form-table" v-if="action.task == 'create_task' || action.task == 'create_company'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Teamwork Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=teamwork' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_teamwork_fields', 'adfoin_get_teamwork_fields' );

function adfoin_get_teamwork_fields() {
    adfoin_verify_nonce();

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_task';

    if ( 'create_company' === $task ) {
        $fields = array(
            array( 'key' => 'name',         'value' => __( 'Company Name (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'address_one',  'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'city',         'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'country_code', 'value' => __( 'Country Code (ISO-2, defaults "IE")', 'advanced-form-integration' ) ),
            array( 'key' => 'email_one',    'value' => __( 'Email Address', 'advanced-form-integration' ) ),
            array( 'key' => 'phone',        'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'website',      'value' => __( 'Website (URL)', 'advanced-form-integration' ) ),
        );
    } else {
        // create_task (default).
        $fields = array(
            array( 'key' => 'tasklist_id', 'value' => __( 'Tasklist ID (required, integer — look up in Teamwork)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'name',        'value' => __( 'Task Name (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ) ),
            array( 'key' => 'priority',    'value' => __( 'Priority (low / normal / high)', 'advanced-form-integration' ) ),
            array( 'key' => 'assignee_id', 'value' => __( 'Assignee User ID (integer)', 'advanced-form-integration' ) ),
            array( 'key' => 'start_date',  'value' => __( 'Start Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'due_date',    'value' => __( 'Due Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_teamwork_job_queue', 'adfoin_teamwork_job_queue', 10, 1 );

function adfoin_teamwork_job_queue( $data ) {
    adfoin_teamwork_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_teamwork_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'create_task', 'create_company' ), true ) ) {
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

    // Resolve every flat key=>value mapping up-front. The per-task block
    // below assembles the v3 envelope. Empty / null parsed values are
    // dropped so we never POST "name": "" to Teamwork.
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

    if ( 'create_task' === $task ) {
        $tasklist_id = isset( $values['tasklist_id'] ) ? trim( (string) $values['tasklist_id'] ) : '';
        $name        = isset( $values['name'] )        ? trim( (string) $values['name'] )        : '';

        if ( '' === $tasklist_id || '' === $name ) {
            return;
        }

        $task_payload = array(
            'name' => $name,
        );

        if ( ! empty( $values['description'] ) ) {
            $task_payload['description'] = (string) $values['description'];
        }

        if ( ! empty( $values['priority'] ) ) {
            $priority = strtolower( trim( (string) $values['priority'] ) );
            if ( in_array( $priority, array( 'low', 'normal', 'high' ), true ) ) {
                $task_payload['priority'] = $priority;
            }
        }

        // Teamwork v3 expects assignees as {"userIds": [int, ...]}. We
        // expose only a single assignee mapping in the UI, so wrap that
        // one ID into the array structure here.
        if ( ! empty( $values['assignee_id'] ) && is_numeric( $values['assignee_id'] ) ) {
            $task_payload['assignees'] = array(
                'userIds' => array( (int) $values['assignee_id'] ),
            );
        }

        // Dates must be YYYY-MM-DD. Accept anything strtotime() understands
        // and re-emit in canonical form; skip silently on parse failure so
        // a bad merge tag doesn't 400 the whole request.
        if ( ! empty( $values['start_date'] ) ) {
            $ts = strtotime( (string) $values['start_date'] );
            if ( false !== $ts ) {
                $task_payload['startDate'] = gmdate( 'Y-m-d', $ts );
            }
        }

        if ( ! empty( $values['due_date'] ) ) {
            $ts = strtotime( (string) $values['due_date'] );
            if ( false !== $ts ) {
                $task_payload['dueDate'] = gmdate( 'Y-m-d', $ts );
            }
        }

        $payload  = array( 'task' => $task_payload );
        $endpoint = 'tasklists/' . rawurlencode( $tasklist_id ) . '/tasks.json';

        adfoin_teamwork_request( $endpoint, 'POST', $payload, $record, $cred_id );
        return;
    }

    // create_company.
    $company_name = isset( $values['name'] ) ? trim( (string) $values['name'] ) : '';
    if ( '' === $company_name ) {
        return;
    }

    $company_payload = array(
        'name' => $company_name,
    );

    if ( ! empty( $values['address_one'] ) ) {
        $company_payload['addressOne'] = (string) $values['address_one'];
    }
    if ( ! empty( $values['city'] ) ) {
        $company_payload['city'] = (string) $values['city'];
    }

    // ISO-2 country, defaulting to Ireland — Teamwork is HQ'd in Cork and
    // most installs are EU-centric, so "IE" is the most useful fallback.
    $country = ! empty( $values['country_code'] ) ? strtoupper( trim( (string) $values['country_code'] ) ) : 'IE';
    $company_payload['countryCode'] = $country;

    if ( ! empty( $values['email_one'] ) ) {
        $company_payload['emailOne'] = (string) $values['email_one'];
    }
    if ( ! empty( $values['phone'] ) ) {
        $company_payload['phone'] = (string) $values['phone'];
    }
    if ( ! empty( $values['website'] ) ) {
        $company_payload['website'] = (string) $values['website'];
    }

    $payload = array( 'company' => $company_payload );

    adfoin_teamwork_request( 'companies.json', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_teamwork_request' ) ) :
function adfoin_teamwork_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'teamwork', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['api_token'] ) || empty( $credentials['site'] ) ) {
        return new WP_Error( 'teamwork_missing_credentials', __( 'Teamwork site or API token not configured.', 'advanced-form-integration' ) );
    }

    $site = adfoin_teamwork_normalize_site( $credentials['site'] );

    if ( '' === $site ) {
        return new WP_Error( 'teamwork_invalid_site', __( 'Teamwork site identifier is invalid.', 'advanced-form-integration' ) );
    }

    $api_token = (string) $credentials['api_token'];
    $url       = 'https://' . $site . '.teamwork.com/projects/api/v3/' . ltrim( $endpoint, '/' );
    $method    = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            // Teamwork classic auth: API token as username, literal "X" as
            // password (any non-empty value works, "X" is documented).
            'Authorization' => 'Basic ' . base64_encode( $api_token . ':X' ),
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
