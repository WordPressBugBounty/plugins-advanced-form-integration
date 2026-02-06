<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailgun_actions', 10, 1 );

function adfoin_mailgun_actions( $actions ) {

    $actions['mailgun'] = array(
        'title' => __( 'Mailgun', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
            'unsubscribe' => __( 'Unsubscribe From List', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailgun_settings_tab', 10, 1 );

function adfoin_mailgun_settings_tab( $providers ) {
    $providers['mailgun'] = __( 'Mailgun', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailgun_settings_view', 10, 1 );

function adfoin_mailgun_settings_view( $current_tab ) {
    if ( 'mailgun' !== $current_tab ) {
        return;
    }

    $nonce   = wp_create_nonce( 'adfoin_mailgun_settings' );
    $api_key = get_option( 'adfoin_mailgun_api_key', '' );
    ?>

    <form name="mailgun_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_mailgun_api_key">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php esc_html_e( 'Mailgun Private API Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_mailgun_api_key"
                           value="<?php echo esc_attr( $api_key ); ?>" placeholder="<?php esc_attr_e( 'Enter API Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description">
                        <?php
                        printf(
                            __( 'Create a private key in your <a href="%s" target="_blank" rel="noopener noreferrer">Mailgun Security</a> settings with Lists permission.', 'advanced-form-integration' ),
                            esc_url( 'https://app.mailgun.com/app/account/security/api_keys' )
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

add_action( 'admin_post_adfoin_save_mailgun_api_key', 'adfoin_save_mailgun_api_key', 10, 0 );

function adfoin_save_mailgun_api_key() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_mailgun_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = isset( $_POST['adfoin_mailgun_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_mailgun_api_key'] ) ) : '';

    update_option( 'adfoin_mailgun_api_key', $api_key );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=mailgun' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_mailgun_js_fields', 10, 1 );

function adfoin_mailgun_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_mailgun_action_fields' );

function adfoin_mailgun_action_fields() {
    ?>
    <script type="text/template" id="mailgun-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Mailgun List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""><?php _e( 'Select List...', 'advanced-form-integration' ); ?></option>
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
            if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
                ?>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Using Pro Features', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'For tags and custom member variables, create a <a href=\"%s\">new integration</a> and select Mailgun [PRO].', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-new' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }

            if ( adfoin_fs()->is_not_paying() ) {
                ?>
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Go Pro', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <span><?php printf( __( 'Unlock tags and custom variables by <a href=\"%s\">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ); ?></span>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mailgun_lists', 'adfoin_get_mailgun_lists', 10, 0 );

function adfoin_get_mailgun_lists() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $lists       = array();
    $endpoint    = 'lists/pages';
    $safe_guard  = 0;

    do {
        $safe_guard++;
        $response = adfoin_mailgun_request( $endpoint, 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['items'] ) && is_array( $body['items'] ) ) {
            foreach ( $body['items'] as $list ) {
                if ( isset( $list['address'], $list['name'] ) ) {
                    $lists[ $list['address'] ] = $list['name'];
                }
            }
        }

        if ( isset( $body['paging']['next'] ) && $body['paging']['next'] ) {
            $parsed_next = wp_parse_url( $body['paging']['next'] );
            $endpoint    = isset( $parsed_next['path'] ) ? ltrim( $parsed_next['path'], '/' ) : 'lists/pages';

            if ( ! empty( $parsed_next['query'] ) ) {
                $endpoint .= '?' . $parsed_next['query'];
            }
        } else {
            $endpoint = '';
        }

    } while ( $endpoint && $safe_guard < 6 );

    wp_send_json_success( $lists );
}

function adfoin_mailgun_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
    $api_key = get_option( 'adfoin_mailgun_api_key', '' );

    if ( ! $api_key ) {
        return new WP_Error( 'adfoin_mailgun_missing_key', __( 'Mailgun API key is missing.', 'advanced-form-integration' ) );
    }

    $endpoint = ltrim( $endpoint, '/' );
    $url      = 'https://api.mailgun.net/v3/' . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT' ), true ) && ! empty( $data ) ) {
        $args['body']    = $data;
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_mailgun_job_queue', 'adfoin_mailgun_job_queue', 10, 1 );

function adfoin_mailgun_job_queue( $data ) {
    adfoin_mailgun_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailgun_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === $record_data['action_data']['cl']['active'] ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $list_id = isset( $field_data['listId'] ) ? $field_data['listId'] : '';
    $email   = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );
    $name    = empty( $field_data['name'] ) ? '' : adfoin_get_parsed_values( $field_data['name'], $posted_data );

    if ( ! $list_id || ! $email ) {
        return;
    }

    if ( 'subscribe' === $task ) {
        $member = array(
            'address'   => $email,
            'subscribed'=> 'yes',
            'upsert'    => 'yes',
        );

        if ( $name ) {
            $member['name'] = $name;
        }

        adfoin_mailgun_request( "lists/{$list_id}/members", 'POST', $member, $record );
    }

    if ( 'unsubscribe' === $task ) {
        adfoin_mailgun_request( "lists/{$list_id}/members/" . rawurlencode( $email ), 'DELETE', array(), $record );
    }
}
