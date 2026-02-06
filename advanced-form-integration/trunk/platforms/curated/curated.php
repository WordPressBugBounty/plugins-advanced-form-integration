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

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'publicationDomain', 
            'label' => __( 'Publication Domain', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'placeholder' => __( 'Enter your publication domain', 'advanced-form-integration' ),
            'show_in_table' => true
        ),
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = '';

    ADFOIN_Account_Manager::render_settings_view( 'curated', __( 'Curated', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_curated_credentials', 'adfoin_get_curated_credentials', 10, 0 );
/*
 * Get Curated credentials
 */
function adfoin_get_curated_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'curated' );
}

add_action( 'wp_ajax_adfoin_save_curated_credentials', 'adfoin_save_curated_credentials', 10, 0 );
/*
 * Save Curated credentials
 */
function adfoin_save_curated_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'curated', array( 'publicationDomain', 'apiKey' ) );
}

/*
 * Curated Credentials List
 */
function adfoin_curated_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'curated' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_curated_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_curated_modify_credentials( $credentials, $platform ) {
    if ( 'curated' == $platform && empty( $credentials ) ) {
        $pub_domain = get_option( 'adfoin_curated_publication_domain' );
        $api_key = get_option( 'adfoin_curated_api_key' );

        if( $pub_domain && $api_key ) {
            $credentials = array(
                array(
                    'id'                => 'legacy',
                    'title'             => __( 'Legacy Account', 'advanced-form-integration' ),
                    'publicationDomain' => $pub_domain,
                    'apiKey'            => $api_key
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
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

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Curated Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=curated' ); ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"]) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data["field_data"];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record["task"];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'curated' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    $credentials = adfoin_get_credentials_by_id( 'curated', $cred_id );
    $pub_domain = isset( $credentials['publicationDomain'] ) ? $credentials['publicationDomain'] : '';
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $pub_domain ) ) {
        $pub_domain = get_option( 'adfoin_curated_publication_domain' ) ? get_option( 'adfoin_curated_publication_domain' ) : '';
    }

    if( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_curated_api_key' ) ? get_option( 'adfoin_curated_api_key' ) : '';
    }

    if( !$pub_domain || !$api_key ) {
        return;
    }

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