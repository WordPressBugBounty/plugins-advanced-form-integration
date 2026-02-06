<?php

add_filter( 'adfoin_action_providers', 'adfoin_phplist_actions', 10, 1 );

function adfoin_phplist_actions( $actions ) {

    $actions['phplist'] = array(
        'title' => __( 'phpList', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Create / Subscribe Subscriber', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_phplist_settings_tab', 10, 1 );

function adfoin_phplist_settings_tab( $tabs ) {
    $tabs['phplist'] = __( 'phpList', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_phplist_settings_view', 10, 1 );

function adfoin_phplist_settings_view( $current_tab ) {
    if ( 'phplist' !== $current_tab ) {
        return;
    }

    $title      = __( 'phpList', 'advanced-form-integration' );
    $key        = 'phplist';
    $arguments  = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'    => 'soapUrl',
                    'label'  => __( 'SOAP API URL', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'tag',
                    'label'  => __( 'Account Tag', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'username',
                    'label'  => __( 'API Username (email)', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'password',
                    'label'  => __( 'API Password', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );
    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing anchor tag */
        __( '<p>phpList Hosted uses a SOAP API (see %1$sAPI documentation%2$s). Enter the SOAP URL (usually https://www.phplist.com/API/soap.php), your account tag, and the API user credentials from your phpList account.</p>', 'advanced-form-integration' ),
        '<a href="https://www.phplist.com/knowledgebase/phplist-api/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_phplist_credentials', 'adfoin_get_phplist_credentials', 10, 0 );

function adfoin_get_phplist_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'phplist' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_phplist_credentials', 'adfoin_save_phplist_credentials', 10, 0 );

function adfoin_save_phplist_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'phplist' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_phplist_credentials_list() {
    $html        = '';
    $credentials = adfoin_read_credentials( 'phplist' );

    foreach ( $credentials as $credential ) {
        $title   = isset( $credential['title'] ) ? $credential['title'] : '';
        $html   .= '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $title ) . '</option>';
    }

    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_phplist_action_fields' );

function adfoin_phplist_action_fields() {
    ?>
    <script type="text/template" id="phplist-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'phpList Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_phplist_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Subscriber List ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[listId]" v-model="fielddata.listId" required="required">
                </td>
            </tr>
            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://www.phplist.com/knowledgebase/phplist-api/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'API documentation', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_phplist_job_queue', 'adfoin_phplist_job_queue', 10, 1 );

function adfoin_phplist_job_queue( $data ) {
    adfoin_phplist_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_phplist_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && isset( $record_data['action_data']['cl']['active'] ) ) {
        if ( 'yes' === $record_data['action_data']['cl']['active'] ) {
            if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    $email_value = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email       = $email_value ? adfoin_get_parsed_values( $email_value, $posted_data ) : '';
    $email       = sanitize_email( $email );

    if ( empty( $cred_id ) || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'phplist', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $list_id = isset( $field_data['listId'] ) ? absint( $field_data['listId'] ) : 0;

    if ( $list_id < 1 ) {
        return;
    }

    $response = adfoin_phplist_soap_request(
        $credentials,
        'insertNewUser',
        array( array( $email ), $list_id ),
        $record
    );

    if ( is_wp_error( $response ) ) {
        return;
    }

    if ( empty( $response ) ) {
        return;
    }
}

function adfoin_phplist_build_soap_url( $credentials ) {
    $soap_url = isset( $credentials['soapUrl'] ) ? trim( $credentials['soapUrl'] ) : '';
    $tag      = isset( $credentials['tag'] ) ? trim( $credentials['tag'] ) : '';
    $username = isset( $credentials['username'] ) ? trim( $credentials['username'] ) : '';
    $password = isset( $credentials['password'] ) ? trim( $credentials['password'] ) : '';

    if ( empty( $soap_url ) ) {
        return new WP_Error( 'phplist_missing_soap_url', __( 'phpList SOAP API URL is missing.', 'advanced-form-integration' ) );
    }

    if ( empty( $tag ) || empty( $username ) || empty( $password ) ) {
        return new WP_Error( 'phplist_missing_credentials', __( 'phpList API credentials are missing.', 'advanced-form-integration' ) );
    }

    if ( ! preg_match( '#^https?://#i', $soap_url ) ) {
        $soap_url = 'https://' . $soap_url;
    }

    return add_query_arg(
        array(
            'tag'  => $tag,
            'user' => $username,
            'pass' => $password,
        ),
        $soap_url
    );
}

function adfoin_phplist_soap_request( $credentials, $method, $params = array(), $record = array() ) {
    $soap_url = adfoin_phplist_build_soap_url( $credentials );
    $log_args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'text/xml; charset=utf-8',
        ),
        'body'    => $params,
    );

    if ( is_wp_error( $soap_url ) ) {
        if ( $record ) {
            adfoin_add_to_log( $soap_url, '', $log_args, $record );
        }

        return $soap_url;
    }

    if ( ! class_exists( 'SoapClient' ) ) {
        $error = new WP_Error( 'phplist_missing_soap_client', __( 'PHP SOAP extension is not available on this server.', 'advanced-form-integration' ) );

        if ( $record ) {
            adfoin_add_to_log( $error, $soap_url, $log_args, $record );
        }

        return $error;
    }

    $options = array(
        'location'           => $soap_url,
        'uri'                => 'urn:phpListHosted',
        'exceptions'         => true,
        'connection_timeout' => 30,
    );

    try {
        $client   = new SoapClient( null, $options );
        $response = $client->__soapCall(
            $method,
            $params,
            array(
                'soapaction' => 'phpListHosted.' . $method,
            )
        );
    } catch ( Exception $exception ) {
        $error = new WP_Error( 'phplist_soap_error', $exception->getMessage() );

        if ( $record ) {
            adfoin_add_to_log( $error, $soap_url, $log_args, $record );
        }

        return $error;
    }

    if ( $record ) {
        $log_response = array(
            'body'     => $response,
            'response' => array(
                'code'    => 200,
                'message' => 'OK',
            ),
        );

        adfoin_add_to_log( $log_response, $soap_url, $log_args, $record );
    }

    return $response;
}
