<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_engagebay_actions',
    10,
    1
);
function adfoin_engagebay_actions(  $actions  ) {
    $actions['engagebay'] = array(
        'title' => __( 'EngageBay', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Create New Contact', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_engagebay_settings_tab',
    10,
    1
);
function adfoin_engagebay_settings_tab(  $providers  ) {
    $providers['engagebay'] = __( 'EngageBay', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_engagebay_settings_view',
    10,
    1
);
function adfoin_engagebay_settings_view(  $current_tab  ) {
    if ( $current_tab != 'engagebay' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf( '<p>%s</p>', __( 'Go to Account Settings > API & Tracking Code and copy REST API Key', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'engagebay',
        __( 'EngageBay', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_engagebay_credentials',
    'adfoin_get_engagebay_credentials',
    10,
    0
);
/*
 * Get EngageBay credentials
 */
function adfoin_get_engagebay_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'engagebay' );
}

add_action(
    'wp_ajax_adfoin_save_engagebay_credentials',
    'adfoin_save_engagebay_credentials',
    10,
    0
);
/*
 * Save EngageBay credentials
 */
function adfoin_save_engagebay_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'engagebay', array('apiKey') );
}

/*
 * EngageBay Credentials List
 */
function adfoin_engagebay_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'engagebay' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_engagebay_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_engagebay_modify_credentials(  $credentials, $platform  ) {
    if ( 'engagebay' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_engagebay_api_key' );
        if ( $api_key ) {
            $credentials = array(array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_key,
            ));
        }
    }
    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action(
    'admin_post_adfoin_engagebay_save_api_key',
    'adfoin_save_engagebay_api_key',
    10,
    0
);
function adfoin_save_engagebay_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_engagebay_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST['adfoin_engagebay_api_key'] );
    // Save keys
    update_option( 'adfoin_engagebay_api_key', $api_key );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=engagebay' );
}

add_action( 'adfoin_action_fields', 'adfoin_engagebay_action_fields' );
function adfoin_engagebay_action_fields() {
    ?>
    <script type="text/template" id="engagebay-action-template">
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
    esc_attr_e( 'EngageBay Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=engagebay' );
    ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'EngageBay List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

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
        printf( __( 'To unlock custom fields, consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
    'wp_ajax_adfoin_get_engagebay_list',
    'adfoin_get_engagebay_list',
    10,
    0
);
/*
 * Get EngageBay subscriber lists
 */
function adfoin_get_engagebay_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $data = adfoin_engagebay_request(
        'panel/contactlist',
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $body = json_decode( wp_remote_retrieve_body( $data ) );
    $lists = wp_list_pluck( $body, 'name', 'id' );
    wp_send_json_success( $lists );
}

add_action(
    'adfoin_engagebay_job_queue',
    'adfoin_engagebay_job_queue',
    10,
    1
);
function adfoin_engagebay_job_queue(  $data  ) {
    adfoin_engagebay_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to EngageBay API
 */
function adfoin_engagebay_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    $list_id = $data['listId'];
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'engagebay' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( $task == 'subscribe' ) {
        $email = ( empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) ) );
        $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
        $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
        $phone = ( empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data ) );
        $role = ( empty( $data['role'] ) ? '' : adfoin_get_parsed_values( $data['role'], $posted_data ) );
        $website = ( empty( $data['website'] ) ? '' : adfoin_get_parsed_values( $data['website'], $posted_data ) );
        $address = ( empty( $data['address'] ) ? '' : adfoin_get_parsed_values( $data['address'], $posted_data ) );
        $city = ( empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data ) );
        $state = ( empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data ) );
        $zip = ( empty( $data['zip'] ) ? '' : adfoin_get_parsed_values( $data['zip'], $posted_data ) );
        $country = ( empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data ) );
        $company = ( empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data ) );
        $company_id = '';
        if ( $company ) {
            $company_data = adfoin_engagebay_maybe_record_exists( $company, 'Company', $cred_id );
            $company_id = $company_data['id'];
            if ( !$company_id ) {
                $company_id = adfoin_engagebay_create_company( $company, $record, $cred_id );
            }
        }
        $is_contact = adfoin_engagebay_maybe_record_exists( $email, 'Subscriber', $cred_id );
        $contact_id = $is_contact['id'];
        $contact_data = array(
            'properties' => array(array(
                'name'  => 'email',
                'value' => $email,
            ), array(
                'name'  => 'name',
                'value' => $first_name,
            )),
        );
        if ( $last_name ) {
            array_push( $contact_data['properties'], array(
                'name'  => 'last_name',
                'value' => $last_name,
            ) );
        }
        if ( $phone ) {
            array_push( $contact_data['properties'], array(
                'name'  => 'phone',
                'value' => $phone,
            ) );
        }
        if ( $role ) {
            array_push( $contact_data['properties'], array(
                'name'  => 'role',
                'value' => $role,
            ) );
        }
        if ( $website ) {
            array_push( $contact_data['properties'], array(
                'name'  => 'website',
                'value' => $website,
            ) );
        }
        if ( $country ) {
            array_push( $contact_data['properties'], array(
                'name'  => 'country',
                'value' => $country,
            ) );
        }
        if ( $address || $city || $zip || $state || $country ) {
            if ( isset( $is_contact['body']['properties'] ) ) {
                $old_address_data = array();
                foreach ( $is_contact['body']['properties'] as $property ) {
                    if ( $property['name'] == 'address' ) {
                        $old_address_data = json_decode( $property['value'], true );
                    }
                }
            }
            $address_data = array();
            if ( $old_address_data ) {
                $address_data = $old_address_data;
            }
            if ( $address ) {
                $address_data['address'] = $address;
            }
            if ( $city ) {
                $address_data['city'] = $city;
            }
            if ( $zip ) {
                $address_data['zip'] = $zip;
            }
            if ( $state ) {
                $address_data['state'] = $state;
            }
            if ( $country ) {
                $address_data['country'] = $country;
            }
            array_push( $contact_data['properties'], array(
                'name'  => 'address',
                'value' => json_encode( $address_data ),
            ) );
        }
        if ( $company_id ) {
            $contact_data['companyIds'] = array($company_id);
        }
        if ( $list_id ) {
            $contact_data['listIds'] = array($list_id);
        }
        if ( $contact_id ) {
            $contact_data['id'] = $contact_id;
            $return = adfoin_engagebay_request(
                'panel/subscribers/update-partial',
                'PUT',
                $contact_data,
                $record,
                $cred_id
            );
        } else {
            $return = adfoin_engagebay_request(
                'panel/subscribers/subscriber',
                'POST',
                $contact_data,
                $record,
                $cred_id
            );
        }
    }
    return;
}

