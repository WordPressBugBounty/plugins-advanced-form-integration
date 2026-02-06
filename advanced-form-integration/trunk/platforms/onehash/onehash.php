<?php

add_filter( 'adfoin_action_providers', 'adfoin_onehash_actions', 10, 1 );

function adfoin_onehash_actions( $actions ) {
    $actions['onehash'] = array(
        'title' => __( 'OneHash', 'advanced-form-integration' ),
        'tasks' => array(
        'add_lead'     => __( 'Add Lead', 'advanced-form-integration' ),
        'add_contact'  => __( 'Add Contact', 'advanced-form-integration' ),
        'add_customer' => __( 'Add Customer', 'advanced-form-integration' ),
    ),
    );
    return $actions;
}

/**
 * Get OneHash credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'company', 'api_key', 'api_secret' keys, or empty strings if not found
 */
function adfoin_onehash_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $company = '';
    $api_key = '';
    $api_secret = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'onehash' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $company = $single['company'];
                $api_key = $single['api_key'];
                $api_secret = $single['api_secret'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $company = get_option( 'adfoin_onehash_company' ) ? get_option( 'adfoin_onehash_company' ) : '';
        $api_key = get_option( 'adfoin_onehash_api_key' ) ? get_option( 'adfoin_onehash_api_key' ) : '';
        $api_secret = get_option( 'adfoin_onehash_api_secret' ) ? get_option( 'adfoin_onehash_api_secret' ) : '';
    }

    return array(
        'company' => $company,
        'api_key' => $api_key,
        'api_secret' => $api_secret
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_onehash_settings_tab', 10, 1 );

function adfoin_onehash_settings_tab( $providers ) {
    $providers['onehash'] = __( 'OneHash', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_onehash_settings_view', 10, 1 );

function adfoin_onehash_settings_view( $current_tab ) {
    if ( $current_tab != 'onehash' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_company = get_option( 'adfoin_onehash_company' ) ? get_option( 'adfoin_onehash_company' ) : '';
    $old_api_key = get_option( 'adfoin_onehash_api_key' ) ? get_option( 'adfoin_onehash_api_key' ) : '';
    $old_api_secret = get_option( 'adfoin_onehash_api_secret' ) ? get_option( 'adfoin_onehash_api_secret' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'onehash' );

    if ( $old_company && $old_api_key && $old_api_secret && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'company' => $old_company,
            'api_key' => $old_api_key,
            'api_secret' => $old_api_secret
        );
        adfoin_save_credentials( 'onehash', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'company',
            'label'         => __( 'Company', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter company subdomain', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_secret',
            'label'         => __( 'API Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter API Secret', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to User > API Access in your OneHash account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Generate Keys" to get API Key and Secret.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your company subdomain, API Key, and API Secret in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'onehash', 'OneHash', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_onehash_credentials', 'adfoin_get_onehash_credentials' );
function adfoin_get_onehash_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'onehash' );
}

add_action( 'wp_ajax_adfoin_save_onehash_credentials', 'adfoin_save_onehash_credentials' );
function adfoin_save_onehash_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'onehash', array( 'company', 'api_key', 'api_secret' ) );
}

add_action( 'wp_ajax_adfoin_get_onehash_credentials_list', 'adfoin_onehash_get_credentials_list_ajax' );
function adfoin_onehash_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'company', 'mask' => false ),
        array( 'name' => 'api_key', 'mask' => true ),
        array( 'name' => 'api_secret', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'onehash', $fields );
}

add_action( 'admin_post_adfoin_save_onehash_api_key', 'adfoin_save_onehash_api_key', 10, 0 );

function adfoin_save_onehash_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_onehash_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $company    = sanitize_text_field( $_POST['adfoin_onehash_company'] );
    $api_key    = sanitize_text_field( $_POST['adfoin_onehash_api_key'] );
    $api_secret = sanitize_text_field( $_POST['adfoin_onehash_api_secret'] );
    // Save tokens
    update_option( 'adfoin_onehash_company', $company );
    update_option( 'adfoin_onehash_api_key', $api_key );
    update_option( 'adfoin_onehash_api_secret', $api_secret );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=onehash' );
}

add_action( 'adfoin_action_fields', 'adfoin_onehash_action_fields' );

function adfoin_onehash_action_fields() {
    ?>
    <script type="text/template" id="onehash-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_lead' || action.task == 'add_contact' || action.task == 'add_customer'">
                <th scope="row"><?php esc_html_e( 'OneHash Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=onehash' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_lead'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' );?>
                </th>
                <td scope="row"></td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>


<?php 
}

/*
 * Onehash API Call
 */
function adfoin_onehash_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
 
    $credentials = adfoin_onehash_get_credentials( $cred_id );
    $company = $credentials['company'];
    $api_key = $credentials['api_key'];
    $api_secret = $credentials['api_secret'];
 
    if( !$company || !$api_key || !$api_secret ) {
        return new WP_Error( 'no_credentials', 'No OneHash credentials found' );
    }
 
    $base_url = "https://{$company}.onehash.ai/api/resource/";
    $url      = $base_url . $endpoint;
 
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'token '. $api_key .':'. $api_secret
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

add_action( 'adfoin_onehash_job_queue', 'adfoin_onehash_job_queue', 10, 1 );

function adfoin_onehash_job_queue( $data ) {
    adfoin_onehash_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to onehash API
 */
function adfoin_onehash_send_data( $record, $posted_data ) {
 
    $record_data = json_decode( $record["data"], true );
 
    if( array_key_exists( 'cl', $record_data['action_data']) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
 
    $data = $record_data['field_data'];
    $task = $record['task'];
    
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    $email        = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
    $companyName  = empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data );
    $gender       = empty( $data['gender'] ) ? '' : adfoin_get_parsed_values( $data['gender'], $posted_data );
    $salutation   = empty( $data['salutation'] ) ? '' : adfoin_get_parsed_values( $data['salutation'], $posted_data );
    $designation  = empty( $data['designation'] ) ? '' : adfoin_get_parsed_values( $data['designation'], $posted_data );
    $addressType  = empty( $data['addressType'] ) ? '' : adfoin_get_parsed_values( $data['addressType'], $posted_data );
    $addressTitle = empty( $data['addressTitle'] ) ? '' : adfoin_get_parsed_values( $data['addressTitle'], $posted_data );
    $addressLine1 = empty( $data['addressLine1'] ) ? '' : adfoin_get_parsed_values( $data['addressLine1'], $posted_data );
    $addressLine2 = empty( $data['addressLine2'] ) ? '' : adfoin_get_parsed_values( $data['addressLine2'], $posted_data );
    $city         = empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data );
    $county       = empty( $data['county'] ) ? '' : adfoin_get_parsed_values( $data['county'], $posted_data );
    $state        = empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data );
    $country      = empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data );
    $pincode      = empty( $data['pincode'] ) ? '' : adfoin_get_parsed_values( $data['pincode'], $posted_data );
    $website      = empty( $data['website'] ) ? '' : adfoin_get_parsed_values( $data['website'], $posted_data );
    $phonNO       = empty( $data['phonNO'] ) ? '' : adfoin_get_parsed_values( $data['phonNO'], $posted_data );
    $mobileNo     = empty( $data['mobileNo'] ) ? '' : adfoin_get_parsed_values( $data['mobileNo'], $posted_data );
    $fax          = empty( $data['fax'] ) ? '' : adfoin_get_parsed_values( $data['fax'], $posted_data );

    if( $task == 'add_lead' ) {        
        $lead_name    = empty( $data['fullName'] ) ? '' : adfoin_get_parsed_values( $data['fullName'], $posted_data );  
        $status       = empty( $data['status'] ) ? '' : adfoin_get_parsed_values( $data['status'], $posted_data );
        $source       = empty( $data['source'] ) ? '' : adfoin_get_parsed_values( $data['source'], $posted_data );
        $campaignName = empty( $data['campaignName'] ) ? '' : adfoin_get_parsed_values( $data['campaignName'], $posted_data );
        $contactBy    = empty( $data['contactBy'] ) ? '' : adfoin_get_parsed_values( $data['contactBy'], $posted_data );
        $contactDate  = empty( $data['contactDate'] ) ? '' : adfoin_get_parsed_values( $data['contactDate'], $posted_data );
        $endsOn       = empty( $data['endsOn'] ) ? '' : adfoin_get_parsed_values( $data['endsOn'], $posted_data );
 
        $data = array(
            'email_id'      => trim( $email ),
            'lead_name'     => $lead_name,
            'company_name'  => $companyName,
            'gender'        => $gender,
            'status'        => $status,
            'salutation'    => $salutation,
            'designation'   => $designation,
            'source'        => $source,
            'campaign_name' => $campaignName,
            'contact_by'    => $contactBy,
            'contact_date'  => $contactDate,
            'ends_on'       => $endsOn,
            'address_type'  => $addressType ,
            'address_title' => $addressTitle,
            'address_line1' => $addressLine1,
            'address_line2' => $addressLine2,
            'city'          => $city,
            'county'        => $county,
            'state'         => $state,
            'country'       => $country,
            'pincode'       => $pincode,
            'phone'         => $phonNO,
            'mobile_no'     => $mobileNo,
            'fax'           => $fax,
            'website'       => $website
        );
 
        $return = adfoin_onehash_request( 'Lead', 'POST', $data, $record, $cred_id );
    }

    if( $task == 'add_customer' ){
        $customerName    = empty( $data['customerName'] ) ? '' : adfoin_get_parsed_values( $data['customerName'], $posted_data );
        $customerType    = empty( $data['customerType'] ) ? '' : adfoin_get_parsed_values( $data['customerType'], $posted_data );
        $customerGroup   = empty( $data['customerGroup'] ) ? '' : adfoin_get_parsed_values( $data['customerGroup'], $posted_data );
        $territory       = empty( $data['territory'] ) ? '' : adfoin_get_parsed_values( $data['territory'], $posted_data );
        $leadName        = empty( $data['leadName'] ) ? '' : adfoin_get_parsed_values( $data['leadName'], $posted_data );
        $opportunityName = empty( $data['opportunityName'] ) ? '' : adfoin_get_parsed_values( $data['opportunityName'], $posted_data );
 
        $data = array(
            'name'             => $customerName,
            'customer_name'    => $customerName,
            'customer_type'    => $customerType,
            'customer_group'   => $customerGroup,
            'territory'        => $territory,
            'email_id'         => $email,
            'gender'           => $gender,
            'status'           => $status,
            'lead_name'        => $leadName,
            'opportunity_name' => $opportunityName,
            'salutation'       => $salutation,
            'designation'      => $designation,
            'address_line1'    => $addressLine1,
            'address_line2'    => $addressLine2,
            'city'             => $city,
            'county'           => $county,
            'state'            => $state,
            'country'          => $country,
            'pincode'          => $pincode,
            'phone'            => $phonNO,
            'mobile_no'        => $mobileNo,
            'fax'              => $fax,
            'website'          => $website
        );
 
        $return = adfoin_onehash_request( 'Customer', 'POST', $data, $record, $cred_id );
       
    }

    if( $task == 'add_contact' ){
        $firstName    = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $middleName   = empty( $data['middleName'] ) ? '' : adfoin_get_parsed_values( $data['middleName'], $posted_data );
        $lastName     = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
        $customerType = empty( $data['customerType'] ) ? '' : adfoin_get_parsed_values( $data['customerType'], $posted_data );
 
        $data = array(
            'first_name'    => $firstName,
            'middle_name'   => $middleName,
            'last_name'     => $lastName,
            'company_name'  => $companyName,
            'email_id'      => $email,
            'gender'        => $gender,
            'status'        => $status,
            'salutation'    => $salutation,
            'designation'   => $designation,
            'address_type'  => $addressType ,
            'address_title' => $addressTitle,
            'address_line1' => $addressLine1,
            'address_line2' => $addressLine2,
            'city'          => $city,
            'county'        => $county,
            'state'         => $state,
            'country'       => $country,
            'pincode'       => $pincode,
            'phone'         => $phonNO,
            'mobile_no'     => $mobileNo,
            'fax'           => $fax,
            'website'       => $website
        );
            
        $return = adfoin_onehash_request( 'Contact', 'POST', $data, $record, $cred_id );
    }
 
    return;
}