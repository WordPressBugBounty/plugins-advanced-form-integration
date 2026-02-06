<?php

add_filter( 'adfoin_action_providers', 'adfoin_ontraport_actions', 10, 1 );

function adfoin_ontraport_actions( $actions ) {

    $actions['ontraport'] = array(
        'title' => __( 'Ontraport', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create/Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ontraport_settings_tab', 10, 1 );

function adfoin_ontraport_settings_tab( $providers ) {
    $providers['ontraport'] = __( 'Ontraport', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ontraport_settings_view', 10, 1 );

function adfoin_ontraport_settings_view( $current_tab ) {
    if ( 'ontraport' !== $current_tab ) {
        return;
    }

    $nonce    = wp_create_nonce( 'adfoin_ontraport_settings' );
    $app_id   = get_option( 'adfoin_ontraport_app_id', '' );
    $api_key  = get_option( 'adfoin_ontraport_api_key', '' );
    ?>

    <form name="ontraport_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_ontraport_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'Ontraport App ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_ontraport_app_id"
                           value="<?php echo esc_attr( $app_id ); ?>" placeholder="<?php esc_attr_e( 'Enter App ID', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'Ontraport API Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_ontraport_api_key"
                           value="<?php echo esc_attr( $api_key ); ?>" placeholder="<?php esc_attr_e( 'Enter API Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php
                        printf(
                            __( 'Generate credentials under <a href="%s" target="_blank" rel="noopener noreferrer">Ontraport → Administration → Integrations → API Keys</a>.', 'advanced-form-integration' ),
                            esc_url( 'https://app.ontraport.com/#!/account/api' )
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

add_action( 'admin_post_adfoin_save_ontraport_credentials', 'adfoin_save_ontraport_credentials', 10, 0 );

function adfoin_save_ontraport_credentials() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_ontraport_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $app_id  = isset( $_POST['adfoin_ontraport_app_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_ontraport_app_id'] ) ) : '';
    $api_key = isset( $_POST['adfoin_ontraport_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_ontraport_api_key'] ) ) : '';

    update_option( 'adfoin_ontraport_app_id', $app_id );
    update_option( 'adfoin_ontraport_api_key', $api_key );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=ontraport' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_ontraport_js_fields', 10, 1 );

function adfoin_ontraport_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_ontraport_action_fields' );

function adfoin_ontraport_action_fields() {
    ?>
    <script type="text/template" id="ontraport-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
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
                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row">
                        <?php esc_attr_e( 'Using Pro Features', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'For tags, campaigns, and advanced fields, create a <a href="%s">new integration</a> and select Ontraport [PRO].', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-new' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }

            if ( adfoin_fs()->is_not_paying() ) {
                ?>
                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row">
                        <?php esc_attr_e( 'Go Pro', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'Unlock tags, campaigns, and custom fields by <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </script>
    <?php
}

function adfoin_ontraport_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
    $app_id  = get_option( 'adfoin_ontraport_app_id', '' );
    $api_key = get_option( 'adfoin_ontraport_api_key', '' );

    if ( ! $app_id || ! $api_key ) {
        return new WP_Error( 'adfoin_ontraport_missing_credentials', __( 'Ontraport credentials are missing.', 'advanced-form-integration' ) );
    }

    $endpoint = ltrim( $endpoint, '/' );
    $url      = 'https://api.ontraport.com/1/' . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Api-Appid' => $app_id,
            'Api-Key'   => $api_key,
            'Accept'    => 'application/json',
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body']                    = http_build_query( $data );
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
    } elseif ( ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_ontraport_job_queue', 'adfoin_ontraport_job_queue', 10, 1 );

function adfoin_ontraport_job_queue( $data ) {
    adfoin_ontraport_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ontraport_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === $record_data['action_data']['cl']['active'] ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_contact' !== $task ) {
        return;
    }

    $email = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );

    if ( ! $email ) {
        return;
    }

    $payload = array(
        'email'      => $email,
        'firstname'  => empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data ),
        'lastname'   => empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data ),
        'add_update' => 1,
    );

    $payload = array_filter( $payload, 'strlen' );

    adfoin_ontraport_request( 'Contacts/saveorupdate', 'POST', $payload, $record );
}