function adfoin_engagebay_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'engagebay', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = ( get_option( 'adfoin_engagebay_api_key' ) ? get_option( 'adfoin_engagebay_api_key' ) : '' );
    }
    if ( !$api_key ) {
        return array();
    }
    $base_url = 'https://app.engagebay.com/dev/api/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => $api_key,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
    $args = apply_filters( 'adfoin_engagebay_before_sent', $args, $url );
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

function adfoin_engagebay_maybe_record_exists(  $keyword, $type, $cred_id = ''  ) {
    $record_id = '';
    $body = '';
    $result = adfoin_engagebay_request(
        'search?type=' . $type . '&q=' . $keyword,
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( !is_wp_error( $result ) ) {
        $body = json_decode( wp_remote_retrieve_body( $result ), true );
        if ( is_array( $body ) && isset( $body[0], $body[0]['id'] ) ) {
            $record_id = $body[0]['id'];
            $body = $body[0];
        }
    }
    return array(
        'id'   => $record_id,
        'body' => $body,
    );
}

function adfoin_engagebay_create_company(  $company, $record, $cred_id = ''  ) {
    $company_id = '';
    $result = adfoin_engagebay_request(
        'panel/companies/company',
        'POST',
        array(
            'properties' => array(array(
                'name'  => 'name',
                'value' => $company,
            )),
        ),
        $record,
        $cred_id
    );
    if ( !is_wp_error( $result ) ) {
        $body = json_decode( wp_remote_retrieve_body( $result ), true );
        if ( isset( $body['id'], $body['name'] ) && $body['name'] == $company ) {
            $company_id = $body['id'];
        }
    }
    return $company_id;
}
