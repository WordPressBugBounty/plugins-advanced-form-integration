<?php
add_filter( 'adfoin_action_providers', 'adfoin_rapidmail_actions', 10, 1 );

function adfoin_rapidmail_actions( $actions ) {
    $actions['rapidmail'] = array(
        'title' => __( 'Rapidmail', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_rapidmail_settings_tab', 10, 1 );

function adfoin_rapidmail_settings_tab( $providers ) {
    $providers['rapidmail'] = __( 'Rapidmail', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_rapidmail_settings_view', 10, 1 );

function adfoin_rapidmail_settings_view( $current_tab ) {
    if( $current_tab != 'rapidmail' ) {
        return;
    }

    $title = __( 'Rapidmail', 'advanced-form-integration' );
    $key = 'rapidmail';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'username',
                'label' => __( 'Username', 'advanced-form-integration' ),
                'hidden' => true
            ],
            [
                'key' => 'password',
                'label' => __( 'Password', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Go to your Rapidmail account > Settings > API.</li>
                    <li>Create a new API user and copy the Username and Password.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_rapidmail_credentials', 'adfoin_get_rapidmail_credentials', 10, 0 );

function adfoin_get_rapidmail_credentials() {
    adfoin_verify_nonce();

    $all_credentials = adfoin_read_credentials( 'rapidmail' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_rapidmail_credentials', 'adfoin_save_rapidmail_credentials', 10, 0 );

function adfoin_save_rapidmail_credentials() {

    adfoin_verify_nonce();

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if( 'rapidmail' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_rapidmail_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'rapidmail' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_rapidmail_action_fields' );

function adfoin_rapidmail_action_fields() {
    ?>
    <script type="text/template" id="rapidmail-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Rapidmail Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_rapidmail_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=rapidmail' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row">
                    <?php esc_attr_e( 'Select List', 'advanced-form-integration' ); ?>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(list, index) in fielddata.lists" :value="index"> {{ list }} </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row">
                    <?php esc_attr_e( 'Subscription', 'advanced-form-integration' ); ?>
                </td>
                <td>
                    <select name="fieldData[optin]" v-model="fielddata.optin">
                        <option value="active"><?php esc_html_e( 'Add as active recipient', 'advanced-form-integration' ); ?></option>
                        <option value="doi"><?php esc_html_e( 'Double opt-in (Rapidmail sends a confirmation email)', 'advanced-form-integration' ); ?></option>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

            <?php adfoin_pro_feature_notice( 'subscribe', 'Rapidmail [PRO]', 'extra fields and tags' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_rapidmail_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'rapidmail', $cred_id );
    $username = isset( $credentials['username'] ) ? $credentials['username'] : '';
    $password = isset( $credentials['password'] ) ? $credentials['password'] : '';

    $base_url = "https://apiv3.emailsys.net/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password )
        ),
    );

    if ( 'POST' == $method || 'PATCH' == $method ) {
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_rapidmail_lists', 'adfoin_get_rapidmail_lists', 10, 0 );

function adfoin_get_rapidmail_lists() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists   = array();
    $page    = 1;

    do {
        $response = adfoin_rapidmail_request( 'recipientlists?page=' . $page, 'GET', array(), array(), $cred_id );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || ! isset( $body['_embedded']['recipientlists'] ) ) {
            if ( $lists ) {
                break;
            }

            wp_send_json_error( isset( $body['detail'] ) ? $body['detail'] : '' );
        }

        foreach ( $body['_embedded']['recipientlists'] as $list ) {
            $lists[ $list['id'] ] = $list['name'];
        }

        $page_count = isset( $body['page_count'] ) ? (int) $body['page_count'] : 1;
        $page++;
    } while ( $page <= $page_count && $page <= 50 );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_rapidmail_job_queue', 'adfoin_rapidmail_job_queue', 10, 1 );

function adfoin_rapidmail_job_queue( $data ) {
    adfoin_rapidmail_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_rapidmail_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    if ( 'subscribe' !== $record['task'] ) {
        return;
    }

    adfoin_rapidmail_subscribe( $record, $posted_data, $record_data['field_data'] );
}

/**
 * Standard recipient fields, parsed and cleaned. Values Rapidmail would
 * reject (birthdate not Y-m-d, unknown gender) are fixed or dropped so the
 * recipient is still saved.
 */
function adfoin_rapidmail_build_recipient( $data, $posted_data ) {
    // Extra 1-10 are added by Rapidmail [PRO].
    $standard  = array( 'email', 'firstname', 'lastname', 'gender', 'title', 'zip', 'birthdate', 'status' );

    $recipient = array();

    foreach ( $standard as $key ) {
        if ( ! isset( $data[ $key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $data[ $key ], $posted_data );
        $value = is_string( $value ) ? trim( $value ) : $value;

        if ( '' === $value || null === $value ) {
            continue;
        }

        $recipient[ $key ] = $value;
    }

    if ( isset( $recipient['email'] ) ) {
        $recipient['email'] = sanitize_email( $recipient['email'] );
    }

    if ( isset( $recipient['gender'] ) ) {
        $gender = adfoin_rapidmail_normalize_gender( $recipient['gender'] );

        if ( $gender ) {
            $recipient['gender'] = $gender;
        } else {
            unset( $recipient['gender'] );
        }
    }

    if ( isset( $recipient['birthdate'] ) ) {
        $birthdate = adfoin_rapidmail_normalize_date( $recipient['birthdate'] );

        if ( $birthdate ) {
            $recipient['birthdate'] = $birthdate;
        } else {
            unset( $recipient['birthdate'] );
        }
    }

    return $recipient;
}

function adfoin_rapidmail_normalize_gender( $value ) {
    $value = strtolower( trim( (string) $value ) );

    if ( in_array( $value, array( 'male', 'm', 'man', 'mr', 'mr.', 'herr', 'männlich', 'maennlich' ), true ) ) {
        return 'male';
    }

    if ( in_array( $value, array( 'female', 'f', 'w', 'woman', 'mrs', 'mrs.', 'ms', 'ms.', 'frau', 'weiblich' ), true ) ) {
        return 'female';
    }

    return '';
}

function adfoin_rapidmail_normalize_date( $value ) {
    $value = trim( (string) $value );

    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
        return $value;
    }

    // d.m.Y (common in DE/AT/CH) and d/m/Y are read day first.
    if ( preg_match( '#^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$#', $value, $m ) && checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) ) {
        return sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
    }

    $time = strtotime( $value );

    return $time ? gmdate( 'Y-m-d', $time ) : '';
}

/**
 * Create or update a recipient in the selected list.
 *
 * @param array $extra Extra recipient data (Pro: tags, mailtype, foreign_id).
 *                     'tags' are merged with the recipient's existing tags.
 */
function adfoin_rapidmail_subscribe( $record, $posted_data, $data, $extra = array() ) {
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $optin   = isset( $data['optin'] ) ? $data['optin'] : '';

    $recipient = array_merge( adfoin_rapidmail_build_recipient( $data, $posted_data ), $extra );

    if ( empty( $recipient['email'] ) || empty( $list_id ) ) {
        return;
    }

    // Older integrations had a free-text Status field; respect it when no
    // Subscription option was saved.
    $legacy_status = isset( $recipient['status'] ) ? strtolower( $recipient['status'] ) : '';
    unset( $recipient['status'] );

    if ( 'doi' === $optin ) {
        $status = 'new';
    } elseif ( '' === $optin && 'new' === $legacy_status ) {
        $status = 'new';
    } else {
        $status = 'active';
    }

    $existing = adfoin_rapidmail_find_recipient( $recipient['email'], $list_id, $cred_id );

    if ( $existing ) {
        if ( isset( $recipient['tags'] ) && ! empty( $existing['tags'] ) ) {
            $recipient['tags'] = array_values( array_unique( array_merge( (array) $existing['tags'], $recipient['tags'] ) ) );
        }

        // Activate, never downgrade an existing recipient.
        if ( 'active' === $status && isset( $existing['status'] ) && 'active' !== $existing['status'] ) {
            $recipient['status'] = 'active';
        }

        return adfoin_rapidmail_request( 'recipients/' . $existing['id'], 'PATCH', $recipient, $record, $cred_id );
    }

    $recipient['recipientlist_id'] = $list_id;
    $recipient['status']           = $status;
    $endpoint                      = 'recipients';

    if ( 'doi' === $optin ) {
        $endpoint .= '?send_activationmail=yes';
    }

    return adfoin_rapidmail_request( $endpoint, 'POST', $recipient, $record, $cred_id );
}

/**
 * Recipient in the list (any status except deleted), or false.
 */
function adfoin_rapidmail_find_recipient( $email, $list_id, $cred_id ) {
    $endpoint = 'recipients?email=' . rawurlencode( $email ) . '&recipientlist_id=' . rawurlencode( $list_id );
    $response = adfoin_rapidmail_request( $endpoint, 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) && isset( $body['_embedded']['recipients'][0]['id'] ) ) {
        return $body['_embedded']['recipients'][0];
    }

    return false;
}

function adfoin_rapidmail_find_contact( $email, $list_id, $cred_id ) {
    $recipient = adfoin_rapidmail_find_recipient( $email, $list_id, $cred_id );

    return $recipient ? $recipient['id'] : false;
}
