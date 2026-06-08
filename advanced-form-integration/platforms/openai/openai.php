<?php

/**
 * OpenAI — Chat Completion via POST /v1/chat/completions.
 *
 * Use case: forward a form submission to ChatGPT for analysis,
 * categorization, or auto-response generation. The AI's reply is captured
 * by the standard adfoin_add_to_log() path so site owners can read it
 * directly from the wp_adfoin_logs table.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_key>
 *
 * @link https://platform.openai.com/docs/api-reference/chat/create
 */

add_filter( 'adfoin_action_providers', 'adfoin_openai_actions', 10, 1 );

function adfoin_openai_actions( $actions ) {
    $actions['openai'] = array(
        'title' => __( 'OpenAI', 'advanced-form-integration' ),
        'tasks' => array(
            'chat_completion' => __( 'Chat Completion', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_openai_settings_tab', 10, 1 );

function adfoin_openai_settings_tab( $providers ) {
    $providers['openai'] = __( 'OpenAI', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_openai_settings_view', 10, 1 );

function adfoin_openai_settings_view( $current_tab ) {
    if ( 'openai' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => 'sk-...',
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to OpenAI and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://platform.openai.com/api-keys">API keys</a>' ),
        esc_html__( 'Click "Create new secret key" and give it a descriptive name (e.g. WordPress AFI).', 'advanced-form-integration' ),
        esc_html__( 'Copy the key starting with sk- immediately — OpenAI only shows it once.', 'advanced-form-integration' ),
        sprintf( __( 'Set a monthly usage limit at %s so a runaway prompt cannot drain your account.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://platform.openai.com/account/limits">Billing limits</a>' ),
        esc_html__( 'Paste the key below. AFI calls https://api.openai.com/v1/ with this key in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'openai', __( 'OpenAI', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_openai_credentials', 'adfoin_get_openai_credentials', 10, 0 );

function adfoin_get_openai_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'openai' );
}

add_action( 'wp_ajax_adfoin_save_openai_credentials', 'adfoin_save_openai_credentials', 10, 0 );

function adfoin_save_openai_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'openai', array( 'apiKey' ) );
}

function adfoin_openai_credentials_list() {
    foreach ( adfoin_read_credentials( 'openai' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_openai_action_fields' );

function adfoin_openai_action_fields() {
    ?>
    <script type="text/template" id="openai-action-template">
        <table class="form-table" v-if="action.task == 'chat_completion'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'OpenAI Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=openai' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_openai_fields', 'adfoin_get_openai_fields' );

function adfoin_get_openai_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array(
            'key'         => 'model',
            'value'       => __( 'Model', 'advanced-form-integration' ),
            'required'    => true,
            'description' => __( 'Recommended: gpt-4o-mini (default, cheapest). Alternatives: gpt-4o, gpt-4-turbo, gpt-3.5-turbo.', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'system_prompt',
            'value'       => __( 'System Prompt', 'advanced-form-integration' ),
            'type'        => 'textarea',
            'description' => __( 'Optional. Defines the AI\'s role/behavior. Example: "You are a helpful assistant that categorizes form submissions."', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'user_prompt',
            'value'       => __( 'User Prompt', 'advanced-form-integration' ),
            'type'        => 'textarea',
            'required'    => true,
            'description' => __( 'The prompt sent to the model. Use form-field tags like {{name}}, {{email}}, {{message}} to inject submission data.', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'temperature',
            'value'       => __( 'Temperature', 'advanced-form-integration' ),
            'description' => __( 'Controls randomness. 0 = deterministic, 2 = very random. Default: 0.3.', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'max_tokens',
            'value'       => __( 'Max Tokens', 'advanced-form-integration' ),
            'description' => __( 'Maximum length of the response in tokens. Default: 500.', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_openai_job_queue', 'adfoin_openai_job_queue', 10, 1 );

function adfoin_openai_job_queue( $data ) {
    adfoin_openai_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_openai_send_data( $record, $posted_data ) {
    if ( 'chat_completion' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    // Resolve all configured field values (with {{tag}} substitution).
    $values   = array();
    $reserved = array( 'credId' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    $user_prompt = isset( $values['user_prompt'] ) ? (string) $values['user_prompt'] : '';
    if ( '' === trim( $user_prompt ) ) {
        return; // user prompt is required
    }

    $model = isset( $values['model'] ) ? trim( (string) $values['model'] ) : '';
    if ( '' === $model ) {
        $model = 'gpt-4o-mini';
    }

    // Coerce temperature into the OpenAI-permitted 0-2 range.
    $temperature = isset( $values['temperature'] ) && '' !== $values['temperature']
        ? (float) $values['temperature']
        : 0.3;
    if ( $temperature < 0 ) {
        $temperature = 0;
    } elseif ( $temperature > 2 ) {
        $temperature = 2;
    }

    $max_tokens = isset( $values['max_tokens'] ) && '' !== $values['max_tokens']
        ? (int) $values['max_tokens']
        : 500;
    if ( $max_tokens < 1 ) {
        $max_tokens = 500;
    }

    $messages = array();

    $system_prompt = isset( $values['system_prompt'] ) ? trim( (string) $values['system_prompt'] ) : '';
    if ( '' !== $system_prompt ) {
        $messages[] = array(
            'role'    => 'system',
            'content' => $system_prompt,
        );
    }

    $messages[] = array(
        'role'    => 'user',
        'content' => $user_prompt,
    );

    $payload = array(
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => $temperature,
        'max_tokens'  => $max_tokens,
    );

    adfoin_openai_request( 'chat/completions', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_openai_request' ) ) :
function adfoin_openai_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'openai', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'openai_missing_credentials', __( 'OpenAI API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.openai.com/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 60,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiKey'],
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
