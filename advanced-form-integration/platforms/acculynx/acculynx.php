<?php

add_filter( 'adfoin_action_providers', 'adfoin_acculynx_actions', 10, 1 );
function adfoin_acculynx_actions( $actions ) {
    $actions['acculynx'] = array(
        'title' => 'AccuLynx',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_acculynx_settings_tab', 10, 1 );
function adfoin_acculynx_settings_tab( $providers ) {
    $providers['acculynx'] = 'AccuLynx';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_acculynx_settings_view', 10, 1 );
function adfoin_acculynx_settings_view( $current_tab ) {
    if ( $current_tab !== 'acculynx' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'acculynx',
        'fields'   => array(
            array( 'key' => 'apiKey',        'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'contactTypeId', 'label' => __( 'Default Contact Type ID (optional)', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In AccuLynx, go to Settings > API Access to generate an API key. The Contact Type and Lead Source for new leads are picked in each integration; the Default Contact Type ID here is only a fallback for older integrations.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'AccuLynx', 'advanced-form-integration' ), 'acculynx', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_acculynx_action_fields' );
function adfoin_acculynx_action_fields() {
    ?>
    <script type="text/template" id="acculynx-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'AccuLynx Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_acculynx_credentials_list(); ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=acculynx' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'create_lead' && fielddata.credId">
                <td scope="row-title"><label><?php esc_attr_e( 'Contact Type', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[contactTypeId]" v-model="fielddata.contactTypeId">
                        <option value=""><?php esc_html_e( 'Account default', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in options.contactTypes" :value="id">{{ name }}</option>
                    </select>
                    <p class="description" v-if="optionsError" style="color: #b32d2e;">{{ optionsError }}</p>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead' && fielddata.credId">
                <td scope="row-title"><label><?php esc_attr_e( 'Lead Source', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[leadSourceId]" v-model="fielddata.leadSourceId">
                        <option value=""><?php esc_html_e( 'None', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in options.leadSources" :value="id">{{ name }}</option>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'AccuLynx [PRO]', 'job category, work type, trade, priority, custom fields, insurance, sales owner and appointment' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_acculynx_credentials', 'adfoin_get_acculynx_credentials' );
function adfoin_get_acculynx_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'acculynx' ) );
}

add_action( 'wp_ajax_adfoin_save_acculynx_credentials', 'adfoin_save_acculynx_credentials' );
function adfoin_save_acculynx_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'acculynx' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'acculynx', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_acculynx_fields', 'adfoin_get_acculynx_fields' );
function adfoin_get_acculynx_fields() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_acculynx_base_fields() );
}

/*
 * Fields of the free action; the PRO action adds to these.
 */
function adfoin_acculynx_base_fields() {
    return array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'companyName', 'value' => 'Company Name', 'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => 'Each submission creates a new contact (AccuLynx can\'t match contacts by email).' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '10-digit number; formatting and a leading +1 are removed.' ),
        array( 'key' => 'mobile',      'value' => 'Mobile',     'description' => '10-digit number.' ),
        array( 'key' => 'street',      'value' => 'Street',     'description' => '' ),
        array( 'key' => 'street2',     'value' => 'Street Line 2', 'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',      'description' => 'Name or code, e.g. Texas or TX.' ),
        array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',     'value' => 'Country',    'description' => 'Name or code, e.g. US. Default: United States.' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source (text)', 'description' => 'Matched to an AccuLynx lead source by name when none is picked above; otherwise added to the job notes.' ),
        array( 'key' => 'jobType',     'value' => 'Job Type',   'description' => 'Added to the job notes.' ),
        array( 'key' => 'damageType',  'value' => 'Damage Type','description' => 'Added to the job notes.' ),
        array( 'key' => 'insuranceCarrier','value' => 'Insurance Carrier', 'description' => 'Added to the job notes.' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => 'Job notes (max 1000 characters).' ),
    );
}

/*
 * Dropdown options for the editor: contact types and lead sources (free),
 * plus job settings and job custom fields when $_POST['pro'] is set.
 */
add_action( 'wp_ajax_adfoin_get_acculynx_options', 'adfoin_get_acculynx_options' );
function adfoin_get_acculynx_options() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists   = array(
        'contactTypes' => array( 'contacts/contact-types', 'id', 'name' ),
        'leadSources'  => array( 'company-settings/leads/lead-sources', 'id', 'name' ),
    );

    if ( ! empty( $_POST['pro'] ) ) {
        $lists['jobCategories'] = array( 'company-settings/job-file-settings/job-categories', 'id', 'name' );
        $lists['workTypes']     = array( 'company-settings/job-file-settings/work-types', 'id', 'name' );
        $lists['tradeTypes']    = array( 'company-settings/job-file-settings/trade-types', 'tradeId', 'name' );
    }

    $options = array();

    foreach ( $lists as $name => $list ) {
        $items = adfoin_acculynx_collection( $list[0], $cred_id );

        if ( is_wp_error( $items ) ) {
            wp_send_json_error( sprintf( __( 'Could not load AccuLynx settings: %s', 'advanced-form-integration' ), $items->get_error_message() ) );
        }

        $options[ $name ] = array();

        foreach ( $items as $item ) {
            if ( ! isset( $item[ $list[1] ], $item[ $list[2] ] ) ) continue;

            $options[ $name ][ $item[ $list[1] ] ] = $item[ $list[2] ];

            // Child lead sources, shown under their parent.
            foreach ( isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array() as $child ) {
                if ( isset( $child['id'], $child['name'] ) ) {
                    $options[ $name ][ $child['id'] ] = $item['name'] . ' › ' . $child['name'];
                }
            }
        }
    }

    if ( ! empty( $_POST['pro'] ) ) {
        $fields = adfoin_acculynx_collection( 'company-settings/custom-fields?filter=jobs', $cred_id );

        if ( is_wp_error( $fields ) ) {
            wp_send_json_error( sprintf( __( 'Could not load AccuLynx settings: %s', 'advanced-form-integration' ), $fields->get_error_message() ) );
        }

        $options['customFields'] = array();

        foreach ( $fields as $field ) {
            if ( isset( $field['id'], $field['label'], $field['fieldType'] ) && ( ! isset( $field['isActive'] ) || $field['isActive'] ) ) {
                $choices = array_values( array_filter( wp_list_pluck( isset( $field['options'] ) ? (array) $field['options'] : array(), 'value' ), 'strlen' ) );

                $options['customFields'][] = array(
                    'key'         => 'jcf__' . $field['fieldType'] . '__' . $field['id'],
                    'value'       => $field['label'] . ' [Custom]',
                    'description' => $choices ? sprintf( __( 'One of: %s', 'advanced-form-integration' ), implode( ', ', $choices ) ) : ( 'Date' === $field['fieldType'] ? 'A date, e.g. 2026-12-31.' : '' ),
                );
            }
        }
    }

    wp_send_json_success( $options );
}

function adfoin_acculynx_credentials_list() {
    foreach ( adfoin_read_credentials( 'acculynx' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_acculynx_api_key( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'acculynx', $cred_id );

    return isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
}

/*
 * AccuLynx's error text, e.g. the "detail" of its problem response.
 */
function adfoin_acculynx_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    foreach ( array( 'detail', 'title', 'message' ) as $key ) {
        if ( ! empty( $body[ $key ] ) && is_string( $body[ $key ] ) ) {
            return $body[ $key ];
        }
    }

    if ( ! empty( $body['error']['detail'] ) ) {
        return $body['error']['detail'];
    }

    if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
        $parts = array();
        array_walk_recursive( $body['errors'], function ( $value ) use ( &$parts ) {
            if ( is_scalar( $value ) ) $parts[] = (string) $value;
        } );
        if ( $parts ) return implode( ' ', $parts );
    }

    return wp_remote_retrieve_response_code( $response ) . ' ' . wp_remote_retrieve_response_message( $response );
}

function adfoin_acculynx_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '', $notes = array() ) {
    $api_key = adfoin_acculynx_api_key( $cred_id );
    $url     = 'https://api.acculynx.com/api/v2/' . ltrim( $endpoint, '/' );

    if ( ! $api_key ) {
        $error = new WP_Error( 'acculynx_no_account', __( 'No AccuLynx account is selected, or the account has no API Key. Check the integration and AFI Settings > AccuLynx.', 'advanced-form-integration' ) );
        if ( $record ) adfoin_add_to_log( $error, $url, array( 'method' => $method, 'body' => $data ), $record );
        return $error;
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) $args['body'] = wp_json_encode( $data );

    $response = wp_remote_request( $url, $args );

    // Rate limited: wait as asked (at most 10s) and try once more.
    if ( ! is_wp_error( $response ) && 429 === (int) wp_remote_retrieve_response_code( $response ) ) {
        sleep( max( 1, min( 10, (int) wp_remote_retrieve_header( $response, 'retry-after' ) ) ) );
        $response = wp_remote_request( $url, $args );
    }

    if ( $record ) {
        $logged = $response;

        if ( ! is_wp_error( $logged ) ) {
            if ( wp_remote_retrieve_response_code( $logged ) >= 300 ) {
                $logged['response']['message'] = adfoin_acculynx_error_message( $logged );
            } elseif ( $notes ) {
                $logged['response']['message'] = sprintf( 'OK (%s)', implode( '; ', $notes ) );
            }
        }

        adfoin_add_to_log( $logged, $url, $args, $record );
    }

    return $response;
}

/*
 * All items of a paged AccuLynx collection, or WP_Error. Settings lists are
 * cached for 12 hours per account.
 */
function adfoin_acculynx_collection( $endpoint, $cred_id ) {
    $cache_key = 'adfoin_acculynx_' . md5( $cred_id . '|' . adfoin_acculynx_api_key( $cred_id ) . '|' . $endpoint );
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) ) {
        return $cached;
    }

    $items = array();

    for ( $start = 0, $page = 0; $page < 20; $page++ ) {
        $glue     = strpos( $endpoint, '?' ) === false ? '?' : '&';
        $response = adfoin_acculynx_request( $endpoint . $glue . 'pageSize=25&pageStartIndex=' . $start . '&recordStartIndex=' . $start, 'GET', array(), array(), $cred_id );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
            // 416 = past the last page.
            if ( ! is_wp_error( $response ) && 416 == wp_remote_retrieve_response_code( $response ) && $items ) break;

            return new WP_Error( 'acculynx_list', adfoin_acculynx_error_message( $response ) );
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $batch = isset( $body['items'] ) && is_array( $body['items'] ) ? $body['items'] : array();
        $items = array_merge( $items, $batch );
        $start = count( $items );

        if ( ! $batch || ! isset( $body['count'] ) || $start >= (int) $body['count'] ) break;
    }

    set_transient( $cache_key, $items, 12 * HOUR_IN_SECONDS );

    return $items;
}

