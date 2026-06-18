<?php

add_filter( 'adfoin_action_providers', 'adfoin_freshsales_actions', 10, 1 );

function adfoin_freshsales_actions( $actions ) {

    $actions['freshsales'] = array(
        'title' => __( 'Freshworks CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'add_ocdna' => __( 'Create New Account, Contact, Deal', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_freshsales_settings_tab', 10, 1 );

function adfoin_freshsales_settings_tab( $providers ) {
    $providers['freshsales'] = __( 'Freshworks CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_freshsales_settings_view', 10, 1 );

function adfoin_freshsales_settings_view( $current_tab ) {
    if( $current_tab != 'freshsales' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'subdomain', 
            'label' => __( 'Subdomain', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'placeholder' => __( 'Enter your subdomain', 'advanced-form-integration' ),
            'show_in_table' => true
        ),
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p><p>%s</p>',
        __('The subdomain part in your URL, before myfreshworks.com (e.g., if URL is https://nasirahmed.myfreshworks.com, enter nasirahmed)', 'advanced-form-integration'),
        __('Click on your profile picture > Settings > API Settings tab > Copy the CRM API Key (not the chat API)', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'freshsales', __( 'Freshworks CRM', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_freshsales_credentials', 'adfoin_get_freshsales_credentials', 10, 0 );
/*
 * Get Freshsales credentials
 */
function adfoin_get_freshsales_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'freshsales' );
}

add_action( 'wp_ajax_adfoin_save_freshsales_credentials', 'adfoin_save_freshsales_credentials', 10, 0 );
/*
 * Save Freshsales credentials
 */
function adfoin_save_freshsales_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'freshsales', array( 'subdomain', 'apiKey' ) );
}

/*
 * Freshsales Credentials List
 */
function adfoin_freshsales_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'freshsales' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

// Legacy single-account import: surfaces old `adfoin_freshsales_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'freshsales', array(
            'subdomain' => 'adfoin_freshsales_subdomain',
            'apiKey' => 'adfoin_freshsales_api_key',
        ) );
    }
}, 20 );

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_freshsales_api_key', 'adfoin_save_freshsales_api_key', 10, 0 );

function adfoin_save_freshsales_api_key() {
    // Security Check
    // Authorization check
    adfoin_require_manage_options();

    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_freshsales_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key   = sanitize_text_field( wp_unslash( $_POST['adfoin_freshsales_api_key'] ) );
    $subdomain = sanitize_text_field( wp_unslash( $_POST['adfoin_freshsales_subdomain'] ) );

    // Save tokens
    update_option( 'adfoin_freshsales_api_key', $api_key );
    update_option( 'adfoin_freshsales_subdomain', $subdomain );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=freshsales' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_freshsales_js_fields', 10, 1 );

function adfoin_freshsales_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_freshsales_action_fields' );

function adfoin_freshsales_action_fields() {
    ?>
    <script type="text/template" id="freshsales-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_ocdna'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ocdna'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Freshsales Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=freshsales' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_ocdna', 'Freshworks CRM [PRO]', 'custom fields' ); ?>
        </table>
    </script>

    <?php
}

/*
 * Normalize whatever the user typed into the Subdomain field down to the bare
 * subdomain label. Accepts a full URL (https://acme.myfreshworks.com/...), a
 * bare host (acme.myfreshworks.com), or just the label (acme) — all resolve to
 * "acme". This prevents the #1 setup error (pasting the whole URL → broken
 * requests / empty field dropdowns).
 */
function adfoin_freshsales_normalize_subdomain( $raw ) {
    $raw = trim( (string) $raw );

    if ( '' === $raw ) {
        return '';
    }

    // Full URL → take the host.
    if ( false !== strpos( $raw, '://' ) ) {
        $host = wp_parse_url( $raw, PHP_URL_HOST );
        if ( $host ) {
            $raw = $host;
        }
    }

    // Strip any leftover path / slashes / spaces.
    $raw = preg_replace( '#[/\s].*$#', '', $raw );

    // If it still looks like a host (has a dot), keep the first label.
    if ( false !== strpos( $raw, '.' ) ) {
        $parts = explode( '.', $raw );
        $raw   = $parts[0];
    }

    return sanitize_text_field( $raw );
}

