<?php

/**
 * Greenhouse — Submit Job Application via the Job Board API:
 * POST https://boards-api.greenhouse.io/v1/boards/{board_token}/jobs/{job_id}.
 *
 * This is Greenhouse's public application-intake endpoint, designed to be
 * called from any external career-site/job-application form — distinct
 * from the Harvest API (internal ATS read/write operations). Multi-account
 * via ADFOIN_Account_Manager, keyed by Board Token.
 *
 * Confirmed via developers.greenhouse.io/job-board.html: JSON or
 * multipart/form-data body; required fields first_name/last_name/email;
 * resume can be supplied as a fetchable `resume_url` instead of an
 * uploaded file, which is what this integration uses (avoids needing to
 * proxy a file upload through WordPress). Auth is optional HTTP Basic with
 * the board's API key as username, blank password — only add the header
 * if the board requires it.
 *
 * @link https://developers.greenhouse.io/job-board.html
 */

add_filter( 'adfoin_action_providers', 'adfoin_greenhouse_actions', 10, 1 );

function adfoin_greenhouse_actions( $actions ) {
    $actions['greenhouse'] = array(
        'title' => __( 'Greenhouse', 'advanced-form-integration' ),
        'tasks' => array( 'submit_application' => __( 'Submit Job Application', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_greenhouse_settings_tab', 10, 1 );

function adfoin_greenhouse_settings_tab( $providers ) {
    $providers['greenhouse'] = __( 'Greenhouse', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_greenhouse_settings_view', 10, 1 );

function adfoin_greenhouse_settings_view( $current_tab ) {
    if ( 'greenhouse' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'boardToken', 'label' => __( 'Board Token', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'show_in_table' => true ),
        array( 'name' => 'apiKey', 'label' => __( 'API Key (optional)', 'advanced-form-integration' ), 'type' => 'text', 'required' => false, 'mask' => true ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Greenhouse, go to Configure > Dev Center > Job Board to find your Board Token.', 'advanced-form-integration' ),
        esc_html__( 'Most boards accept applications without any API key — only fill it in if Greenhouse tells you your board requires authenticated submissions. You\'ll need each specific Job\'s ID (from its Job Board API listing) when mapping fields on your form action.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'greenhouse', __( 'Greenhouse', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_greenhouse_credentials', 'adfoin_get_greenhouse_credentials', 10, 0 );

function adfoin_get_greenhouse_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'greenhouse' );
}

add_action( 'wp_ajax_adfoin_save_greenhouse_credentials', 'adfoin_save_greenhouse_credentials', 10, 0 );

function adfoin_save_greenhouse_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'greenhouse', array( 'boardToken', 'apiKey' ) );
}

function adfoin_greenhouse_credentials_list() {
    foreach ( adfoin_read_credentials( 'greenhouse' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_greenhouse_action_fields' );

function adfoin_greenhouse_action_fields() {
    ?>
    <script type="text/template" id="greenhouse-action-template">
        <table class="form-table" v-if="action.task == 'submit_application'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Greenhouse Board', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=greenhouse' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <tr valign="top" class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Job ID', 'advanced-form-integration' ); ?></label></td>
                <td><input type="text" name="fieldData[jobId]" v-model="fielddata.jobId" placeholder="1234567" /></td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'submit_application', 'Greenhouse [PRO]', 'custom application questions' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_greenhouse_fields', 'adfoin_get_greenhouse_fields' );

function adfoin_get_greenhouse_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',    'value' => __( 'First Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'lastName',     'value' => __( 'Last Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email',        'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone',        'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'resumeUrl',    'value' => __( 'Resume URL', 'advanced-form-integration' ), 'description' => __( 'A publicly fetchable URL to the uploaded resume file', 'advanced-form-integration' ) ),
        array( 'key' => 'coverLetter',  'value' => __( 'Cover Letter (text)', 'advanced-form-integration' ) ),
    );
    wp_send_json_success( $fields );
}

function adfoin_greenhouse_build_payload( $fields ) {
    $payload = array(
        'first_name' => $fields['firstName'],
        'last_name'  => $fields['lastName'],
        'email'      => $fields['email'],
    );
    if ( ! empty( $fields['phone'] ) )       $payload['phone']              = $fields['phone'];
    if ( ! empty( $fields['resumeUrl'] ) )   $payload['resume_url']         = $fields['resumeUrl'];
    if ( ! empty( $fields['coverLetter'] ) ) $payload['cover_letter_text']  = $fields['coverLetter'];
    return $payload;
}

add_action( 'adfoin_greenhouse_job_queue', 'adfoin_greenhouse_job_queue', 10, 1 );
function adfoin_greenhouse_job_queue( $data ) {
    adfoin_greenhouse_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_greenhouse_send_data( $record, $posted_data ) {
    if ( 'submit_application' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );
    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $job_id     = isset( $field_data['jobId'] ) ? trim( (string) $field_data['jobId'] ) : '';
    if ( ! $cred_id || ! $job_id ) {
        return;
    }

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( in_array( $key, array( 'credId', 'jobId' ), true ) ) continue;
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) $fields[ $key ] = $parsed;
    }

    if ( empty( $fields['firstName'] ) || empty( $fields['lastName'] ) || empty( $fields['email'] ) ) {
        return;
    }

    adfoin_greenhouse_request( $job_id, adfoin_greenhouse_build_payload( $fields ), $cred_id, $record );
}

if ( ! function_exists( 'adfoin_greenhouse_request' ) ) :
function adfoin_greenhouse_request( $job_id, $payload, $cred_id, $record = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'greenhouse', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['boardToken'] ) ) {
        return new WP_Error( 'greenhouse_missing_credentials', __( 'Greenhouse Board Token not configured.', 'advanced-form-integration' ) );
    }

    $url = 'https://boards-api.greenhouse.io/v1/boards/' . rawurlencode( $credentials['boardToken'] ) . '/jobs/' . rawurlencode( $job_id );

    $headers = array( 'Content-Type' => 'application/json' );
    if ( ! empty( $credentials['apiKey'] ) ) {
        $headers['Authorization'] = 'Basic ' . base64_encode( $credentials['apiKey'] . ':' );
    }

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
