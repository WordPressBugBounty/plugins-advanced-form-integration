<?php

add_filter( 'adfoin_action_providers', 'adfoin_emailit_actions', 10, 1 );
function adfoin_emailit_actions( $actions ) {
    $actions['emailit'] = array(
        'title' => __( 'Emailit', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to Audience', 'advanced-form-integration' ),
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_emailit_settings_tab', 10, 1 );
function adfoin_emailit_settings_tab( $providers ) {
    $providers['emailit'] = __( 'Emailit', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_emailit_settings_view', 10, 1 );
function adfoin_emailit_settings_view( $current_tab ) {
    if( $current_tab != 'emailit' ) return;
    $title = __( 'Emailit', 'advanced-form-integration' );
    $key = 'emailit';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'api_key',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = __(
        '<p>
            <ol>
                <li>Log in to your Emailit account.</li>
                <li>Go to Credentials and create an API Key with <strong>Full access</strong>. Sending-only keys cannot add subscribers.</li>
            </ol>
        </p>',
        'advanced-form-integration'
    );
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_emailit_credentials', 'adfoin_get_emailit_credentials', 10, 0 );
function adfoin_get_emailit_credentials() {
    adfoin_verify_nonce();
    $all_credentials = adfoin_read_credentials( 'emailit' );
    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_emailit_credentials', 'adfoin_save_emailit_credentials', 10, 0 );
function adfoin_save_emailit_credentials() {
    adfoin_verify_nonce();
    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );
    if( 'emailit' == $platform ) {
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

function adfoin_emailit_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'emailit' );
    if (is_array($credentials)) {
        foreach( $credentials as $option ) {
            if (isset($option['id']) && isset($option['title'])) {
                 $html .= '<option value="'. esc_attr($option['id']) .'">' . esc_html($option['title']) . '</option>';
            }
        }
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_emailit_action_fields' );
function adfoin_emailit_action_fields() {
    ?>
    <script type="text/template" id="emailit-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label>
                        <?php esc_attr_e( 'Emailit Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getAudiences">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_emailit_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=emailit' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label>
                        <?php esc_attr_e( 'Audiences', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[audienceId]" v-model="fielddata.audienceId">
                        <option value=""> <?php _e( 'Select Audience...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="audience in fielddata.audiences" v-bind:key="audience.id" v-bind:value="audience.id">
                            {{ audience.name }}
                        </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                    <p class="description" v-if="audienceError" style="color: #b32d2e;">{{ audienceError }}</p>
                </td>
            </tr>
            
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

            <?php adfoin_pro_feature_notice( 'subscribe', 'Emailit [PRO]', 'custom fields, tags and double opt-in' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_emailit_build_request( $endpoint, $method, $data, $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'emailit', $cred_id );
    $api_key     = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'emailit_no_account', __( 'No Emailit account is selected, or the account has no API key. Check the integration and AFI Settings > Emailit.', 'advanced-form-integration' ) );
    }

    $url  = 'https://api.emailit.com/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout'     => 30,
        'method'      => strtoupper( $method ),
        'user-agent'  => 'Advanced Form Integration (WordPress Plugin)',
        'headers'     => array(
            'Authorization' => 'Bearer ' . $api_key,
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

function adfoin_emailit_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $request = adfoin_emailit_build_request( $endpoint, $method, $data, $cred_id );

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
 * Logs a response that was fetched without a record (see adfoin_emailit_subscribe()).
 */
function adfoin_emailit_log( $response, $endpoint, $method, $data, $record, $cred_id, $notes = array() ) {
    $request = adfoin_emailit_build_request( $endpoint, $method, $data, $cred_id );
    $url     = is_wp_error( $request ) ? $endpoint : $request[0];
    $args    = is_wp_error( $request ) ? array( 'method' => $method, 'body' => $data ) : $request[1];

    if ( !is_wp_error( $response ) ) {
        if ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
            $response['response']['message'] = adfoin_emailit_error_message( $response );
        } elseif ( $notes ) {
            $response['response']['message'] = sprintf( 'OK (%s)', implode( '; ', $notes ) );
        }
    }

    adfoin_add_to_log( $response, $url, $args, $record );
}

function adfoin_emailit_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'emailit_not_sent', $message ), 'https://api.emailit.com/v2/audiences', array( 'method' => 'POST' ), $record );
}

function adfoin_emailit_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    foreach ( array( 'message', 'error' ) as $key ) {
        if ( !empty( $body[ $key ] ) && is_string( $body[ $key ] ) ) {
            return $body[ $key ];
        }
    }

    return wp_remote_retrieve_response_code( $response ) . ' ' . wp_remote_retrieve_response_message( $response );
}

add_action( 'adfoin_emailit_job_queue', 'adfoin_emailit_job_queue', 10, 1 );
function adfoin_emailit_job_queue( $data ) {
    adfoin_emailit_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_emailit_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $data       = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $subscriber = adfoin_emailit_base_subscriber( $data, $posted_data, $record );

    if ( !$subscriber ) return;

    adfoin_emailit_subscribe( $subscriber, $data, $record );
}

function adfoin_emailit_value( $data, $key, $posted_data ) {
    return trim( (string) adfoin_get_parsed_values( isset( $data[ $key ] ) ? $data[ $key ] : '', $posted_data ) );
}

/*
 * Email and name, shared by the free and PRO actions.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_emailit_base_subscriber( $data, $posted_data, $record ) {
    if ( empty( $data['audienceId'] ) ) {
        adfoin_emailit_log_skipped( $record, __( 'Not sent: no Emailit audience is selected in the integration.', 'advanced-form-integration' ) );
        return false;
    }

    $raw   = adfoin_emailit_value( $data, 'email', $posted_data );
    $email = sanitize_email( $raw );

    if ( !$email ) {
        adfoin_emailit_log_skipped(
            $record,
            $raw === ''
                ? __( 'Not sent: the mapped email field is empty.', 'advanced-form-integration' )
                : sprintf( __( 'Not sent: "%s" is not a valid email address.', 'advanced-form-integration' ), $raw )
        );
        return false;
    }

    $subscriber = array( 'email' => $email );

    foreach ( array( 'first_name', 'last_name' ) as $key ) {
        $value = adfoin_emailit_value( $data, $key, $posted_data );

        if ( $value !== '' ) {
            $subscriber[ $key ] = $value;
        }
    }

    return $subscriber;
}

/*
 * Integrations saved before the v2 API stored the audience token, which v2
 * doesn't accept. Looks the token up among the account's audiences and
 * remembers the audience ID it belongs to.
 */
function adfoin_emailit_audience_id( $audience, $cred_id ) {
    if ( strpos( $audience, 'aud_' ) === 0 ) {
        return $audience;
    }

    $map = get_option( 'adfoin_emailit_audience_tokens', array() );

    if ( isset( $map[ $audience ] ) ) {
        return $map[ $audience ];
    }

    $audiences = adfoin_emailit_fetch_audiences( $cred_id );

    if ( is_wp_error( $audiences ) ) {
        return $audiences;
    }

    foreach ( $audiences as $item ) {
        $response = adfoin_emailit_request( 'audiences/' . rawurlencode( $item['id'] ), 'GET', array(), array(), $cred_id );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( !empty( $body['token'] ) && $body['token'] === $audience ) {
            $map[ $audience ] = $item['id'];
            update_option( 'adfoin_emailit_audience_tokens', $map, false );

            return $item['id'];
        }
    }

    // Not a token: let Emailit try it as an audience name.
    return $audience;
}

/*
 * Subscribes the person to the audience, or updates them when they are on it.
 *
 * Emailit answers 409 for someone already subscribed (nothing is updated) and
 * silently re-subscribes someone who unsubscribed, so the subscriber is looked
 * up first. People who unsubscribed are only re-subscribed when $options['resubscribe'] is set.
 *
 * $options (PRO): custom_fields, tags, resubscribe, double_opt_in, send_confirmation_email.
 * Each submission gets one log entry.
 */
function adfoin_emailit_subscribe( $subscriber, $data, $record, $options = array(), $notes = array() ) {
    $cred_id  = isset( $data['credId'] ) ? $data['credId'] : '';
    $audience = adfoin_emailit_audience_id( $data['audienceId'], $cred_id );

    if ( is_wp_error( $audience ) ) {
        adfoin_emailit_log( $audience, 'audiences', 'GET', array(), $record, $cred_id );
        return $audience;
    }

    $base   = 'audiences/' . rawurlencode( $audience ) . '/subscribers';
    $member = $base . '/' . rawurlencode( $subscriber['email'] );
    $lookup = adfoin_emailit_request( $member, 'GET', array(), array(), $cred_id );
    $code   = is_wp_error( $lookup ) ? 0 : wp_remote_retrieve_response_code( $lookup );

    // 404 is also returned for an unknown audience; the POST below reports that.
    if ( $code != 200 && $code != 404 ) {
        adfoin_emailit_log( $lookup, $member, 'GET', array(), $record, $cred_id );
        return $lookup;
    }

    $existing = $code == 200 ? json_decode( wp_remote_retrieve_body( $lookup ), true ) : null;

    if ( !empty( $options['custom_fields'] ) ) {
        // Emailit replaces all custom fields, so keep the ones not sent. Only the
        // contact (not the subscriber) returns them, and the contact may exist
        // without being on this audience.
        $contact = adfoin_emailit_request( 'contacts/' . rawurlencode( $subscriber['email'] ), 'GET', array(), array(), $cred_id );
        $contact = !is_wp_error( $contact ) && wp_remote_retrieve_response_code( $contact ) == 200 ? json_decode( wp_remote_retrieve_body( $contact ), true ) : array();
        $current = !empty( $contact['custom_fields'] ) && is_array( $contact['custom_fields'] ) ? $contact['custom_fields'] : array();

        $subscriber['custom_fields'] = array_merge( $current, $options['custom_fields'] );
    }

    if ( !$existing || ( empty( $existing['subscribed'] ) && !empty( $options['resubscribe'] ) ) ) {
        $body = $subscriber;

        if ( !$existing ) {
            foreach ( array( 'double_opt_in', 'send_confirmation_email' ) as $flag ) {
                if ( !empty( $options[ $flag ] ) ) {
                    $body[ $flag ] = true;
                }
            }
        } else {
            $notes[] = __( 're-subscribed', 'advanced-form-integration' );
        }

        $endpoint = $base;
        $method   = 'POST';
        $response = adfoin_emailit_request( $endpoint, $method, $body, array(), $cred_id );

        if ( !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 202 ) {
            $notes[] = __( 'double opt-in email sent, waiting for confirmation', 'advanced-form-integration' );
        }
    } else {
        $body = $subscriber;
        unset( $body['email'] );

        $notes[]  = empty( $existing['subscribed'] )
            ? __( 'unsubscribed from this audience: details updated, not re-subscribed', 'advanced-form-integration' )
            : __( 'already subscribed, details updated', 'advanced-form-integration' );
        $endpoint = $member;
        $method   = 'POST';

        if ( $body ) {
            $response = adfoin_emailit_request( $endpoint, $method, $body, array(), $cred_id );
        } else {
            $response = $lookup;
            $endpoint = $member;
            $method   = 'GET';
        }
    }

    if ( !empty( $options['tags'] ) && !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
        $tags = adfoin_emailit_request( 'contacts/' . rawurlencode( $subscriber['email'] ) . '/tags', 'POST', array( 'tags' => $options['tags'] ), array(), $cred_id );

        $notes[] = !is_wp_error( $tags ) && wp_remote_retrieve_response_code( $tags ) < 300
            ? sprintf( __( 'tags added: %s', 'advanced-form-integration' ), implode( ', ', $options['tags'] ) )
            : sprintf( __( 'tags not added: %s', 'advanced-form-integration' ), adfoin_emailit_error_message( $tags ) );
    }

    adfoin_emailit_log( $response, $endpoint, $method, $body, $record, $cred_id, $notes );

    return $response;
}

/*
 * All audiences of the account as [id, name], or WP_Error.
 */
function adfoin_emailit_fetch_audiences( $cred_id ) {
    $audiences = array();

    for ( $page = 1; $page <= 20; $page++ ) {
        $response = adfoin_emailit_request( 'audiences', 'GET', array( 'limit' => 100, 'page' => $page ), array(), $cred_id );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
            return new WP_Error( 'emailit_audiences', adfoin_emailit_error_message( $response ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        foreach ( isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : array() as $aud ) {
            if ( isset( $aud['id'] ) ) {
                $audiences[] = array( 'id' => $aud['id'], 'name' => !empty( $aud['name'] ) ? $aud['name'] : $aud['id'] );
            }
        }

        if ( empty( $body['next_page_url'] ) ) {
            break;
        }
    }

    return $audiences;
}

add_action( 'wp_ajax_adfoin_get_emailit_audiences', 'adfoin_get_emailit_audiences', 10, 0 );
function adfoin_get_emailit_audiences() {
    adfoin_verify_nonce();

    $cred_id   = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $audiences = adfoin_emailit_fetch_audiences( $cred_id );

    if ( is_wp_error( $audiences ) ) {
        $message = $audiences->get_error_message();

        if ( strpos( $message, 'Permission denied' ) !== false ) {
            $message = __( 'This API key cannot read audiences. Create an API key with Full access in Emailit and save it in AFI Settings > Emailit.', 'advanced-form-integration' );
        }

        wp_send_json_error( sprintf( __( 'Could not load Emailit audiences: %s', 'advanced-form-integration' ), $message ) );
    }

    wp_send_json_success( $audiences );
}
