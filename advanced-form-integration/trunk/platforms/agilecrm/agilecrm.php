<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_agilecrm_actions',
    10,
    1
);
function adfoin_agilecrm_actions(  $actions  ) {
    $actions['agilecrm'] = array(
        'title' => __( 'Agile CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create New Contact/Deal/Note', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Agile CRM credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key', 'email', 'subdomain' keys, or empty strings if not found
 */
function adfoin_agilecrm_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $api_key = '';
    $user_email = '';
    $subdomain = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'agilecrm' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                $user_email = $single['email'];
                $subdomain = $single['subdomain'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = ( get_option( 'adfoin_agilecrm_api_key' ) ? get_option( 'adfoin_agilecrm_api_key' ) : '' );
        $user_email = ( get_option( 'adfoin_agilecrm_email' ) ? get_option( 'adfoin_agilecrm_email' ) : '' );
        $subdomain = ( get_option( 'adfoin_agilecrm_subdomain' ) ? get_option( 'adfoin_agilecrm_subdomain' ) : '' );
    }
    return array(
        'api_key'   => $api_key,
        'email'     => $user_email,
        'subdomain' => $subdomain,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_agilecrm_settings_tab',
    10,
    1
);
function adfoin_agilecrm_settings_tab(  $providers  ) {
    $providers['agilecrm'] = __( 'Agile CRM', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_agilecrm_settings_view',
    10,
    1
);
function adfoin_agilecrm_settings_view(  $current_tab  ) {
    if ( $current_tab != 'agilecrm' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = get_option( 'adfoin_agilecrm_api_key' );
    $old_email = get_option( 'adfoin_agilecrm_email' );
    $old_subdomain = get_option( 'adfoin_agilecrm_subdomain' );
    $existing_creds = adfoin_read_credentials( 'agilecrm' );
    if ( $old_api_key && $old_email && $old_subdomain && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'        => uniqid(),
            'title'     => 'Default Account',
            'api_key'   => $old_api_key,
            'email'     => $old_email,
            'subdomain' => $old_subdomain,
        );
        adfoin_save_credentials( 'agilecrm', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'subdomain',
        'label'         => __( 'Subdomain', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'e.g., mycompany', 'advanced-form-integration' ),
        'description'   => __( 'If your URL is https://mycompany.agilecrm.com, enter "mycompany"', 'advanced-form-integration' ),
        'show_in_table' => true,
    ), array(
        'name'          => 'email',
        'label'         => __( 'User Email', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Your login email', 'advanced-form-integration' ),
        'show_in_table' => true,
    ), array(
        'name'          => 'api_key',
        'label'         => __( 'REST API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter your REST API Key', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Enter the Subdomain part of your Agile CRM URL (e.g., if URL is https://mycompany.agilecrm.com, enter "mycompany").', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Go to Admin Settings > Developers & API > copy REST API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your User Email (login email).', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'agilecrm',
        'Agile CRM',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_agilecrm_credentials', 'adfoin_get_agilecrm_credentials' );
function adfoin_get_agilecrm_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'agilecrm' );
}

add_action( 'wp_ajax_adfoin_save_agilecrm_credentials', 'adfoin_save_agilecrm_credentials' );
function adfoin_save_agilecrm_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'agilecrm', array('subdomain', 'email', 'api_key') );
}

add_action( 'wp_ajax_adfoin_get_agilecrm_credentials_list', 'adfoin_agilecrm_get_credentials_list_ajax' );
function adfoin_agilecrm_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'subdomain',
        'mask' => false,
    ), array(
        'name' => 'email',
        'mask' => false,
    ), array(
        'name' => 'api_key',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'agilecrm', $fields );
}

add_action( 'adfoin_action_fields', 'adfoin_agilecrm_action_fields' );
function adfoin_agilecrm_action_fields() {
    ?>
    <script type="text/template" id="agilecrm-action-template">
        <div>
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row"><?php 
    esc_html_e( 'Agile CRM Account', 'advanced-form-integration' );
    ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchPipelines">
                            <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=agilecrm' );
    ?>" 
                           target="_blank" 
                           style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                            <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" v-if="action.task == 'add_contact'">
                    <th scope="row">
                        <?php 
    esc_attr_e( 'Contact Fields', 'advanced-form-integration' );
    ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <tr class="alternate" v-if="action.task == 'add_contact'">
                    <td>
                        <label for="tablecell">
                            <?php 
    esc_attr_e( 'Instructions', 'advanced-form-integration' );
    ?>
                        </label>
                    </td>

                    <td>
                        <p><?php 
    _e( 'This task will create Contact, Deal & Note. Leave blank Deal & Note fields if not needed', 'advanced-form-integration' );
    ?></p>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                        <tr valign="top" v-if="action.task == 'add_contact'">
                            <th scope="row">
                                <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                            </th>
                            <td scope="row">
                                <span><?php 
        printf( __( 'To unlock tags & custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                            </td>
                        </tr>
                        <?php 
    }
    ?>
            </table>
        </div>
    </script>

    <?php 
}

add_action(
    'wp_ajax_adfoin_get_agilecrm_pipelines',
    'adfoin_get_agilecrm_pipelines',
    10,
    0
);
function adfoin_get_agilecrm_pipelines() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $credentials = adfoin_agilecrm_get_credentials();
    $api_key = $credentials['api_key'];
    $user_email = $credentials['email'];
    $subdomain = $credentials['subdomain'];
    if ( !$api_key || !$subdomain || !$user_email ) {
        return;
    }
    $users = "";
    $pipelines = "";
    $sources = "";
    $args = array(
        "timeout" => 30,
        "headers" => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $user_email . ':' . $api_key ),
        ),
    );
    $user_url = "https://{$subdomain}.agilecrm.com/dev/api/users";
    $user_response = wp_remote_get( $user_url, $args );
    // adfoin_add_to_log( $user_response, $user_url, $args, array( "id" => "999" ) );
    if ( !is_wp_error( $user_response ) ) {
        $user_body = json_decode( wp_remote_retrieve_body( $user_response ) );
        foreach ( $user_body as $single ) {
            $users .= $single->name . ': ' . $single->id . ' ';
        }
    }
    $url = "https://{$subdomain}.agilecrm.com/dev/api/milestone/pipelines";
    $response = wp_remote_get( $url, $args );
    // adfoin_add_to_log( $response, $url, $args, array( "id" => "999" ) );
    if ( !is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ) );
        foreach ( $body as $single ) {
            $pipelines .= $single->name . ': ' . $single->id . ' ';
        }
        $deal_fields = array(
            array(
                'key'         => 'dealName',
                'value'       => 'Name [Deal]',
                'description' => 'Required for Deal creation',
            ),
            array(
                'key'         => 'dealValue',
                'value'       => 'Value [Deal]',
                'description' => 'Required for Deal creation',
            ),
            array(
                'key'         => 'dealProbability',
                'value'       => 'Probability [Deal]',
                'description' => 'Integer value',
            ),
            array(
                'key'         => 'dealCloseDate',
                'value'       => 'Close Date [Deal]',
                'description' => 'Use YYYY-MM-DD format',
            ),
            array(
                'key'         => 'dealSource',
                'value'       => 'Source ID [Deal]',
                'description' => '',
            ),
            array(
                'key'         => 'dealDescription',
                'value'       => 'Description [Deal]',
                'description' => '',
            ),
            array(
                'key'         => 'dealTrack',
                'value'       => 'Track/Pipeline ID [Deal]',
                'description' => $pipelines,
            ),
            array(
                'key'         => 'dealMilestone',
                'value'       => 'Milestone [Deal]',
                'description' => 'Example: New, Prospect, Proposal, Won, Lost',
            ),
            array(
                'key'         => 'dealOwner',
                'value'       => 'Owner ID [Deal]',
                'description' => $users,
            ),
            array(
                'key'         => 'noteSubject',
                'value'       => 'Subject [Note]',
                'description' => '',
            ),
            array(
                'key'         => 'noteDescription',
                'value'       => 'Description [Note]',
                'description' => '',
            )
        );
        wp_send_json_success( $deal_fields );
    }
}

