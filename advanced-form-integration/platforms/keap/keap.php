<?php

add_filter( 'adfoin_action_providers', 'adfoin_keap_actions', 10, 1 );

function adfoin_keap_actions( $actions ) {
    $actions['keap'] = array(
        'title' => __( 'Keap', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create / Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_keap_settings_tab', 10, 1 );

function adfoin_keap_settings_tab( $providers ) {
    $providers['keap'] = __( 'Keap', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_keap_settings_view', 10, 1 );

function adfoin_keap_settings_view( $current_tab ) {
    if ( 'keap' !== $current_tab ) {
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
            'placeholder'   => __( 'Service Account Key or Personal Access Token', 'advanced-form-integration' ),
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
        esc_html__( 'Generate a Service Account Key (recommended) or Personal Access Token', 'advanced-form-integration' ),
        esc_html__( 'Sign in to Keap and open Settings → Integrations → API.', 'advanced-form-integration' ),
        esc_html__( 'Click "Generate Service Account Key" and give it a recognizable name.', 'advanced-form-integration' ),
        esc_html__( 'Copy the key value — Keap will only show it once.', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the key into the field above and save the settings.', 'advanced-form-integration' ),
        esc_html__( 'Use the "Test Connection" button in the table to confirm the key works against your Keap app.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends every request to https://api.infusionsoft.com/crm/rest/v2/ with the X-Keap-API-Key header.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Keap [PRO] to push custom contact fields, apply or remove tags, and add notes to a contact.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view(
        'keap',
        __( 'Keap', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action( 'wp_ajax_adfoin_get_keap_credentials', 'adfoin_get_keap_credentials', 10, 0 );

function adfoin_get_keap_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'keap' );
}

add_action( 'wp_ajax_adfoin_save_keap_credentials', 'adfoin_save_keap_credentials', 10, 0 );

function adfoin_save_keap_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    ADFOIN_Account_Manager::ajax_save_credentials( 'keap', array(
        'apiKey' => 'password',
    ) );
}

/**
 * Connection-status helpers. Stored in a separate option keyed by cred_id
 * so we never have to mutate the credential record itself.
 */
if ( ! function_exists( 'adfoin_keap_connection_status' ) ) :
function adfoin_keap_connection_status() {
    $status = get_option( 'adfoin_keap_connection_status', array() );
    return is_array( $status ) ? $status : array();
}
endif;

if ( ! function_exists( 'adfoin_keap_mark_connection_failed' ) ) :
function adfoin_keap_mark_connection_failed( $cred_id, $reason = '' ) {
    if ( ! $cred_id ) {
        return;
    }
    $status             = adfoin_keap_connection_status();
    $status[ $cred_id ] = array(
        'failed'     => true,
        'reason'     => (string) $reason,
        'updated_at' => time(),
    );
    update_option( 'adfoin_keap_connection_status', $status, false );
}
endif;

if ( ! function_exists( 'adfoin_keap_mark_connection_ok' ) ) :
function adfoin_keap_mark_connection_ok( $cred_id ) {
    if ( ! $cred_id ) {
        return;
    }
    $status = adfoin_keap_connection_status();
    if ( isset( $status[ $cred_id ] ) ) {
        unset( $status[ $cred_id ] );
        update_option( 'adfoin_keap_connection_status', $status, false );
    }
}
endif;

/**
 * Central HTTP helper for every Keap v2 call.
 *
 * @param string $endpoint    Endpoint path relative to /rest/v2/ (no leading slash).
 * @param string $method      HTTP method.
 * @param array  $data        JSON-encoded body for write methods.
 * @param array  $record      Integration record (enables logging + 429 retry).
 * @param string $cred_id     Account Manager credential id.
 * @param array  $query_args  Query string params.
 */
if ( ! function_exists( 'adfoin_keap_request' ) ) :
function adfoin_keap_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $query_args = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'keap', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Legacy single-account fallback so existing installs keep working.
    if ( ! $api_key ) {
        $api_key = (string) get_option( 'adfoin_keap_api_key', '' );
    }

    if ( ! $api_key ) {
        return new WP_Error(
            'adfoin_keap_missing_credentials',
            __( 'Keap API key is missing.', 'advanced-form-integration' )
        );
    }

    $url = 'https://api.infusionsoft.com/crm/rest/v2/' . ltrim( $endpoint, '/' );

    if ( ! empty( $query_args ) ) {
        $url = add_query_arg( array_map( 'rawurlencode', $query_args ), $url );
    }

    $version = defined( 'ADVANCED_FORM_INTEGRATION_VERSION' ) ? ADVANCED_FORM_INTEGRATION_VERSION : 'dev';

    $args = array(
        'method'      => strtoupper( $method ),
        'timeout'     => 30,
        'sslverify'   => true,
        'redirection' => 0,
        'user-agent'  => 'AdvancedFormIntegration/' . $version . '; +' . home_url(),
        'headers'     => array(
            'Content-Type'   => 'application/json',
            'Accept'         => 'application/json',
            'X-Keap-API-Key' => $api_key,
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( ! is_wp_error( $response ) ) {
        $code = (int) wp_remote_retrieve_response_code( $response );

        if ( 401 === $code || 403 === $code ) {
            adfoin_keap_mark_connection_failed( $cred_id, 'unauthorized' );
        } elseif ( 429 === $code && $record && function_exists( 'as_schedule_single_action' ) ) {
            as_schedule_single_action(
                time() + 60,
                'adfoin_keap_job_queue',
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
            adfoin_keap_mark_connection_ok( $cred_id );
        }
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_keap_extract_error' ) ) :
function adfoin_keap_extract_error( $response, $fallback = '' ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) ) {
        if ( ! empty( $body['message'] ) ) {
            return (string) $body['message'];
        }
        if ( ! empty( $body['error'] ) ) {
            return is_string( $body['error'] ) ? $body['error'] : wp_json_encode( $body['error'] );
        }
    }

    if ( $fallback ) {
        return $fallback;
    }

    /* translators: %d: HTTP status code */
    return sprintf( __( 'Keap returned HTTP %d.', 'advanced-form-integration' ), $code );
}
endif;

/**
 * Test-connection endpoint surfaced to the credentials table.
 */
add_action( 'wp_ajax_adfoin_test_keap_connection', 'adfoin_test_keap_connection' );

function adfoin_test_keap_connection() {
    adfoin_require_manage_options();

    adfoin_verify_nonce();

    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    // /rest/v2/contacts/model is cheap, read-only, and requires auth.
    $response = adfoin_keap_request( 'contacts/model', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $status ) {
        wp_send_json_error( array(
            'message' => adfoin_keap_extract_error( $response ),
            'status'  => $status,
        ) );
    }

    wp_send_json_success( array(
        'message' => __( 'Connected to Keap.', 'advanced-form-integration' ),
    ) );
}

add_action( 'adfoin_add_js_fields', 'adfoin_keap_js_fields', 10, 1 );

function adfoin_keap_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_keap_action_fields' );

function adfoin_keap_action_fields() {
    ?>
    <script type="text/template" id="keap-action-template">
        <table class="form-table">
            <tr class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Keap Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select account…', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=keap' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Duplicate Check', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[duplicateOption]" v-model="fielddata.duplicateOption">
                        <option value="Email"><?php esc_html_e( 'Email', 'advanced-form-integration' ); ?></option>
                        <option value="EmailAndName"><?php esc_html_e( 'Email + Name', 'advanced-form-integration' ); ?></option>
                        <option value="EmailAndNameAndCompany"><?php esc_html_e( 'Email + Name + Company', 'advanced-form-integration' ); ?></option>
                        <option value=""><?php esc_html_e( 'Always create (no dedupe)', 'advanced-form-integration' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Existing contact matched by this strategy will be updated. Leave on "Email" for typical lead capture.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_contact', 'Keap [PRO]', 'custom fields, tags and notes' ); ?>

            <tr class="alternate" v-if="action.task == 'add_contact'">
                <th scope="row"><?php esc_html_e( 'Need custom fields, tags, or notes?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: pricing page URL */
                                __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Keap [PRO]</a> to push custom contact fields, apply or remove tags, and add notes to a contact.', 'advanced-form-integration' ),
                                esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) )
                            ),
                            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
                        );
                    ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_keap_job_queue', 'adfoin_keap_job_queue', 10, 1 );

function adfoin_keap_job_queue( $data ) {
    adfoin_keap_send_data( $data['record'], $data['posted_data'] );
}

/**
 * Build and POST a contact to /rest/v2/contacts.
 */
function adfoin_keap_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return null;
    }

    $data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_contact' !== $task ) {
        return null;
    }

    $cred_id          = isset( $data['credId'] ) ? $data['credId'] : '';
    $duplicate_option = isset( $data['duplicateOption'] ) ? $data['duplicateOption'] : 'Email';

    $get = function ( $key ) use ( $data, $posted_data ) {
        if ( empty( $data[ $key ] ) ) {
            return '';
        }
        return trim( (string) adfoin_get_parsed_values( $data[ $key ], $posted_data ) );
    };

    $email   = $get( 'email' );
    $email2  = $get( 'email2' );
    $email3  = $get( 'email3' );
    $optin   = $get( 'optin' );

    // v2: a contact must contain at least one email_addresses OR phone_numbers entry.
    $body = array();

    // Names + identity.
    foreach ( array(
        'prefix'           => 'prefix',
        'firstName'        => 'given_name',
        'middleName'       => 'middle_name',
        'lastName'         => 'family_name',
        'suffix'           => 'suffix',
        'preferredName'    => 'preferred_name',
        'contactType'      => 'contact_type',
        'jobTitle'         => 'job_title',
        'website'          => 'website',
        'birthDate'        => 'birth_date',
        'anniversaryDate'  => 'anniversary_date',
        'spouseName'       => 'spouse_name',
        'leadsourceId'     => 'leadsource_id',
        'ownerId'          => 'owner_id',
        'timeZone'         => 'time_zone',
        'preferredLocale'  => 'preferred_locale',
    ) as $local => $remote ) {
        $value = $get( $local );
        if ( '' !== $value ) {
            $body[ $remote ] = $value;
        }
    }

    // Legacy field names (birthday/anniversary) fall back to the v2 keys when
    // they're explicitly mapped by older saved integrations.
    if ( empty( $body['birth_date'] ) ) {
        $legacy = $get( 'birthday' );
        if ( '' !== $legacy ) {
            $body['birth_date'] = $legacy;
        }
    }
    if ( empty( $body['anniversary_date'] ) ) {
        $legacy = $get( 'anniversary' );
        if ( '' !== $legacy ) {
            $body['anniversary_date'] = $legacy;
        }
    }

    // Company: v2 BasicCompany accepts {company_name}. Keap will reuse an
    // existing company record with the same name automatically.
    $company = $get( 'company' );
    if ( '' !== $company ) {
        $body['company'] = array( 'company_name' => $company );
    }

    // Email addresses.
    if ( $email || $email2 || $email3 ) {
        $body['email_addresses'] = array();

        if ( $email ) {
            $entry = array( 'email' => $email, 'field' => 'EMAIL1' );
            if ( '' !== $optin ) {
                $truthy = in_array( strtolower( $optin ), array( '1', 'true', 'yes', 'on' ), true );
                if ( $truthy ) {
                    $entry['opt_in_reason'] = __( 'User opted in via form submission', 'advanced-form-integration' );
                }
            }
            $body['email_addresses'][] = $entry;
        }
        if ( $email2 ) {
            $body['email_addresses'][] = array( 'email' => $email2, 'field' => 'EMAIL2' );
        }
        if ( $email3 ) {
            $body['email_addresses'][] = array( 'email' => $email3, 'field' => 'EMAIL3' );
        }
    }

    // Phone numbers.
    $phone_map = array(
        'mobilePhone' => array( 'field' => 'PHONE1', 'type' => 'Mobile' ),
        'workPhone'   => array( 'field' => 'PHONE2', 'type' => 'Work' ),
        'homePhone'   => array( 'field' => 'PHONE3', 'type' => 'Home' ),
    );
    foreach ( $phone_map as $local => $meta ) {
        $value = $get( $local );
        if ( '' !== $value ) {
            $body['phone_numbers'][] = array(
                'field'  => $meta['field'],
                'type'   => $meta['type'],
                'number' => $value,
            );
        }
    }

    // Addresses.
    $address_pairs = array(
        'BILLING'  => 'billing',
        'SHIPPING' => 'shipping',
    );
    foreach ( $address_pairs as $field_enum => $prefix ) {
        $line1        = $get( $prefix . 'Street1' );
        $line2        = $get( $prefix . 'Street2' );
        $locality     = $get( $prefix . 'City' );
        $region       = $get( $prefix . 'State' );
        $region_code  = $get( $prefix . 'RegionCode' );
        $postal_code  = $get( $prefix . 'Zip' );
        $country_code = $get( $prefix . 'CountryCode' );

        if ( $line1 || $locality || $postal_code || $region || $region_code || $country_code ) {
            $address = array( 'field' => $field_enum );
            if ( $line1 ) {
                $address['line1'] = $line1;
            }
            if ( $line2 ) {
                $address['line2'] = $line2;
            }
            if ( $locality ) {
                $address['locality'] = $locality;
            }
            if ( $region_code ) {
                $address['region_code'] = $region_code;
            } elseif ( $region ) {
                // Legacy "Billing State" string falls back to the deprecated region key.
                $address['region'] = $region;
            }
            if ( $postal_code ) {
                $address['postal_code'] = $postal_code;
            }
            if ( $country_code ) {
                $address['country_code'] = $country_code;
            }
            $body['addresses'][] = $address;
        }
    }

    // Social accounts — v2 enum is UPPERCASE_WITH_UNDERSCORES.
    $social_map = array(
        'facebook'  => 'FACEBOOK',
        'linkedin'  => 'LINKED_IN',
        'twitter'   => 'TWITTER',
        'instagram' => 'INSTAGRAM',
    );
    foreach ( $social_map as $local => $enum ) {
        $value = $get( $local );
        if ( '' !== $value ) {
            $body['social_accounts'][] = array(
                'name' => $value,
                'type' => $enum,
            );
        }
    }

    // Bail if we have nothing the v2 contact endpoint will accept.
    if ( empty( $body['email_addresses'] ) && empty( $body['phone_numbers'] ) ) {
        return null;
    }

    /**
     * Filter the v2 contact body before sending to Keap. Pro adds custom_fields
     * via this hook.
     *
     * @param array $body        CreateUpdateContactRequest payload.
     * @param array $data        Raw field_data map.
     * @param array $posted_data Form submission values.
     * @param array $record      Integration record.
     */
    $body = apply_filters( 'adfoin_keap_contact_body', $body, $data, $posted_data, $record );

    $query = array();
    if ( $duplicate_option && in_array( $duplicate_option, array( 'Email', 'EmailAndName', 'EmailAndNameAndCompany' ), true ) ) {
        $query['duplicate_option'] = $duplicate_option;
    }

    $response = adfoin_keap_request( 'contacts', 'POST', $body, $record, $cred_id, $query );

    /**
     * Fires after the v2 contact create/update request completes. Pro uses
     * this to apply tags or add a note in the same shot.
     *
     * @param mixed $response    wp_remote_request response (array) or WP_Error.
     * @param array $body        Sent CreateUpdateContactRequest body.
     * @param array $data        Raw field_data map.
     * @param array $posted_data Form submission values.
     * @param array $record      Integration record.
     * @param string $cred_id    Account Manager credential id.
     */
    do_action( 'adfoin_keap_after_contact', $response, $body, $data, $posted_data, $record, $cred_id );

    return $response;
}
