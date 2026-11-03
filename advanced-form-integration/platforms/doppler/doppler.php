<?php

add_filter( 'adfoin_action_providers', 'adfoin_doppler_actions', 10, 1 );

function adfoin_doppler_actions( $actions ) {
    $actions['doppler'] = array(
        'title' => __( 'Doppler', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_doppler_settings_tab', 10, 1 );

function adfoin_doppler_settings_tab( $providers ) {
    $providers['doppler'] = __( 'Doppler', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_doppler_settings_view', 10, 1 );

function adfoin_doppler_settings_view( $current_tab ) {
    if( $current_tab != 'doppler' ) return;

    $title = __( 'Doppler', 'advanced-form-integration' );
    $key = 'doppler';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'account_email',
                'label' => __( 'Account Email', 'advanced-form-integration' ),
                'hidden' => false
            ],
            [
                'key' => 'api_key',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Log in to your Doppler account.</li>
                    <li>Go to Profile &gt; Control Panel Advanced Preferences &gt; Doppler API. Copy the API Key.</li>
                    <li>Enter the API Key below.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_doppler_credentials', 'adfoin_get_doppler_credentials', 10, 0 );

function adfoin_get_doppler_credentials() {
    adfoin_verify_nonce();
    $all_credentials = adfoin_read_credentials( 'doppler' );
    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_doppler_credentials', 'adfoin_save_doppler_credentials', 10, 0 );

function adfoin_save_doppler_credentials() {
    adfoin_verify_nonce();
    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );
    if( 'doppler' == $platform ) {
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

function adfoin_doppler_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'doppler' );
    if (is_array($credentials)) {
        foreach( $credentials as $option ) {
            if (isset($option['id']) && isset($option['title'])) {
                 $html .= '<option value="'. esc_attr($option['id']) .'">' . esc_html($option['title']) . '</option>';
            }
        }
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_doppler_action_fields' );

function adfoin_doppler_action_fields() {
    ?>
    <script type="text/template" id="doppler-action-template">
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
                        <?php esc_attr_e( 'Doppler Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_doppler_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=doppler' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
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

            <?php adfoin_pro_feature_notice( 'subscribe', 'Doppler [PRO]', 'gender, birthday, country, consent and custom fields' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_doppler_build_request( $endpoint, $method, $data, $cred_id ) {
    $credentials   = adfoin_get_credentials_by_id( 'doppler', $cred_id );
    $api_key       = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';
    $account_email = isset( $credentials['account_email'] ) ? $credentials['account_email'] : '';

    if ( !$api_key || !$account_email ) {
        return new WP_Error( 'doppler_no_account', __( 'No Doppler account is selected, or the account has no Account Email or API Key. Check the integration and AFI Settings > Doppler.', 'advanced-form-integration' ) );
    }

    $url  = 'https://restapi.fromdoppler.com/accounts/' . rawurlencode( $account_email ) . '/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout'     => 30,
        'method'      => strtoupper( $method ),
        'user-agent'  => 'Advanced Form Integration (WordPress Plugin)',
        'headers'     => array(
            'Authorization' => 'token ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ) ) ) {
        $args['body'] = !empty( $data ) ? wp_json_encode( $data ) : wp_json_encode( (object) array() );
    } elseif ( 'GET' == $args['method'] && !empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    return array( $url, $args );
}

function adfoin_doppler_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $request = adfoin_doppler_build_request( $endpoint, $method, $data, $cred_id );

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
 * Doppler's error as one line, e.g. "Invalid field values: GENDER "Male"".
 */
function adfoin_doppler_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $message = !empty( $body['title'] ) ? $body['title'] : wp_remote_retrieve_response_code( $response ) . ' ' . wp_remote_retrieve_response_message( $response );
    $details = array();

    foreach ( isset( $body['errors'] ) && is_array( $body['errors'] ) ? $body['errors'] : array() as $error ) {
        if ( isset( $error['fieldName'] ) ) {
            $details[] = $error['fieldName'] . ( isset( $error['invalidValue'] ) ? ' "' . $error['invalidValue'] . '"' : '' );
        } elseif ( isset( $error['detail'] ) ) {
            $details[] = $error['detail'];
        }
    }

    if ( !$details && !empty( $body['detail'] ) && strpos( $body['detail'], 'See `errors`' ) === false ) {
        $details[] = preg_replace( '/ - Resolving .*$/', '', $body['detail'] );
    }

    return $details ? $message . ': ' . implode( ', ', $details ) : $message;
}

function adfoin_doppler_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'doppler_not_sent', $message ), 'https://restapi.fromdoppler.com/', array( 'method' => 'POST' ), $record );
}

add_action( 'wp_ajax_adfoin_get_doppler_lists', 'adfoin_get_doppler_lists', 10, 0 );

function adfoin_get_doppler_lists() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists   = array();

    for ( $page = 1; $page <= 50; $page++ ) {
        $response = adfoin_doppler_request( 'lists', 'GET', array( 'page' => $page, 'per_page' => 200 ), array(), $cred_id );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
            wp_send_json_error( sprintf( __( 'Could not load Doppler lists: %s', 'advanced-form-integration' ), adfoin_doppler_error_message( $response ) ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        foreach ( isset( $body['items'] ) && is_array( $body['items'] ) ? $body['items'] : array() as $list_item ) {
            if ( isset( $list_item['listId'], $list_item['name'] ) ) {
                $lists[] = array( 'id' => $list_item['listId'], 'name' => $list_item['name'] );
            }
        }

        if ( empty( $body['pagesCount'] ) || $page >= $body['pagesCount'] ) {
            break;
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_doppler_job_queue', 'adfoin_doppler_job_queue', 10, 1 );

function adfoin_doppler_job_queue( $data ) {
    adfoin_doppler_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_doppler_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $data       = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $subscriber = adfoin_doppler_base_subscriber( $data, $posted_data, $record );

    if ( !$subscriber ) return;

    foreach ( array( 'FIRSTNAME', 'LASTNAME' ) as $name ) {
        $value = trim( (string) adfoin_get_parsed_values( isset( $data[ $name ] ) ? $data[ $name ] : '', $posted_data ) );

        if ( $value !== '' ) {
            $subscriber['fields'][] = array( 'name' => $name, 'value' => $value );
        }
    }

    adfoin_doppler_add_subscriber( $subscriber, $data, $record );
}

/*
 * List and email, shared by the free and PRO actions.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_doppler_base_subscriber( $data, $posted_data, $record ) {
    if ( empty( $data['listId'] ) ) {
        adfoin_doppler_log_skipped( $record, __( 'Not sent: no Doppler list is selected in the integration.', 'advanced-form-integration' ) );
        return false;
    }

    $raw   = trim( (string) adfoin_get_parsed_values( isset( $data['email'] ) ? $data['email'] : '', $posted_data ) );
    $email = sanitize_email( $raw );

    if ( !$email ) {
        adfoin_doppler_log_skipped(
            $record,
            $raw === ''
                ? __( 'Not sent: the mapped email field is empty.', 'advanced-form-integration' )
                : sprintf( __( 'Not sent: "%s" is not a valid email address.', 'advanced-form-integration' ), $raw )
        );
        return false;
    }

    return array( 'email' => $email, 'fields' => array() );
}

/*
 * Adds the subscriber to the list (Doppler updates the fields of an existing
 * subscriber). Doppler refuses to re-add someone who unsubscribed; that is
 * logged as OK with a note. Each submission gets one log entry.
 */
function adfoin_doppler_add_subscriber( $subscriber, $data, $record, $notes = array() ) {
    $cred_id  = isset( $data['credId'] ) ? $data['credId'] : '';
    $endpoint = 'lists/' . rawurlencode( $data['listId'] ) . '/subscribers';
    $response = adfoin_doppler_request( $endpoint, 'POST', $subscriber, array(), $cred_id );

    if ( !is_wp_error( $response ) ) {
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code == 400 && isset( $body['detail'] ) && strpos( $body['detail'], 'Already Unsubscribed' ) !== false ) {
            $response['response']['code'] = 200;
            $notes[] = __( 'the subscriber had unsubscribed in Doppler: not re-added to the list', 'advanced-form-integration' );
        } elseif ( $code >= 300 ) {
            $response['response']['message'] = adfoin_doppler_error_message( $response );
        }

        if ( $notes && wp_remote_retrieve_response_code( $response ) < 300 ) {
            $response['response']['message'] = sprintf( 'OK (%s)', implode( '; ', $notes ) );
        }
    }

    $request = adfoin_doppler_build_request( $endpoint, 'POST', $subscriber, $cred_id );

    adfoin_add_to_log(
        $response,
        is_wp_error( $request ) ? $endpoint : $request[0],
        is_wp_error( $request ) ? array( 'method' => 'POST', 'body' => $subscriber ) : $request[1],
        $record
    );

    return $response;
}

/*
 * Converts a form value to the format Doppler accepts for the field type.
 * Returns null when it can't, so the field is left out instead of Doppler
 * rejecting the whole subscriber.
 */
function adfoin_doppler_field_value( $value, $type, $allowed = array() ) {
    $lower = strtolower( trim( $value ) );

    switch ( $type ) {
        case 'gender':
            $map = array(
                'm' => 'M', 'male' => 'M', 'man' => 'M', 'masculino' => 'M', 'hombre' => 'M',
                'f' => 'F', 'female' => 'F', 'woman' => 'F', 'femenino' => 'F', 'mujer' => 'F',
                'n' => 'N', 'other' => 'N', 'non-binary' => 'N', 'nonbinary' => 'N', 'otro' => 'N',
            );
            return isset( $map[ $lower ] ) ? $map[ $lower ] : null;

        case 'date':
            $date = date_create( $value, wp_timezone() );
            return $date ? $date->format( 'Y-m-d' ) : null;

        case 'consent':
        case 'boolean':
            if ( in_array( $lower, array( 'true', 'yes', 'y', '1', 'on', 'checked', 'si', 'sí', 'accept', 'accepted', 'agree', 'i agree' ), true ) ) {
                return 'true';
            }
            if ( $type === 'boolean' && in_array( $lower, array( 'false', 'no', 'n', '0', 'off' ), true ) ) {
                return 'false';
            }
            return null;

        case 'number':
            $number = str_replace( ',', '', $value );
            return is_numeric( $number ) ? $number : null;

        case 'country':
            foreach ( $allowed as $option ) {
                $parts = explode( '|', $option, 2 );

                foreach ( $parts as $part ) {
                    if ( strtolower( $part ) === $lower ) {
                        return $parts[0];
                    }
                }
            }
            return null;
    }

    if ( $allowed ) {
        foreach ( $allowed as $option ) {
            if ( strtolower( $option ) === $lower ) {
                return $option;
            }
        }
        return null;
    }

    return $value;
}

/*
 * The account's fields as name => [type, allowed values], or null.
 */
function adfoin_doppler_account_fields( $cred_id ) {
    $response = adfoin_doppler_request( 'fields', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
        return null;
    }

    $body   = json_decode( wp_remote_retrieve_body( $response ), true );
    $fields = array();

    foreach ( isset( $body['items'] ) && is_array( $body['items'] ) ? $body['items'] : array() as $field ) {
        if ( empty( $field['name'] ) || !empty( $field['readonly'] ) ) continue;

        $fields[ $field['name'] ] = array(
            'type'    => isset( $field['type'] ) ? $field['type'] : 'string',
            'allowed' => isset( $field['allowedValues'] ) && is_array( $field['allowedValues'] ) ? $field['allowedValues'] : array(),
        );
    }

    return $fields;
}
