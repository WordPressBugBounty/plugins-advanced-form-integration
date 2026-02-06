<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_mailersend_actions',
    10,
    1
);
function adfoin_mailersend_actions(  $actions  ) {
    $actions['mailersend'] = array(
        'title' => __( 'MailerSend', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add/Update Recipient', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_mailersend_settings_tab',
    10,
    1
);
function adfoin_mailersend_settings_tab(  $providers  ) {
    $providers['mailersend'] = __( 'MailerSend', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_mailersend_settings_view',
    10,
    1
);
function adfoin_mailersend_settings_view(  $current_tab  ) {
    if ( $current_tab !== 'mailersend' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_mailersend_settings' );
    $api_token = ( get_option( 'adfoin_mailersend_api_token' ) ? get_option( 'adfoin_mailersend_api_token' ) : '' );
    ?>

    <form name="mailersend_save_form" action="<?php 
    echo esc_url( admin_url( 'admin-post.php' ) );
    ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_mailersend_api_token">
        <input type="hidden" name="_nonce" value="<?php 
    echo esc_attr( $nonce );
    ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php 
    esc_html_e( 'MailerSend API Token', 'advanced-form-integration' );
    ?></th>
                <td>
                    <input type="text" name="adfoin_mailersend_api_token"
                           value="<?php 
    echo esc_attr( $api_token );
    ?>" placeholder="<?php 
    esc_attr_e( 'Enter API Token', 'advanced-form-integration' );
    ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php 
    printf( __( 'Create a token in <a href="%s" target="_blank" rel="noopener noreferrer">MailerSend → API</a> with Contacts permissions.', 'advanced-form-integration' ), esc_url( 'https://app.mailersend.com/api-tokens' ) );
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
    'admin_post_adfoin_save_mailersend_api_token',
    'adfoin_save_mailersend_api_token',
    10,
    0
);
function adfoin_save_mailersend_api_token() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_mailersend_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_token = ( isset( $_POST['adfoin_mailersend_api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_mailersend_api_token'] ) ) : '' );
    update_option( 'adfoin_mailersend_api_token', $api_token );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=mailersend' );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_mailersend_js_fields',
    10,
    1
);
function adfoin_mailersend_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_mailersend_action_fields' );
function adfoin_mailersend_action_fields() {
    ?>
    <script type="text/template" id="mailersend-action-template">
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
    esc_attr_e( 'MailerSend List', 'advanced-form-integration' );
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
        printf( __( 'Unlock tags and custom fields by <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
    'wp_ajax_adfoin_get_mailersend_lists',
    'adfoin_get_mailersend_lists',
    10,
    0
);
function adfoin_get_mailersend_lists() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $lists = array();
    $page = 1;
    $has_more = true;
    $max_pages = 10;
    $query_param = 'page=';
    while ( $has_more && $page <= $max_pages ) {
        $endpoint = 'recipient-lists?' . $query_param . $page;
        $response = adfoin_mailersend_request( $endpoint, 'GET' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }
        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            foreach ( $body['data'] as $list ) {
                if ( isset( $list['id'], $list['name'] ) ) {
                    $lists[$list['id']] = $list['name'];
                }
            }
        }
        if ( isset( $body['meta']['next_page'] ) && $body['meta']['next_page'] ) {
            $page = (int) $body['meta']['next_page'];
        } else {
            $has_more = false;
        }
    }
    wp_send_json_success( $lists );
}

function adfoin_mailersend_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array()
) {
    $api_token = ( get_option( 'adfoin_mailersend_api_token' ) ? get_option( 'adfoin_mailersend_api_token' ) : '' );
    if ( !$api_token ) {
        return new WP_Error('adfoin_mailersend_missing_token', __( 'MailerSend API token is missing.', 'advanced-form-integration' ));
    }
    $endpoint = ltrim( $endpoint, '/' );
    $url = 'https://api.mailersend.com/v1/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_token,
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
    'adfoin_mailersend_job_queue',
    'adfoin_mailersend_job_queue',
    10,
    1
);
function adfoin_mailersend_job_queue(  $data  ) {
    adfoin_mailersend_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailersend_send_data(  $record, $posted_data  ) {
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
    $name = ( empty( $field_data['name'] ) ? '' : adfoin_get_parsed_values( $field_data['name'], $posted_data ) );
    if ( !$email ) {
        return;
    }
    $payload = array(
        'email'          => $email,
        'update_enabled' => true,
    );
    if ( $name ) {
        $payload['name'] = $name;
    }
    if ( $list_id ) {
        $payload['groups'] = array($list_id);
    }
    adfoin_mailersend_request(
        'recipients',
        'POST',
        $payload,
        $record
    );
}