function adfoin_acculynx_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'acculynx_not_sent', $message ), '', array(), $record );
}

/*
 * 10-digit phone number, or '' (AccuLynx accepts exactly 10 digits).
 */
function adfoin_acculynx_phone( $value ) {
    $digits = preg_replace( '/\D/', '', (string) $value );

    if ( strlen( $digits ) === 11 && $digits[0] === '1' ) {
        $digits = substr( $digits, 1 );
    }

    return strlen( $digits ) === 10 ? $digits : '';
}

/*
 * Country and state from form text, resolved against AccuLynx's lists.
 * Returns array( country item|null, state item|null ); items have id, name, abbreviation.
 */
function adfoin_acculynx_resolve_location( $fields, $cred_id ) {
    $country_text = isset( $fields['country'] ) ? strtolower( trim( $fields['country'] ) ) : '';
    $state_text   = isset( $fields['state'] ) ? strtolower( trim( $fields['state'] ) ) : '';
    $country      = null;
    $state        = null;

    if ( $country_text === '' && $state_text === '' ) {
        return array( null, null );
    }

    $countries = adfoin_acculynx_collection( 'acculynx/countries', $cred_id );
    $countries = is_wp_error( $countries ) ? array() : $countries;
    $aliases   = array( 'usa' => 'us', 'united states of america' => 'us', 'u.s.' => 'us', 'u.s.a.' => 'us' );
    $wanted    = $country_text === '' ? 'us' : ( isset( $aliases[ $country_text ] ) ? $aliases[ $country_text ] : $country_text );

    foreach ( $countries as $item ) {
        if ( isset( $item['id'] ) && ( strtolower( (string) ( $item['abbreviation'] ?? '' ) ) === $wanted || strtolower( (string) ( $item['name'] ?? '' ) ) === $wanted ) ) {
            $country = $item;
            break;
        }
    }

    if ( $country && $state_text !== '' ) {
        $states = adfoin_acculynx_collection( 'acculynx/countries/' . (int) $country['id'] . '/states', $cred_id );

        foreach ( is_wp_error( $states ) ? array() : $states as $item ) {
            if ( isset( $item['id'] ) && ( strtolower( (string) ( $item['abbreviation'] ?? '' ) ) === $state_text || strtolower( (string) ( $item['name'] ?? '' ) ) === $state_text ) ) {
                $state = $item;
                break;
            }
        }
    }

    return array( $country, $state );
}

