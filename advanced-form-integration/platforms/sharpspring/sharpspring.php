<?php

add_filter( 'adfoin_action_providers', 'adfoin_sharpspring_actions', 10, 1 );

function adfoin_sharpspring_actions( $actions ) {

    $actions['sharpspring'] = array(
        'title' => __( 'SharpSpring', 'advanced-form-integration' ),
        'tasks' => array(
            'add_lead' => __( 'Create/Update Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sharpspring_settings_tab', 10, 1 );

function adfoin_sharpspring_settings_tab( $providers ) {
    $providers['sharpspring'] = __( 'SharpSpring', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sharpspring_settings_view', 10, 1 );

function adfoin_sharpspring_settings_view( $current_tab ) {
    if ( 'sharpspring' !== $current_tab ) {
        return;
    }

    $nonce   = wp_create_nonce( 'adfoin_sharpspring_settings' );
    $account = get_option( 'adfoin_sharpspring_account_id', '' );
    $secret  = get_option( 'adfoin_sharpspring_secret_key', '' );
    ?>

    <form name="sharpspring_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_sharpspring_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'Account ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sharpspring_account_id"
                           value="<?php echo esc_attr( $account ); ?>" placeholder="<?php esc_attr_e( 'Enter Account ID', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'Secret Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sharpspring_secret_key"
                           value="<?php echo esc_attr( $secret ); ?>" placeholder="<?php esc_attr_e( 'Enter Secret Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php
                        printf(
                            __( 'Generate API credentials under <a href="%s" target="_blank" rel="noopener noreferrer">SharpSpring Settings → API Settings</a>.', 'advanced-form-integration' ),
                            esc_url( 'https://app.sharpspring.com/app/settings/account/apiSettings.jsf' )
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_sharpspring_credentials', 'adfoin_save_sharpspring_credentials', 10, 0 );

function adfoin_save_sharpspring_credentials() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_sharpspring_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $account = isset( $_POST['adfoin_sharpspring_account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sharpspring_account_id'] ) ) : '';
    $secret  = isset( $_POST['adfoin_sharpspring_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sharpspring_secret_key'] ) ) : '';

    update_option( 'adfoin_sharpspring_account_id', $account );
    update_option( 'adfoin_sharpspring_secret_key', $secret );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=sharpspring' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_sharpspring_js_fields', 10, 1 );

function adfoin_sharpspring_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_sharpspring_action_fields' );

function adfoin_sharpspring_action_fields() {
    ?>
    <script type="text/template" id="sharpspring-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_lead'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <editable-field v-for="field in fields"
                            v-bind:key="field.value"
                            v-bind:field="field"
                            v-bind:trigger="trigger"
                            v-bind:action="action"
                            v-bind:fielddata="fielddata"></editable-field>
            <?php
            if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
                ?>
                <tr valign="top" v-if="action.task == 'add_lead'">
                    <th scope="row">
                        <?php esc_attr_e( 'Using Pro Features', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'For tags, campaigns, and custom fields, create a <a href="%s">new integration</a> and select SharpSpring [PRO].', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-new' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }

            if ( adfoin_fs()->is_not_paying() ) {
                ?>
                <tr valign="top" v-if="action.task == 'add_lead'">
                    <th scope="row">
                        <?php esc_attr_e( 'Go Pro', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'Unlock tags and campaigns by <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </script>
    <?php
}

function adfoin_sharpspring_request( $method, $params = array(), $record = array() ) {
    $account = get_option( 'adfoin_sharpspring_account_id', '' );
    $secret  = get_option( 'adfoin_sharpspring_secret_key', '' );

    if ( ! $account || ! $secret ) {
        return new WP_Error( 'adfoin_sharpspring_missing_credentials', __( 'SharpSpring credentials are missing.', 'advanced-form-integration' ) );
    }

    $endpoint = 'https://api.sharpspring.com/pubapi/v1/';

    $payload = array(
        'method'    => $method,
        'params'    => $params,
        'id'        => time(),
        'jsonrpc'   => '2.0',
    );

    $body = array(
        'accountID' => $account,
        'secretKey' => $secret,
        'request'   => wp_json_encode( $payload ),
    );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'body'    => $body,
    );

    $response = wp_remote_post( $endpoint, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $endpoint, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_sharpspring_job_queue', 'adfoin_sharpspring_job_queue', 10, 1 );

function adfoin_sharpspring_job_queue( $data ) {
    adfoin_sharpspring_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sharpspring_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === $record_data['action_data']['cl']['active'] ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_lead' !== $task ) {
        return;
    }

    $email = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );

    if ( ! $email ) {
        return;
    }

    $lead = array(
        'emailAddress' => $email,
    );

    $first_name = empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
    $last_name  = empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data );

    if ( $first_name ) {
        $lead['firstName'] = $first_name;
    }

    if ( $last_name ) {
        $lead['lastName'] = $last_name;
    }

    $params = array(
        'objects' => array( $lead ),
        'idField' => 'emailAddress',
    );

    adfoin_sharpspring_request( 'createOrUpdateLeads', $params, $record );
}
