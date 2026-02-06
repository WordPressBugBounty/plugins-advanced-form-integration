<?php

add_filter( 'adfoin_action_providers', 'adfoin_unisender_actions', 10, 1 );

function adfoin_unisender_actions( $actions ) {

    $actions['unisender'] = array(
        'title' => __( 'UniSender', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Add / Update Subscriber', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_unisender_settings_tab', 10, 1 );

function adfoin_unisender_settings_tab( $tabs ) {
    $tabs['unisender'] = __( 'UniSender', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_unisender_settings_view', 10, 1 );

function adfoin_unisender_settings_view( $current_tab ) {
    if ( 'unisender' !== $current_tab ) {
        return;
    }

    $title     = __( 'UniSender', 'advanced-form-integration' );
    $key       = 'unisender';
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
                    'placeholder' => 'https://api.unisender.com/ru/api/',
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor, 2: closing anchor */
        __( '<p>Copy your UniSender API key (<a href="https://cp.unisender.com/en/api/keys/" target="_blank" rel="noopener noreferrer">API settings</a>) and, if required, set the appropriate regional API URL (e.g. <code>https://api.unisender.com/en/api/</code>). AFI calls the <code>subscribe</code> endpoint to add/update contacts. See the %1$sAPI docs%2$s for all supported fields.</p>', 'advanced-form-integration' ),
        '<a href="https://www.unisender.com/ru/support/category/api/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_unisender_credentials', 'adfoin_get_unisender_credentials', 10, 0 );

function adfoin_get_unisender_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'unisender' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_unisender_credentials', 'adfoin_save_unisender_credentials', 10, 0 );

function adfoin_save_unisender_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'unisender' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_unisender_credentials_list() {
    $credentials = adfoin_read_credentials( 'unisender' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_unisender_action_fields' );

function adfoin_unisender_action_fields() {
    ?>
    <script type="text/template" id="unisender-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'UniSender Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_unisender_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'List ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="number" class="regular-text" name="fieldData[listId]" v-model="fielddata.listId" min="1" placeholder="<?php esc_attr_e( 'Numeric list ID', 'advanced-form-integration' ); ?>" required>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Double Opt-in', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[doubleOptin]" v-model="fielddata.doubleOptin">
                        <option value="0"><?php esc_html_e( 'Disabled (0)', 'advanced-form-integration' ); ?></option>
                        <option value="1"><?php esc_html_e( 'Enabled (1)', 'advanced-form-integration' ); ?></option>
                        <option value="3"><?php esc_html_e( 'According to list settings (3)', 'advanced-form-integration' ); ?></option>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Overwrite existing data', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="fieldData[overwrite]" v-model="fielddata.overwrite" true-value="1" false-value="0">
                        <?php esc_html_e( 'Yes, overwrite subscriber data if it already exists.', 'advanced-form-integration' ); ?>
                    </label>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Tags (comma separated)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[tags]" v-model="fielddata.tags" placeholder="<?php esc_attr_e( 'lead,welcome', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"City":"Warsaw","Age":"28"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Each key/value will be sent as a UniSender custom field.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://www.unisender.com/ru/support/api/subscribe/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'API documentation', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_unisender_job_queue', 'adfoin_unisender_job_queue', 10, 1 );

function adfoin_unisender_job_queue( $data ) {
    adfoin_unisender_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_unisender_send_data( $record, $posted_data ) {
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
    $list_id    = isset( $field_data['listId'] ) ? trim( (string) $field_data['listId'] ) : '';

    $email_field = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email       = $email_field ? sanitize_email( adfoin_get_parsed_values( $email_field, $posted_data ) ) : '';

    if ( empty( $cred_id ) || empty( $list_id ) || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'unisender', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( empty( $api_key ) ) {
        return;
    }

    $payload = array(
        'api_key'   => $api_key,
        'list_ids'  => $list_id,
        'fields[email]' => $email,
        'double_optin'  => isset( $field_data['doubleOptin'] ) ? $field_data['doubleOptin'] : 0,
        'overwrite'     => ! empty( $field_data['overwrite'] ) ? 1 : 0,
    );

    $fields_map = array(
        'first_name' => 'FirstName',
        'lastname'   => 'LastName',
        'firstname'  => 'FirstName',
        'phone'      => 'Phone',
        'company'    => 'Company',
    );

    foreach ( $fields_map as $field_key => $api_field ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' !== $value ) {
            $payload[ 'fields[' . $api_field . ']' ] = $value;
        }
    }

    if ( ! empty( $field_data['tags'] ) ) {
        $payload['tags'] = adfoin_get_parsed_values( $field_data['tags'], $posted_data );
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customFields'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            foreach ( $custom as $key => $value ) {
                if ( '' === (string) $key ) {
                    continue;
                }
                $payload[ 'fields[' . $key . ']' ] = $value;
            }
        }
    }

    adfoin_unisender_api_request( $credentials, 'subscribe', $payload, $record );
}

function adfoin_unisender_api_request( $credentials, $endpoint, $payload, $record ) {
    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? $credentials['baseUrl'] : 'https://api.unisender.com/ru/api/';
    $base_url = trailingslashit( $base_url );

    $url = $base_url . $endpoint . '?format=json';

    $args = array(
        'timeout' => 30,
        'body'    => $payload,
    );

    $response = wp_remote_post( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, array( 'body' => $payload ), $record );
    }

    return $response;
}
