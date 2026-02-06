<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_sendgrid_actions',
    10,
    1
);
function adfoin_sendgrid_actions(  $actions  ) {
    $actions['sendgrid'] = array(
        'title' => __( 'SendGrid', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add/Update Contact', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_sendgrid_settings_tab',
    10,
    1
);
function adfoin_sendgrid_settings_tab(  $providers  ) {
    $providers['sendgrid'] = __( 'SendGrid', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_sendgrid_settings_view',
    10,
    1
);
function adfoin_sendgrid_settings_view(  $current_tab  ) {
    if ( $current_tab !== 'sendgrid' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_sendgrid_settings' );
    $api_key = ( get_option( 'adfoin_sendgrid_api_key' ) ? get_option( 'adfoin_sendgrid_api_key' ) : '' );
    ?>

    <form name="sendgrid_save_form" action="<?php 
    echo esc_url( admin_url( 'admin-post.php' ) );
    ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_sendgrid_api_key">
        <input type="hidden" name="_nonce" value="<?php 
    echo esc_attr( $nonce );
    ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php 
    esc_html_e( 'SendGrid API Key', 'advanced-form-integration' );
    ?></th>
                <td>
                    <input type="text" name="adfoin_sendgrid_api_key"
                           value="<?php 
    echo esc_attr( $api_key );
    ?>" placeholder="<?php 
    esc_attr_e( 'Enter API Key', 'advanced-form-integration' );
    ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php 
    printf( __( 'Create a key in your <a href="%s" target="_blank" rel="noopener noreferrer">SendGrid API Keys</a> area with Marketing Campaigns permissions.', 'advanced-form-integration' ), esc_url( 'https://app.sendgrid.com/settings/api_keys' ) );
    ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php 
    submit_button();
    ?>
    </form>

    <?php 
}

add_action(
    'admin_post_adfoin_save_sendgrid_api_key',
    'adfoin_save_sendgrid_api_key',
    10,
    0
);
function adfoin_save_sendgrid_api_key() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_sendgrid_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = ( isset( $_POST['adfoin_sendgrid_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sendgrid_api_key'] ) ) : '' );
    update_option( 'adfoin_sendgrid_api_key', $api_key );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=sendgrid' );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_sendgrid_js_fields',
    10,
    1
);
function adfoin_sendgrid_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_sendgrid_action_fields' );
function adfoin_sendgrid_action_fields() {
    ?>
    <script type="text/template" id="sendgrid-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'SendGrid List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""><?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index">{{ item }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            v-bind:key="field.value"
                            v-bind:field="field"
                            v-bind:trigger="trigger"
                            v-bind:action="action"
                            v-bind:fielddata="fielddata"></editable-field>
            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope="row">
                        <span><?php 
        printf( __( 'Unlock custom field and tag support by <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                    </td>
                </tr>
                <?php 
    }
    ?>
        </table>
    </script>
    <?php 
}

add_action(
    'wp_ajax_adfoin_get_sendgrid_lists',
    'adfoin_get_sendgrid_lists',
    10,
    0
);
function adfoin_get_sendgrid_lists() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $lists = array();
    $page_token = '';
    $attempts = 0;
    do {
        $attempts++;
        $endpoint = 'marketing/lists?page_size=200';
        if ( $page_token ) {
            $endpoint .= '&page_token=' . rawurlencode( $page_token );
        }
        $response = adfoin_sendgrid_request( $endpoint, 'GET' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            wp_send_json_error();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['result'] ) && is_array( $body['result'] ) ) {
            foreach ( $body['result'] as $list ) {
                if ( isset( $list['id'], $list['name'] ) ) {
                    $lists[$list['id']] = $list['name'];
                }
            }
        }
        if ( !empty( $body['next_page_token'] ) ) {
            $page_token = $body['next_page_token'];
        } else {
            $page_token = '';
        }
    } while ( $page_token && $attempts < 5 );
    wp_send_json_success( $lists );
}

function adfoin_sendgrid_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array()
) {
    $api_key = ( get_option( 'adfoin_sendgrid_api_key' ) ? get_option( 'adfoin_sendgrid_api_key' ) : '' );
    if ( !$api_key ) {
        return new WP_Error('adfoin_sendgrid_missing_key', __( 'SendGrid API key is missing.', 'advanced-form-integration' ));
    }
    $endpoint = ltrim( $endpoint, '/' );
    $url = 'https://api.sendgrid.com/v3/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( in_array( strtoupper( $method ), array(
        'POST',
        'PUT',
        'PATCH',
        'DELETE'
    ), true ) && !empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}

add_action(
    'adfoin_sendgrid_job_queue',
    'adfoin_sendgrid_job_queue',
    10,
    1
);
function adfoin_sendgrid_job_queue(  $data  ) {
    adfoin_sendgrid_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sendgrid_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( 'yes' === $record_data['action_data']['cl']['active'] ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $field_data = ( isset( $record_data['field_data'] ) ? $record_data['field_data'] : array() );
    $task = ( isset( $record['task'] ) ? $record['task'] : '' );
    if ( 'subscribe' !== $task ) {
        return;
    }
    $list_id = ( isset( $field_data['listId'] ) ? $field_data['listId'] : '' );
    $email = ( empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) ) );
    $first_name = ( empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data ) );
    $last_name = ( empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data ) );
    if ( !$email ) {
        return;
    }
    $contact = array(
        'email' => $email,
    );
    if ( $first_name ) {
        $contact['first_name'] = $first_name;
    }
    if ( $last_name ) {
        $contact['last_name'] = $last_name;
    }
    $payload = array(
        'contacts' => array(array_filter( $contact )),
    );
    if ( $list_id ) {
        $payload['list_ids'] = array($list_id);
    }
    adfoin_sendgrid_request(
        'marketing/contacts',
        'PUT',
        $payload,
        $record
    );
}