function adfoin_acculynx_build_contact_note( $fields, $extra_labels = array() ) {
    $labels = array_merge( array( 'jobType' => 'Job Type', 'damageType' => 'Damage Type', 'insuranceCarrier' => 'Insurance Carrier' ), $extra_labels );
    $extra  = array();

    foreach ( $labels as $key => $label ) {
        if ( ! empty( $fields[ $key ] ) ) $extra[] = "{$label}: {$fields[ $key ]}";
    }
    if ( ! empty( $fields['note'] ) ) $extra[] = $fields['note'];

    return implode( "\n", $extra );
}

/*
 * Contact type: the integration's, then the account's default setting, then
 * AccuLynx's default contact type.
 */
function adfoin_acculynx_contact_type( $data, $cred_id ) {
    if ( ! empty( $data['contactTypeId'] ) ) return $data['contactTypeId'];

    $credentials = adfoin_get_credentials_by_id( 'acculynx', $cred_id );
    if ( ! empty( $credentials['contactTypeId'] ) ) return $credentials['contactTypeId'];

    $types = adfoin_acculynx_collection( 'contacts/contact-types', $cred_id );

    foreach ( is_wp_error( $types ) ? array() : $types as $type ) {
        if ( ! empty( $type['isDefault'] ) && ! empty( $type['id'] ) ) return $type['id'];
    }

    return '';
}