function adfoin_freshsales_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'freshsales', $cred_id );
    $subdomain = isset( $credentials['subdomain'] ) ? $credentials['subdomain'] : '';
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $subdomain ) || empty( $api_key ) ) {
        $subdomain = get_option( 'adfoin_freshsales_subdomain' );
        $api_key = get_option( 'adfoin_freshsales_api_key' );
    }

    $subdomain = adfoin_freshsales_normalize_subdomain( $subdomain );

    if( !$subdomain || !$api_key ) {
        return new WP_Error( 'freshsales_missing_credentials', __( 'Freshworks CRM: subdomain or API key is missing. Check Settings → Freshworks CRM.', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method' => $method,
        'headers' => array(
            'Authorization' => "Token token={$api_key}",
            'Content-Type'  => 'application/json'
        )
    );
    $base_url = "https://{$subdomain}.myfreshworks.com/crm/sales/api/";
    $url      = $base_url . $endpoint;

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    // Back off once on rate limiting (HTTP 429) honouring Retry-After.
    if ( ! is_wp_error( $response ) && 429 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
        $wait        = is_numeric( $retry_after ) ? max( 1, min( (int) $retry_after, 30 ) ) : 3;
        sleep( $wait );
        $response = wp_remote_request( $url, $args );
    }

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

/*
 * Readable error message from a Freshsales response for AJAX handlers, so a
 * wrong subdomain / API key shows a reason instead of an empty dropdown.
 */
function adfoin_freshsales_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) && ! empty( $body['errors']['message'] ) ) {
        $msg = is_array( $body['errors']['message'] ) ? implode( ' ', $body['errors']['message'] ) : $body['errors']['message'];
    } elseif ( is_array( $body ) && ! empty( $body['message'] ) ) {
        $msg = $body['message'];
    } else {
        $msg = '';
    }

    if ( 401 === $code || 403 === $code ) {
        $msg = trim( $msg . ' ' . __( 'Check your Freshworks subdomain and API key in Settings.', 'advanced-form-integration' ) );
    }

    if ( '' === $msg ) {
        $msg = $code ? sprintf( __( 'Freshworks API returned HTTP %d.', 'advanced-form-integration' ), $code ) : __( 'Could not reach Freshworks CRM.', 'advanced-form-integration' );
    }

    return $msg;
}

/*
 * Map of {field api_name => Freshsales type} for an entity, cached 12h per
 * credential. Used to coerce values to the type Freshsales expects.
 * $entity: 'sales_accounts' | 'contacts' | 'deals'.
 */
function adfoin_freshsales_field_types( $entity, $cred_id = '' ) {
    $cache_key = 'adfoin_fs_ftypes_' . md5( (string) $cred_id . '|' . $entity );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $types = array();
    $data  = adfoin_freshsales_request( "settings/{$entity}/fields", 'GET', array(), array(), $cred_id );

    if ( ! is_wp_error( $data ) && 200 === (int) wp_remote_retrieve_response_code( $data ) ) {
        $body = json_decode( wp_remote_retrieve_body( $data ), true );
        if ( ! empty( $body['fields'] ) && is_array( $body['fields'] ) ) {
            foreach ( $body['fields'] as $f ) {
                if ( isset( $f['name'], $f['type'] ) ) {
                    $types[ $f['name'] ] = $f['type'];
                }
            }
        }
    }

    set_transient( $cache_key, $types, 12 * HOUR_IN_SECONDS );

    return $types;
}

/*
 * Coerce a flat field array to the types Freshsales expects: date -> Y-m-d,
 * datetime -> ISO 8601, multi-select -> array, checkbox -> bool. Leaves text/
 * dropdown/number as-is (Freshsales accepts those as strings). Unknown fields
 * pass through unchanged.
 */
