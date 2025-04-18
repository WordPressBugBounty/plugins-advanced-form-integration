<?php

add_filter( 'adfoin_action_providers', 'adfoin_directiq_actions', 10, 1 );

function adfoin_directiq_actions( $actions ) {

    $actions['directiq'] = array(
        'title' => __( 'DirectIQ', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact To List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_directiq_settings_tab', 10, 1 );

function adfoin_directiq_settings_tab( $providers ) {
    $providers['directiq'] = __( 'DirectIQ', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_directiq_settings_view', 10, 1 );

function adfoin_directiq_settings_view( $current_tab ) {
    if( $current_tab != 'directiq' ) {
        return;
    }

    $nonce     = wp_create_nonce( "adfoin_directiq_settings" );
    $user_name = get_option( 'adfoin_directiq_user_name' ) ? get_option( 'adfoin_directiq_user_name' ) : "";
    $api_key   = get_option( 'adfoin_directiq_api_key' ) ? get_option( 'adfoin_directiq_api_key' ) : "";
    ?>

    <form name="directiq_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_directiq_api_key">
        <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php _e( 'User Name', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_directiq_user_name"
                           value="<?php echo esc_attr( $user_name ); ?>" placeholder="<?php _e( 'Enter User Name', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php _e( 'API Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_directiq_api_key"
                           value="<?php echo esc_attr( $api_key ); ?>" placeholder="<?php _e( 'Enter API Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description"><?php _e( 'Go to MY ACCOUNT > Social Media & Integrations > API Integration', 'advanced-form-integration' ); ?></p>
                </td>

            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_directiq_api_key', 'adfoin_save_directiq_api_key', 10, 0 );

function adfoin_save_directiq_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_directiq_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $user_name = sanitize_text_field( $_POST["adfoin_directiq_user_name"] );
    $api_key   = sanitize_text_field( $_POST["adfoin_directiq_api_key"] );

    // Save tokens
    update_option( "adfoin_directiq_user_name", $user_name );
    update_option( "adfoin_directiq_api_key", $api_key );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=directiq" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_directiq_js_fields', 10, 1 );

function adfoin_directiq_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_directiq_action_fields' );

function adfoin_directiq_action_fields() {
    ?>
    <script type="text/template" id="directiq-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'DirectIQ List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_directiq_list', 'adfoin_get_directiq_list', 10, 0 );
/*
 * Get DirectIQ subscriber lists
 */
function adfoin_get_directiq_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $user_name = get_option( "adfoin_directiq_user_name" );
    $api_key   = get_option( "adfoin_directiq_api_key" );

    if( ! $user_name || !$api_key ) {
        return;
    }

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'user-name'    => $user_name,
            'api-key'      => $api_key
        )
    );

    $url  = "https://restapi.directiq.com/contactlists";
    $data = wp_remote_request( $url, $args );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( $data["body"] );
    $lists = wp_list_pluck( $body, 'Name', 'Id' );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_directiq_job_queue', 'adfoin_directiq_job_queue', 10, 1 );

function adfoin_directiq_job_queue( $data ) {
    adfoin_directiq_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to DirectIQ API
 */
function adfoin_directiq_send_data( $record, $posted_data ) {

    $user_name = get_option( 'adfoin_directiq_user_name' ) ? get_option( 'adfoin_directiq_user_name' ) : '';
    $api_key   = get_option( 'adfoin_directiq_api_key' ) ? get_option( 'adfoin_directiq_api_key' ) : '';

    if( !$user_name || !$api_key ) {
        return;
    }

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data["field_data"];
    $task = $record["task"];

    if( $task == "subscribe" ) {
        $list_id    = $data["listId"];
        $email      = empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data );
        $first_name = empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data );
        $last_name  = empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data );

        $data = array(
            'Email'     => $email,
            'FirstName' => $first_name,
            'LastName'  => $last_name
        );

        $url = "https://restapi.directiq.com/contacts/{$list_id}";

        $args = array(

            'headers' => array(
                'Content-Type' => 'application/json',
                'user-name'    => $user_name,
                'api-key'      => $api_key
            ),
            'body' => json_encode( $data )
        );

        $return = wp_remote_post( $url, $args );

        adfoin_add_to_log( $return, $url, $args, $record );
    }

    return;
}