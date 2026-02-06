<?php
add_filter( 'adfoin_action_providers', 'adfoin_mailmint_actions', 10, 1 );

function adfoin_mailmint_actions( $actions ) {

    $actions['mailmint'] = array(
        'title' => __( 'Mail Mint', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_mailmint_action_fields' );

function adfoin_mailmint_action_fields() {
    ?>
    <script type="text/template" id="mailmint-action-template">
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
                        <?php esc_attr_e( 'MailMint List', 'advanced-form-integration' ); ?>
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

add_action( 'wp_ajax_adfoin_get_mailmint_list', 'adfoin_get_mailmint_list', 10, 0 );

function adfoin_get_mailmint_list() {
    if (!adfoin_verify_nonce()) return;

    $lists = [];
    if (class_exists('Mint\MRM\DataBase\Models\ContactGroupModel')) {
        $list_data = Mint\MRM\DataBase\Models\ContactGroupModel::get_all('lists');

        if (!empty($list_data)) {
            foreach ($list_data['data'] as $list) {
                $lists[$list['id']] = $list['title'];
            }
        }
    }

    wp_send_json_success($lists);
}

add_action( 'wp_ajax_adfoin_get_mailmint_fields', 'adfoin_get_mailmint_fields', 10 );

function adfoin_get_mailmint_fields() {
    if ( !adfoin_verify_nonce() ) return;

    $tags = [];

    if ( class_exists( 'Mint\MRM\DataBase\Models\ContactGroupModel' ) ) {
        $tag_data = Mint\MRM\DataBase\Models\ContactGroupModel::get_all( 'tags' );

        if ( !empty( $tag_data ) ) {
            foreach ( $tag_data['data'] as $tag ) {
                $tags[] = $tag['title']. ': ' . $tag['id'];
            }
        }
    }

    $tags_fields = implode( ', ', $tags );

    $fields = [
        ['key' => 'email', 'value' => 'Email'],
        ['key' => 'first_name', 'value' => 'First Name'],
        ['key' => 'last_name', 'value' => 'Last Name'],
        // ['key' => 'date_of_birth', 'value' => 'Date of Birth'],
        // ['key' => 'company', 'value' => 'Company Name'],
        // ['key' => 'address_line_1', 'value' => 'Address Line 1'],
        // ['key' => 'address_line_2', 'value' => 'Address Line 2'],
        // ['key' => 'postal', 'value' => 'Postal Code/ Zip'],
        // ['key' => 'city', 'value' => 'City'],
        // ['key' => 'state', 'value' => 'State'],
        // ['key' => 'country', 'value' => 'Country'],
        // ['key' => 'phone_number', 'value' => 'Phone'],
        // ['key' => 'gender', 'value' => 'Gender'],
        ['key' => 'status', 'value'    => 'Status', 'description' => 'pending, subscribed, unsubscribed, bounced'],
        ['key' => 'tags', 'value'    => 'Tag IDs', 'description' => 'Enter IDs only. ' . $tags_fields],
    ];

    wp_send_json_success( $fields );
}

add_action( 'adfoin_mailmint_job_queue', 'adfoin_mailmint_job_queue', 10, 1 );

function adfoin_mailmint_job_queue( $data ) {
    adfoin_mailmint_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailmint_send_data( $record, $posted_data ) {

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
        $tags = isset($data["tags"]) ? $data["tags"] : '';

        unset( $data["listId"] );
        unset( $data["tags"] );

        $subscriber_data = [];

        foreach( $data as $key => $value ) {
            if( $value != "" ) {
                $subscriber_data[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        if (class_exists('Mint\MRM\DataStores\ContactData') && class_exists('Mint\MRM\DataBase\Models\ContactModel')) {
            $existing_contact_id = Mint\MRM\DataBase\Models\ContactModel::is_contact_exist($subscriber_data['email']);

            if ($existing_contact_id) {
                Mint\MRM\DataBase\Models\ContactModel::update($subscriber_data, $existing_contact_id);
                $contact_id = $existing_contact_id;
            } else {
                $contact_id = Mint\MRM\DataBase\Models\ContactModel::insert(new Mint\MRM\DataStores\ContactData($subscriber_data['email'], $subscriber_data));
            }

            if ($contact_id && $list_id) {
                Mint\MRM\DataBase\Models\ContactGroupModel::set_lists_to_contact([['id' => $list_id]], $contact_id);
            }

            if ($contact_id && $tags) {
                $tag_ids = explode(',', $tags);
                $formatted_tag_ids = [];

                foreach ($tag_ids as $key => $tag_id) {
                    $formatted_tag_ids[] = ['id' => $tag_id];
                }

                Mint\MRM\DataBase\Models\ContactGroupModel::set_tags_to_contact($formatted_tag_ids, $contact_id);
            }
        }
    }

    return;
}