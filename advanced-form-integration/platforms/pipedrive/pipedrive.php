<?php

add_filter( 'adfoin_action_providers', 'adfoin_pipedrive_actions', 10, 1 );

function adfoin_pipedrive_actions( $actions ) {

    $actions['pipedrive'] = array(
        'title' => __( 'Pipedrive', 'advanced-form-integration' ),
        'tasks' => array(
            'add_ocdna' => __( 'Create New Contact, Organization, Deal, Note, Activity', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_pipedrive_settings_tab', 10, 1 );

function adfoin_pipedrive_settings_tab( $providers ) {
    $providers['pipedrive'] = __( 'Pipedrive', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_pipedrive_settings_view', 10, 1 );

function adfoin_pipedrive_settings_view( $current_tab ) {
    if( $current_tab != 'pipedrive' ) {
        return;
    }

    $title = __( 'Pipedrive', 'advanced-form-integration' );
    $key = 'pipedrive';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key'    => 'accessToken',
                'label'  => __( 'API Token', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        '<ol><li>%s</li></ol>',
        __('Go to Profile > Personal preferences > API to get API Token', 'advanced-form-integration')
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

function adfoin_pipedrive_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'pipedrive' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_pipedrive_action_fields', 10, 1 );

function adfoin_pipedrive_action_fields() {
    ?>
    <script type="text/template" id="pipedrive-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_ocdna'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ocdna'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Pipedrive Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_pipedrive_get_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ocdna' && fielddata.credId">
                <td scope="row-title"></td>
                <td>
                    <button type="button" class="button" @click="refreshFields"><?php esc_attr_e( 'Refresh Fields', 'advanced-form-integration' ); ?></button>
                    <p class="description"><?php esc_attr_e( 'Reload fields from Pipedrive after adding new custom fields.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ocdna'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Allow Duplicate Person', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[duplicate]" value="true" v-model="fielddata.duplicate">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ocdna'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Allow Duplicate Organization', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[duplicateOrg]" value="true" v-model="fielddata.duplicateOrg">
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_ocdna', 'Pipedrive [PRO]', 'lead creation' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_pipedrive_get_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'pipedrive' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

function adfoin_pipedrive_get_credentials( $cred_id ) {
    $credentials     = array();
    $all_credentials = adfoin_read_credentials( 'pipedrive' );

    if( is_array( $all_credentials ) ) {
        $credentials = $all_credentials[0];

        foreach( $all_credentials as $single ) {
            if( $cred_id && $cred_id == $single['id'] ) {
                $credentials = $single;
            }
        }
    }

    return $credentials;
}

// Legacy single-account import: surfaces old `adfoin_pipedrive_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'pipedrive', array(
            'accessToken' => 'adfoin_pipedrive_api_token',
        ), array(
            'id' => '123456',
            'title' => 'Untitled',
        ) );
    }
}, 20 );

add_action( 'wp_ajax_adfoin_get_pipedrive_credentials', 'adfoin_get_pipedrive_credentials', 10, 0 );

function adfoin_get_pipedrive_credentials() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $all_credentials = adfoin_read_credentials( 'pipedrive' );
    $formatted      = array();

    // loop through all and hide part of access token
    foreach( $all_credentials as $single ) {
        $single['accessToken'] = substr( $single['accessToken'], 0, 6 ) . '**********';
        array_push( $formatted, $single );
    }

    wp_send_json_success( $formatted );
}

add_action( 'wp_ajax_adfoin_save_pipedrive_credentials', 'adfoin_save_pipedrive_credentials', 10, 0 );
/*
 * Get pipedrive subscriber lists
 */
function adfoin_save_pipedrive_credentials() {
    // Security Check
    // Authorization check
    adfoin_require_manage_options();

    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if( 'pipedrive' == $platform ) {
        $data = $_POST['data'];

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

/*
 * Delete all cached Pipedrive field/user transients for a credential, so the
 * next fetch re-reads them live from the API. Used by the "Refresh Fields"
 * button and reusable elsewhere.
 */
function adfoin_pipedrive_clear_field_cache( $cred_id ) {
    $hash = md5( $cred_id );

    delete_transient( 'adfoin_pipedrive_users_' . $hash );
    delete_transient( 'adfoin_pipedrive_org_fields_' . $hash );
    delete_transient( 'adfoin_pipedrive_person_fields_' . $hash );
    delete_transient( 'adfoin_pipedrive_deal_fields_' . $hash );
    delete_transient( 'adfoin_pipedrive_domain_' . $hash );
}

add_action( 'wp_ajax_adfoin_get_pipedrive_fields', 'adfoin_get_pipedrive_fields', 10, 0 );

/*
 * Get Pipedrive Owner list
 */
function adfoin_get_pipedrive_fields() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = sanitize_text_field( wp_unslash( $_POST['credId'] ) );

    // Allow the "Refresh Fields" button to bypass the 24h field cache so newly
    // added Pipedrive custom fields show up immediately.
    if( ! empty( $_POST['refresh'] ) ) {
        adfoin_pipedrive_clear_field_cache( $cred_id );
    }

    $fields = array();
    $users = adfoin_get_pipedrive_users( $cred_id );

    array_push( $fields, array( 'key' => 'owner', 'value' => 'Owner', 'description' => implode(', ', $users ) ) );

    // Get Organization Fields
    $org_fields = adfoin_get_pipedrive_org_fields( $cred_id );
    $fields = array_merge( $fields, $org_fields );

    // Get Person Fields
    $person_fields = adfoin_get_pipedrive_person_fields( $cred_id );
    $fields = array_merge( $fields, $person_fields );

    // Get Deal Fields
    $deal_fields = adfoin_get_pipedrive_deal_fields( $cred_id );
    $fields = array_merge( $fields, $deal_fields );
    
    array_push( $fields, array( 'key' => 'note_content', 'value' => 'Content [Note]', 'description' => '' ) );
    array_push( $fields, array( 'key' => 'act_subject', 'value' => 'Subject [Activity]', 'description' => 'Required for creating an activity' ) );
    array_push( $fields, array( 'key' => 'act_type', 'value' => 'Type [Activity]', 'description' => 'Example: call, meeting, task, deadline, email, lunch' ) );
    array_push( $fields, array( 'key' => 'act_due_date', 'value' => 'Due Date [Activity]', 'description' => 'Format: YYYY-MM-DD' ) );
    array_push( $fields, array( 'key' => 'act_after_days', 'value' => 'Due Date After X days [Activity]', 'description' => 'Accepts numeric value. If filled, due date will be calculated and set' ) );
    array_push( $fields, array( 'key' => 'act_due_time', 'value' => 'Due Time [Activity]', 'description' => 'Format: HH:MM' ) );
    array_push( $fields, array( 'key' => 'act_duration', 'value' => 'Duration [Activity]', 'description' => 'Format: HH:MM' ) );
    array_push( $fields, array( 'key' => 'act_note', 'value' => 'Note [Activity]', 'description' => '' ) );
    
    wp_send_json_success( $fields );
}

function adfoin_get_pipedrive_users( $cred_id ) {
    // Check cache first (24 hour expiration)
    $cache_key = 'adfoin_pipedrive_users_' . md5( $cred_id );
    $cached_users = get_transient( $cache_key );
    
    if( false !== $cached_users ) {
        return $cached_users;
    }
    
    $user_data = adfoin_pipedrive_request( 'users?limit=500', 'GET', array(), array(), $cred_id );
    
    if ( is_wp_error( $user_data ) ) {
        return array();
    }

    $user_body = json_decode( wp_remote_retrieve_body( $user_data ), true );

    $users = array();

    foreach( $user_body['data'] as $single ) {
        $users[] = $single['name'] . ': ' . $single['id'];
    }
    
    // Cache for 24 hours
    set_transient( $cache_key, $users, DAY_IN_SECONDS );

    return $users;
}

/*
 * Get Pipedrive Organization Fields
 */
function adfoin_get_pipedrive_org_fields( $cred_id) {
    // Check cache first (24 hour expiration)
    $cache_key = 'adfoin_pipedrive_org_fields_' . md5( $cred_id );
    $cached_fields = get_transient( $cache_key );
    
    if( false !== $cached_fields ) {
        return $cached_fields;
    }

    $org_fields = array(
        array( 'key' => 'org_name', 'value' => 'Name [Organziation]', 'description' => '' ),
        array( 'key' => 'org_address', 'value' => 'Address [Organziation]', 'description' => '' ),
    );

    $data = adfoin_pipedrive_request( 'organizationFields?limit=500', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body = json_decode( $data['body'] );

    foreach( $body->data as $single ) {
        if( strlen( $single->key ) == 40 || $single->key == 'label' ) {

            $description = '';

            if( $single->field_type == 'enum' || $single->field_type == 'set' ) {
                foreach( $single->options as $value ) {
                    $description .= $value->label . ': ' . $value->id . '  ';
                }
            }

            array_push( $org_fields, array( 'key' => 'org_' . $single->key, 'value' => $single->name . ' [Organziation]', 'description' => $description ) );
        }
    }
    
    // Cache for 24 hours
    set_transient( $cache_key, $org_fields, DAY_IN_SECONDS );

    return $org_fields;
}

/*
 * Get Pipedrive Peson Fields
 */
function adfoin_get_pipedrive_person_fields( $cred_id ) {
    // Check cache first (24 hour expiration)
    $cache_key = 'adfoin_pipedrive_person_fields_' . md5( $cred_id );
    $cached_fields = get_transient( $cache_key );
    
    if( false !== $cached_fields ) {
        return $cached_fields;
    }
    
    $person_fields = array();
    $cred_id       = sanitize_text_field( wp_unslash( $_POST['credId'] ) );
    $data          = adfoin_pipedrive_request( 'personFields?limit=500', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_success( $person_fields );
    }

    $body = json_decode( wp_remote_retrieve_body( $data ) );

    foreach( $body->data as $single ) {
        $description = '';

        // Standard editable fields expose bulk_edit_allowed = true. Custom fields use a
        // 40-character hash key and may have bulk_edit_allowed = false depending on their
        // type (address, monetary, time, date, etc.), so detect them by key length too —
        // mirroring how org and deal fields are detected.
        $is_custom_field = ( strlen( $single->key ) == 40 );
        $is_editable     = ( isset( $single->bulk_edit_allowed ) && true == $single->bulk_edit_allowed );

        if( $is_custom_field || $is_editable ) {

            if( 'name' == $single->key ) {
                $description = __( 'Required for creating a person', 'advanced-form-integration' );
            }

            if( 'visible_to' == $single->key ) {
                $description = __( 'Owner & followers (private): 1 Entire company (shared): 3', 'advanced-form-integration' );
            }

            if( 'first_name' == $single->key || 'last_name' == $single->key || 'org_id' == $single->key || 'owner_id' == $single->key ) {
                continue;
            }

            if( $single->field_type == 'enum' || $single->field_type == 'set' ) {
                foreach( $single->options as $value ) {
                    $description .= $value->label . ': ' . $value->id . '  ';
                }
            }

            array_push( $person_fields, array( 'key' => 'per_' . $single->key, 'value' => $single->name . ' [Person]', 'description' => $description ) );
        }
    }
    
    // Cache for 24 hours
    set_transient( $cache_key, $person_fields, DAY_IN_SECONDS );

    return $person_fields;
}

/*
 * Get Pipedrive Deal Fields
 */
function adfoin_get_pipedrive_deal_fields( $cred_id) {
    // Check cache first (24 hour expiration)
    $cache_key = 'adfoin_pipedrive_deal_fields_' . md5( $cred_id );
    $cached_fields = get_transient( $cache_key );
    
    if( false !== $cached_fields ) {
        return $cached_fields;
    }
    
    $stages     = '';
    $cred_id    = sanitize_text_field( wp_unslash( $_POST['credId'] ) );
    $stage_data = adfoin_pipedrive_request( 'stages?limit=500', 'GET', array(), array(), $cred_id );
    $stage_body = json_decode( $stage_data['body'] );

    foreach( $stage_body->data as $single ) {
        $stages .= $single->pipeline_name . '/' . $single->name . ': ' . $single->id . ' ';
    }

    $deal_fields = array(
        array( 'key' => 'deal_title', 'value' => 'Title [Deal]', 'description' => __( 'Required for creating a deal.', 'advanced-form-integration' ) ),
        array( 'key' => 'deal_value', 'value' => 'Value [Deal]', 'description' => 'Numeric value of the deal. If omitted, it will be set to 0.' ),
        array( 'key' => 'deal_currency', 'value' => 'Currency [Deal]', 'description' => 'Accepts a 3-character currency code. If omitted, currency will be set to the default currency of the authorized user.' ),
        array( 'key' => 'deal_probability', 'value' => 'Probability [Deal]', 'description' => '' ),
        array( 'key' => 'deal_stage_id', 'value' => 'Stage ID [Deal]', 'description' => $stages ),
        array( 'key' => 'deal_status', 'value' => 'Status [Deal]', 'description' => 'Example: open, lost, won, deleted' ),
        array( 'key' => 'deal_lost_reason', 'value' => 'Lost Reason [Deal]', 'description' => '' ),
        array( 'key' => 'deal_expected_close_date', 'value' => 'Expected Close Date [Deal]', 'description' => 'YYYY-MM-DD' )
    );

    $data = adfoin_pipedrive_request( 'dealFields?limit=500', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body = json_decode( $data['body'] );

    foreach( $body->data as $single ) {
        if( strlen( $single->key ) == 40 || $single->key == 'label' ) {

            $description = '';

            if( $single->field_type == 'enum' || $single->field_type == 'set' ) {
                foreach( $single->options as $value ) {
                    $description .= $value->label . ': ' . $value->id . '  ';
                }
            }

            array_push( $deal_fields, array( 'key' => 'deal_' . $single->key, 'value' => $single->name . ' [Deal]', 'description' => $description ) );
        }
    }
    
    // Cache for 24 hours
    set_transient( $cache_key, $deal_fields, DAY_IN_SECONDS );

    return $deal_fields;
}

add_action( 'adfoin_pipedrive_job_queue', 'adfoin_pipedrive_job_queue', 10, 1 );

function adfoin_pipedrive_job_queue( $data ) {
    adfoin_pipedrive_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Pipedrive API
 */
function adfoin_pipedrive_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data          = $record_data['field_data'];
    $task          = $record['task'];
    $owner         = isset( $data['owner'] ) ? adfoin_get_parsed_values( $data['owner'], $posted_data ) : '';
    $duplicate     = isset( $data['duplicate'] ) ? $data['duplicate'] : '';
    $duplicate_org = isset( $data['duplicateOrg'] ) ? $data['duplicateOrg'] : '';
    $cred_id       = isset( $data['credId'] ) ? $data['credId'] : '';
    $org_id        = '';
    $person_id     = '';
    $deal_id       = '';

    if( $task == 'add_ocdna' ) {

        $holder      = array();
        $org_data    = array();
        $person_data = array();
        $deal_data   = array();
        $note_data   = array();
        $act_data    = array();

        foreach( $data as $key => $value ) {
            $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
        }

        foreach( $holder as $key => $value ) {
            if( substr( $key, 0, 4 ) == 'org_' && $value ) {
                $key = substr( $key, 4 );

                $org_data[$key] = $value;
            }

            if( substr( $key, 0, 4 ) == 'per_' && $value ) {
                $key = substr( $key, 4 );

                $person_data[$key] = $value;
            }

            if( substr( $key, 0, 5 ) == 'deal_' && $value ) {
                $key = substr( $key, 5 );

                $deal_data[$key] = $value;
            }

            if( substr( $key, 0, 5 ) == 'note_' && $value ) {
                $key = substr( $key, 5 );

                $note_data[$key] = $value;
            }

            if( substr( $key, 0, 4 ) == 'act_' && $value ) {
                $key = substr( $key, 4 );

                $act_data[$key] = $value;
            }
        }

        if( isset( $org_data['name'] ) && $org_data['name'] ) {
            $org_data['owner_id'] = $owner;

            $org_data = array_filter( array_map( 'trim', $org_data ) );
            
            // Only search for existing organization if duplicate checking is DISABLED
            // When duplicates are allowed, skip search and always create new (saves 40 tokens)
            if( 'true' != $duplicate_org ) {
                $org_id = adfoin_pipedrive_organization_exists( $org_data['name'], $cred_id );
            }

            if( $org_id ) {
                // Existing organization found - update it
                $org_data_v2 = adfoin_pipedrive_transform_to_v2( $org_data );
                $org_response = adfoin_pipedrive_request( 'organizations/' . $org_id, 'PUT', $org_data_v2, $record, $cred_id );
            } else {
                // No existing organization found (or duplicates allowed) - create new
                usleep( 250000 ); // 0.25 seconds
                $org_data_v2 = adfoin_pipedrive_transform_to_v2( $org_data );
                $org_response = adfoin_pipedrive_request( 'organizations', 'POST', $org_data_v2, $record, $cred_id );
                $org_body     = json_decode( wp_remote_retrieve_body( $org_response ) );

                if( $org_body->success == true ) {
                    $org_id = $org_body->data->id;
                }
            }
        }

        if( isset( $person_data['name'] ) && $person_data['name'] ) {            
            $person_data['owner_id'] = $owner;

            if( $org_id ) {
                $person_data['org_id'] = $org_id;
            }

            $person_data = array_filter( array_map( 'trim', $person_data ) );

            // Search for an existing person when duplicate checking is DISABLED.
            // Matches on email or phone, so a person mapped with only a phone is
            // still de-duplicated.
            if( 'true' != $duplicate ) {
                $dedupe_email = isset( $person_data['email'] ) ? $person_data['email'] : '';
                $dedupe_phone = isset( $person_data['phone'] ) ? $person_data['phone'] : '';

                if( $dedupe_email || $dedupe_phone ) {
                    $person_id = adfoin_pipedrive_person_exists( $dedupe_email, $cred_id, $dedupe_phone );
                }
            }

            if( $person_id ) {
                // Existing person found - update it
                usleep( 250000 ); // 0.25 seconds
                $person_data_v2 = adfoin_pipedrive_transform_to_v2( $person_data );
                $person_response = adfoin_pipedrive_request( 'persons/' . $person_id, 'PUT', $person_data_v2, $record, $cred_id );
            } else {
                // No existing person found (or duplicates allowed) - create new
                usleep( 250000 ); // 0.25 seconds
                $person_data_v2 = adfoin_pipedrive_transform_to_v2( $person_data );
                $person_response = adfoin_pipedrive_request( 'persons', 'POST', $person_data_v2, $record, $cred_id );
                $person_body     = json_decode( wp_remote_retrieve_body( $person_response ) );

                if( $person_body->success == true ) {
                    $person_id = $person_body->data->id;
                }
            }
        }

        if( isset( $deal_data['title'] ) && $deal_data['title'] ) {
            $deal_data['user_id'] = $owner;

            if( $org_id ) {
                $deal_data['org_id'] = $org_id;
            }

            if( $person_id ) {
                $deal_data['person_id'] = $person_id;
            }

            $deal_data     = array_filter( array_map( 'trim', $deal_data ) );
            $deal_data_v2 = adfoin_pipedrive_transform_to_v2( $deal_data );
            usleep( 250000 ); // 0.25 seconds
            $deal_response = adfoin_pipedrive_request( 'deals', 'POST', $deal_data_v2, $record, $cred_id );
            $deal_body     = json_decode( wp_remote_retrieve_body( $deal_response ) );

            if( $deal_body->success == true ) {
                $deal_id = $deal_body->data->id;
            }
        }

        if( isset( $note_data['content'] ) && $note_data['content'] ) {
            $note_data['user_id'] = $owner;

            if( $org_id ) {
                $note_data['org_id'] = $org_id;
            }

            if( $person_id ) {
                $note_data['person_id'] = $person_id;
            }

            if( $deal_id ) {
                $note_data['deal_id'] = $deal_id;
            }

            $note_data     = array_filter( array_map( 'trim', $note_data ) );
            usleep( 250000 ); // 0.25 seconds
            $note_response = adfoin_pipedrive_request( 'notes', 'POST', $note_data, $record, $cred_id );
            $note_body     = json_decode( wp_remote_retrieve_body( $note_response ) );
        }

        if( isset( $act_data['subject'] ) && $act_data['subject'] ) {
            $act_data['user_id'] = $owner;

            if( $org_id ) {
                $act_data['org_id'] = $org_id;
            }

            if( $person_id ) {
                $act_data['person_id'] = $person_id;
            }

            if( $deal_id ) {
                $act_data['deal_id'] = $deal_id;
            }

            if( isset( $act_data['after_days'] ) && $act_data['after_days'] ) {
                $after_days = (int) $act_data['after_days'];

                if( $after_days ) {
                    $timezone             = wp_timezone();
                    $date                 = date_create( '+' . $after_days . ' days', $timezone );
                    $formatted_date       = date_format( $date, 'Y-m-d' );
                    $act_data['due_date'] = $formatted_date;

                    unset( $act_data['after_days'] );
                }
            }

            $act_data     = array_filter( array_map( 'trim', $act_data ) );
            $act_data_v2 = adfoin_pipedrive_transform_to_v2( $act_data );
            usleep( 250000 ); // 0.25 seconds
            $act_response = adfoin_pipedrive_request( 'activities', 'POST', $act_data_v2, $record, $cred_id );
            // $act_body     = json_decode( wp_remote_retrieve_body( $act_response ) );
        }
    }

    return;
}

function adfoin_pipedrive_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $retry_count = 0 ) {

    $credentials = adfoin_pipedrive_get_credentials( $cred_id );
    $api_token = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        )
    );

    // Writes stay on v1 (still supported and battle-tested); search uses v2 for
    // the cheaper token cost.
    $api_version = apply_filters( 'adfoin_pipedrive_api_version', 'v1' );

    if ( 'GET' == $method && strpos( $endpoint, '/search' ) !== false ) {
        $api_version = 'v2';
    }

    // IMPORTANT: the Pipedrive API v2 is ONLY served from the company-specific
    // domain — https://<company-domain>.pipedrive.com/api/v2/ — NOT from the
    // generic api.pipedrive.com host. Calling the generic host for v2 returns a
    // 404 HTML page, which silently broke every dedup search (so "Allow
    // Duplicate" off still created duplicates). Resolve + cache the domain and
    // fall back to v1 if it can't be determined, so search still works.
    if ( 'v2' == $api_version ) {
        $domain = adfoin_pipedrive_get_company_domain( $cred_id );

        if ( $domain ) {
            $base_url = "https://{$domain}.pipedrive.com/api/v2/";
        } else {
            $api_version = 'v1';
            $base_url    = 'https://api.pipedrive.com/v1/';
        }
    } else {
        $base_url = 'https://api.pipedrive.com/v1/';
    }

    $url = $base_url . $endpoint;
    $url = add_query_arg( 'api_token', $api_token, $url );

    if( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
        // Custom fields are wrapped in a `custom_fields` object for the v2 API, but the
        // v1 write endpoints expect them flat at the root keyed by the 40-char hash.
        // Sending the wrapper to v1 makes Pipedrive silently drop the values (saved as
        // null), so flatten it back out whenever the request is actually hitting v1.
        if( 'v1' == $api_version && is_array( $data ) && isset( $data['custom_fields'] ) ) {
            $data = adfoin_pipedrive_transform_from_v2( $data );
        }

        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );

    // Handle rate limiting with exponential backoff
    if( 429 == $response_code && $retry_count < 3 ) {
        $headers = wp_remote_retrieve_headers( $response );
        $retry_after = isset( $headers['x-ratelimit-reset'] ) ? (float) $headers['x-ratelimit-reset'] : 2;
        
        // Exponential backoff: 2s, 4s, 8s
        $delay = min( $retry_after, pow( 2, $retry_count + 1 ) );

        sleep( $delay );

        return adfoin_pipedrive_request( $endpoint, $method, $data, $record, $cred_id, $retry_count + 1 );
    }

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

/**
 * Resolve the Pipedrive company domain for a credential (e.g. "acme" for
 * acme.pipedrive.com). The v2 API is only served from this domain, so it's
 * required to build v2 URLs. Cached per credential; a negative result is cached
 * briefly so a transient failure doesn't disable v2 for a day.
 *
 * @return string Company domain, or '' if it couldn't be resolved.
 */
function adfoin_pipedrive_get_company_domain( $cred_id ) {
    $cache_key = 'adfoin_pipedrive_domain_' . md5( (string) $cred_id );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    // users/me is a v1 GET, so this does not recurse into the v2 branch.
    $response = adfoin_pipedrive_request( 'users/me', 'GET', array(), array(), $cred_id );
    $domain   = '';

    if ( ! is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['data']['company_domain'] ) && $body['data']['company_domain'] ) {
            $domain = sanitize_text_field( $body['data']['company_domain'] );
        }
    }

    set_transient( $cache_key, $domain, $domain ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );

    return $domain;
}

/**
 * Transform data for Pipedrive API v2
 * Moves custom fields (40-char hash keys) into nested 'custom_fields' object
 * 
 * @param array $data Data to transform
 * @return array Transformed data for v2 API
 */
function adfoin_pipedrive_transform_to_v2( $data ) {
    $custom_fields = array();
    $standard_fields = array();
    
    foreach ( $data as $key => $value ) {
        // Custom fields have 40-character hash keys
        if ( strlen( $key ) == 40 ) {
            $custom_fields[$key] = $value;
        } else {
            $standard_fields[$key] = $value;
        }
    }
    
    // Only add custom_fields object if we have custom fields
    if ( ! empty( $custom_fields ) ) {
        $standard_fields['custom_fields'] = $custom_fields;
    }
    
    return $standard_fields;
}

/**
 * Transform data from Pipedrive API v2 response
 * Flattens custom_fields object for backward compatibility
 * 
 * @param array $data Data from v2 API response
 * @return array Flattened data
 */
function adfoin_pipedrive_transform_from_v2( $data ) {
    if ( isset( $data['custom_fields'] ) && is_array( $data['custom_fields'] ) ) {
        // Flatten custom fields to root level
        foreach ( $data['custom_fields'] as $key => $value ) {
            $data[$key] = $value;
        }
        unset( $data['custom_fields'] );
    }
    
    return $data;
}

function adfoin_pipedrive_person_exists( $email, $cred_id, $phone = '' ) {
    // Match on email first, then fall back to phone. Previously only email was
    // searched, so a person mapped without an email always created a duplicate
    // even with "Allow Duplicate Person" off.
    if ( $email ) {
        $person_id = adfoin_pipedrive_item_exists(
            'persons/search',
            array( 'fields' => 'email', 'exact_match' => 'true', 'term' => $email ),
            $cred_id
        );

        if ( $person_id ) {
            return $person_id;
        }
    }

    if ( $phone ) {
        $person_id = adfoin_pipedrive_item_exists(
            'persons/search',
            array( 'fields' => 'phone', 'exact_match' => 'true', 'term' => $phone ),
            $cred_id
        );

        if ( $person_id ) {
            return $person_id;
        }
    }

    return false;
}

function adfoin_pipedrive_organization_exists( $name, $cred_id ) {
    $endpoint = 'organizations/search';

    $query_args = array(
        'fields'      => 'name',
        'exact_match' => true,
        'term'        => $name
    );

    $org_id = adfoin_pipedrive_item_exists( $endpoint, $query_args, $cred_id );

    return $org_id;
}

function adfoin_pipedrive_item_exists( $endpoint, $query_args, $cred_id ) {
    $endpoint      = add_query_arg( $query_args, $endpoint );
    $response      = adfoin_pipedrive_request( $endpoint, 'GET', array(), array(), $cred_id );
    $response_code = wp_remote_retrieve_response_code( $response );
    $item_id     = '';
    
    if( 200 == $response_code ) {
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if( isset( $response_body['data']['items'] ) && is_array( $response_body['data']['items'] ) ) {
            if( count( $response_body['data']['items'] ) > 0 ) {
                $item_id = $response_body['data']['items'][0]['item']['id'];
            }
        }
    }

    if( $item_id ) {
        return $item_id;
    } else{
        return false;
    }
}