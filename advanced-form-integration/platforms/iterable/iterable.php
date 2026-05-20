<?php

add_filter( 'adfoin_action_providers', 'adfoin_iterable_actions', 10, 1 );

function adfoin_iterable_actions( $actions ) {
    $actions['iterable'] = array(
        'title' => __( 'Iterable', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_iterable_settings_tab', 10, 1 );

function adfoin_iterable_settings_tab( $providers ) {
    $providers['iterable'] = __( 'Iterable', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_iterable_settings_view', 10, 1 );

function adfoin_iterable_settings_view( $current_tab ) {
    if ( 'iterable' !== $current_tab ) {
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
            'placeholder'   => __( 'Server-side Iterable API Key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'dataCenter',
            'label'         => __( 'Data Center', 'advanced-form-integration' ),
            'type'          => 'select',
            'required'      => false,
            'options'       => array(
                'us' => __( 'US (api.iterable.com) — default', 'advanced-form-integration' ),
                'eu' => __( 'EU (api.eu.iterable.com)', 'advanced-form-integration' ),
            ),
            'placeholder'   => 'us',
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                </ol>
            </li>
            <li><strong>%5$s</strong>
                <ol>
                    <li>%6$s</li>
                    <li>%7$s</li>
                </ol>
            </li>
        </ol>
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Generate a server-side API key', 'advanced-form-integration' ),
        esc_html__( 'Sign in to Iterable and navigate to Integrations → API Keys.', 'advanced-form-integration' ),
        esc_html__( 'Click "New API Key", choose the Server-side type, and enable User, Lists, and Event permissions.', 'advanced-form-integration' ),
        esc_html__( 'Give the key a recognizable name (e.g., "AFI Basic") and copy the value.', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the API key into the field above, pick the matching data center (US or EU), and save the settings.', 'advanced-form-integration' ),
        esc_html__( 'Repeat to add keys for other Iterable projects or sandboxes.', 'advanced-form-integration' ),
        esc_html__( 'AFI uses the data center to route requests to api.iterable.com (US) or api.eu.iterable.com (EU).', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Iterable [PRO] to track custom events, trigger journeys, subscribe lists with custom fields, and push full profile attributes.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view(
        'iterable',
        __( 'Iterable', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action( 'wp_ajax_adfoin_get_iterable_credentials', 'adfoin_get_iterable_credentials', 10, 0 );

function adfoin_get_iterable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'iterable' );
}

add_action( 'wp_ajax_adfoin_save_iterable_credentials', 'adfoin_save_iterable_credentials', 10, 0 );

function adfoin_save_iterable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    ADFOIN_Account_Manager::ajax_save_credentials( 'iterable', array(
        'apiKey'     => 'password',
        'dataCenter' => 'text',
    ) );
}

/**
 * Resolve the Iterable base URL for a given credential, honoring the
 * dataCenter field (us|eu). Defaults to US.
 */
if ( ! function_exists( 'adfoin_iterable_base_url' ) ) :
function adfoin_iterable_base_url( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'iterable', $cred_id );
    $region      = isset( $credentials['dataCenter'] ) ? strtolower( (string) $credentials['dataCenter'] ) : 'us';

    return 'eu' === $region
        ? 'https://api.eu.iterable.com/api/'
        : 'https://api.iterable.com/api/';
}
endif;

/**
 * Connection-status helpers. Stored in a separate option keyed by cred_id
 * so we never have to mutate the credential record itself.
 *
 * Shape: array(
 *     $cred_id => array( 'failed' => bool, 'reason' => string, 'updated_at' => int )
 * )
 */
if ( ! function_exists( 'adfoin_iterable_connection_status' ) ) :
function adfoin_iterable_connection_status() {
    $status = get_option( 'adfoin_iterable_connection_status', array() );
    return is_array( $status ) ? $status : array();
}
endif;

if ( ! function_exists( 'adfoin_iterable_mark_connection_failed' ) ) :
function adfoin_iterable_mark_connection_failed( $cred_id, $reason = '' ) {
    if ( ! $cred_id ) {
        return;
    }
    $status             = adfoin_iterable_connection_status();
    $status[ $cred_id ] = array(
        'failed'     => true,
        'reason'     => (string) $reason,
        'updated_at' => time(),
    );
    update_option( 'adfoin_iterable_connection_status', $status, false );
}
endif;

if ( ! function_exists( 'adfoin_iterable_mark_connection_ok' ) ) :
function adfoin_iterable_mark_connection_ok( $cred_id ) {
    if ( ! $cred_id ) {
        return;
    }
    $status = adfoin_iterable_connection_status();
    if ( isset( $status[ $cred_id ] ) ) {
        unset( $status[ $cred_id ] );
        update_option( 'adfoin_iterable_connection_status', $status, false );
    }
}
endif;

if ( ! function_exists( 'adfoin_iterable_request' ) ) :
function adfoin_iterable_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'iterable', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) {
        return new WP_Error(
            'adfoin_iterable_missing_credentials',
            __( 'Iterable API key is missing.', 'advanced-form-integration' )
        );
    }

    $url = adfoin_iterable_base_url( $cred_id ) . ltrim( $endpoint, '/' );

    $version = defined( 'ADVANCED_FORM_INTEGRATION_VERSION' ) ? ADVANCED_FORM_INTEGRATION_VERSION : 'dev';

    $args = array(
        'method'      => $method,
        'timeout'     => 30,
        'sslverify'   => true,
        'redirection' => 0,
        'user-agent'  => 'AdvancedFormIntegration/' . $version . '; +' . home_url(),
        'headers'     => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Api-Key'      => $api_key,
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( ! is_wp_error( $response ) ) {
        $code = (int) wp_remote_retrieve_response_code( $response );

        if ( 401 === $code ) {
            adfoin_iterable_mark_connection_failed( $cred_id, 'invalid_api_key' );
        } elseif ( 429 === $code && $record && function_exists( 'as_schedule_single_action' ) ) {
            // Soft retry on rate-limit: schedule the original send_data 60s later.
            // We only schedule when we have a real $record so the retry has the
            // integration data to replay against.
            as_schedule_single_action(
                time() + 60,
                'adfoin_iterable_job_queue',
                array(
                    array(
                        'record'      => $record,
                        'posted_data' => array(),
                        'retry'       => true,
                    ),
                ),
                'adfoin'
            );
        } elseif ( $code >= 200 && $code < 300 ) {
            adfoin_iterable_mark_connection_ok( $cred_id );
        }
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_iterable_extract_error' ) ) :
function adfoin_iterable_extract_error( $response, $fallback = '' ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) ) {
        if ( ! empty( $body['msg'] ) ) {
            return (string) $body['msg'];
        }
        if ( ! empty( $body['message'] ) ) {
            return (string) $body['message'];
        }
    }

    if ( $fallback ) {
        return $fallback;
    }

    /* translators: %d: HTTP status code */
    return sprintf( __( 'Iterable returned HTTP %d.', 'advanced-form-integration' ), $code );
}
endif;

/**
 * Round-trip a /lists call to validate an API key + data-center pairing.
 * Surfaced as an AJAX endpoint so the settings UI can offer a "Test Connection"
 * button. Also wires connection-broken state automatically via the request fn.
 */
add_action( 'wp_ajax_adfoin_test_iterable_connection', 'adfoin_test_iterable_connection' );

function adfoin_test_iterable_connection() {
    adfoin_require_manage_options();

    if ( ! adfoin_verify_nonce() ) {
        wp_send_json_error( array(
            'message' => __( 'Security check failed', 'advanced-form-integration' ),
        ) );
    }

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_iterable_request( 'lists', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $status ) {
        wp_send_json_error( array(
            'message' => adfoin_iterable_extract_error( $response ),
            'status'  => $status,
        ) );
    }

    wp_send_json_success( array(
        'message' => __( 'Connected to Iterable.', 'advanced-form-integration' ),
    ) );
}

add_action( 'wp_ajax_adfoin_get_iterable_lists', 'adfoin_get_iterable_lists' );

function adfoin_get_iterable_lists() {
    adfoin_require_manage_options();

    if ( ! adfoin_verify_nonce() ) {
        wp_send_json_error( array(
            'message' => __( 'Security check failed', 'advanced-form-integration' ),
        ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $force   = ! empty( $_POST['force'] );

    if ( ! $cred_id ) {
        wp_send_json_success( array() );
    }

    $cache_key = 'adfoin_iterable_lists_' . md5( $cred_id );

    if ( ! $force ) {
        $cached = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            wp_send_json_success( $cached );
        }
    }

    $response = adfoin_iterable_request( 'lists', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $status ) {
        wp_send_json_error( array(
            'message' => adfoin_iterable_extract_error(
                $response,
                __( 'Iterable rejected the API key.', 'advanced-form-integration' )
            ),
            'status'  => $status,
        ) );
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $lists = array();

    if ( ! empty( $body['lists'] ) && is_array( $body['lists'] ) ) {
        foreach ( $body['lists'] as $list ) {
            if ( isset( $list['id'], $list['name'] ) ) {
                $lists[ $list['id'] ] = $list['name'];
            }
        }
    }

    set_transient( $cache_key, $lists, 5 * MINUTE_IN_SECONDS );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_iterable_job_queue', 'adfoin_iterable_job_queue', 10, 1 );

function adfoin_iterable_job_queue( $data ) {
    adfoin_iterable_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_iterable_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = isset( $data['listId'] ) ? (int) $data['listId'] : 0;

    $email   = isset( $data['email'] ) ? trim( (string) adfoin_get_parsed_values( $data['email'], $posted_data ) ) : '';
    $user_id = isset( $data['userId'] ) ? trim( (string) adfoin_get_parsed_values( $data['userId'], $posted_data ) ) : '';

    if ( ! $cred_id || ! $list_id ) {
        return;
    }

    // Iterable identifies a profile by email OR userId (project-dependent).
    // We accept either; if both are present we send both fields — Iterable's
    // SubscribeRequest / OptionalApiUser tolerates this on lists/subscribe.
    if ( ! $email && ! $user_id ) {
        return;
    }

    if ( $email && ! is_email( $email ) ) {
        return;
    }

    // Keys that must not be aliased into dataFields: form-control inputs plus
    // anything Iterable consumes at the subscriber or request body level.
    $reserved = array(
        'credId',
        'listId',
        'lists',
        'email',
        'userId',
        'dataFields',
        'preferUserId',
        'mergeNestedObjects',
        'updateExistingUsersOnly',
        'tags',
    );

    $data_fields = array();

    foreach ( $data as $key => $value ) {
        if ( in_array( $key, $reserved, true ) ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' !== $parsed && null !== $parsed ) {
            $data_fields[ $key ] = $parsed;
        }
    }

    // Build subscriber payload (OptionalApiUser shape).
    $subscriber = array();

    if ( $email ) {
        $subscriber['email'] = $email;
    }
    if ( $user_id ) {
        $subscriber['userId'] = $user_id;
    }
    if ( ! empty( $data_fields ) ) {
        $subscriber['dataFields'] = $data_fields;
    }
    if ( adfoin_iterable_is_truthy( $data, 'preferUserId', $posted_data ) ) {
        $subscriber['preferUserId'] = true;
    }
    if ( adfoin_iterable_is_truthy( $data, 'mergeNestedObjects', $posted_data ) ) {
        $subscriber['mergeNestedObjects'] = true;
    }

    /**
     * Filter the subscriber payload before sending to Iterable.
     *
     * @param array $subscriber  Subscriber fields (email/userId/dataFields/...).
     * @param array $data        Raw field_data map from the integration record.
     * @param array $posted_data Form submission values.
     * @param array $record      Full integration record (including id).
     */
    $subscriber = apply_filters( 'adfoin_iterable_subscriber_payload', $subscriber, $data, $posted_data, $record );

    // Build top-level SubscribeRequest body.
    $body = array(
        'listId'      => $list_id,
        'subscribers' => array( $subscriber ),
    );

    if ( adfoin_iterable_is_truthy( $data, 'updateExistingUsersOnly', $posted_data ) ) {
        $body['updateExistingUsersOnly'] = true;
    }

    /**
     * Filter the full /lists/subscribe request body.
     *
     * @param array $body        SubscribeRequest body.
     * @param array $data        Raw field_data map.
     * @param array $posted_data Form submission values.
     * @param array $record      Integration record.
     */
    $body = apply_filters( 'adfoin_iterable_subscribe_body', $body, $data, $posted_data, $record );

    adfoin_iterable_request( 'lists/subscribe', 'POST', $body, $record, $cred_id );
}

/**
 * Helper: interpret a mapped field value as a boolean flag. Accepts any of
 * "1", "true", "yes", "on" (case-insensitive) after special-tag parsing.
 */
if ( ! function_exists( 'adfoin_iterable_is_truthy' ) ) :
function adfoin_iterable_is_truthy( $data, $key, $posted_data ) {
    if ( ! isset( $data[ $key ] ) ) {
        return false;
    }
    $value = adfoin_get_parsed_values( $data[ $key ], $posted_data );
    if ( is_bool( $value ) ) {
        return $value;
    }
    $value = strtolower( trim( (string) $value ) );
    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}
endif;

add_action( 'adfoin_action_fields', 'adfoin_iterable_action_fields' );

function adfoin_iterable_action_fields() {
    ?>
    <script type="text/template" id="iterable-action-template">
        <table class="form-table">
            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Iterable Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select account…', 'advanced-form-integration' ); ?></option>
                        <?php foreach ( adfoin_read_credentials( 'iterable' ) as $option ) : ?>
                            <option value="<?php echo esc_attr( $option['id'] ); ?>"><?php echo esc_html( $option['title'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Iterable List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, value) in fielddata.lists" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <a href="#" @click.prevent="getLists(true)" style="margin-left:8px;"><?php esc_html_e( 'Refresh', 'advanced-form-integration' ); ?></a>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p v-if="listError" class="adfoin-error" style="color:#b32d2e;">{{ listError }}</p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Iterable [PRO]', 'custom fields' ); ?>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need automation events?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: pricing page URL */
                                __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Iterable [PRO]</a> to trigger journeys, capture custom events, subscribe lists with custom fields, and sync every profile attribute.', 'advanced-form-integration' ),
                                esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) )
                            ),
                            array(
                                'a' => array(
                                    'href'   => array(),
                                    'target' => array(),
                                    'rel'    => array(),
                                ),
                            )
                        );
                    ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}
