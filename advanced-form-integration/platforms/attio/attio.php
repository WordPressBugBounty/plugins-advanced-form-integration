<?php

add_filter( 'adfoin_action_providers', 'adfoin_attio_actions', 10 );
function adfoin_attio_actions(  $actions  ) {
    $actions['attio'] = [
        'title' => __( 'Attio CRM', 'advanced-form-integration' ),
        'tasks' => [
            'subscribe' => __( 'Create or Update Record', 'advanced-form-integration' ),
        ],
    ];
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_attio_settings_tab', 10 );
function adfoin_attio_settings_tab(  $providers  ) {
    $providers['attio'] = __( 'Attio CRM', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_attio_settings_view', 10 );
function adfoin_attio_settings_view(  $current_tab  ) {
    if ( $current_tab != 'attio' ) {
        return;
    }
    $title = __( 'Attio CRM', 'advanced-form-integration' );
    $key = 'attio';
    $arguments = json_encode( [
        'platform' => 'attio',
        'fields'   => [[
            'key'    => 'accessToken',
            'label'  => __( 'Access Token', 'advanced-form-integration' ),
            'hidden' => true,
        ]],
    ] );
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Navigate to Workspace settings > Developers.', 'advanced-form-integration' ),
        __( 'Create a new integration and assign it a name.', 'advanced-form-integration' ),
        __( 'Under scopes, select "Read-write" for all or at least the necessary ones.', 'advanced-form-integration' ),
        __( 'Copy the Access Token and add it here', 'advanced-form-integration' )
    );
    echo adfoin_platform_settings_template(
        $title,
        $key,
        $arguments,
        $instructions
    );
}

/*
 * Attio Credentials List
 */
function adfoin_attio_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'attio' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_attio_action_fields' );
/*
 * Attio Action Fields
 */
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

/*
 * Attio API Request
 */
function adfoin_attio_request(
    $endpoint,
    $method = 'GET',
    $data = [],
    $record = [],
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'attio', $cred_id );
    $access_token = ( isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '' );
    $base_url = 'https://api.attio.com/v2/';
    $url = $base_url . $endpoint;
    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ],
    ];
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
/*
 * Get Attio credentials
 */
function adfoin_get_attio_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
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
 * Save Attio credentials
 */
function adfoin_save_attio_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $platform = sanitize_text_field( $_POST['platform'] );
    if ( 'attio' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
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
 * Get Attio object fields
 */
function adfoin_get_attio_object_fields() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $object_id = ( isset( $_POST['objectId'] ) ? sanitize_text_field( $_POST['objectId'] ) : '' );
    $data = adfoin_attio_request(
        "objects/{$object_id}/attributes",
        'GET',
        [],
        [],
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $response_body = json_decode( wp_remote_retrieve_body( $data ), true );
    $skip = ['team', 'categories', 'associated_deals'];
    $fields = [];
    if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
        foreach ( $response_body['data'] as $single ) {
            if ( true == $single['is_writable'] && !in_array( $single['api_slug'], $skip ) ) {
                $prefix = ( true == $single['is_multiselect'] ? 'multi' : 'single' );
                array_push( $fields, [
                    'key'         => $prefix . '__' . $single['type'] . '__' . $single['api_slug'],
                    'value'       => $single['title'],
                    'description' => '',
                ] );
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
/*
 * Get Attio onjects
 */
function adfoin_get_attio_objects() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $response = adfoin_attio_request(
        'objects',
        'GET',
        [],
        [],
        $cred_id
    );
    $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $response_body ) ) {
        wp_send_json_error();
    }
    if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
        $allowed_list = ['companies', 'people', 'deals'];
        $objects = [];
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
/*
 * Attio Job Queue
 */
function adfoin_attio_job_queue(  $data  ) {
    adfoin_attio_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to attio API
 */
function adfoin_attio_send_data(  $record, $posted_data  ) {
    sleep( 3 );
    $record_data = json_decode( $record['data'], true );
    if ( adfoin_check_conditional_logic( $record_data["action_data"]["cl"] ?? [], $posted_data ) ) {
        return;
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
        $request_data = [];
        $skip = ['team', 'categories', 'associated_deals'];
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
        $request_data = [
            'data' => [
                'values' => $request_data,
            ],
        ];
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

/*
 * Search Record
 */
function adfoin_attio_search_record(  $object_id, $term, $cred_id  ) {
    $serarch_term = [
        'limit'  => 1,
        'filter' => [],
    ];
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
        [],
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
