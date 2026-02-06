<?php

add_filter( 'adfoin_action_providers', 'adfoin_companyhub_actions', 10, 1 );

function adfoin_companyhub_actions( $actions ) {

    $actions['companyhub'] = array(
        'title' => __( 'CompanyHub', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact'   => __( 'Add New Contact', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_companyhub_settings_tab', 10, 1 );

function adfoin_companyhub_settings_tab( $providers ) {
    $providers['companyhub'] = __( 'CompanyHub', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_companyhub_settings_view', 10, 1 );

function adfoin_companyhub_settings_view( $current_tab ) {
    if( $current_tab != 'companyhub' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'subdomain', 
            'label' => __( 'Subdomain', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'placeholder' => __( 'Enter your subdomain', 'advanced-form-integration' ),
            'show_in_table' => true
        ),
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Token', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Token', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Settings > Integrations and click on the Generate API Key button.', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'companyhub', __( 'CompanyHub', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_companyhub_credentials', 'adfoin_get_companyhub_credentials', 10, 0 );
/*
 * Get CompanyHub credentials
 */
function adfoin_get_companyhub_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'companyhub' );
}

add_action( 'wp_ajax_adfoin_save_companyhub_credentials', 'adfoin_save_companyhub_credentials', 10, 0 );
/*
 * Save CompanyHub credentials
 */
function adfoin_save_companyhub_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'companyhub', array( 'subdomain', 'apiKey' ) );
}

/*
 * CompanyHub Credentials List
 */
function adfoin_companyhub_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'companyhub' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_companyhub_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_companyhub_modify_credentials( $credentials, $platform ) {
    if ( 'companyhub' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_companyhub_api_key' );
        $subdomain = get_option( 'adfoin_companyhub_subdomain' );

        if( $api_key && $subdomain ) {
            $credentials = array(
                array(
                    'id'        => 'legacy',
                    'title'     => __( 'Legacy Account', 'advanced-form-integration' ),
                    'subdomain' => $subdomain,
                    'apiKey'    => $api_key
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_companyhub_save_api_key', 'adfoin_save_companyhub_api_key', 10, 0 );

function adfoin_save_companyhub_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_companyhub_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $subdomain = sanitize_text_field( $_POST["adfoin_companyhub_subdomain"] );
    $api_key   = sanitize_text_field( $_POST["adfoin_companyhub_api_key"] );

    // Save tokens
    update_option( "adfoin_companyhub_subdomain", $subdomain );
    update_option( "adfoin_companyhub_api_key", $api_key );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=companyhub" );
}

add_action( 'adfoin_action_fields', 'adfoin_companyhub_action_fields' );

function adfoin_companyhub_action_fields() {
    ?>
    <script type="text/template" id="companyhub-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Contact Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'CompanyHub Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=companyhub' ); ?>" target="_blank">
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

function adfoin_companyhub_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'companyhub', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $subdomain = isset( $credentials['subdomain'] ) ? $credentials['subdomain'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_companyhub_api_key' ) ? get_option( 'adfoin_companyhub_api_key' ) : '';
    }

    if( empty( $subdomain ) ) {
        $subdomain = get_option( 'adfoin_companyhub_subdomain' ) ? get_option( 'adfoin_companyhub_subdomain' ) : '';
    }

    if( !$api_key || !$subdomain ) {
        return array();
    }

    $base_url = 'https://api.companyhub.com/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => $subdomain . ' ' . $api_key
        )
    );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_companyhub_job_queue', 'adfoin_companyhub_job_queue', 10, 1 );

function adfoin_companyhub_job_queue( $data ) {
    adfoin_companyhub_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to CompanyHub API
 */
function adfoin_companyhub_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( 'cl', $record_data['action_data']) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'companyhub' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'add_contact' ) {
        $email       = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $first_name  = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
        // $comapny    = empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data );

        // $comapny_data = array(
        //     'Name' => $comapny
        // );

        // $company_api_return = adfoin_companyhub_request( 'tables/company', 'POST', $comapny_data, $record );
        // $company_body = json_decode( wp_remote_retrieve_body( $company_api_return ), true );
        // $company_id = $company_body['Id'];
        
        $data = array(
            'FirstName' => $first_name,
            'LastName'  => $last_name,
            'Email'     => $email
        );

        // if( $company_id ) {
        //     $data['Company'] = array( 'ID' => $company_id );
        // }

        $return = adfoin_companyhub_request( 'tables/contact', 'POST', $data, $record, $cred_id );

    }

    return;
}