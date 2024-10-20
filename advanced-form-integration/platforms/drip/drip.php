<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_drip_actions',
    10,
    1
);
function adfoin_drip_actions(  $actions  ) {
    $actions['drip'] = array(
        'title' => __( 'Drip', 'advanced-form-integration' ),
        'tasks' => array(
            'create_subscriber' => __( 'Create Subscriber', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_drip_settings_tab',
    10,
    1
);
function adfoin_drip_settings_tab(  $providers  ) {
    $providers['drip'] = __( 'Drip', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_drip_settings_view',
    10,
    1
);
function adfoin_drip_settings_view(  $current_tab  ) {
    if ( $current_tab != 'drip' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_drip_settings' );
    $api_token = ( get_option( 'adfoin_drip_api_token' ) ? get_option( 'adfoin_drip_api_token' ) : '' );
    ?>

    <form name="drip_save_form" action="<?php 
    echo esc_url( admin_url( 'admin-post.php' ) );
    ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_drip_api_token">
        <input type="hidden" name="_nonce" value="<?php 
    echo $nonce;
    ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php 
    _e( 'API Token', 'advanced-form-integration' );
    ?></th>
                <td>
                    <input type="text" name="adfoin_drip_api_token"
                           value="<?php 
    echo esc_attr( $api_token );
    ?>" placeholder="<?php 
    _e( 'Please enter API Token', 'advanced-form-integration' );
    ?>"
                           class="regular-text"/>
                    <p>
                        To find your API token login to your Drip account and go to <a target="_blank" rel="noopener noreferrer" href="https://www.getdrip.com/user/edit">https://www.getdrip.com/user/edit</a>. It will be near the bottom under 'API Token'
                    </p>
                </td>
            </tr>
        </table>
        <?php 
    submit_button();
    ?>
    </form>

    <?php 
}

add_action(
    'admin_post_adfoin_save_drip_api_token',
    'adfoin_save_drip_api_token',
    10,
    0
);
function adfoin_save_drip_api_token() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_drip_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_token = sanitize_text_field( $_POST['adfoin_drip_api_token'] );
    // Save tokens
    update_option( 'adfoin_drip_api_token', $api_token );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=drip' );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_drip_js_fields',
    10,
    1
);
function adfoin_drip_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_drip_action_fields' );
function adfoin_drip_action_fields() {
    ?>

    <script type="text/template" id="drip-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_subscriber'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Subscriber Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'create_subscriber'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[accountId]" v-model="fielddata.accountId" required="true" @change="getList">
                        <option value=""><?php 
    _e( 'Select...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.accounts" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': accountLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_subscriber'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Campaign', 'advanced-form-integration' );
    ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[campaignId]" v-model="fielddata.campaignId">
                        <option value=""><?php 
    _e( 'Select...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': campaignLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_subscriber'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Workflow', 'advanced-form-integration' );
    ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[workflowId]" v-model="fielddata.workflowId">
                        <option value=""><?php 
    _e( 'Select...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.workflows" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': workflowLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
        printf( __( 'To unlock custom fields and tags consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
    'wp_ajax_adfoin_get_drip_accounts',
    'adfoin_get_drip_accounts',
    10,
    0
);
/*
 * Get Drip accounts
 */
function adfoin_get_drip_accounts() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $accounts = adfoin_drip_request( 'accounts' );
    if ( !is_wp_error( $accounts ) ) {
        $body = json_decode( wp_remote_retrieve_body( $accounts ) );
        $lists = wp_list_pluck( $body->accounts, 'name', 'id' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'wp_ajax_adfoin_get_drip_list',
    'adfoin_get_drip_list',
    20,
    0
);
/*
 * Get Drip list
 */
function adfoin_get_drip_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $account_id = ( $_POST['accountId'] ? sanitize_text_field( $_POST['accountId'] ) : '' );
    $accounts = adfoin_drip_request( "{$account_id}/campaigns" );
    if ( !is_wp_error( $accounts ) ) {
        $body = json_decode( wp_remote_retrieve_body( $accounts ) );
        $lists = wp_list_pluck( $body->campaigns, 'name', 'id' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'wp_ajax_adfoin_get_drip_workflows',
    'adfoin_get_drip_workflows',
    20,
    0
);
/*
 * Get Drip list
 */
function adfoin_get_drip_workflows() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_token = get_option( 'adfoin_drip_api_token' );
    if ( !$api_token ) {
        wp_send_json_error();
    }
    $account_id = ( $_POST['accountId'] ? sanitize_text_field( $_POST['accountId'] ) : '' );
    $workflows = adfoin_drip_request( "{$account_id}/workflows" );
    if ( !is_wp_error( $workflows ) ) {
        $body = json_decode( wp_remote_retrieve_body( $workflows ) );
        $lists = wp_list_pluck( $body->workflows, 'name', 'id' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_drip_job_queue',
    'adfoin_drip_job_queue',
    10,
    1
);
function adfoin_drip_job_queue(  $data  ) {
    adfoin_drip_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Drip API
 */
function adfoin_drip_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $account = ( empty( $data['accountId'] ) ? '' : $data['accountId'] );
    $campaign = ( empty( $data['campaignId'] ) ? '' : $data['campaignId'] );
    $workflow = ( empty( $data['workflowId'] ) ? '' : $data['workflowId'] );
    $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
    $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
    $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
    $phone = ( empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data ) );
    $address1 = ( empty( $data['address1'] ) ? '' : adfoin_get_parsed_values( $data['address1'], $posted_data ) );
    $address2 = ( empty( $data['address2'] ) ? '' : adfoin_get_parsed_values( $data['address2'], $posted_data ) );
    $city = ( empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data ) );
    $state = ( empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data ) );
    $zip = ( empty( $data['zip'] ) ? '' : adfoin_get_parsed_values( $data['zip'], $posted_data ) );
    $country = ( empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data ) );
    if ( $task == 'create_subscriber' ) {
        $data = array(
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'phone'      => $phone,
            'address1'   => $address1,
            'address2'   => $address2,
            'city'       => $city,
            'state'      => $state,
            'zip'        => $zip,
            'country'    => $country,
        );
        $data = array_filter( $data );
        $body = array(
            'subscribers' => array($data),
        );
        $response = adfoin_drip_request(
            "{$account}/subscribers",
            'POST',
            $body,
            $record
        );
        if ( $campaign ) {
            $camp_body = array(
                'subscribers' => array(array(
                    'email' => $email,
                )),
            );
            $camp_response = adfoin_drip_request(
                "{$account}/campaigns/{$campaign}/subscribers",
                'POST',
                $camp_body,
                $record
            );
        }
        if ( $workflow ) {
            $wfl_body = array(
                'subscribers' => array(array(
                    'email' => $email,
                )),
            );
            $wfl_response = adfoin_drip_request(
                "{$account}/workflows/{$workflow}/subscribers",
                'POST',
                $wfl_body,
                $record
            );
        }
    }
    return;
}

/*
 * Drip API Request
 */
function adfoin_drip_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $api_token = ( get_option( 'adfoin_drip_api_token' ) ? get_option( 'adfoin_drip_api_token' ) : '' );
    $base_url = 'https://api.getdrip.com/v2/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
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
