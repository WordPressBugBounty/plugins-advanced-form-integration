<?php

add_filter('adfoin_action_providers', 'adfoin_civicrm_actions', 10, 1);

function adfoin_civicrm_actions($actions) {
    $actions['civicrm'] = array(
        'title' => __('CiviCRM', 'advanced-form-integration'),
        'tasks' => array(
            'add_contact' => __('Add Contact', 'advanced-form-integration'),
        )
    );

    return $actions;
}

add_action('adfoin_action_fields', 'adfoin_civicrm_action_fields');

function adfoin_civicrm_action_fields() {
    ?>
    <script type="text/template" id="civicrm-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                </th>
                <td scope="row"></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('CiviCRM Group', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId" required="required">
                        <option value=""><?php _e('Select Group...', 'advanced-form-integration'); ?></option>
                        <option v-for="(item, index) in fielddata.groupList" :value="index">{{item}}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_civicrm_groups', 'adfoin_get_civicrm_groups', 10, 0);

/*
 * Get CiviCRM groups
 */
function adfoin_get_civicrm_groups() {
    if (!adfoin_verify_nonce()) return;

    $civicrm_api = civicrm_api3('Group', 'get', []);
    $groups = [];

    foreach ($civicrm_api['values'] as $group) {
        $groups[$group['id']] = $group['title'];
    }

    wp_send_json_success($groups);
}

add_action('wp_ajax_adfoin_get_civicrm_contact_fields', 'adfoin_get_civicrm_contact_fields', 10);

/*
 * Get CiviCRM contact fields
 */
function adfoin_get_civicrm_contact_fields() {
    if (!adfoin_verify_nonce()) return;

    $contact_fields = civicrm_api3('Contact', 'getfields', []);
    $all_fields = [];

    $contact_types = adfoin_get_civicrm_contact_types();

    foreach ($contact_fields['values'] as $field => $details) {

        if( 'contact_type' == $field ) {
            $all_fields[] = [
                'key' => $field,
                'value' => $details['title'],
                'description' => __('Required field. Possible values are: ', 'advanced-form-integration') . implode(', ', $contact_types),
            ];

            continue;
        }
        $all_fields[] = [
            'key' => $field,
            'value' => $details['title'],
            'description' => $details['description'] ? $details['description'] : '',
        ];
    }

    wp_send_json_success($all_fields);
}

function adfoin_get_civicrm_contact_types() {
    $contact_types = civicrm_api3('Contact', 'getoptions', [
        'field' => 'contact_type',
    ]);

    return array_values( $contact_types['values'] );
}

add_action('adfoin_civicrm_job_queue', 'adfoin_civicrm_job_queue', 10, 1);

function adfoin_civicrm_job_queue($data) {
    adfoin_civicrm_send_data($data['record'], $data['posted_data']);
}

/*
 * Handles sending data to CiviCRM API
 */
function adfoin_civicrm_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (array_key_exists('cl', $record_data['action_data'])) {
        if ($record_data['action_data']['cl']['active'] == 'yes') {
            if (!adfoin_match_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];

    if ($task == 'add_contact') {
        $group_id = $data['groupId'];
        $contact_data = [];

        foreach ($data as $key => $value) {
            if ($key != 'groupId' && $value != '') {
                $contact_data[$key] = adfoin_get_parsed_values($value, $posted_data);
            }
        }

        try {
            $contact = civicrm_api3('Contact', 'create', $contact_data);
        } catch (\Exception $e) {
            return;
        }

        if ($contact && $group_id) {
            civicrm_api3('GroupContact', 'create', [
                'contact_id' => $contact['id'],
                'group_id' => $group_id,
                'status' => 'Added',
            ]);
        }
    }

    return;
}
