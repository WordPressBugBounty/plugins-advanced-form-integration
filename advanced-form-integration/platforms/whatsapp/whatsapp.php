<?php

/**
 * WhatsApp Business Platform (Cloud API) — Send Template Message via
 * POST graph.facebook.com/v21.0/{phone_number_id}/messages.
 *
 * Auth: permanent System User access token (Business Settings > System
 * Users). Multi-account via ADFOIN_Account_Manager.
 *
 * IMPORTANT (confirmed via developers.facebook.com/docs/whatsapp/cloud-api):
 * business-initiated messages (this entire "form submitted -> notify"
 * use case) CANNOT be free-form text — WhatsApp requires a pre-approved
 * Message Template unless the recipient messaged your business number
 * first within the last 24 hours. Create and get a template approved in
 * WhatsApp Manager before this integration can send anything. This is a
 * paid API — every template send is billed (Utility-category templates,
 * the right fit for a form-confirmation message, are the cheapest tier).
 *
 * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages
 */

if ( ! defined( 'ADFOIN_WHATSAPP_API_VERSION' ) ) {
    define( 'ADFOIN_WHATSAPP_API_VERSION', 'v21.0' );
}

add_filter( 'adfoin_action_providers', 'adfoin_whatsapp_actions', 10, 1 );

function adfoin_whatsapp_actions( $actions ) {
    $actions['whatsapp'] = array(
        'title' => __( 'WhatsApp Business Platform', 'advanced-form-integration' ),
        'tasks' => array( 'send_template' => __( 'Send Template Message', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_whatsapp_settings_tab', 10, 1 );

function adfoin_whatsapp_settings_tab( $providers ) {
    $providers['whatsapp'] = __( 'WhatsApp Business Platform', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_whatsapp_settings_view', 10, 1 );

function adfoin_whatsapp_settings_view( $current_tab ) {
    if ( 'whatsapp' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'phoneNumberId', 'label' => __( 'Phone Number ID', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'show_in_table' => true ),
        array( 'name' => 'accessToken', 'label' => __( 'System User Access Token', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li><strong>%s</strong></li></ol>',
        sprintf( __( 'In %s, find your Phone Number ID under WhatsApp > API Setup.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://business.facebook.com/settings">Meta Business Settings</a>' ),
        esc_html__( 'Under Business Settings > System Users, create a System User, assign it to your WhatsApp Business Account, and generate a permanent token with the whatsapp_business_messaging scope.', 'advanced-form-integration' ),
        esc_html__( 'This only works with a pre-approved Message Template (WhatsApp Manager > Message Templates) — free-form text cannot be sent to someone who hasn\'t messaged your number first. Sending is also billed per message by Meta.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'whatsapp', __( 'WhatsApp Business Platform', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_whatsapp_credentials', 'adfoin_get_whatsapp_credentials', 10, 0 );

function adfoin_get_whatsapp_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'whatsapp' );
}

add_action( 'wp_ajax_adfoin_save_whatsapp_credentials', 'adfoin_save_whatsapp_credentials', 10, 0 );

function adfoin_save_whatsapp_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'whatsapp', array( 'phoneNumberId', 'accessToken' ) );
}

function adfoin_whatsapp_credentials_list() {
    foreach ( adfoin_read_credentials( 'whatsapp' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_whatsapp_action_fields' );

function adfoin_whatsapp_action_fields() {
    ?>
    <script type="text/template" id="whatsapp-action-template">
        <table class="form-table" v-if="action.task == 'send_template'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'WhatsApp Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=whatsapp' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'send_template', 'WhatsApp [PRO]', 'header media & extra params' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_whatsapp_fields', 'adfoin_get_whatsapp_fields' );

function adfoin_get_whatsapp_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'to',           'value' => __( 'Recipient Phone (E.164, no +)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'templateName', 'value' => __( 'Template Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'languageCode', 'value' => __( 'Language Code', 'advanced-form-integration' ), 'description' => __( 'Must match the template\'s approved language, e.g. en_US. Default: en_US', 'advanced-form-integration' ) ),
        array( 'key' => 'bodyParam1',   'value' => __( 'Body Param {{1}}', 'advanced-form-integration' ) ),
        array( 'key' => 'bodyParam2',   'value' => __( 'Body Param {{2}}', 'advanced-form-integration' ) ),
        array( 'key' => 'bodyParam3',   'value' => __( 'Body Param {{3}}', 'advanced-form-integration' ) ),
    );
    wp_send_json_success( $fields );
}

function adfoin_whatsapp_normalize_phone( $phone ) {
    return preg_replace( '/[^0-9]/', '', (string) $phone );
}

function adfoin_whatsapp_build_body_params( $fields, $keys ) {
    $params = array();
    foreach ( $keys as $key ) {
        if ( ! empty( $fields[ $key ] ) ) $params[] = array( 'type' => 'text', 'text' => (string) $fields[ $key ] );
    }
    return $params;
}

add_action( 'adfoin_whatsapp_job_queue', 'adfoin_whatsapp_job_queue', 10, 1 );
function adfoin_whatsapp_job_queue( $data ) {
    adfoin_whatsapp_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_whatsapp_send_data( $record, $posted_data ) {
    if ( 'send_template' !== ( $record['task'] ?? '' ) ) {
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

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) continue;
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) $fields[ $key ] = $parsed;
    }

    if ( empty( $fields['to'] ) || empty( $fields['templateName'] ) ) {
        return;
    }

    $components = array();
    $body_params = adfoin_whatsapp_build_body_params( $fields, array( 'bodyParam1', 'bodyParam2', 'bodyParam3' ) );
    if ( ! empty( $body_params ) ) {
        $components[] = array( 'type' => 'body', 'parameters' => $body_params );
    }

    $body = array(
        'messaging_product' => 'whatsapp',
        'recipient_type'    => 'individual',
        'to'                => adfoin_whatsapp_normalize_phone( $fields['to'] ),
        'type'              => 'template',
        'template'          => array(
            'name'     => $fields['templateName'],
            'language' => array( 'code' => ! empty( $fields['languageCode'] ) ? $fields['languageCode'] : 'en_US' ),
        ),
    );
    if ( ! empty( $components ) ) $body['template']['components'] = $components;

    adfoin_whatsapp_request( $body, $cred_id, $record );
}

if ( ! function_exists( 'adfoin_whatsapp_request' ) ) :
function adfoin_whatsapp_request( $body, $cred_id, $record = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'whatsapp', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['phoneNumberId'] ) || empty( $credentials['accessToken'] ) ) {
        return new WP_Error( 'whatsapp_missing_credentials', __( 'WhatsApp Phone Number ID / Access Token not configured.', 'advanced-form-integration' ) );
    }

    $url = 'https://graph.facebook.com/' . ADFOIN_WHATSAPP_API_VERSION . '/' . rawurlencode( $credentials['phoneNumberId'] ) . '/messages';

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['accessToken'],
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
