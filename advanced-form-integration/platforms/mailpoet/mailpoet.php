<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailpoet_actions', 10, 1 );

function adfoin_mailpoet_actions( $actions ) {

    $actions['mailpoet'] = array(
        'title' => __( 'MailPoet', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_mailpoet_action_fields' );

function adfoin_mailpoet_action_fields() {
    ?>
    <script type="text/template" id="mailpoet-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'MailPoet List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mailpoet_list', 'adfoin_get_mailpoet_list', 10, 0 );
/*
 * Get Kalviyo subscriber lists
 */
function adfoin_get_mailpoet_list() {
    if (!adfoin_verify_nonce()) return;

    $mailpoet_api = \MailPoet\API\API::MP('v1');
    $raw_lists = $mailpoet_api->getLists();

    $all_list = [];

    foreach ($raw_lists as $list ) {
        $all_list[$list['id']] = $list['name'];
    }

    wp_send_json_success( $all_list );
}

add_action( 'wp_ajax_adfoin_get_mailpoet_subscriber_fields', 'adfoin_get_mailpoet_subscriber_fields', 10 );

/*
 * Get MailPoet subscriber fields
 */
function adfoin_get_mailpoet_subscriber_fields() {
    if ( !adfoin_verify_nonce() ) return;

    $mailpoet_api = \MailPoet\API\API::MP( 'v1' );
    $subscriber_form_fields = $mailpoet_api->getSubscriberFields();

    $all_fields = [];

    foreach ( $subscriber_form_fields as $fields ) {
        array_push( $all_fields, [
            'key'       => $fields['id'],
            'value'     => $fields['name'],
            'description' => ''
        ] );
    }

    wp_send_json_success( $all_fields );
}

add_action( 'adfoin_mailpoet_job_queue', 'adfoin_mailpoet_job_queue', 10, 1 );

function adfoin_mailpoet_job_queue( $data ) {
    adfoin_mailpoet_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to MailPoet API
 */
function adfoin_mailpoet_send_data( $record, $posted_data ) {

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
        $list_id = $data["listId"];
        $subscriber_data = [];

        foreach( $data as $key => $value ) {
            if( $key != "listId" && $value != "" ) {
                $subscriber_data[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        $mailpoet_api = \MailPoet\API\API::MP('v1');

        try {
            $subscriber = $mailpoet_api->getSubscriber( $subscriber_data['email'] );
        } catch (\Exception $e) {
            $subscriber = null;
        }

        if( $subscriber ) {
            $subscriber_id = $subscriber['id'];
            $mailpoet_api->updateSubscriber( $subscriber_id, $subscriber_data );
        } else {
            $subscriber = $mailpoet_api->addSubscriber( $subscriber_data );
        }

        if( $subscriber && $list_id ) {
            $subscriber_id = $subscriber['id'];
            $mailpoet_api->subscribeToLists( $subscriber_id, [ $list_id ] );
        }
    }

    return;
}