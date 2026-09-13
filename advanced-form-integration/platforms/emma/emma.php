<?php
add_filter('adfoin_action_providers', 'adfoin_emma_actions', 10, 1);
function adfoin_emma_actions($actions) {
    $actions['emma'] = [
        'title' => __('Emma', 'advanced-form-integration'),
        'tasks' => ['add_contact' => __('Add Contact to Group', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_emma_settings_tab', 10, 1);
function adfoin_emma_settings_tab($providers) {
    $providers['emma'] = __('Emma', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_emma_settings_view', 10, 1);
function adfoin_emma_settings_view($current_tab) {
    if ($current_tab !== 'emma') return;

    $title = __('Emma', 'advanced-form-integration');
    $key = 'emma';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'publicKey', 'label' => __('Public API Key', 'advanced-form-integration')],
            ['key' => 'privateKey', 'label' => __('Private API Key', 'advanced-form-integration')],
            ['key' => 'accountId', 'label' => __('Account ID', 'advanced-form-integration')]
        ]
    ]);
    $instructions = __('Get your Emma API credentials from your account settings.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_emma_credentials', 'adfoin_get_emma_credentials');
function adfoin_get_emma_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('emma'));
}

add_action('wp_ajax_adfoin_save_emma_credentials', 'adfoin_save_emma_credentials');
function adfoin_save_emma_credentials() {
    adfoin_verify_nonce();

    if ($_POST['platform'] === 'emma') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('emma', $data);
    }

    wp_send_json_success();
}

function adfoin_emma_credentials_list() {
    foreach (adfoin_read_credentials('emma') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_emma_groups', 'adfoin_get_emma_groups');
function adfoin_get_emma_groups() {
    adfoin_verify_nonce();

    $cred_id = sanitize_text_field( wp_unslash( $_POST['credId'] ?? '' ) );
    if ( empty( $cred_id ) ) {
        wp_send_json_error();
    }

    $response = adfoin_emma_request('groups', 'GET', [], [], $cred_id);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        wp_send_json_error();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        wp_send_json_error();
    }

    // Emma identifies a group by `member_group_id`, not `group_id`
    // (https://api.myemma.com/api/external/groups.html). Build the map explicitly
    // rather than with wp_list_pluck(): when its index key is missing from the items,
    // wp_list_pluck() silently falls back to appending with positional keys (0, 1, 2...),
    // so the dropdown stored a position instead of a real Emma group id and every
    // submission failed with either "group(s) do not exist" or "[group_ids] is required".
    $groups = [];

    foreach ($body as $group) {
        if (!is_array($group)) {
            continue;
        }

        $group_identifier = $group['member_group_id'] ?? ($group['group_id'] ?? '');

        if ('' === $group_identifier || !isset($group['group_name'])) {
            continue;
        }

        $groups[(string) $group_identifier] = $group['group_name'];
    }

    // Cast to object so this is always a JSON object. A plain PHP array whose keys
    // happened to run 0..n would encode as a JSON array, and the Vue dropdown would
    // again bind positions rather than ids.
    wp_send_json_success((object) $groups);
}

add_action('adfoin_emma_job_queue', 'adfoin_emma_job_queue', 10, 1);
function adfoin_emma_job_queue($data) {
    adfoin_emma_send_data($data['record'], $data['posted_data']);
}

function adfoin_emma_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'] ?? '';
    $group_id = $data['groupId'] ?? '';
    unset($data['credId'], $data['groupId']);

    $contact = [];
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $contact[$key] = $parsed;
        }
    }

    $email = $contact['email'] ?? '';
    unset($contact['email']);

    if (empty($email)) {
        return;
    }

    // The select's placeholder option is an empty value; anything else is a real
    // Emma member_group_id, so don't treat '0' as if it were the placeholder.
    $group_ids = [];
    if ('' !== (string) $group_id) {
        $group_ids[] = (int) $group_id;
    }

    $body = [
        'email'     => $email,
        'group_ids' => $group_ids,
        'fields'    => (object) $contact
    ];

    adfoin_emma_request('members/signup', 'POST', $body, $record, $cred_id);
}

function adfoin_emma_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('emma', $cred_id);
    $account_id = $credentials['accountId'] ?? '';
    $url = "https://api.e2ma.net/{$account_id}/{$endpoint}";

    $args = [
        'timeout' => 30,
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode(($credentials['publicKey'] ?? '') . ':' . ($credentials['privateKey'] ?? '')),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ]
    ];

    if (!empty($data)) {
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request($url, $args);
    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'adfoin_action_fields', 'adfoin_emma_action_fields' );

function adfoin_emma_action_fields() {
?>
<script type="text/template" id="emma-action-template">
    <table class="form-table">
        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td><label><?php _e('Emma Account', 'advanced-form-integration'); ?></label></td>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getGroups">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_emma_credentials_list(); ?>
                </select>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=emma' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td><label><?php _e('Group', 'advanced-form-integration'); ?></label></td>
            <td>
                <select name="fieldData[groupId]" v-model="fielddata.groupId" required>
                    <option value=""><?php _e('Select Group...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.groups" :value="id">{{ name }}</option>
                </select>
                <div class="afi-spinner" v-bind:class="{ 'is-active': groupLoading }"></div>
            </td>
        </tr>

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>
    </table>
</script>
<?php
}