function adfoin_freshsales_coerce_fields( $fields, $types ) {
    if ( ! is_array( $fields ) ) {
        return $fields;
    }

    foreach ( $fields as $name => $value ) {
        if ( '' === $value || null === $value || is_array( $value ) ) {
            continue;
        }

        $type = isset( $types[ $name ] ) ? $types[ $name ] : '';

        if ( 'date' === $type ) {
            $dt = date_create( (string) $value );
            if ( $dt ) {
                $fields[ $name ] = $dt->format( 'Y-m-d' );
            }
        } elseif ( 'datetime' === $type || 'date_time' === $type ) {
            $dt = date_create( (string) $value, wp_timezone() );
            if ( $dt ) {
                $fields[ $name ] = $dt->format( 'c' );
            }
        } elseif ( 'multi_select_dropdown' === $type || 'multiselect' === $type ) {
            $fields[ $name ] = array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
        } elseif ( 'checkbox' === $type ) {
            $fields[ $name ] = in_array( strtolower( trim( (string) $value ) ), array( 'true', '1', 'yes', 'on', 'checked' ), true );
        }
    }

    return $fields;
}

function adfoin_freshsales_if_contact_exists( $email, $cred_id = '' ) {
    $contact_id = '';
    $endpoint   = 'search?q=' . rawurlencode( $email ) . '&include=contact';

    $data = adfoin_freshsales_request( $endpoint, 'GET', array(), array(), $cred_id );

    // Runs during form submission — never emit JSON / die here.
    if( is_wp_error( $data ) ) {
        return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $data ), true );

    if( isset( $body[0]['id'] ) ) {
        $contact_id = $body[0]['id'];
    }

    return $contact_id;
}

function adfoin_freshsales_if_account_exists( $name, $cred_id = '' ) {
    $account_id = '';
    $endpoint   = 'search?q=' . rawurlencode( $name ) . '&include=sales_account';

    $data = adfoin_freshsales_request( $endpoint, 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $data ), true );

    if( isset( $body[0]['id'] ) ) {
        $account_id = $body[0]['id'];
    }

    return $account_id;
}

add_action( 'wp_ajax_adfoin_get_freshsales_account_fields', 'adfoin_get_freshsales_account_fields', 10, 0 );

/*
 * Get Freshsales Account Fields
 */
function adfoin_get_freshsales_account_fields() {
    // Authorization + nonce check (capability is verified first).
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $ignore_list = array( 
        'parent_sales_account_id',
        'last_contacted',
        'last_contacted_mode', 
        'last_contacted_sales_activity_mode',
        'last_contacted_via_sales_activity',
        'active_sales_sequences',
        'completed_sales_sequences',
        'creater_id',
        'created_at',
        'updater_id',
        'updated_at',
        'last_assigned_at',
        'recent_note'
    );

    $data = adfoin_freshsales_request( 'settings/sales_accounts/fields', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) || 200 !== (int) wp_remote_retrieve_response_code( $data ) ) {
        wp_send_json_error( adfoin_freshsales_error_message( $data ) );
    }

    $body           = json_decode( wp_remote_retrieve_body( $data ) );
    $account_fields = array();

    if( ! isset( $body->fields ) || ! is_array( $body->fields ) ) {
        wp_send_json_error( adfoin_freshsales_error_message( $data ) );
    }

    foreach( $body->fields as $single ) {
        $description = '';

        if( !in_array( $single->name, $ignore_list ) ) {

            if( isset( $single->choices ) && !empty( $single->choices ) ) {
                $parts = array();
                foreach( $single->choices as $single_choice ) {
                    $parts[] = $single_choice->value . ': ' . $single_choice->id;
                }

                $description = implode( ', ', $parts );
            }

            array_push( $account_fields, array( 'key' => 'account_' . $single->name, 'value' => $single->label . ' [Account]', 'description' => $description ) );
        }
    }

    wp_send_json_success( $account_fields );
}

