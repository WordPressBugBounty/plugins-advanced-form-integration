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
    ?>

    <div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					
						<h2 class="hndle"><span><?php esc_attr_e( 'Pipedrive Accounts', 'advanced-form-integration' ); ?></span></h2>
						<div class="inside">
                            <div id="pipedrive-auth">


                                <table v-if="tableData.length > 0" class="wp-list-table widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Title', 'advanced-form-integration'); ?></th>
                                            <th><?php _e('API Token', 'advanced-form-integration'); ?></th>
                                            <th><?php _e('Actions', 'advanced-form-integration'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in tableData" :key="index">
                                            <td>{{ row.title }}</td>
                                            <td>{{ formatApiKey(row.accessToken) }}</td>
                                            <td>
                                                <button @click="editRow(index)"><span class="dashicons dashicons-edit"></span></button>
                                                <button @click="confirmDelete(index)"><span class="dashicons dashicons-trash"></span></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br>
                                <form @submit.prevent="addOrUpdateRow">
                                    <table class="form-table">
                                        <tr valign="top">
                                            <th scope="row"> <?php _e( 'Title', 'advanced-form-integration' ); ?></th>
                                            <td>
                                                <input type="text" class="regular-text"v-model="rowData.title" placeholder="Enter any title here" required />
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"> <?php _e( 'API Token', 'advanced-form-integration' ); ?></th>
                                            <td>
                                                <input type="text" class="regular-text"v-model="rowData.accessToken" placeholder="Access Token" required />
                                            </td>
                                        </tr>
                                    </table>
                                    <button class="button button-primary" type="submit">{{ isEditing ? 'Update' : 'Add' }}</button>
                                </form>


                            </div>
						</div>
						<!-- .inside -->
					
				</div>
				<!-- .meta-box-sortables .ui-sortable -->
			</div>
			<!-- post-body-content -->

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
						<h2 class="hndle"><span><?php esc_attr_e('Instructions', 'advanced-form-integration'); ?></span></h2>
						<div class="inside">
                        <div class="card" style="margin-top: 0px;">
                            <p>
                                Go to Profile > Personal preferences > API to get API Token
                            </p>
                        </div>
                        
						</div>
						<!-- .inside -->
				</div>
				<!-- .meta-box-sortables -->
			</div>
			<!-- #postbox-container-1 .postbox-container -->
		</div>
		<!-- #post-body .metabox-holder .columns-2 -->
		<br class="clear">
	</div>
    <?php
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
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

add_filter( 'adfoin_get_credentials', 'adfoin_pipedrive_modify_credentials', 10, 2 );

function adfoin_pipedrive_modify_credentials( $credentials, $platform ) {

    if ( 'pipedrive' == $platform && empty( $credentials ) ) {
        $private_key = get_option( 'adfoin_pipedrive_api_token' ) ? get_option( 'adfoin_pipedrive_api_token' ) : '';

        if( $private_key ) {
            $credentials[] = array(
                'id'         => '123456',
                'title'      => __( 'Untitled', 'advanced-form-integration' ),
                'accessToken' => $private_key
            );
        }
    }

    return $credentials;
}

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
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'pipedrive' == $platform ) {
        $data = $_POST['data'];

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
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

    $cred_id = sanitize_text_field( $_POST['credId'] );
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
    $user_data = adfoin_pipedrive_request( 'users?limit=500', 'GET', array(), array(), $cred_id );
    
    if ( is_wp_error( $user_data ) ) {
        return array();
    }

    $user_body = json_decode( wp_remote_retrieve_body( $user_data ), true );

    $users = array();

    foreach( $user_body['data'] as $single ) {
        $users[] = $single['name'] . ': ' . $single['id'];
    }

    return $users;
}

/*
 * Get Pipedrive Organization Fields
 */
function adfoin_get_pipedrive_org_fields( $cred_id) {

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

    return $org_fields;
}

/*
 * Get Pipedrive Peson Fields
 */
function adfoin_get_pipedrive_person_fields( $cred_id ) {
    $person_fields = array();
    $cred_id       = sanitize_text_field( $_POST['credId'] );
    $data          = adfoin_pipedrive_request( 'personFields?limit=500', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_success( $person_fields );
    }

    $body = json_decode( wp_remote_retrieve_body( $data ) );

    foreach( $body->data as $single ) {
        $description = '';

        if( isset( $single->bulk_edit_allowed ) && true == $single->bulk_edit_allowed ) {

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

    return $person_fields;
}

/*
 * Get Pipedrive Deal Fields
 */
function adfoin_get_pipedrive_deal_fields( $cred_id) {
    
    $stages     = '';
    $cred_id    = sanitize_text_field( $_POST['credId'] );
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

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
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
            $holder[$key] = adfoin_get_parsed_values( $data[$key], $posted_data );
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
            $org_id   = adfoin_pipedrive_organization_exists( $org_data['name'], $cred_id );

            if( $org_id && 'true' != $duplicate_org ) {
                $org_response = adfoin_pipedrive_request( 'organizations/' . $org_id, 'PUT', $org_data, $record, $cred_id );
            } else{
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

            if( isset( $person_data['email'] ) ) {
                $person_id = adfoin_pipedrive_person_exists( $person_data['email'], $cred_id );
            }

            if( $person_id && 'true' != $duplicate ) {
                $person_response = adfoin_pipedrive_request( 'persons/' . $person_id, 'PUT', $person_data, $record, $cred_id );
            } else{
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
            $act_response = adfoin_pipedrive_request( 'activities', 'POST', $act_data, $record, $cred_id );
            // $act_body     = json_decode( wp_remote_retrieve_body( $act_response ) );
        }
    }

    return;
}

function adfoin_pipedrive_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

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

    $base_url = 'https://api.pipedrive.com/v1/';
    $url      = $base_url . $endpoint;
    $url      = add_query_arg( 'api_token', $api_token, $url );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

function adfoin_pipedrive_person_exists( $email, $cred_id ) {
    $endpoint = 'persons/search';

    $query_args = array(
        'fields'      => 'email',
        'exact_match' => true,
        'term'        => $email
    );

    $person_id = adfoin_pipedrive_item_exists( $endpoint, $query_args, $cred_id );

    return $person_id;
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