/*
 * Creates the Contact and then a Job for it (AccuLynx has no lead endpoint;
 * new jobs start in the Lead milestone). Shared by the free and PRO actions.
 * $job_extra is merged into the job body; $notes are added to the job's log.
 * Returns the new job ID or ''.
 */
function adfoin_acculynx_create_lead( $fields, $data, $record, $job_extra = array(), $notes = array(), $extra_note_labels = array() ) {
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( ! adfoin_acculynx_api_key( $cred_id ) ) {
        adfoin_acculynx_log_skipped( $record, __( 'Not sent: no AccuLynx account is selected, or the account has no API Key.', 'advanced-form-integration' ) );
        return '';
    }

    $contact_type = adfoin_acculynx_contact_type( $data, $cred_id );

    if ( ! $contact_type ) {
        adfoin_acculynx_log_skipped( $record, __( 'Not sent: pick a Contact Type in the integration (AccuLynx requires one for every contact).', 'advanced-form-integration' ) );
        return '';
    }

    if ( empty( $fields['firstName'] ) && empty( $fields['lastName'] ) && empty( $fields['companyName'] ) ) {
        adfoin_acculynx_log_skipped( $record, __( 'Not sent: map a first name, last name or company name.', 'advanced-form-integration' ) );
        return '';
    }

    $contact = array( 'contactTypeIds' => array( $contact_type ) );

    foreach ( array( 'firstName', 'lastName', 'companyName' ) as $key ) {
        if ( ! empty( $fields[ $key ] ) ) $contact[ $key ] = $fields[ $key ];
    }

    foreach ( array( 'phone' => 'Home', 'mobile' => 'Mobile' ) as $key => $type ) {
        if ( empty( $fields[ $key ] ) ) continue;

        $number = adfoin_acculynx_phone( $fields[ $key ] );

        if ( $number ) {
            $contact['phoneNumbers'][] = array( 'number' => $number, 'type' => $type, 'primary' => empty( $contact['phoneNumbers'] ) );
        } else {
            $notes[] = sprintf( __( '%1$s "%2$s" is not a 10-digit number, left out', 'advanced-form-integration' ), $key, $fields[ $key ] );
        }
    }

    if ( ! empty( $fields['email'] ) ) {
        if ( is_email( $fields['email'] ) ) {
            $contact['emailAddresses'] = array( array( 'address' => $fields['email'], 'type' => 'Personal', 'primary' => true ) );
        } else {
            $notes[] = sprintf( __( 'email "%s" is not valid, left out', 'advanced-form-integration' ), $fields['email'] );
        }
    }

    list( $country, $state ) = adfoin_acculynx_resolve_location( $fields, $cred_id );

    $mailing = array();
    foreach ( array( 'street' => 'street1', 'street2' => 'street2', 'city' => 'city', 'zip' => 'zipCode' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $mailing[ $remote ] = $fields[ $local ];
    }
    if ( $state ) $mailing['state'] = array( 'id' => (int) $state['id'] );
    if ( $country && ( $mailing || $state ) ) $mailing['country'] = array( 'id' => (int) $country['id'] );
    if ( ! empty( $fields['state'] ) && ! $state ) $notes[] = sprintf( __( 'state "%s" not found in AccuLynx, left out', 'advanced-form-integration' ), $fields['state'] );
    if ( $mailing ) $contact['mailingAddress'] = $mailing;

    $contact_response = adfoin_acculynx_request( 'contacts', 'POST', $contact, $record, $cred_id );
    $contact_body     = is_wp_error( $contact_response ) ? array() : json_decode( wp_remote_retrieve_body( $contact_response ), true );

    if ( empty( $contact_body['id'] ) ) {
        return '';
    }

    $job = array( 'contact' => array( 'id' => $contact_body['id'] ) );

    // Lead source: picked in the integration, or matched by name from the form.
    $lead_source = ! empty( $data['leadSourceId'] ) ? $data['leadSourceId'] : '';

    if ( ! $lead_source && ! empty( $fields['leadSource'] ) ) {
        $sources = adfoin_acculynx_collection( 'company-settings/leads/lead-sources', $cred_id );

        foreach ( is_wp_error( $sources ) ? array() : $sources as $source ) {
            foreach ( array_merge( array( $source ), isset( $source['children'] ) && is_array( $source['children'] ) ? $source['children'] : array() ) as $item ) {
                if ( isset( $item['id'], $item['name'] ) && strcasecmp( trim( $item['name'] ), trim( $fields['leadSource'] ) ) === 0 ) {
                    $lead_source = $item['id'];
                    break 2;
                }
            }
        }

        if ( ! $lead_source ) $extra_note_labels['leadSource'] = 'Lead Source';
    }

    if ( $lead_source ) $job['leadSource'] = array( 'id' => $lead_source );

    // The job address needs all of street, city, state, zip and country.
    if ( ! empty( $fields['street'] ) && ! empty( $fields['city'] ) && ! empty( $fields['zip'] ) && $state && $country ) {
        $job['locationAddress'] = array(
            'street1' => mb_substr( $fields['street'], 0, 250 ),
            'city'    => mb_substr( $fields['city'], 0, 50 ),
            'state'   => $state['abbreviation'],
            'country' => $country['abbreviation'],
            'zipCode' => mb_substr( $fields['zip'], 0, 50 ),
        );
        if ( ! empty( $fields['street2'] ) ) $job['locationAddress']['street2'] = mb_substr( $fields['street2'], 0, 50 );
    } elseif ( ! empty( $fields['street'] ) || ! empty( $fields['city'] ) || ! empty( $fields['zip'] ) ) {
        $notes[] = __( 'job address left out: AccuLynx needs street, city, state, zip and country', 'advanced-form-integration' );
    }

    $note = adfoin_acculynx_build_contact_note( $fields, $extra_note_labels );

    if ( $note !== '' ) {
        if ( mb_strlen( $note ) > 1000 ) {
            $note    = mb_substr( $note, 0, 1000 );
            $notes[] = __( 'notes cut to 1000 characters', 'advanced-form-integration' );
        }
        $job['notes'] = $note;
    }

    $job_response = adfoin_acculynx_request( 'jobs', 'POST', array_merge( $job, $job_extra ), $record, $cred_id, $notes );
    $job_body     = is_wp_error( $job_response ) ? array() : json_decode( wp_remote_retrieve_body( $job_response ), true );

    return isset( $job_body['id'] ) ? $job_body['id'] : '';
}

add_action( 'adfoin_acculynx_job_queue', 'adfoin_acculynx_job_queue', 10, 1 );
function adfoin_acculynx_job_queue( $data ) {
    adfoin_acculynx_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Mapped values, parsed and trimmed, without empty ones.
 */
function adfoin_acculynx_collect( $data, $posted_data ) {
    $fields = array();

    foreach ( $data as $k => $v ) {
        if ( ! is_string( $v ) ) continue;

        $parsed = trim( (string) adfoin_get_parsed_values( $v, $posted_data ) );
        if ( $parsed !== '' ) $fields[ $k ] = $parsed;
    }

    return $fields;
}

function adfoin_acculynx_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    if ( $record['task'] !== 'create_lead' ) return;

    $data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();

    adfoin_acculynx_create_lead( adfoin_acculynx_collect( $data, $posted_data ), $data, $record );
}
