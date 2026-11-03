<?php

add_filter( 'adfoin_action_providers', 'adfoin_emailchef_actions', 10, 1 );

function adfoin_emailchef_actions( $actions ) {
    $actions['emailchef'] = array(
        'title' => __( 'Emailchef', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_emailchef_settings_tab', 10, 1 );

function adfoin_emailchef_settings_tab( $providers ) {
    $providers['emailchef'] = __( 'Emailchef', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_emailchef_settings_view', 10, 1 );

function adfoin_emailchef_settings_view( $current_tab ) {
    if( $current_tab != 'emailchef' ) {
        return;
    }

    $title = __( 'eMailChef', 'advanced-form-integration' );
    $key = 'emailchef';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'consumer_key',
                'label' => __( 'Consumer Key', 'advanced-form-integration' ),
                'hidden' => false
            ],
            [
                'key' => 'consumer_secret',
                'label' => __( 'Consumer Secret', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Log in to your eMailChef account.</li>
                    <li>Navigate to the API / Integrations section to find your Consumer Key and Consumer Secret.</li>
                    <li>Enter these credentials below.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_emailchef_credentials', 'adfoin_get_emailchef_credentials', 10, 0 );

function adfoin_get_emailchef_credentials() {
    adfoin_verify_nonce();

    $all_credentials = adfoin_read_credentials( 'emailchef' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_emailchef_credentials', 'adfoin_save_emailchef_credentials', 10, 0 );

function adfoin_save_emailchef_credentials() {
    adfoin_verify_nonce();

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if( 'emailchef' == $platform ) {
        $data_to_save = array();
        if (isset($_POST['data']) && is_array($_POST['data'])) {
            foreach ($_POST['data'] as $key => $value) {
                if (is_array($value)) {
                     $data_to_save[$key] = adfoin_array_map_recursive( 'sanitize_text_field', $value );
                } else {
                    $data_to_save[sanitize_key($key)] = sanitize_text_field( $value );
                }
            }
        }
        adfoin_save_credentials( $platform, $data_to_save );
    }

    wp_send_json_success();
}


function adfoin_emailchef_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'emailchef' );

    if (is_array($credentials)) {
        foreach( $credentials as $option ) {
            if (isset($option['id']) && isset($option['title'])) {
                 $html .= '<option value="'. esc_attr($option['id']) .'">' . esc_html($option['title']) . '</option>';
            }
        }
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_emailchef_action_fields' );

function adfoin_emailchef_action_fields() {
    ?>
    <script type="text/template" id="emailchef-action-template">
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
                    <label>
                        <?php esc_attr_e( 'eMailChef Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_emailchef_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=emailchef' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Select List', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="list in fielddata.lists" :value="list.id"> {{ list.name }} </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                    <p class="description" v-if="listError" style="color: #b32d2e;">{{ listError }}</p>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Emailchef [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_emailchef_build_request( $endpoint, $method, $data, $cred_id ) {
    $credentials     = adfoin_get_credentials_by_id( 'emailchef', $cred_id );
    $consumer_key    = isset( $credentials['consumer_key'] ) ? $credentials['consumer_key'] : '';
    $consumer_secret = isset( $credentials['consumer_secret'] ) ? $credentials['consumer_secret'] : '';

    if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
        return new WP_Error( 'emailchef_no_account', __( 'No Emailchef account is selected, or the account has no Consumer Key or Secret. Check the integration and AFI Settings > Emailchef.', 'advanced-form-integration' ) );
    }

    $url  = 'https://app.emailchef.com/apps/api/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout'     => 30,
        'method'      => strtoupper( $method ),
        'user-agent'  => 'Advanced Form Integration (WordPress Plugin)',
        'headers'     => array(
            'consumerKey'    => $consumer_key,
            'consumerSecret' => $consumer_secret,
            'Content-Type'   => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ) ) ) {
        $args['body'] = !empty( $data ) ? wp_json_encode( $data ) : wp_json_encode( (object) array() );
    } elseif ( 'GET' == $args['method'] && !empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    return array( $url, $args );
}

function adfoin_emailchef_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $request = adfoin_emailchef_build_request( $endpoint, $method, $data, $cred_id );

    if ( is_wp_error( $request ) ) {
        if ( $record ) {
            adfoin_add_to_log( $request, $endpoint, array( 'method' => $method, 'body' => $data ), $record );
        }

        return $request;
    }

    list( $url, $args ) = $request;

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

/*
 * Emailchef's error text, or '' when the response is a success.
 * It answers some errors (e.g. an invalid email) with HTTP 200 and {"status":"ERROR"}.
 */
function adfoin_emailchef_response_error( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code < 300 && !( isset( $body['status'] ) && $body['status'] === 'ERROR' ) ) {
        return '';
    }

    $messages = array(
        'incorrect_email'    => __( 'Emailchef rejected the email address.', 'advanced-form-integration' ),
        'resource_not_found' => __( 'The list was not found. It may have been deleted; pick the list again in the integration.', 'advanced-form-integration' ),
        'General Error'      => __( 'Emailchef rejected the contact (General Error). Check that the list and the mapped custom fields still exist.', 'advanced-form-integration' ),
    );
    $message = isset( $body['message'] ) && is_string( $body['message'] ) ? $body['message'] : '';

    if ( isset( $messages[ $message ] ) ) {
        return $messages[ $message ];
    }

    return $message !== '' ? $message : $code . ' ' . wp_remote_retrieve_response_message( $response );
}

function adfoin_emailchef_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'emailchef_not_sent', $message ), 'https://app.emailchef.com/apps/api/v1/contacts', array( 'method' => 'POST' ), $record );
}

add_action( 'wp_ajax_adfoin_get_emailchef_lists', 'adfoin_get_emailchef_lists', 10, 0 );

function adfoin_get_emailchef_lists() {
    adfoin_verify_nonce();

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_emailchef_request( 'lists', 'GET', array(), array(), $cred_id );
    $error    = adfoin_emailchef_response_error( $response );
    $body     = $error ? null : json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $error || !is_array( $body ) ) {
        wp_send_json_error( sprintf( __( 'Could not load Emailchef lists: %s', 'advanced-form-integration' ), $error ? $error : __( 'unexpected response', 'advanced-form-integration' ) ) );
    }

    $lists = array();

    foreach ( $body as $list_item ) {
        if ( isset( $list_item['id'] ) && isset( $list_item['name'] ) ) {
            $lists[] = array( 'id' => $list_item['id'], 'name' => $list_item['name'] );
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_emailchef_job_queue', 'adfoin_emailchef_job_queue', 10, 1 );

function adfoin_emailchef_job_queue( $data ) {
    adfoin_emailchef_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_emailchef_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $contact = adfoin_emailchef_base_contact( $data, $posted_data, $record );

    if ( !$contact ) return;

    adfoin_emailchef_add_contact( $contact, $data, $record );
}

/*
 * List, email and name, shared by the free and PRO actions.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_emailchef_base_contact( $data, $posted_data, $record ) {
    if ( empty( $data['listId'] ) ) {
        adfoin_emailchef_log_skipped( $record, __( 'Not sent: no Emailchef list is selected in the integration.', 'advanced-form-integration' ) );
        return false;
    }

    $raw   = trim( (string) adfoin_get_parsed_values( isset( $data['email'] ) ? $data['email'] : '', $posted_data ) );
    $email = sanitize_email( $raw );

    if ( !$email ) {
        adfoin_emailchef_log_skipped(
            $record,
            $raw === ''
                ? __( 'Not sent: the mapped email field is empty.', 'advanced-form-integration' )
                : sprintf( __( 'Not sent: "%s" is not a valid email address.', 'advanced-form-integration' ), $raw )
        );
        return false;
    }

    $contact = array(
        'list_id' => $data['listId'],
        'email'   => $email,
        'status'  => 'subscribed',
        'mode'    => 'ADMIN',
    );

    foreach ( array( 'firstname', 'lastname' ) as $key ) {
        $value = trim( (string) adfoin_get_parsed_values( isset( $data[ $key ] ) ? $data[ $key ] : '', $posted_data ) );

        if ( $value !== '' ) {
            $contact[ $key ] = $value;
        }
    }

    return $contact;
}

/*
 * Adds the contact to the list; Emailchef updates the contact when the email
 * is already on it. Logs once, with Emailchef's in-body errors turned into failures.
 */
function adfoin_emailchef_add_contact( $contact, $data, $record, $notes = array() ) {
    $cred_id  = isset( $data['credId'] ) ? $data['credId'] : '';
    $payload  = array( 'instance_in' => $contact );
    $response = adfoin_emailchef_request( 'contacts', 'POST', $payload, array(), $cred_id );
    $error    = adfoin_emailchef_response_error( $response );
    $request  = adfoin_emailchef_build_request( 'contacts', 'POST', $payload, $cred_id );

    if ( !is_wp_error( $response ) ) {
        if ( $error ) {
            if ( wp_remote_retrieve_response_code( $response ) < 300 ) {
                $response['response']['code'] = 400;
            }

            $response['response']['message'] = $error;
        } else {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $body['contact_added_to_list'] ) && !$body['contact_added_to_list'] ) {
                $notes[] = __( 'already on the list, details updated', 'advanced-form-integration' );
            }

            if ( $notes ) {
                $response['response']['message'] = sprintf( 'OK (%s)', implode( '; ', $notes ) );
            }
        }
    }

    adfoin_add_to_log(
        $response,
        is_wp_error( $request ) ? 'contacts' : $request[0],
        is_wp_error( $request ) ? array( 'method' => 'POST', 'body' => $payload ) : $request[1],
        $record
    );

    return $response;
}
