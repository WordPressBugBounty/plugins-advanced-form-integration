<?php
add_filter( 'adfoin_action_providers', 'adfoin_newsletter_actions', 10, 1 );

function adfoin_newsletter_actions( $actions ) {

    $actions['newsletter'] = array(
        'title' => __( 'Newsletter', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_newsletter_action_fields' );

function adfoin_newsletter_action_fields() {
    ?>
    <script type="text/template" id="newsletter-action-template">
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
                        <?php esc_attr_e( 'Newsletter List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <?php
                        $lists = [];
                        for ($i = 1; $i <= 40; $i++) {
                            $lists[] = "<option value='{$i}'>List {$i}</option>";
                        }
                        echo implode("\n", $lists);
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_newsletter_fields', 'adfoin_get_newsletter_fields', 10 );

function adfoin_get_newsletter_fields() {
    if ( !adfoin_verify_nonce() ) return;

    $fields = [
        ['key' => 'email', 'value' => 'Email'],
        ['key' => 'name', 'value' => 'First Name'],
        ['key' => 'surname', 'value' => 'Last Name'],
        ['key' => 'status', 'value' => 'Status'],
        ['key' => 'gender', 'value' => 'Gender'],
        ['key' => 'country', 'value' => 'Country Code'],
        ['key' => 'region', 'value' => 'Region'],
        ['key' => 'city', 'value' => 'City'],
    ];

    for ($i = 1; $i <= 20; $i++) {
        $fields[] = ['key' => "profile_$i", 'value' => "Custom Field $i"];
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_newsletter_job_queue', 'adfoin_newsletter_job_queue', 10, 1 );

function adfoin_newsletter_job_queue( $data ) {
    adfoin_newsletter_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_newsletter_send_data( $record, $posted_data ) {

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
        unset( $data["listId"] );

        $subscriber_data = [];
        foreach( $data as $key => $value ) {
            if( $value != "" ) {
            $subscriber_data[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        if( !empty( $subscriber_data ) ) {
            $subscriber_data["lists"] = explode(',', $list_id);

            $response = TNP::add_subscriber($subscriber_data);

            if( is_wp_error( $response ) ) {
                return;
            }
        }
    }

    return;
}