<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_attio_actions',
    10,
    1
);
function adfoin_attio_actions(  $actions  ) {
    $actions['attio'] = array(
        'title' => __( 'Attio CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Create or Update Record', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_attio_settings_tab',
    10,
    1
);
function adfoin_attio_settings_tab(  $providers  ) {
    $providers['attio'] = __( 'Attio CRM', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_attio_settings_view',
    10,
    1
);
function adfoin_attio_settings_view(  $current_tab  ) {
    if ( $current_tab != 'attio' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_attio_settings' );
    ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					
						<h2 class="hndle"><span><?php 
    esc_attr_e( 'Attio Accounts', 'advanced-form-integration' );
    ?></span></h2>
						<div class="inside">
                            <div id="attio-auth">


                                <table v-if="tableData.length > 0" class="wp-list-table widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php 
    _e( 'Title', 'advanced-form-integration' );
    ?></th>
                                            <th><?php 
    _e( 'Access Token', 'advanced-form-integration' );
    ?></th>
                                            <th><?php 
    _e( 'Actions', 'advanced-form-integration' );
    ?></th>
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
                                            <th scope="row"> <?php 
    _e( 'Title', 'advanced-form-integration' );
    ?></th>
                                            <td>
                                                <input type="text" class="regular-text"v-model="rowData.title" placeholder="Enter any title here" required />
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"> <?php 
    _e( 'Acess Token', 'advanced-form-integration' );
    ?></th>
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
						<h2 class="hndle"><span><?php 
    esc_attr_e( 'Instructions', 'advanced-form-integration' );
    ?></span></h2>
						<div class="inside">
                        <div class="card" style="margin-top: 0px;">
                            <p>
                                <ol>
                                    <li>Navigate to Workspace settings > Developers.</li>
                                    <li>Create a new integration and assign it a name.</li>
                                    <li>Under scopes, select "Read-write" for all or at least the necessary ones.</li>
                                    <li>Copy the Access Token and add it here</li>
                                <ol>
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

function adfoin_attio_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'attio' );
    foreach ( $credentials as $option ) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_attio_action_fields' );
function adfoin_attio_action_fields() {
    ?>
    <script type='text/template' id='attio-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'subscribe'">
                    <th scope='row'>
                        <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                    </th>
                    <td scope='row'>
                    <div class='spinner' v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
    esc_attr_e( 'Attio Account', 'advanced-form-integration' );
    ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getObjects">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                            <?php 
    adfoin_attio_get_credentials_list();
    ?>
                        </select>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
    esc_attr_e( 'Object', 'advanced-form-integration' );
    ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[objectId]" v-model="fielddata.objectId" @change=getFields>
                            <option value=''> <?php 
    _e( 'Select Object...', 'advanced-form-integration' );
    ?> </option>
                            <option v-for='(item, index) in fielddata.objects' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': objectLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
    esc_attr_e( 'Update Existing Record', 'advanced-form-integration' );
    ?>
                        </label>
                    </td>
                    <td>
                        <input type="checkbox" name="fieldData[update]" value="true" v-model="fielddata.update">
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
    esc_attr_e( 'Matching Attribute', 'advanced-form-integration' );
    ?>
                        </label>
                    </td>
                    <td>
                        <input class="regular-text" type="text" name="fieldData[match]" v-model="fielddata.match">
                        <p class="description"><?php 
    esc_attr_e( 'Only required for updating existing records. Example: domains, email_addresses, name.', 'advanced-form-integration' );
    ?></p>
                    </td>
                </tr>


                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>

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
        printf( __( 'To unlock all consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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

function adfoin_attio_get_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'attio' );
    foreach ( $credentials as $option ) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }
    echo $html;
}

function adfoin_attio_get_credentials(  $cred_id  ) {
    $credentials = array();
    $all_credentials = adfoin_read_credentials( 'attio' );
    if ( is_array( $all_credentials ) ) {
        $credentials = $all_credentials[0];
        foreach ( $all_credentials as $single ) {
            if ( $cred_id && $cred_id == $single['id'] ) {
                $credentials = $single;
            }
        }
    }
    return $credentials;
}

/*
 * Attio API Private Request
 */
function adfoin_attio_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_attio_get_credentials( $cred_id );
    $access_token = ( isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '' );
    $base_url = 'https://api.attio.com/v2/';
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
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

add_action(
    'wp_ajax_adfoin_get_attio_credentials',
    'adfoin_get_attio_credentials',
    10,
    0
);
function adfoin_get_attio_credentials() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $all_credentials = adfoin_read_credentials( 'attio' );
    wp_send_json_success( $all_credentials );
}

add_action(
    'wp_ajax_adfoin_save_attio_credentials',
    'adfoin_save_attio_credentials',
    10,
    0
);
/*
 * Get Attio subscriber lists
 */
function adfoin_save_attio_credentials() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $platform = sanitize_text_field( $_POST['platform'] );
    if ( 'attio' == $platform ) {
        $data = $_POST['data'];
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

add_action(
    'wp_ajax_adfoin_get_attio_object_fields',
    'adfoin_get_attio_object_fields',
    10,
    0
);
/*
 * Get Attio subscriber lists
 */
function adfoin_get_attio_object_fields() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $object_id = ( isset( $_POST['objectId'] ) ? sanitize_text_field( $_POST['objectId'] ) : '' );
    $data = adfoin_attio_request(
        "objects/{$object_id}/attributes",
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $response_body = json_decode( wp_remote_retrieve_body( $data ), true );
    $skip = array('team', 'categories', 'associated_deals');
    $fields = array();
    if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
        foreach ( $response_body['data'] as $single ) {
            if ( true == $single['is_writable'] && !in_array( $single['api_slug'], $skip ) ) {
                $prefix = ( true == $single['is_multiselect'] ? 'multi' : 'single' );
                array_push( $fields, array(
                    'key'         => $prefix . '__' . $single['type'] . '__' . $single['api_slug'],
                    'value'       => $single['title'],
                    'description' => '',
                ) );
            }
        }
    } else {
        wp_send_json_error();
    }
    wp_send_json_success( $fields );
}

add_action(
    'wp_ajax_adfoin_get_attio_objects',
    'adfoin_get_attio_objects',
    10,
    0
);
function adfoin_get_attio_objects() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $response = adfoin_attio_request(
        'objects',
        'GET',
        array(),
        array(),
        $cred_id
    );
    $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $response_body ) ) {
        wp_send_json_error();
    }
    if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
        $allowed_list = array('companies', 'people', 'deals');
        $objects = array();
        foreach ( $response_body['data'] as $single ) {
            if ( in_array( $single['api_slug'], $allowed_list ) ) {
                $objects[$single['api_slug']] = $single['plural_noun'];
            }
        }
        wp_send_json_success( $objects );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_attio_job_queue',
    'adfoin_attio_job_queue',
    10,
    1
);
function adfoin_attio_job_queue(  $data  ) {
    adfoin_attio_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to attio API
 */
function adfoin_attio_send_data(  $record, $posted_data  ) {
    sleep( 3 );
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $object_id = ( isset( $data['objectId'] ) ? $data['objectId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    $update = ( isset( $data['update'] ) ? $data['update'] : '' );
    $matching_attribute = ( isset( $data['match'] ) ? $data['match'] : '' );
    unset($data['objectId']);
    unset($data['credId']);
    unset($data['update']);
    unset($data['match']);
    if ( $task == 'subscribe' ) {
        $request_data = array();
        $skip = array('team', 'categories', 'associated_deals');
        $matching_attribute = '';
        foreach ( $data as $key => $value ) {
            list( $is_single, $type, $field ) = explode( '__', $key );
            if ( in_array( $field, $skip ) ) {
                continue;
            }
            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
            if ( 'record-reference' == $type && ('company' == $field || 'associated_company' == $field) ) {
                $parsed_value = adfoin_attio_search_record( 'companies', $parsed_value, $cred_id );
            }
            if ( 'record-reference' == $type && ('people' == $field || 'associated_people' == $field) ) {
                $parsed_value = adfoin_attio_search_record( 'people', $parsed_value, $cred_id );
            }
            if ( $parsed_value ) {
                if ( 'single' == $is_single ) {
                    $request_data[$field] = $parsed_value;
                } else {
                    $request_data[$field] = explode( ',', $parsed_value );
                }
            }
        }
        $request_data = array(
            'data' => array(
                'values' => $request_data,
            ),
        );
        if ( $update == 'true' ) {
            if ( !$matching_attribute ) {
                if ( $object_id == 'companies' ) {
                    $matching_attribute = 'domains';
                }
                if ( $object_id == 'people' ) {
                    $matching_attribute = 'email_addresses';
                }
                if ( $object_id == 'deals' ) {
                    $matching_attribute = 'name';
                }
            }
            $return = adfoin_attio_request(
                "objects/{$object_id}/records?matching_attribute={$matching_attribute}",
                'PUT',
                $request_data,
                $record,
                $cred_id
            );
        } else {
            $return = adfoin_attio_request(
                "objects/{$object_id}/records",
                'POST',
                $request_data,
                $record,
                $cred_id
            );
        }
    }
    return;
}

function adfoin_attio_search_record(  $object_id, $term, $cred_id  ) {
    $serarch_term = array(
        'limit'  => 1,
        'filter' => array(),
    );
    if ( 'companies' == $object_id ) {
        $serarch_term['filter']['name'] = $term;
    }
    if ( 'people' == $object_id ) {
        $serarch_term['filter']['email_addresses'] = $term;
    }
    $result = adfoin_attio_request(
        "objects/{$object_id}/records/query",
        'POST',
        $serarch_term,
        array(),
        $cred_id
    );
    $body = json_decode( wp_remote_retrieve_body( $result ), true );
    if ( isset( 
        $body['data'],
        $body['data'][0],
        $body['data'][0]['id'],
        $body['data'][0]['id']['record_id']
     ) ) {
        return $body['data'][0]['id']['record_id'];
    } else {
        return false;
    }
}
