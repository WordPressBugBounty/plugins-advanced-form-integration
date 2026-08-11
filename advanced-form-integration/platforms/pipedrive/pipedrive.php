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
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=pipedrive' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
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
    delete_transient( 'adfoin_pipedrive_field_types_organization_' . $hash );
    delete_transient( 'adfoin_pipedrive_field_types_person_' . $hash );
    delete_transient( 'adfoin_pipedrive_field_types_deal_' . $hash );
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

    // API v2 stage objects no longer carry pipeline_name (removed in v2), so
    // fetch pipelines once and map pipeline_id to its name for the description.
    $pipeline_names = array();
    $pipeline_data  = adfoin_pipedrive_request( 'pipelines?limit=500', 'GET', array(), array(), $cred_id );

    if( ! is_wp_error( $pipeline_data ) ) {
        $pipeline_body = json_decode( wp_remote_retrieve_body( $pipeline_data ) );

        if( isset( $pipeline_body->data ) && is_array( $pipeline_body->data ) ) {
            foreach( $pipeline_body->data as $single ) {
                $pipeline_names[ $single->id ] = $single->name;
            }
        }
    }

    $stage_data = adfoin_pipedrive_request( 'stages?limit=500', 'GET', array(), array(), $cred_id );

    if( ! is_wp_error( $stage_data ) ) {
        $stage_body = json_decode( wp_remote_retrieve_body( $stage_data ) );

        if( isset( $stage_body->data ) && is_array( $stage_body->data ) ) {
            foreach( $stage_body->data as $single ) {
                $pipeline_name = '';

                if( isset( $single->pipeline_name ) && $single->pipeline_name ) {
                    // v1 fallback response
                    $pipeline_name = $single->pipeline_name;
                } elseif( isset( $single->pipeline_id, $pipeline_names[ $single->pipeline_id ] ) ) {
                    // v2 response
                    $pipeline_name = $pipeline_names[ $single->pipeline_id ];
                }

                $stages .= ( $pipeline_name ? $pipeline_name . '/' : '' ) . $single->name . ': ' . $single->id . ' ';
            }
        }
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

            // Data stays in flat v1 shape here; adfoin_pipedrive_request() converts
            // it to the v2 schema (and PUT to PATCH) when the call is routed to v2.
            if( $org_id ) {
                // Existing organization found - update it
                $org_response = adfoin_pipedrive_request( 'organizations/' . $org_id, 'PUT', $org_data, $record, $cred_id );
            } else {
                // No existing organization found (or duplicates allowed) - create new
                usleep( 250000 ); // 0.25 seconds
                $org_response = adfoin_pipedrive_request( 'organizations', 'POST', $org_data, $record, $cred_id );
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
                $person_response = adfoin_pipedrive_request( 'persons/' . $person_id, 'PUT', $person_data, $record, $cred_id );
            } else {
                // No existing person found (or duplicates allowed) - create new
                usleep( 250000 ); // 0.25 seconds
                $person_response = adfoin_pipedrive_request( 'persons', 'POST', $person_data, $record, $cred_id );
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
            usleep( 250000 ); // 0.25 seconds
            $deal_response = adfoin_pipedrive_request( 'deals', 'POST', $deal_data, $record, $cred_id );
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
            usleep( 250000 ); // 0.25 seconds
            $act_response = adfoin_pipedrive_request( 'activities', 'POST', $act_data, $record, $cred_id );
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

    // Pipedrive has deprecated API v1 for every resource that has a v2
    // equivalent (activities, deals, persons, organizations, pipelines,
    // stages, search) and is sunsetting those v1 endpoints, so they are
    // routed to v2 by default. Resources WITHOUT a v2 counterpart (users,
    // notes, leads, leadLabels, *Fields) remain on v1, which stays supported.
    // The filter is a kill switch: return 'v1' to force everything back to v1.
    $api_version = apply_filters( 'adfoin_pipedrive_api_version', 'v2' );

    $resource     = adfoin_pipedrive_get_endpoint_resource( $endpoint );
    $v2_resources = array( 'activities', 'deals', 'persons', 'organizations', 'pipelines', 'stages' );

    if ( ! in_array( $resource, $v2_resources, true ) ) {
        $api_version = 'v1';
    }

    // IMPORTANT: the Pipedrive API v2 is ONLY served from the company-specific
    // domain — https://<company-domain>.pipedrive.com/api/v2/ — NOT from the
    // generic api.pipedrive.com host. Calling the generic host for v2 returns a
    // 404 HTML page, which silently broke every dedup search (so "Allow
    // Duplicate" off still created duplicates). Resolve + cache the domain and
    // fall back to v1 if it can't be determined, so the request still works.
    if ( 'v2' == $api_version ) {
        $domain = adfoin_pipedrive_get_company_domain( $cred_id );

        if ( $domain ) {
            $base_url = "https://{$domain}.pipedrive.com/api/v2/";
        } else {
            $api_version = 'v1';
        }
    }

    if ( 'v1' == $api_version ) {
        $base_url = 'https://api.pipedrive.com/v1/';
    }

    // v2 update endpoints only accept PATCH; PUT is v1-only.
    if ( 'v2' == $api_version && 'PUT' == $method ) {
        $method         = 'PATCH';
        $args['method'] = 'PATCH';
    }

    $url = $base_url . $endpoint;
    $url = add_query_arg( 'api_token', $api_token, $url );

    if( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
        if ( 'v2' == $api_version ) {
            // Convert the flat v1-style body to the v2 write schema: custom
            // fields nested under `custom_fields` with strictly typed values,
            // person email/phone as emails/phones arrays, organization address
            // as an object, user_id renamed to owner_id, IDs cast to integers.
            $data = adfoin_pipedrive_transform_to_v2( $data, $resource, $cred_id );
        } elseif ( is_array( $data ) && isset( $data['custom_fields'] ) ) {
            // v1 write endpoints expect custom fields flat at the root keyed by
            // the 40-char hash. Sending the wrapper to v1 makes Pipedrive
            // silently drop the values (saved as null), so flatten it back out
            // whenever the request is actually hitting v1.
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
 * Extract the root resource from an endpoint string, e.g.
 * "organizations/123" -> "organizations", "persons/search?term=x" -> "persons",
 * "stages?limit=500" -> "stages". Used to decide v1 vs v2 routing.
 *
 * @param string $endpoint Relative endpoint passed to adfoin_pipedrive_request().
 * @return string Root resource name.
 */
function adfoin_pipedrive_get_endpoint_resource( $endpoint ) {
    $path  = explode( '?', (string) $endpoint );
    $parts = explode( '/', trim( $path[0], '/' ) );

    return $parts[0];
}

/**
 * Fetch custom field definitions for an entity: field_type plus the enum/set
 * option list (label => id), so v2 payloads can be strictly typed and option
 * labels can be translated to the numeric IDs v2 requires.
 * Uses the v1 *Fields endpoints, which are not deprecated. Cached per credential.
 *
 * @param string $entity  organization|person|deal
 * @param string $cred_id Credential ID.
 * @return array Map of 40-char field key => array( 'type' => field_type, 'options' => array( lowercased label => id ) ).
 */
function adfoin_pipedrive_get_field_types( $entity, $cred_id ) {
    $endpoint_map = array(
        'organization' => 'organizationFields',
        'person'       => 'personFields',
        'deal'         => 'dealFields',
    );

    if ( ! isset( $endpoint_map[ $entity ] ) ) {
        return array();
    }

    $cache_key = 'adfoin_pipedrive_field_types_' . $entity . '_' . md5( (string) $cred_id );
    $cached    = get_transient( $cache_key );

    // Only accept the current cache shape (each entry is an array with 'type').
    if ( false !== $cached && is_array( $cached ) ) {
        $first = reset( $cached );

        if ( empty( $cached ) || ( is_array( $first ) && isset( $first['type'] ) ) ) {
            return $cached;
        }
    }

    $types    = array();
    $response = adfoin_pipedrive_request( $endpoint_map[ $entity ] . '?limit=500', 'GET', array(), array(), $cred_id );

    if ( ! is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            foreach ( $body['data'] as $single ) {
                if ( isset( $single['key'], $single['field_type'] ) && 40 == strlen( $single['key'] ) ) {
                    $options = array();

                    if ( isset( $single['options'] ) && is_array( $single['options'] ) ) {
                        foreach ( $single['options'] as $option ) {
                            if ( isset( $option['label'], $option['id'] ) ) {
                                $options[ strtolower( trim( (string) $option['label'] ) ) ] = $option['id'];
                            }
                        }
                    }

                    $types[ $single['key'] ] = array(
                        'type'    => $single['field_type'],
                        'options' => $options,
                    );
                }
            }
        }
    }

    // Cache a failed lookup only briefly so a transient error doesn't stick.
    set_transient( $cache_key, $types, $types ? DAY_IN_SECONDS : HOUR_IN_SECONDS );

    return $types;
}

/**
 * Resolve an enum/set option value to its numeric option ID. Accepts the ID
 * itself or the option label (case-insensitive), since users map either.
 *
 * @param mixed $value   Raw single option value.
 * @param array $options Map of lowercased label => id.
 * @return int|string Numeric ID, or the original value if it can't be resolved.
 */
function adfoin_pipedrive_resolve_option_id( $value, $options ) {
    $value = trim( (string) $value );

    if ( is_numeric( $value ) ) {
        return (int) $value;
    }

    $lower = strtolower( $value );

    if ( isset( $options[ $lower ] ) ) {
        return (int) $options[ $lower ];
    }

    return $value;
}

/**
 * Normalize a date string to the strict YYYY-MM-DD format API v2 requires.
 * Leaves the value untouched if it already matches or can't be parsed.
 *
 * @param string $value Raw date value.
 * @return string Normalized date.
 */
function adfoin_pipedrive_normalize_date_v2( $value ) {
    $value = trim( (string) $value );

    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
        return $value;
    }

    $timestamp = strtotime( $value );

    if ( false !== $timestamp ) {
        return gmdate( 'Y-m-d', $timestamp );
    }

    return $value;
}

/**
 * Normalize a time string to the HH:MM:SS format API v2 expects.
 *
 * @param string $value Raw time value.
 * @return string Normalized time.
 */
function adfoin_pipedrive_normalize_time_v2( $value ) {
    $value = trim( (string) $value );

    if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $value, $m ) ) {
        return str_pad( $m[1], 2, '0', STR_PAD_LEFT ) . ':' . $m[2] . ':00';
    }

    return $value;
}