add_action( 'wp_ajax_adfoin_get_freshsales_contact_fields', 'adfoin_get_freshsales_contact_fields', 10, 0 );

/*
 * Get Freshsales Contact Fields
 */
function adfoin_get_freshsales_contact_fields() {
    // Authorization + nonce check (capability is verified first).
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $ignore_list = array( 
        'sales_accounts',
        'emails',
        'time_zone', 
        'phone_numbers',
        'campaign_id',
        'last_contacted',
        'last_contacted_mode',
        'last_contacted_sales_activity_mode',
        'last_contacted_via_sales_activity',
        'active_sales_sequences',
        'completed_sales_sequences',
        'last_seen',
        'customer_fit',
        'creater_id',
        'created_at',
        'updater_id',
        'updated_at',
        'web_form_ids',
        'last_assigned_at',
        'lost_reason_id',
        'contact_status_id',
        'recent_note'
    );

    $data = adfoin_freshsales_request( 'settings/contacts/fields', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) || 200 !== (int) wp_remote_retrieve_response_code( $data ) ) {
        wp_send_json_error( adfoin_freshsales_error_message( $data ) );
    }

    $body           = json_decode( wp_remote_retrieve_body( $data ) );
    $contact_fields = array(
        array( 'key' => 'contact_email', 'value' => 'Email [Contact]', 'description' => 'Required' )
    );

    if( ! isset( $body->fields ) || ! is_array( $body->fields ) ) {
        wp_send_json_error( adfoin_freshsales_error_message( $data ) );
    }

    foreach( $body->fields as $single ) {
        $description = '';

        if( !in_array( $single->name, $ignore_list ) ) {

            if( isset( $single->choices ) && !empty( $single->choices ) ) {
                $parts = array();
                foreach( $single->choices as $single_choice ) {
                    $parts[] = $single_choice->value . ': ' . $single_choice->id;
                }

                $description = implode( ', ', $parts );
            }

            if( $single->name == 'lists' ) {
                $lists = adfoin_freshsales_request( 'lists', 'GET', array(), array(), $cred_id );
                $lists = json_decode( wp_remote_retrieve_body( $lists ) );
                $string = array();

                foreach( $lists->lists as $list ) {
                    $string[] = $list->name . ': ' . $list->id;
                }

                $description = implode( ', ', $string );

            }

            array_push( $contact_fields, array( 'key' => 'contact_' . $single->name, 'value' => $single->label . ' [Contact]', 'description' => $description ) );
        }
    }

    wp_send_json_success( $contact_fields );
}

add_action( 'wp_ajax_adfoin_get_freshsales_deal_fields', 'adfoin_get_freshsales_deal_fields', 10, 0 );

/*
 * Get Freshsales Deal Fields
 */
function adfoin_get_freshsales_deal_fields() {
    // Authorization + nonce check (capability is verified first).
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $ignore_list = array( 
        'sales_account_id',
        'contacts',
        'deal_reason_id', 
        'closed_date',
        'campaign_id',
        'last_contacted_sales_activity_mode',
        'last_contacted_via_sales_activity',
        'active_sales_sequences',
        'completed_sales_sequences',
        'creater_id',
        'created_at',
        'updater_id',
        'updated_at',
        'web_form_ids',
        'upcoming_activities_time',
        'stage_updated_time',
        'last_assigned_at',
        'web_form_id',
        'recent_note'
    );

    $data = adfoin_freshsales_request( 'settings/deals/fields', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) || 200 !== (int) wp_remote_retrieve_response_code( $data ) ) {
        wp_send_json_error( adfoin_freshsales_error_message( $data ) );
    }

    $body        = json_decode( wp_remote_retrieve_body( $data ) );
    $deal_fields = array();

    if( ! isset( $body->fields ) || ! is_array( $body->fields ) ) {
        wp_send_json_error( adfoin_freshsales_error_message( $data ) );
    }

    foreach( $body->fields as $single ) {
        $description = '';

        if( !in_array( $single->name, $ignore_list ) ) {

            if( isset( $single->choices ) && !empty( $single->choices ) ) {
                $parts = array();
                foreach( $single->choices as $single_choice ) {
                    $parts[] = $single_choice->value . ': ' . $single_choice->id;
                }

                $description = implode( ', ', $parts );
            }

            array_push( $deal_fields, array( 'key' => 'deal_' . $single->name, 'value' => $single->label . ' [Deal]', 'description' => $description ) );
        }
    }

    wp_send_json_success( $deal_fields );
}