// Check if contact exists
function adfoin_agilecrm_check_if_contact_exists(  $email, $headers, $subdomain  ) {
    if ( !$email || !$headers ) {
        return false;
    }
    $url = "https://{$subdomain}.agilecrm.com/dev/api/contacts/search/email/{$email}";
    $args = array(
        "headers" => $headers,
    );
    $data = wp_remote_get( $url, $args );
    if ( is_wp_error( $data ) ) {
        return false;
    }
    if ( 200 !== wp_remote_retrieve_response_code( $data ) ) {
        return false;
    }
    $body = json_decode( wp_remote_retrieve_body( $data ) );
    if ( $body->id ) {
        return $body->id;
    } else {
        return false;
    }
}

add_action(
    'adfoin_agilecrm_job_queue',
    'adfoin_agilecrm_job_queue',
    10,
    1
);
function adfoin_agilecrm_job_queue(  $data  ) {
    adfoin_agilecrm_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Agile CRM API
 */
function adfoin_agilecrm_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    $field_data = ( isset( $record_data['field_data'] ) ? $record_data['field_data'] : array() );
    $cred_id = ( isset( $field_data['credId'] ) ? $field_data['credId'] : '' );
    $credentials = adfoin_agilecrm_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];
    $user_email = $credentials['email'];
    $subdomain = $credentials['subdomain'];
    if ( !$api_key || !$subdomain || !$user_email ) {
        return;
    }
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
    $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
    $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
    $title = ( empty( $data['title'] ) ? '' : adfoin_get_parsed_values( $data['title'], $posted_data ) );
    $company = ( empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data ) );
    $phone = ( empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data ) );
    $address = ( empty( $data['address'] ) ? '' : adfoin_get_parsed_values( $data['address'], $posted_data ) );
    $city = ( empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data ) );
    $state = ( empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data ) );
    $zip = ( empty( $data['zip'] ) ? '' : adfoin_get_parsed_values( $data['zip'], $posted_data ) );
    $country = ( empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data ) );
    $deal_name = ( empty( $data['dealName'] ) ? '' : adfoin_get_parsed_values( $data['dealName'], $posted_data ) );
    $note_sub = ( empty( $data['noteSubject'] ) ? '' : adfoin_get_parsed_values( $data['noteSubject'], $posted_data ) );
    $note_desc = ( empty( $data['noteDescription'] ) ? '' : adfoin_get_parsed_values( $data['noteDescription'], $posted_data ) );
    if ( $task == 'add_contact' ) {
        $headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $user_email . ':' . $api_key ),
        );
        $body = array(
            'properties' => array(),
        );
        if ( $first_name ) {
            $body['properties'][] = array(
                'type'  => 'SYSTEM',
                'name'  => 'first_name',
                'value' => $first_name,
            );
        }
        if ( $last_name ) {
            $body['properties'][] = array(
                'type'  => 'SYSTEM',
                'name'  => 'last_name',
                'value' => $last_name,
            );
        }
        if ( $email ) {
            $body['properties'][] = array(
                'type'  => 'SYSTEM',
                'name'  => 'email',
                'value' => $email,
            );
        }
        if ( $title ) {
            $body['properties'][] = array(
                'type'  => 'SYSTEM',
                'name'  => 'title',
                'value' => $title,
            );
        }
        if ( $company ) {
            $body['properties'][] = array(
                'type'  => 'SYSTEM',
                'name'  => 'company',
                'value' => $company,
            );
        }
        if ( $phone ) {
            $body['properties'][] = array(
                'type'  => 'SYSTEM',
                'name'  => 'phone',
                'value' => $phone,
            );
        }
        if ( $address || $city || $state || $zip || $country ) {
            $body['properties'][] = array(
                'name'  => 'address',
                'value' => json_encode( array_filter( array(
                    'address'     => $address,
                    'city'        => $city,
                    'state'       => $state,
                    'zip'         => $zip,
                    'countryname' => $country,
                ) ) ),
            );
        }
        $contact_id = adfoin_agilecrm_check_if_contact_exists( $email, $headers, $subdomain );
        if ( $contact_id ) {
            $url = "https://{$subdomain}.agilecrm.com/dev/api/contacts/edit-properties";
            $method = 'PUT';
            $body['id'] = $contact_id;
        } else {
            $url = "https://{$subdomain}.agilecrm.com/dev/api/contacts";
            $method = 'POST';
        }
        $args = array(
            'timeout' => 30,
            'headers' => $headers,
            'method'  => $method,
            'body'    => json_encode( $body ),
        );
        $response = wp_remote_post( $url, $args );
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
        if ( !is_wp_error( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ) );
            if ( !isset( $body->id ) ) {
                return;
            }
        }
        $contact_id = $body->id;
        if ( $contact_id && $deal_name ) {
            $deal_name = ( empty( $data['dealName'] ) ? '' : adfoin_get_parsed_values( $data['dealName'], $posted_data ) );
            $deal_value = ( empty( $data['dealValue'] ) ? '' : adfoin_get_parsed_values( $data['dealValue'], $posted_data ) );
            $deal_probability = ( empty( $data['dealProbability'] ) ? '' : adfoin_get_parsed_values( $data['dealProbability'], $posted_data ) );
            $deal_close_date = ( empty( $data['dealCloseDate'] ) ? '' : strtotime( adfoin_get_parsed_values( $data['dealCloseDate'], $posted_data ) ) );
            $deal_source = ( empty( $data['dealSource'] ) ? '' : adfoin_get_parsed_values( $data['dealSource'], $posted_data ) );
            $deal_description = ( empty( $data['dealDescription'] ) ? '' : adfoin_get_parsed_values( $data['dealDescription'], $posted_data ) );
            $deal_track = ( empty( $data['dealTrack'] ) ? '' : adfoin_get_parsed_values( $data['dealTrack'], $posted_data ) );
            $deal_milestone = ( empty( $data['dealMilestone'] ) ? '' : adfoin_get_parsed_values( $data['dealMilestone'], $posted_data ) );
            $deal_owner = ( empty( $data['dealOwner'] ) ? '' : adfoin_get_parsed_values( $data['dealOwner'], $posted_data ) );
            $deal_url = "https://{$subdomain}.agilecrm.com/dev/api/opportunity";
            $deal_args = array(
                'timeout' => 30,
                'headers' => $headers,
                'body'    => json_encode( array(
                    'name'           => $deal_name,
                    'contact_ids'    => array($contact_id),
                    'expected_value' => $deal_value,
                    'owner_id'       => $deal_owner,
                    'pipeline_id'    => $deal_track,
                    'milestone'      => $deal_milestone,
                    'description'    => $deal_description,
                    'probability'    => intval( $deal_probability ),
                    'close_date'     => $deal_close_date,
                    'deal_source_id' => $deal_source,
                ) ),
            );
            $deal_response = wp_remote_post( $deal_url, $deal_args );
            adfoin_add_to_log(
                $deal_response,
                $deal_url,
                $deal_args,
                $record
            );
        }
        if ( $contact_id && $note_sub ) {
            $note_url = "https://{$subdomain}.agilecrm.com/dev/api/notes/";
            $note_args = array(
                'timeout' => 30,
                'headers' => $headers,
                'body'    => json_encode( array(
                    'subject'     => $note_sub,
                    'description' => $note_desc,
                    'contact_ids' => array($contact_id),
                ) ),
            );
            $note_response = wp_remote_post( $note_url, $note_args );
            adfoin_add_to_log(
                $note_response,
                $note_url,
                $note_args,
                $record
            );
        }
    }
    return;
}