/**
 * Normalize a time string to the HH:MM format API v2 requires for the
 * standard Activity `due_time`/`duration` fields. Confirmed live against
 * the v2 API: "14:30:00" is rejected ("This value is not a valid
 * datetime"), "14:30" is accepted — the opposite direction from
 * adfoin_pipedrive_normalize_time_v2(), which pads custom time-type fields
 * out to HH:MM:SS for their nested `{value: ...}` object shape. Strips a
 * trailing :SS if present; leaves an already-HH:MM value unchanged.
 *
 * @param string $value Raw time value.
 * @return string Normalized HH:MM time.
 */
function adfoin_pipedrive_normalize_activity_time_v2( $value ) {
    $value = trim( (string) $value );

    if ( preg_match( '/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m ) ) {
        return str_pad( $m[1], 2, '0', STR_PAD_LEFT ) . ':' . $m[2];
    }

    return $value;
}

/**
 * Cast a single custom field value to the shape API v2 requires. v2 has strict
 * input validation: enum/set option IDs must be integers (set as an array),
 * monetary/address/time/date-range values must be objects, numbers can't be
 * strings. See the v2 migration guide's custom fields section.
 *
 * @param mixed        $value Raw mapped value (string from form parsing).
 * @param array|string $field Field definition array ('type', 'options'), or '' if unknown.
 * @return mixed Typed value for the v2 custom_fields object.
 */
function adfoin_pipedrive_cast_custom_field_v2( $value, $field ) {
    $type    = is_array( $field ) && isset( $field['type'] ) ? $field['type'] : ( is_string( $field ) ? $field : '' );
    $options = is_array( $field ) && isset( $field['options'] ) ? $field['options'] : array();

    switch ( $type ) {
        case 'enum':
            return adfoin_pipedrive_resolve_option_id( $value, $options );

        case 'set':
            $parts = array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' );
            $ids   = array();

            foreach ( $parts as $part ) {
                $id = adfoin_pipedrive_resolve_option_id( $part, $options );

                if ( is_int( $id ) ) {
                    $ids[] = $id;
                }
            }

            return $ids;

        case 'user':
        case 'people':
        case 'org':
        case 'int':
            return is_numeric( $value ) ? (int) $value : $value;

        case 'double':
            return is_numeric( $value ) ? (float) $value : $value;

        case 'monetary':
            // currency subfield is optional; account default currency is used.
            return array( 'value' => is_numeric( $value ) ? (float) $value : $value );

        case 'address':
            // Only `value` is required; other subfields are optional.
            return array( 'value' => (string) $value );

        case 'date':
            return adfoin_pipedrive_normalize_date_v2( $value );

        case 'daterange':
            // v2 requires an object with both `value` and `until`. The mapping UI
            // exposes a single input, so accept "start,end"; with a single date
            // both bounds are set to it (a one-day range).
            $parts = array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
            $start = isset( $parts[0] ) ? adfoin_pipedrive_normalize_date_v2( $parts[0] ) : '';
            $until = count( $parts ) > 1 ? adfoin_pipedrive_normalize_date_v2( end( $parts ) ) : $start;

            return array( 'value' => $start, 'until' => $until );

        case 'time':
            // v2 time fields are objects; timezone_name is optional.
            return array( 'value' => adfoin_pipedrive_normalize_time_v2( $value ) );

        case 'timerange':
            $parts = array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
            $start = isset( $parts[0] ) ? adfoin_pipedrive_normalize_time_v2( $parts[0] ) : '';
            $until = count( $parts ) > 1 ? adfoin_pipedrive_normalize_time_v2( end( $parts ) ) : $start;

            return array( 'value' => $start, 'until' => $until );

        default:
            // text, varchar, varchar_auto, phone etc. pass through unchanged.
            return $value;
    }
}

/**
 * Transform a flat v1-style payload into the Pipedrive API v2 write schema:
 * - custom fields (40-char hash keys) move into a nested `custom_fields`
 *   object with strictly typed values
 * - person: email/phone become emails/phones arrays of objects
 * - organization: address becomes an object with a `value` key
 * - deal/activity: user_id is renamed to owner_id
 * - single `label` enum becomes `label_ids` array of integers
 * - ID and numeric fields are cast to real numbers (v2 rejects numeric strings)
 *
 * @param array  $data     Flat v1-style data.
 * @param string $resource Root resource (organizations|persons|deals|activities).
 * @param string $cred_id  Credential ID, used to look up custom field types.
 * @return array Payload for the v2 API.
 */
function adfoin_pipedrive_transform_to_v2( $data, $resource = '', $cred_id = '' ) {
    if ( ! is_array( $data ) ) {
        return $data;
    }

    $entity_map = array(
        'organizations' => 'organization',
        'persons'       => 'person',
        'deals'         => 'deal',
        'activities'    => 'activity',
    );

    $entity = isset( $entity_map[ $resource ] ) ? $entity_map[ $resource ] : '';

    $field_types = array();

    if ( in_array( $entity, array( 'organization', 'person', 'deal' ), true ) ) {
        $field_types = adfoin_pipedrive_get_field_types( $entity, $cred_id );
    }

    $int_fields = array( 'owner_id', 'user_id', 'org_id', 'person_id', 'deal_id', 'lead_id', 'stage_id', 'pipeline_id', 'visible_to', 'probability', 'creator_user_id' );

    $transformed   = array();
    $custom_fields = array();

    foreach ( $data as $key => $value ) {
        // Custom fields have 40-character hash keys.
        if ( 40 == strlen( $key ) ) {
            $field_def = isset( $field_types[ $key ] ) ? $field_types[ $key ] : '';

            $custom_fields[ $key ] = adfoin_pipedrive_cast_custom_field_v2( $value, $field_def );
            continue;
        }

        // Support payloads that were already wrapped by an earlier version.
        if ( 'custom_fields' == $key && is_array( $value ) ) {
            foreach ( $value as $cf_key => $cf_value ) {
                $field_def = isset( $field_types[ $cf_key ] ) ? $field_types[ $cf_key ] : '';

                $custom_fields[ $cf_key ] = adfoin_pipedrive_cast_custom_field_v2( $cf_value, $field_def );
            }
            continue;
        }

        // v2 renamed the owner field on deals and activities.
        if ( 'user_id' == $key && in_array( $entity, array( 'deal', 'activity' ), true ) ) {
            $key = 'owner_id';
        }

        // v2 persons take arrays of email/phone objects instead of strings.
        if ( 'email' == $key && 'person' == $entity ) {
            $transformed['emails'] = array( array( 'value' => (string) $value, 'primary' => true ) );
            continue;
        }

        if ( 'phone' == $key && 'person' == $entity ) {
            $transformed['phones'] = array( array( 'value' => (string) $value, 'primary' => true ) );
            continue;
        }

        // v2 renamed the person `im` field to an `ims` array as well.
        if ( 'im' == $key && 'person' == $entity ) {
            $transformed['ims'] = array( array( 'value' => (string) $value, 'primary' => true ) );
            continue;
        }

        // v2 organization address is an object; the plain string goes in `value`.
        if ( 'address' == $key && 'organization' == $entity ) {
            $transformed['address'] = array( 'value' => (string) $value );
            continue;
        }

        // v1 single `label` enum became `label_ids` (array of integers) in v2.
        if ( 'label' == $key && in_array( $entity, array( 'organization', 'person', 'deal' ), true ) ) {
            $transformed['label_ids'] = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
            continue;
        }

        // v2 rejects due_time/duration with seconds (confirmed live: "14:30:00"
        // 400s with "This value is not a valid datetime", "14:30" is accepted).
        if ( 'activity' == $entity && in_array( $key, array( 'due_time', 'duration' ), true ) ) {
            $value = adfoin_pipedrive_normalize_activity_time_v2( $value );
        }

        // v2 rejects numeric strings for ID/number fields.
        if ( in_array( $key, $int_fields, true ) && is_numeric( $value ) ) {
            $value = (int) $value;
        }

        if ( 'value' == $key && 'deal' == $entity && is_numeric( $value ) ) {
            $value = (float) $value;
        }

        $transformed[ $key ] = $value;
    }

    if ( ! empty( $custom_fields ) ) {
        $transformed['custom_fields'] = $custom_fields;
    }

    return $transformed;
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