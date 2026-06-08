<?php

add_filter( 'adfoin_action_providers', 'adfoin_hubspot_actions', 10, 1 );

function adfoin_hubspot_actions( $actions ) {

    $actions['hubspot'] = array(
        'title' => __( 'Hubspot CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create New Contact', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_hubspot_settings_tab', 10, 1 );

function adfoin_hubspot_settings_tab( $providers ) {
    $providers['hubspot'] = __( 'Hubspot CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_hubspot_settings_view', 10, 1 );

function adfoin_hubspot_settings_view( $current_tab ) {
    if( $current_tab != 'hubspot' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'accessToken', 
            'label' => __( 'Access Token', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your Access Token', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Settings > Integrations > Private Apps > Create a private app > Scopes tab > Select required scopes > Create > Show token and copy', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'hubspot', __( 'HubSpot CRM', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_hubspot_credentials', 'adfoin_get_hubspot_credentials', 10, 0 );
/*
 * Get HubSpot credentials
 */
function adfoin_get_hubspot_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'hubspot' );
}

add_action( 'wp_ajax_adfoin_save_hubspot_credentials', 'adfoin_save_hubspot_credentials', 10, 0 );
/*
 * Save HubSpot credentials
 */
function adfoin_save_hubspot_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'hubspot', array( 'accessToken' ) );
}

/*
 * HubSpot Credentials List
 */
function adfoin_hubspot_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'hubspot' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

// Legacy single-account import: surfaces old `adfoin_hubspot_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'hubspot', array(
            'accessToken' => array( 'adfoin_hubspot_access_token', 'adfoin_hubspot_api_token' ),
        ) );
    }
}, 20 );

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_hubspot_access_token', 'adfoin_save_hubspot_access_token', 10, 0 );

function adfoin_save_hubspot_access_token() {
    // Security Check
    // Authorization check
    adfoin_require_manage_options();

    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_hubspot_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $access_token = sanitize_text_field( wp_unslash( $_POST["adfoin_hubspot_access_token"] ) );

    // Save tokens
    update_option( "adfoin_hubspot_access_token", $access_token );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=hubspot" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_hubspot_js_fields', 10, 1 );

function adfoin_hubspot_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_hubspot_action_fields', 10, 1 );

function adfoin_hubspot_action_fields() {
    ?>
    <script type="text/template" id="hubspot-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'HubSpot Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=hubspot' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_contact', 'Hubspot CRM [PRO]', 'custom objects and deals' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_hubspot_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $retry_count = 0 ) {
    $credentials = adfoin_get_credentials_by_id( 'hubspot', $cred_id );
    $access_token = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $access_token ) ) {
        $access_token = get_option( 'adfoin_hubspot_access_token' );
        if( empty( $access_token ) ) {
            $access_token = get_option( 'adfoin_hubspot_api_token' );
        }
    }

    if( !$access_token ) {
        return array();
    }

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json'
        )
    );

    $base_url = 'https://api.hubapi.com/crm/v3/';
    $url      = $base_url . $endpoint;

    $args['headers']['Authorization'] = 'Bearer ' . $access_token;

    if( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    // Retry on 429 (rate limited), honouring Retry-After, bounded to 2 retries.
    // HubSpot enforces strict per-second/burst limits; high-volume forms hit
    // them and previously failed with no retry.
    if ( ! is_wp_error( $response ) && 429 == (int) wp_remote_retrieve_response_code( $response ) && $retry_count < 2 ) {
        $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
        $delay       = $retry_after ? min( (int) $retry_after, 10 ) : ( $retry_count + 1 ) * 2;
        sleep( $delay );

        return adfoin_hubspot_request( $endpoint, $method, $data, $record, $cred_id, $retry_count + 1 );
    }

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_hubspot_contact_fields', 'adfoin_get_hubspot_contact_fields', 10, 0 );

/*
 * Get HubSpot Contact Fields
 */
function adfoin_get_hubspot_contact_fields() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $contact_fields = array();
    $data           = adfoin_hubspot_request( 'properties/contacts', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $data ) );

    if( isset( $body->results ) && is_array( $body->results ) ) {
        foreach( $body->results as $single ) {
            if( false == $single->modificationMetadata->readOnlyValue ) {
                $description = $single->description;

                // Tell the user the expected date format so a locale string like
                // DD/MM/YYYY (which HubSpot mis-parses) isn't mapped to a date
                // property by mistake.
                if( isset( $single->type ) && 'date' == $single->type ) {
                    $description .= ' ' . __( '(Date — use YYYY-MM-DD)', 'advanced-form-integration' );
                } elseif( isset( $single->type ) && 'datetime' == $single->type ) {
                    $description .= ' ' . __( '(Date & time — ISO 8601, e.g. 2026-12-25T10:00:00Z)', 'advanced-form-integration' );
                }

                if( $single->options ) {
                    if( is_array( $single->options ) ) {
                        $description .= " Possible values are: ";
                        $values = wp_list_pluck( $single->options, 'value' );
                        $description .= implode( ' | ', $values );
                    }
                }

                array_push( $contact_fields, array( 'key' => $single->name, 'value' => $single->label, 'description' => $description ) );
            }
        }
    }

    wp_send_json_success( $contact_fields );
}

