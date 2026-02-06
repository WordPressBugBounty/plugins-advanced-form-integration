<?php

add_filter( 'adfoin_action_providers', 'adfoin_dynamics365marketing_actions', 10, 1 );

function adfoin_dynamics365marketing_actions( $actions ) {
    $actions['dynamics365marketing'] = array(
        'title' => __( 'Dynamics 365 Marketing', 'advanced-form-integration' ),
        'tasks' => array(
            'create_marketing_contact' => __( 'Create Marketing Contact (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_dynamics365marketing_settings_tab', 10, 1 );

function adfoin_dynamics365marketing_settings_tab( $providers ) {
    $providers['dynamics365marketing'] = __( 'Dynamics 365 Marketing', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_dynamics365marketing_settings_view', 10, 1 );

function adfoin_dynamics365marketing_settings_view( $current_tab ) {
    if ( 'dynamics365marketing' !== $current_tab ) {
        return;
    }

    $title = __( 'Dynamics 365 Marketing', 'advanced-form-integration' );
    $key   = 'dynamics365';

    $arguments = json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'instanceUrl', 'label' => __( 'Instance URL', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientId', 'label' => __( 'Client ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'tenantId', 'label' => __( 'Tenant (Directory) ID', 'advanced-form-integration' ), 'hidden' => false ),
        ),
    ) );

    $instructions = sprintf(
        '<p>%s</p>',
        esc_html__( 'Reuse the Azure AD application you configured for Dynamics 365 CRM. Enter the Instance URL, Client ID, Client Secret, and Tenant ID here. Upgrade to the Pro connector for list membership and extended field mapping.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, 'dynamics365', $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_dynamics365marketing_action_fields' );

function adfoin_dynamics365marketing_action_fields() {
    ?>
    <script type="text/template" id="dynamics365marketing-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Dynamics 365 Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_dynamics365_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata">
            </editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need more?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Dynamics 365 Marketing [PRO]</a> to map additional fields and add contacts to marketing lists.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_dynamics365marketing_fields', 'adfoin_get_dynamics365marketing_fields' );

function adfoin_get_dynamics365marketing_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'firstname', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'lastname', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'emailaddress1', 'value' => __( 'Email Address', 'advanced-form-integration' ), 'description' => '', 'required' => true ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_dynamics365marketing_job_queue', 'adfoin_dynamics365marketing_job_queue', 10, 1 );

function adfoin_dynamics365marketing_job_queue( $data ) {
    adfoin_dynamics365marketing_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_dynamics365marketing_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = isset( $record['task'] ) ? $record['task'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $fields = array();
    foreach ( $data as $key => $value ) {
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    if ( 'create_marketing_contact' === $task ) {
        adfoin_dynamics365marketing_create_contact( $fields, $record, $cred_id );
        return;
    }

    if ( 'create_marketing_contact' === $task ) {
        adfoin_dynamics365marketing_create_contact( $fields, $record, $cred_id );
    }
}

function adfoin_dynamics365marketing_create_contact( $fields, $record, $cred_id ) {
    $payload = array(
        'firstname'     => isset( $fields['firstname'] ) ? $fields['firstname'] : '',
        'lastname'      => isset( $fields['lastname'] ) ? $fields['lastname'] : '',
        'emailaddress1' => isset( $fields['emailaddress1'] ) ? $fields['emailaddress1'] : '',
    );

    $payload = array_filter( $payload, static function( $value ) {
        return '' !== $value && null !== $value;
    } );

    if ( empty( $payload['emailaddress1'] ) ) {
        return;
    }

    adfoin_dynamics365_request( 'contacts', 'POST', $payload, $record, $cred_id );
}
