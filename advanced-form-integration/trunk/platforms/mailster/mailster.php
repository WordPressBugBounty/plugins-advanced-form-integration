<?php
add_filter( 'adfoin_action_providers', 'adfoin_mailster_actions', 10, 1 );

function adfoin_mailster_actions( $actions ) {

    $actions['mailster'] = array(
        'title' => __( 'Mailster', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_mailster_action_fields' );

function adfoin_mailster_action_fields() {
    ?>
    <script type="text/template" id="mailster-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Mailster List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.lists" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Status', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[status]" v-model="fielddata.status" required="required">
                        <option value="0"> <?php _e( 'Pending', 'advanced-form-integration' ); ?> </option>
                        <option value="1"> <?php _e( 'Subscribed', 'advanced-form-integration' ); ?> </option>
                        <option value="2"> <?php _e( 'Unsubscribed', 'advanced-form-integration' ); ?> </option>
                        <option value="3"> <?php _e( 'Hardbounced', 'advanced-form-integration' ); ?> </option>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mailster_lists', 'adfoin_get_mailster_list', 10, 0 );

function adfoin_get_mailster_list() {
    if (!adfoin_verify_nonce()) return;

    $lists = [];
    if (class_exists('MailsterLists')) {
        $instance = new MailsterLists();
        $raw_lists = $instance->get();

        if (!empty($raw_lists)) {
            foreach ($raw_lists as $list) {
                $lists[$list->ID] = $list->name;
            }
        }
    }

    wp_send_json_success($lists);
}

add_action( 'wp_ajax_adfoin_get_mailster_fields', 'adfoin_get_mailster_fields', 10 );

function adfoin_get_mailster_fields() {
    if ( !adfoin_verify_nonce() ) return;

    $fields = [
        ['key' => 'email', 'value' => 'Email'],
        ['key' => 'firstname', 'value' => 'First Name'],
        ['key' => 'lastname', 'value' => 'Last Name']
    ];

    

    wp_send_json_success( $fields );
}

add_action( 'adfoin_mailster_job_queue', 'adfoin_mailster_job_queue', 10, 1 );

function adfoin_mailster_job_queue( $data ) {
    adfoin_mailster_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailster_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data["field_data"];
    $task = $record["task"];

    if( $task == "subscribe" ) {
        $list_id = isset($data["listId"]) ? $data["listId"] : '';
        $status = isset($data["status"]) ? $data["status"] : '';

        unset( $data["listId"] );
        unset( $data["status"] );

        $subscriber_data = [];

        foreach( $data as $key => $value ) {
            if( $value != "" ) {
                $subscriber_data[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        if( !empty( $subscriber_data ) ) {
            $subscriber_data["status"] = $status;

            if( class_exists( 'MailsterSubscribers' ) ) {
                $mailster_subscribers = new MailsterSubscribers();
                $subscriber_add = $mailster_subscribers->add( $subscriber_data );

                if( is_wp_error( $subscriber_add ) ) {
                    return;
                }

                if( !empty( $list_id ) ) {
                    $mailster_subscribers->assign_lists( $subscriber_add, explode( ',', $list_id ) );
                }
            }
        }
    }

    return;
}