add_action( 'adfoin_freshsales_job_queue', 'adfoin_freshsales_job_queue', 10, 1 );

function adfoin_freshsales_job_queue( $data ) {
    adfoin_freshsales_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Freshsales API
 */
function adfoin_freshsales_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data       = $record_data['field_data'];
    $cred_id    = isset( $data['credId'] ) ? $data['credId'] : '';
    $task       = $record['task'];
    $account_id = '';
    $contact_id = '';
    $deal_id    = '';

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'freshsales' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'add_ocdna' ) {

        $holder                = array();
        $account_fields        = array();
        $account_custom_fields = array();
        $contact_fields        = array();
        $contact_custom_fields = array();
        $deal_fields           = array();
        $deal_custom_fields    = array();
        $account_id            = '';
        $contact_id            = '';
        $contact_lists         = array();

        foreach( $data as $key => $value ) {
            $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
        }

        foreach( $holder as $key => $value ) {
            if( substr( $key, 0, 8 ) == 'account_' && $value ) {
                $key = substr( $key, 8 );

                if( substr( $key, 0, 3 ) == 'cf_' ) {
                    $account_custom_fields[$key] = $value;
                    continue;
                }

                $account_fields[$key] = $value;
                continue;
            }

            if( substr( $key, 0, 8 ) == 'contact_' && $value ) {
                $key = substr( $key, 8 );

                if( substr( $key, 0, 3 ) == 'cf_' ) {
                    $contact_custom_fields[$key] = $value;
                    continue;
                }

                $contact_fields[$key] = $value;
                continue;
            }

            if( substr( $key, 0, 5 ) == 'deal_' && $value ) {
                $key = substr( $key, 5 );

                if( substr( $key, 0, 3 ) == 'cf_' ) {
                    $deal_custom_fields[$key] = $value;
                    continue;
                }

                $deal_fields[$key] = $value;
                continue;
            }
        }

        if( !empty( $account_fields ) ) {

            $account_types  = adfoin_freshsales_field_types( 'sales_accounts', $cred_id );
            $account_fields = adfoin_freshsales_coerce_fields( $account_fields, $account_types );

            if( !empty( $account_custom_fields ) ) {
                $account_fields['custom_field'] = adfoin_freshsales_coerce_fields( $account_custom_fields, $account_types );
            }

            $account_body = array(
                'sales_account' => $account_fields
            );

            // check if account exists
            if( isset( $account_fields['name'] ) && $account_fields['name'] ){
                $account_id = adfoin_freshsales_if_account_exists( $account_fields['name'], $cred_id );
            }

            if( $account_id ) {
                $account_response = adfoin_freshsales_request( 'sales_accounts/' . $account_id, 'PUT', $account_body, $record, $cred_id );
            } else{
                $account_response = adfoin_freshsales_request( 'sales_accounts', 'POST', $account_body, $record, $cred_id );
            }
            
            $account_return = json_decode( wp_remote_retrieve_body( $account_response ) );

            if( 200 === (int) wp_remote_retrieve_response_code( $account_response ) && isset( $account_return->sales_account->id ) ) {
                $account_id = $account_return->sales_account->id;
            }
        }

        if( !empty( $contact_fields ) ) {

            if( $account_id ) {
                $contact_fields['sales_accounts'] = array(
                    array(
                        'id'         => $account_id,
                        'is_primary' => true
                    )
                );
            }

            if( !empty( $contact_fields['lists'] ) ) {
                $contact_lists = explode( ',', $contact_fields['lists'] );
                unset( $contact_fields['lists'] );
            }

            $contact_types  = adfoin_freshsales_field_types( 'contacts', $cred_id );
            $contact_fields = adfoin_freshsales_coerce_fields( $contact_fields, $contact_types );

            if( !empty( $contact_custom_fields ) ) {
                $contact_fields['custom_field'] = adfoin_freshsales_coerce_fields( $contact_custom_fields, $contact_types );
            }

            $contact_body = array(
                'contact' => $contact_fields
            );

            if( isset( $contact_fields['email'] ) && $contact_fields['email'] ){
                $contact_id = adfoin_freshsales_if_contact_exists( $contact_fields['email'], $cred_id );

                // Merge tags only for an EXISTING contact (so we append instead
                // of overwrite). For a new contact the comma string is sent as-is.
                // Guard every step — a missing/failed lookup must not fatal.
                if( $contact_id && isset( $contact_body['contact']['tags'] ) && $contact_body['contact']['tags'] ){
                    $tags             = explode( ',', $contact_body['contact']['tags'] );
                    $existing_response = adfoin_freshsales_request( 'contacts/' . $contact_id, 'GET', array(), array(), $cred_id );

                    if( ! is_wp_error( $existing_response ) ) {
                        $existing_body = json_decode( wp_remote_retrieve_body( $existing_response ), true );
                        $existing_tags = ( isset( $existing_body['contact']['tags'] ) && is_array( $existing_body['contact']['tags'] ) ) ? $existing_body['contact']['tags'] : array();
                        $tags          = array_unique( array_merge( $tags, $existing_tags ) );
                    }

                    $contact_body['contact']['tags'] = implode( ',', $tags );
                }
            }

            if( $contact_id ) {
                $contact_response = adfoin_freshsales_request( 'contacts/' . $contact_id, 'PUT', $contact_body, $record, $cred_id );
            } else{
                $contact_response = adfoin_freshsales_request( 'contacts', 'POST', $contact_body, $record, $cred_id );
            }

            
            $contact_body = json_decode( wp_remote_retrieve_body( $contact_response ) );

            if( 200 === (int) wp_remote_retrieve_response_code( $contact_response ) && isset( $contact_body->contact->id ) ) {
                $contact_id = $contact_body->contact->id;
            }

            if( !empty( $contact_lists ) && $contact_id ) {
                foreach( $contact_lists as $list ) {
                    $list_body = array(
                        'ids' => array( $contact_id )
                    );

                    $list_response = adfoin_freshsales_request( 'lists/' . trim( $list ) . '/add_contacts', 'PUT', $list_body, $record, $cred_id );
                }
            }
        }

        if( !empty( $deal_fields ) ) {

            $deal_types  = adfoin_freshsales_field_types( 'deals', $cred_id );
            $deal_fields = adfoin_freshsales_coerce_fields( $deal_fields, $deal_types );

            if ( $account_id ) {
                $deal_fields['sales_account_id'] = $account_id;
            }

            if( $contact_id ) {
                $deal_fields['contacts_added_list'] = array( $contact_id );
            }

            if( !empty( $deal_custom_fields ) ) {
                $deal_fields['custom_field'] = adfoin_freshsales_coerce_fields( $deal_custom_fields, $deal_types );
            }

            $body = array(
                'deal' => $deal_fields
            );

            $response = adfoin_freshsales_request( 'deals', 'POST', $body, $record, $cred_id );
            $body     = json_decode( wp_remote_retrieve_body( $response ) );

            if( 200 === (int) wp_remote_retrieve_response_code( $response ) && isset( $body->deal->id ) ) {
                $deal_id = $body->deal->id;
            }
        }
    }
    return;
}