add_action( 'adfoin_hubspot_job_queue', 'adfoin_hubspot_job_queue', 10, 1 );

function adfoin_hubspot_job_queue( $data ) {
    adfoin_hubspot_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Hubspot API
 */
function adfoin_hubspot_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data = $record_data["field_data"];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record["task"];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'hubspot' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    // Suppress a duplicate fire of the same submission (form auto-save, a
    // double-clicked submit, or a hook firing twice) within a short window.
    if ( adfoin_hubspot_is_duplicate_submission( $record, $posted_data ) ) {
        return;
    }

    if( $task == "add_contact" ) {

        $holder = array();

        if( $data ) {
            foreach( $data as $key => $value ) {
                // Skip credId - it's for internal use only, not a HubSpot property
                if( $key === 'credId' ) {
                    continue;
                }
                $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        $response = adfoin_hubspot_upsert_contact( $holder, $record, $cred_id );
    }

    return;
}

/**
 * Create or update a contact atomically by email using the v3 batch upsert
 * (idProperty=email). This replaces the previous search-then-create/PATCH flow,
 * which raced because HubSpot's search index is eventually consistent — a rapid
 * re-submit (auto-save, retry) could search-miss the just-created contact and
 * then 409, or duplicate. With no email to dedupe on, fall back to a plain create.
 */
function adfoin_hubspot_upsert_contact( $holder, $record, $cred_id ) {
    $email      = isset( $holder['email'] ) ? $holder['email'] : '';
    $properties = array_filter( $holder );

    if ( $email ) {
        $body = array(
            'inputs' => array(
                array(
                    'idProperty' => 'email',
                    'id'         => $email,
                    'properties' => $properties,
                ),
            ),
        );

        return adfoin_hubspot_request( 'objects/contacts/batch/upsert', 'POST', $body, $record, $cred_id );
    }

    return adfoin_hubspot_request( 'objects/contacts', 'POST', array( 'properties' => $properties ), $record, $cred_id );
}

/**
 * Idempotency guard for HubSpot submissions. Returns true when an identical
 * submission for the same integration was seen within the window. Backed by an
 * atomic add_option lock (UNIQUE option_name), so concurrent jobs are handled.
 * Filterable via `adfoin_hubspot_dedupe`.
 */
function adfoin_hubspot_is_duplicate_submission( $record, $posted_data ) {
    if ( ! apply_filters( 'adfoin_hubspot_dedupe', true, $record, $posted_data ) ) {
        return false;
    }

    $id = isset( $record['id'] ) ? (string) $record['id'] : '';
    if ( '' === $id ) {
        return false;
    }

    $key = 'adfoin_hsdup_' . md5( $id . '|' . wp_json_encode( $posted_data ) );
    $now = time();
    $ttl = 120;

    if ( add_option( $key, (string) ( $now + $ttl ), '', 'no' ) ) {
        return false; // first time — not a duplicate
    }

    $expires = (int) get_option( $key, 0 );
    if ( $expires > 0 && $now > $expires ) {
        update_option( $key, (string) ( $now + $ttl ), 'no' );
        return false; // stale lock taken over
    }

    return true; // identical submission within the window
}

function adfoin_hubspot_contact_exists( $email, $cred_id = '' ) {

    $data = array(
        'filterGroups' => array(
            array(
                'filters' => array(
                    array(
                        'value' => $email,
                        'propertyName' => 'email',
                        'operator' => 'EQ'
                    )
                )
            )
        )
    );

    $result = adfoin_hubspot_request( 'objects/contacts/search', 'POST', $data, array(), $cred_id );
    
    if( 200 == wp_remote_retrieve_response_code( $result ) ) {
        $body = json_decode( wp_remote_retrieve_body( $result ), true );

        if( isset( $body['total'], $body['results'][0]['id'] ) && $body['total'] > 0 ) {
            return $body['results'][0]['id'];
        }
    }

    return false;
}