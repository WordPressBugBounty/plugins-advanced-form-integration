<?php

/**
 * Jetpack CRM (formerly Zero BS CRM) trigger.
 *
 * Jetpack CRM 3.0+ stores contacts and companies in its own tables
 * (wp_zbs_contacts, wp_zbs_companies), not as posts, so the record is read
 * through its data layer ($zbs->DAL) rather than post meta. The available
 * fields follow Jetpack CRM's own field model, so custom fields are included.
 *
 * Hooks: zbs_new_customer / jpcrm_after_contact_update / zbs_new_company,
 * fired by the plugin's internal automator once the record (including
 * custom fields and tags) has been saved.
 */

// Get JetpackCRM Triggers
function adfoin_jetpackcrm_get_forms( $form_provider ) {
    if ( $form_provider != 'jetpackcrm' ) {
        return;
    }

    $triggers = array(
        'addContact'    => __( 'Contact Added', 'advanced-form-integration' ),
        'updateContact' => __( 'Contact Updated', 'advanced-form-integration' ),
        'addCompany'    => __( 'Company Added', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get JetpackCRM Fields
function adfoin_jetpackcrm_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'jetpackcrm' ) {
        return;
    }

    if ( 'addContact' === $form_id || 'updateContact' === $form_id ) {
        $fields = array(
            'contact_id' => __( 'Contact ID', 'advanced-form-integration' ),
            'status'     => __( 'Contact Status', 'advanced-form-integration' ),
            'first_name' => __( 'First Name', 'advanced-form-integration' ),
            'last_name'  => __( 'Last Name', 'advanced-form-integration' ),
            'full_name'  => __( 'Full Name', 'advanced-form-integration' ),
            'email'      => __( 'Email', 'advanced-form-integration' ),
            'phone'      => __( 'Phone Number (first available)', 'advanced-form-integration' ),
            'address'    => __( 'Address (single line)', 'advanced-form-integration' ),
        );

        $fields = array_merge( $fields, adfoin_jetpackcrm_model_labels( 'contact' ) );

        $fields['tw']           = __( 'Twitter / X Handle', 'advanced-form-integration' );
        $fields['fb']           = __( 'Facebook Page', 'advanced-form-integration' );
        $fields['li']           = __( 'LinkedIn Profile', 'advanced-form-integration' );
        $fields['tags']         = __( 'Tags', 'advanced-form-integration' );
        $fields['companies']    = __( 'Companies', 'advanced-form-integration' );
        $fields['created_date'] = __( 'Created Date', 'advanced-form-integration' );

        return $fields;
    }

    if ( 'addCompany' === $form_id ) {
        $fields = array(
            'company_id'   => __( 'Company ID', 'advanced-form-integration' ),
            'status'       => __( 'Company Status', 'advanced-form-integration' ),
            'company_name' => __( 'Company Name', 'advanced-form-integration' ),
            'email'        => __( 'Company Email', 'advanced-form-integration' ),
            'phone'        => __( 'Company Phone', 'advanced-form-integration' ),
            'address'      => __( 'Company Address (single line)', 'advanced-form-integration' ),
        );

        $fields = array_merge( $fields, adfoin_jetpackcrm_model_labels( 'company' ) );

        $fields['tags']         = __( 'Tags', 'advanced-form-integration' );
        $fields['created_date'] = __( 'Created Date', 'advanced-form-integration' );

        return $fields;
    }

    return array();
}

/*
 * Jetpack CRM's field model for contacts or companies (standard + custom
 * fields), minus fields whose setting is off (e.g. second address).
 */
function adfoin_jetpackcrm_field_model( $type ) {
    global $zbs;

    $obj_type = 'company' === $type ? ( defined( 'ZBS_TYPE_COMPANY' ) ? ZBS_TYPE_COMPANY : 0 ) : ( defined( 'ZBS_TYPE_CONTACT' ) ? ZBS_TYPE_CONTACT : 0 );
    $var_name = 'company' === $type ? 'zbsCompanyFields' : 'zbsCustomerFields';

    if ( $obj_type && isset( $zbs->DAL ) && method_exists( $zbs->DAL, 'objFieldVarName' ) ) {
        $var_name = $zbs->DAL->objFieldVarName( $obj_type );
    }

    if ( empty( $var_name ) || empty( $GLOBALS[ $var_name ] ) || ! is_array( $GLOBALS[ $var_name ] ) ) {
        return array();
    }

    $model = array();

    foreach ( $GLOBALS[ $var_name ] as $key => $field ) {
        if ( ! is_array( $field ) ) {
            continue;
        }

        if ( ! empty( $field['opt'] ) && isset( $zbs->settings ) && ! $zbs->settings->get( $field['opt'] ) ) {
            continue;
        }

        $model[ $key ] = $field;
    }

    return $model;
}

/*
 * Field-model keys already covered by the fixed fields above.
 */
function adfoin_jetpackcrm_skip_keys( $type ) {
    return 'company' === $type
        ? array( 'status', 'name', 'email' )
        : array( 'status', 'fname', 'lname', 'email' );
}

/*
 * Trigger field labels from the field model, keyed as in the payload.
 */
function adfoin_jetpackcrm_model_labels( $type ) {
    $labels = array();

    foreach ( adfoin_jetpackcrm_field_model( $type ) as $key => $field ) {
        if ( in_array( $key, adfoin_jetpackcrm_skip_keys( $type ), true ) ) {
            continue;
        }

        $labels[ $key ] = isset( $field[1] ) && '' !== $field[1] ? $field[1] : $key;
    }

    return $labels;
}

/*
 * The data layer returns second-address fields as secaddr_<field> while the
 * field model calls them sec<field> (secaddr1 => secaddr_addr1).
 */
function adfoin_jetpackcrm_value( $record, $key ) {
    if ( array_key_exists( $key, $record ) ) {
        return $record[ $key ];
    }

    $aliases = array(
        'secaddr1'    => 'secaddr_addr1',
        'secaddr2'    => 'secaddr_addr2',
        'seccity'     => 'secaddr_city',
        'seccounty'   => 'secaddr_county',
        'secpostcode' => 'secaddr_postcode',
        'seccountry'  => 'secaddr_country',
    );

    if ( isset( $aliases[ $key ] ) && array_key_exists( $aliases[ $key ], $record ) ) {
        return $record[ $aliases[ $key ] ];
    }

    return '';
}

/*
 * Field-model values as plain strings (dates as Y-m-d, lists comma-separated).
 */
function adfoin_jetpackcrm_model_values( $type, $record ) {
    $values = array();

    foreach ( adfoin_jetpackcrm_field_model( $type ) as $key => $field ) {
        if ( in_array( $key, adfoin_jetpackcrm_skip_keys( $type ), true ) ) {
            continue;
        }

        $value = adfoin_jetpackcrm_value( $record, $key );

        if ( is_array( $value ) ) {
            $value = implode( ', ', array_filter( array_map( 'strval', $value ), 'strlen' ) );
        } elseif ( 'date' === ( $field[0] ?? '' ) && is_numeric( $value ) && $value > 0 ) {
            $value = gmdate( 'Y-m-d', (int) $value );
        }

        $values[ $key ] = (string) $value;
    }

    return $values;
}

function adfoin_jetpackcrm_tag_names( $record ) {
    $names = array();

    foreach ( (array) ( $record['tags'] ?? array() ) as $tag ) {
        if ( is_array( $tag ) && isset( $tag['name'] ) ) {
            $names[] = $tag['name'];
        }
    }

    return implode( ', ', $names );
}

/*
 * Creation time as Y-m-d H:i:s in the site's timezone (created_date from the
 * data layer is in the site's display date format).
 */
function adfoin_jetpackcrm_created_date( $record ) {
    $timestamp = 0;

    foreach ( array( 'createduts', 'created' ) as $key ) {
        if ( isset( $record[ $key ] ) && is_numeric( $record[ $key ] ) && (int) $record[ $key ] > 0 ) {
            $timestamp = (int) $record[ $key ];
            break;
        }
    }

    if ( $timestamp > 0 ) {
        return wp_date( 'Y-m-d H:i:s', $timestamp );
    }

    return isset( $record['created_date'] ) ? (string) $record['created_date'] : '';
}

function adfoin_jetpackcrm_single_line_address( $record ) {
    $parts = array();

    foreach ( array( 'addr1', 'addr2', 'city', 'county', 'postcode', 'country' ) as $key ) {
        if ( ! empty( $record[ $key ] ) ) {
            $parts[] = trim( $record[ $key ] );
        }
    }

    return implode( ', ', $parts );
}

/*
 * Trigger payload for a contact, or empty array if it can't be read.
 */
function adfoin_jetpackcrm_contact_data( $contact_id ) {
    global $zbs;

    if ( ! isset( $zbs->DAL->contacts ) ) {
        return array();
    }

    $contact = $zbs->DAL->contacts->getContact(
        $contact_id,
        array(
            'withCustomFields' => true,
            'withTags'         => true,
            'withCompanies'    => true,
            'ignoreowner'      => true,
        )
    );

    if ( ! is_array( $contact ) || empty( $contact['id'] ) ) {
        return array();
    }

    $phone = '';

    foreach ( array( 'mobtel', 'worktel', 'hometel' ) as $key ) {
        if ( ! empty( $contact[ $key ] ) ) {
            $phone = $contact[ $key ];
            break;
        }
    }

    $companies = array();

    foreach ( (array) ( $contact['companies'] ?? array() ) as $company ) {
        if ( is_array( $company ) && ! empty( $company['name'] ) ) {
            $companies[] = $company['name'];
        }
    }

    $data = array(
        'contact_id' => $contact['id'],
        'status'     => $contact['status'] ?? '',
        'first_name' => $contact['fname'] ?? '',
        'last_name'  => $contact['lname'] ?? '',
        'full_name'  => $contact['fullname'] ?? trim( ( $contact['fname'] ?? '' ) . ' ' . ( $contact['lname'] ?? '' ) ),
        'email'      => $contact['email'] ?? '',
        'phone'      => $phone,
        'address'    => adfoin_jetpackcrm_single_line_address( $contact ),
    );

    $data = array_merge( $data, adfoin_jetpackcrm_model_values( 'contact', $contact ) );

    $data['tw']           = $contact['tw'] ?? '';
    $data['fb']           = $contact['fb'] ?? '';
    $data['li']           = $contact['li'] ?? '';
    $data['tags']         = adfoin_jetpackcrm_tag_names( $contact );
    $data['companies']    = implode( ', ', $companies );
    $data['created_date'] = adfoin_jetpackcrm_created_date( $contact );

    return $data;
}

/*
 * Trigger payload for a company, or empty array if it can't be read.
 */
function adfoin_jetpackcrm_company_data( $company_id ) {
    global $zbs;

    if ( ! isset( $zbs->DAL->companies ) ) {
        return array();
    }

    $company = $zbs->DAL->companies->getCompany(
        $company_id,
        array(
            'withCustomFields' => true,
            'withTags'         => true,
            'withContacts'     => false,
            'ignoreowner'      => true,
        )
    );

    if ( ! is_array( $company ) || empty( $company['id'] ) ) {
        return array();
    }

    $data = array(
        'company_id'   => $company['id'],
        'status'       => $company['status'] ?? '',
        'company_name' => $company['name'] ?? '',
        'email'        => $company['email'] ?? '',
        'phone'        => ! empty( $company['maintel'] ) ? $company['maintel'] : ( $company['sectel'] ?? '' ),
        'address'      => adfoin_jetpackcrm_single_line_address( $company ),
    );

    $data = array_merge( $data, adfoin_jetpackcrm_model_values( 'company', $company ) );

    $data['tags']         = adfoin_jetpackcrm_tag_names( $company );
    $data['created_date'] = adfoin_jetpackcrm_created_date( $company );

    return $data;
}

add_action( 'zbs_new_customer', 'adfoin_jetpackcrm_handle_contact_add', 10, 1 );
// Handle Contact Added
function adfoin_jetpackcrm_handle_contact_add( $contact_id ) {
    adfoin_jetpackcrm_dispatch_contact( 'addContact', $contact_id );
}

add_action( 'jpcrm_after_contact_update', 'adfoin_jetpackcrm_handle_contact_update', 10, 1 );
// Handle Contact Updated
function adfoin_jetpackcrm_handle_contact_update( $contact_id ) {
    adfoin_jetpackcrm_dispatch_contact( 'updateContact', $contact_id );
}

function adfoin_jetpackcrm_dispatch_contact( $trigger, $contact_id ) {
    // One save can fire the update hook more than once (Jetpack CRM raises
    // both contact.update and contact.status.update when the status changes).
    static $sent = array();

    $sent_key = $trigger . ':' . $contact_id;

    if ( isset( $sent[ $sent_key ] ) ) {
        return;
    }

    $sent[ $sent_key ] = true;

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'jetpackcrm', $trigger );

    if ( empty( $saved_records ) ) {
        return;
    }

    $contact_data = adfoin_jetpackcrm_contact_data( $contact_id );

    if ( empty( $contact_data ) ) {
        return;
    }

    adfoin_dispatch_integrations( $saved_records, $contact_data );
}

add_action( 'zbs_new_company', 'adfoin_jetpackcrm_handle_company_add', 10, 1 );
// Handle Company Added
function adfoin_jetpackcrm_handle_company_add( $company_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'jetpackcrm', 'addCompany' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $company_data = adfoin_jetpackcrm_company_data( $company_id );

    if ( empty( $company_data ) ) {
        return;
    }

    adfoin_dispatch_integrations( $saved_records, $company_data );
}
