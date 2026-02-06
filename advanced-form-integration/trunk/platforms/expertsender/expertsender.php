<?php

add_filter( 'adfoin_action_providers', 'adfoin_expertsender_actions', 10, 1 );

function adfoin_expertsender_actions( $actions ) {

    $actions['expertsender'] = array(
        'title' => __( 'ExpertSender', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Add / Update Subscriber', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_expertsender_settings_tab', 10, 1 );

function adfoin_expertsender_settings_tab( $tabs ) {
    $tabs['expertsender'] = __( 'ExpertSender', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_expertsender_settings_view', 10, 1 );

function adfoin_expertsender_settings_view( $current_tab ) {
    if ( 'expertsender' !== $current_tab ) {
        return;
    }

    $title     = __( 'ExpertSender', 'advanced-form-integration' );
    $key       = 'expertsender';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'apiKey',
                    'label' => __( 'API Key', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'API Base URL', 'advanced-form-integration' ),
                    'placeholder' => 'https://api.expertsender.com/api/',
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing tag */
        __( '<p>Use the API key from ExpertSender (My profile → API). The default API URL is <code>https://api.expertsender.com/api/</code>, but you can override it for different data centers. See the %1$sAdd Subscriber%2$s documentation for payload details.</p>', 'advanced-form-integration' ),
        '<a href="https://help.expertsender.com/emp/api/methods/subscribers/add-subscriber/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_expertsender_credentials', 'adfoin_get_expertsender_credentials', 10, 0 );

function adfoin_get_expertsender_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'expertsender' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_expertsender_credentials', 'adfoin_save_expertsender_credentials', 10, 0 );

function adfoin_save_expertsender_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'expertsender' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_expertsender_credentials_list() {
    $credentials = adfoin_read_credentials( 'expertsender' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_expertsender_action_fields' );

function adfoin_expertsender_action_fields() {
    ?>
    <script type="text/template" id="expertsender-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'ExpertSender Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_expertsender_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'List ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="number" class="regular-text" name="fieldData[listId]" v-model="fielddata.listId" min="1" placeholder="<?php esc_attr_e( 'Enter numeric List ID', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Mode', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[mode]" v-model="fielddata.mode">
                        <option value="AddAndUpdate"><?php esc_html_e( 'AddAndUpdate (default)', 'advanced-form-integration' ); ?></option>
                        <option value="Add"><?php esc_html_e( 'Add only', 'advanced-form-integration' ); ?></option>
                        <option value="Update"><?php esc_html_e( 'Update existing', 'advanced-form-integration' ); ?></option>
                        <option value="AddAndReplace"><?php esc_html_e( 'Add and replace', 'advanced-form-integration' ); ?></option>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"city":"Berlin","age":"35"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Optional key/value pairs appended as custom fields. Must be valid JSON.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://help.expertsender.com/emp/api/methods/subscribers/add-subscriber/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'API documentation', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_expertsender_job_queue', 'adfoin_expertsender_job_queue', 10, 1 );

function adfoin_expertsender_job_queue( $data ) {
    adfoin_expertsender_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_expertsender_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) ) {
        $cl = $record_data['action_data']['cl'];
        if ( isset( $cl['active'] ) && 'yes' === $cl['active'] ) {
            if ( ! adfoin_match_conditional_logic( $cl, $posted_data ) ) {
                return;
            }
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $list_id    = isset( $field_data['listId'] ) ? absint( $field_data['listId'] ) : 0;

    $email_field = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email       = $email_field ? sanitize_email( adfoin_get_parsed_values( $email_field, $posted_data ) ) : '';

    if ( empty( $cred_id ) || ! $list_id || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'expertsender', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( empty( $api_key ) ) {
        return;
    }

    $mode = isset( $field_data['mode'] ) && $field_data['mode'] ? $field_data['mode'] : 'AddAndUpdate';

    $xml = adfoin_expertsender_build_request_xml( $api_key, $list_id, $email, $field_data, $posted_data, $mode );

    if ( is_wp_error( $xml ) ) {
        return;
    }

    adfoin_expertsender_api_request( $credentials, $xml, $record );
}

function adfoin_expertsender_build_request_xml( $api_key, $list_id, $email, $field_data, $posted_data, $mode ) {
    if ( ! class_exists( 'SimpleXMLElement' ) ) {
        return new WP_Error( 'expertsender_simplexml_missing', __( 'SimpleXML extension is required for ExpertSender integration.', 'advanced-form-integration' ) );
    }

    $xml = new SimpleXMLElement( '<AddSubscriberRequest xmlns="http://api.expertsender.com/api"></AddSubscriberRequest>' );
    $xml->addChild( 'ApiKey', $api_key );
    $xml->addChild( 'ListId', (string) $list_id );
    $xml->addChild( 'Email', $email );

    $optional_fields = array(
        'firstname' => 'FirstName',
        'lastname'  => 'LastName',
        'company'   => 'CompanyName',
        'phone'     => 'Phone',
    );

    foreach ( $optional_fields as $field_key => $xml_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' !== $value ) {
            $xml->addChild( $xml_key, esc_html( $value ) );
        }
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customFields'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $custom_node = $xml->addChild( 'CustomFields' );
            foreach ( $custom as $key => $value ) {
                if ( '' === (string) $key ) {
                    continue;
                }
                $field_node = $custom_node->addChild( 'CustomField' );
                $field_node->addChild( 'Key', esc_html( $key ) );
                $field_node->addChild( 'Value', esc_html( $value ) );
            }
        }
    }

    $xml->addChild( 'Mode', esc_html( $mode ) );

    return $xml->asXML();
}

function adfoin_expertsender_api_request( $credentials, $body, $record ) {
    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? $credentials['baseUrl'] : 'https://api.expertsender.com/api/';
    $base_url = trailingslashit( $base_url );
    $endpoint = $base_url . 'ApiKey/Subscribers/AddSubscriber';

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/xml; charset=utf-8',
            'Accept'       => 'application/xml',
        ),
        'body'    => $body,
    );

    $response = wp_remote_post( $endpoint, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $endpoint, $args, $record );
    }

    return $response;
}
