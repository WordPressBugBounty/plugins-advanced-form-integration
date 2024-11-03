<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_flodesk_actions',
    10,
    1
);
function adfoin_flodesk_actions(  $actions  ) {
    $actions['flodesk'] = array(
        'title' => __( 'Flodesk', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_flodesk_settings_tab',
    10,
    1
);
function adfoin_flodesk_settings_tab(  $providers  ) {
    $providers['flodesk'] = __( 'Flodesk', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_flodesk_settings_view',
    10,
    1
);
function adfoin_flodesk_settings_view(  $current_tab  ) {
    if ( $current_tab != 'flodesk' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_flodesk_settings' );
    ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					
						<h2 class="hndle"><span><?php 
    esc_attr_e( 'Flodesk Accounts', 'advanced-form-integration' );
    ?></span></h2>
						<div class="inside">
                            <div id="flodesk-auth">


                                <table v-if="tableData.length > 0" class="wp-list-table widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php 
    _e( 'Title', 'advanced-form-integration' );
    ?></th>
                                            <th><?php 
    _e( 'API Key', 'advanced-form-integration' );
    ?></th>
                                            <th><?php 
    _e( 'Actions', 'advanced-form-integration' );
    ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in tableData" :key="index">
                                            <td>{{ row.title }}</td>
                                            <td>{{ formatApiKey(row.apiKey) }}</td>
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
                                                <input type="text" class="regular-text"v-model="rowData.title" placeholder="Add any title here" required />
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"> <?php 
    _e( 'API Key', 'advanced-form-integration' );
    ?></th>
                                            <td>
                                                <input type="text" class="regular-text"v-model="rowData.apiKey" placeholder="API Key" required />
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
                                    <li>Go to Profile > Integrations > API Keys.</li>
                                    <li>Crate API Key and copy.</li>
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

function adfoin_flodesk_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'flodesk' );
    foreach ( $credentials as $option ) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_flodesk_action_fields' );
function adfoin_flodesk_action_fields() {
    ?>
    <script type="text/template" id="flodesk-action-template">
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
    esc_attr_e( 'Flodesk Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getSegments">
                    <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <?php 
    adfoin_flodesk_credentials_list();
    ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Flodesk Segment', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[segmentId]" v-model="fielddata.segmentId">
                        <option value=""> <?php 
    _e( 'Select Segment...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.segments" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': segmentsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Double Opt-In', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doptin]" value="true" v-model="fielddata.doptin">
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
        printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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

function adfoin_flodesk_get_credentials(  $cred_id  ) {
    $credentials = array();
    $all_credentials = adfoin_read_credentials( 'flodesk' );
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
 * flodesk API Request
 */
function adfoin_flodesk_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_flodesk_get_credentials( $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $base_url = 'https://api.flodesk.com/v1/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ),
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
    'wp_ajax_adfoin_get_flodesk_credentials',
    'adfoin_get_flodesk_credentials',
    10,
    0
);
function adfoin_get_flodesk_credentials() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $all_credentials = adfoin_read_credentials( 'flodesk' );
    wp_send_json_success( $all_credentials );
}

add_action(
    'wp_ajax_adfoin_save_flodesk_credentials',
    'adfoin_save_flodesk_credentials',
    10,
    0
);
/*
 * Get Save Flodesk credentials
 */
function adfoin_save_flodesk_credentials() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $platform = sanitize_text_field( $_POST['platform'] );
    if ( 'flodesk' == $platform ) {
        $data = $_POST['data'];
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

add_action(
    'wp_ajax_adfoin_get_flodesk_segments',
    'adfoin_get_flodesk_segments',
    10,
    0
);
/*
 * Get Kalviyo subscriber lists
 */
function adfoin_get_flodesk_segments() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = sanitize_text_field( $_POST['credId'] );
    $data = adfoin_flodesk_request(
        'segments',
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
    'adfoin_flodesk_job_queue',
    'adfoin_flodesk_job_queue',
    10,
    1
);
function adfoin_flodesk_job_queue(  $data  ) {
    adfoin_flodesk_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to flodesk API
 */
function adfoin_flodesk_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $segment_id = ( isset( $data['segmentId'] ) ? $data['segmentId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $doptin = ( isset( $data['doptin'] ) && $data['doptin'] ? true : false );
    $task = $record['task'];
    if ( $task == 'subscribe' ) {
        $subscriber_data = array_filter( array(
            'email'        => ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) ),
            'first_name'   => ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) ),
            'last_name'    => ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) ),
            'segment_ids'  => array($segment_id),
            'double_optin' => $doptin,
        ) );
        $return = adfoin_flodesk_request(
            'subscribers',
            'POST',
            $subscriber_data,
            $record,
            $cred_id
        );
    }
    return;
}
