<?php

add_filter( 'adfoin_action_providers', 'adfoin_curated_actions', 10, 1 );

function adfoin_curated_actions( $actions ) {

    $actions['curated'] = array(
        'title' => __( 'Curated', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Subscriber', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_curated_settings_tab', 10, 1 );

function adfoin_curated_settings_tab( $providers ) {
    $providers['curated'] = __( 'Curated', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_curated_settings_view', 10, 1 );

function adfoin_curated_settings_view( $current_tab ) {
    if( $current_tab != 'curated' ) {
        return;
    }

    $nonce      = wp_create_nonce( "adfoin_curated_settings" );
    $pub_domain = get_option( 'adfoin_curated_publication_domain' ) ? get_option( 'adfoin_curated_publication_domain' ) : "";
    $api_key    = get_option( 'adfoin_curated_api_key' ) ? get_option( 'adfoin_curated_api_key' ) : "";
    ?>

    <form name="curated_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_curated_api_key">
        <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php _e( 'Publication Domain', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_curated_publication_domain"
                           value="<?php echo esc_attr( $pub_domain ); ?>" placeholder="<?php _e( 'Please enter Publication Domain', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php _e( 'API Key', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_curated_api_key"
                           value="<?php echo esc_attr( $api_key ); ?>" placeholder="<?php _e( 'Please enter API Key', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_curated_api_key', 'adfoin_save_curated_api_key', 10, 0 );

function adfoin_save_curated_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_curated_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $pub_domain = sanitize_text_field( $_POST["adfoin_curated_publication_domain"] );
    $api_key    = sanitize_text_field( $_POST["adfoin_curated_api_key"] );

    // Save tokens
    update_option( "adfoin_curated_publication_domain", $pub_domain );
    update_option( "adfoin_curated_api_key", $api_key );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=curated" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_curated_js_fields', 10, 1 );

function adfoin_curated_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_curated_action_fields' );

function adfoin_curated_action_fields() {
    ?>
    <script type="text/template" id="curated-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_curated_job_queue', 'adfoin_curated_job_queue', 10, 1 );

function adfoin_curated_job_queue( $data ) {
    adfoin_curated_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Curated API
 */
function adfoin_curated_send_data( $record, $posted_data ) {

    $pub_domain = get_option( 'adfoin_curated_publication_domain' ) ? get_option( 'adfoin_curated_publication_domain' ) : "";
    $api_key    = get_option( 'adfoin_curated_api_key' ) ? get_option( 'adfoin_curated_api_key' ) : "";

    if( !$pub_domain || !$api_key ) {
        return;
    }

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"]) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data["field_data"];
    $task = $record["task"];

    if( $task == "subscribe" ) {
        $email = empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data );

        $headers = array(
            "Accept"        => "application/json",
            "Content-Type"  => "application/json",
            "Authorization" => "Token token={$api_key}"
        );

        $url = "https://api.curated.co/{$pub_domain}/api/v1/email_subscribers";

        $body = array(
            "email" => $email
        );

        $args = array(
            "headers" => $headers,
            "body" => json_encode( $body )
        );

        $response = wp_remote_post( $url, $args );

        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return;
}