<?php

add_filter( 'adfoin_action_providers', 'adfoin_trello_actions', 10, 1 );

function adfoin_trello_actions( $actions ) {

    $actions['trello'] = array(
        'title' => __( 'Trello', 'advanced-form-integration' ),
        'tasks' => array(
            'add_card'   => __( 'Add New Card', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_trello_settings_tab', 10, 1 );

function adfoin_trello_settings_tab( $providers ) {
    $providers['trello'] = __( 'Trello', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_trello_settings_view', 10, 1 );

function adfoin_trello_settings_view( $current_tab ) {
    if( $current_tab != 'trello' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_trello_api_token' );
    
    $existing_creds = adfoin_read_credentials( 'trello' );

    if ( $old_api_token && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => wp_generate_uuid4(),
            'title' => 'Default Account',
            'api_token' => $old_api_token
        );
        adfoin_save_credentials( 'trello', array( $new_cred ) );
    }

    $api_key = adfoin_trello_get_api_key();
    $auth_url = "https://trello.com/1/authorize?expiration=never&name=Advanced%20Form%20Integration&scope=read%2Cwrite%2Caccount&response_type=token&key={$api_key}";

    $fields = array(
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Token', 'advanced-form-integration' ),
            'description'   => '<a href="' . esc_url( $auth_url ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Click here to get token', 'advanced-form-integration' ) . '</a>',
            'mask'          => true,  // Mask API token in table
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Click the "Click here to get token" link above to authorize the application.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy the token from the authorization page.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your API Token above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'trello', 'Trello', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_trello_credentials', 'adfoin_get_trello_credentials' );
function adfoin_get_trello_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'trello' );
}

add_action( 'wp_ajax_adfoin_save_trello_credentials', 'adfoin_save_trello_credentials' );
function adfoin_save_trello_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'trello', array( 'api_token' => 'password' ) );
}

add_action( 'wp_ajax_adfoin_get_trello_credentials_list', 'adfoin_trello_get_credentials_list_ajax' );
function adfoin_trello_get_credentials_list_ajax() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_token', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'trello', $fields );
}

add_action( 'adfoin_action_fields', 'adfoin_trello_action_fields' );

function adfoin_trello_action_fields() {
    ?>
    <script type="text/template" id="trello-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_card'">
                <th scope="row"><?php esc_html_e( 'Trello Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=trello' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_card'">
                <th scope="row"><?php esc_html_e( 'Board', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[boardId]" v-model="fielddata.boardId" @change="handleBoardChange">
                        <option value=""> <?php _e( 'Select Board...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.boards" :value="index">{{ item }}</option>
                    </select>
                    <span v-if="boardLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_card'">
                <th scope="row"><?php esc_html_e( 'List', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.lists" :value="index">{{ item }}</option>
                    </select>
                    <span v-if="listLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_card'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_card', '', 'custom fields' ); ?>

        </table>
    </script>
    <?php
}

function adfoin_trello_get_api_key() {
    return '13b9118a04aaece3faae1eda7e424edc';
}

add_action( 'wp_ajax_adfoin_get_trello_boards', 'adfoin_get_trello_boards', 10, 0 );
/*
 * Get Trello boards
 */
function adfoin_get_trello_boards() {
    adfoin_verify_nonce();

    $api_key = adfoin_trello_get_api_key();
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $account = adfoin_get_credentials_by_id( 'trello', $cred_id );

    if ( is_wp_error( $account ) || empty( $account ) ) {
        wp_send_json_error();
        return;
    }

    $api_token = isset( $account['api_token'] ) ? $account['api_token'] : '';

    if ( ! $api_key || ! $api_token ) {
        wp_send_json_error();
        return;
    }

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/json'
        )
    );

    $url = "https://api.trello.com/1/members/me/boards?&filter=open&key={$api_key}&token={$api_token}";

    $result = wp_remote_get( $url, $args);

    if( is_wp_error( $result ) || '200' != $result['response']['code'] ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $result ) );
    
    if ( $body && is_array( $body ) ) {
        $boards = wp_list_pluck( $body, 'name', 'id' );
        wp_send_json_success( $boards );
    } else {
        wp_send_json_error();
    }
}

add_action( 'wp_ajax_adfoin_get_trello_lists', 'adfoin_get_trello_lists', 10, 0 );

function adfoin_get_trello_lists() {
    adfoin_verify_nonce();

    $api_key  = adfoin_trello_get_api_key();
    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $board_id = isset( $_POST['boardId'] ) ? sanitize_text_field( wp_unslash( $_POST['boardId'] ) ) : '';
    $account  = adfoin_get_credentials_by_id( 'trello', $cred_id );

    if ( is_wp_error( $account ) || empty( $account ) || ! $board_id ) {
        wp_send_json_error();
        return;
    }

    $api_token = isset( $account['api_token'] ) ? $account['api_token'] : '';

    if ( ! $api_key || ! $api_token ) {
        wp_send_json_error();
        return;
    }

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/json'
        )
    );

    $url = "https://api.trello.com/1/boards/{$board_id}/lists?filter=open&key={$api_key}&token={$api_token}";

    $result = wp_remote_get( $url, $args);

    if( is_wp_error( $result ) || '200' != $result['response']['code'] ) {
        wp_send_json_error();
    }
    
    $body = json_decode( wp_remote_retrieve_body( $result ) );
    
    if ( $body && is_array( $body ) ) {
        $lists = wp_list_pluck( $body, 'name', 'id' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_trello_job_queue', 'adfoin_trello_job_queue', 10, 1 );

function adfoin_trello_job_queue( $data ) {
    adfoin_trello_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Trello API
 */
function adfoin_trello_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = $record['task'];
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    $api_key = adfoin_trello_get_api_key();
    $account = adfoin_get_credentials_by_id( 'trello', $cred_id );

    if ( is_wp_error( $account ) || empty( $account ) ) {
        return;
    }

    $api_token = isset( $account['api_token'] ) ? $account['api_token'] : '';

    if ( ! $api_key || ! $api_token ) {
        return;
    }

    if ( $task === 'add_card' ) {
        $list_id     = isset( $field_data['listId'] ) ? $field_data['listId'] : '';
        $name        = empty( $field_data['name'] ) ? '' : adfoin_get_parsed_values( $field_data['name'], $posted_data );
        $description = empty( $field_data['description'] ) ? '' : adfoin_get_parsed_values( $field_data['description'], $posted_data );
        $pos         = empty( $field_data['pos'] ) ? '' : adfoin_get_parsed_values( $field_data['pos'], $posted_data );

        if ( empty( $name ) || empty( $list_id ) ) {
            return;
        }

        $body = array_filter( array(
            'name' => $name,
            'desc' => $description,
            'pos'  => $pos,
        ) );

        $url  = "https://api.trello.com/1/cards?key={$api_key}&token={$api_token}&idList={$list_id}";
        $args = array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
        );

        $return = wp_remote_post( $url, $args );
        adfoin_add_to_log( $return, $url, $args, $record );
    }
}