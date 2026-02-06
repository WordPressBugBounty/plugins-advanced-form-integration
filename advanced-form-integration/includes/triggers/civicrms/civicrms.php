<?php

function adfoin_civicrms_get_forms( $form_provider ) {
    if ( $form_provider !== 'civicrms' ) {
        return;
    }

    return array(
        'contactCreated'      => __( 'Contact Created', 'advanced-form-integration' ),
        'contactUpdated'      => __( 'Contact Updated', 'advanced-form-integration' ),
        'contributionCreated' => __( 'Contribution Created', 'advanced-form-integration' ),
        'membershipCreated'   => __( 'Membership Created', 'advanced-form-integration' ),
    );
}

function adfoin_civicrms_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'civicrms' ) {
        return;
    }

    $fields = array();

    if ( in_array( $form_id, array( 'contactCreated', 'contactUpdated' ), true ) ) {
        $fields = array(
            'contact_id'            => __( 'Contact ID', 'advanced-form-integration' ),
            'contact_type'          => __( 'Contact Type', 'advanced-form-integration' ),
            'contact_sub_type'      => __( 'Contact Subtype', 'advanced-form-integration' ),
            'display_name'          => __( 'Display Name', 'advanced-form-integration' ),
            'first_name'            => __( 'First Name', 'advanced-form-integration' ),
            'middle_name'           => __( 'Middle Name', 'advanced-form-integration' ),
            'last_name'             => __( 'Last Name', 'advanced-form-integration' ),
            'nick_name'             => __( 'Nickname', 'advanced-form-integration' ),
            'preferred_language'    => __( 'Preferred Language', 'advanced-form-integration' ),
            'source'                => __( 'Source', 'advanced-form-integration' ),
            'job_title'             => __( 'Job Title', 'advanced-form-integration' ),
            'birth_date'            => __( 'Birth Date', 'advanced-form-integration' ),
            'gender_id'             => __( 'Gender ID', 'advanced-form-integration' ),
            'preferred_mail_format' => __( 'Preferred Mail Format', 'advanced-form-integration' ),
            'primary_email'         => __( 'Primary Email', 'advanced-form-integration' ),
            'primary_phone'         => __( 'Primary Phone', 'advanced-form-integration' ),
            'created_date'          => __( 'Created Date', 'advanced-form-integration' ),
            'modified_date'         => __( 'Modified Date', 'advanced-form-integration' ),
        );
    } elseif ( 'contributionCreated' === $form_id ) {
        $fields = array(
            'contribution_id'       => __( 'Contribution ID', 'advanced-form-integration' ),
            'contact_id'            => __( 'Contact ID', 'advanced-form-integration' ),
            'financial_type_id'     => __( 'Financial Type ID', 'advanced-form-integration' ),
            'payment_instrument_id' => __( 'Payment Instrument ID', 'advanced-form-integration' ),
            'total_amount'          => __( 'Total Amount', 'advanced-form-integration' ),
            'fee_amount'            => __( 'Fee Amount', 'advanced-form-integration' ),
            'net_amount'            => __( 'Net Amount', 'advanced-form-integration' ),
            'currency'              => __( 'Currency', 'advanced-form-integration' ),
            'trxn_id'               => __( 'Transaction ID', 'advanced-form-integration' ),
            'invoice_number'        => __( 'Invoice Number', 'advanced-form-integration' ),
            'source'                => __( 'Source', 'advanced-form-integration' ),
            'contribution_status_id'=> __( 'Status ID', 'advanced-form-integration' ),
            'receive_date'          => __( 'Receive Date', 'advanced-form-integration' ),
            'receipt_date'          => __( 'Receipt Date', 'advanced-form-integration' ),
            'campaign_id'           => __( 'Campaign ID', 'advanced-form-integration' ),
        );
    } elseif ( 'membershipCreated' === $form_id ) {
        $fields = array(
            'membership_id'             => __( 'Membership ID', 'advanced-form-integration' ),
            'contact_id'                => __( 'Contact ID', 'advanced-form-integration' ),
            'membership_type_id'        => __( 'Membership Type ID', 'advanced-form-integration' ),
            'status_id'                 => __( 'Status ID', 'advanced-form-integration' ),
            'source'                    => __( 'Source', 'advanced-form-integration' ),
            'join_date'                 => __( 'Join Date', 'advanced-form-integration' ),
            'start_date'                => __( 'Start Date', 'advanced-form-integration' ),
            'end_date'                  => __( 'End Date', 'advanced-form-integration' ),
            'is_override'               => __( 'Is Override', 'advanced-form-integration' ),
            'status_override_end_date'  => __( 'Status Override End Date', 'advanced-form-integration' ),
            'is_test'                   => __( 'Is Test', 'advanced-form-integration' ),
            'auto_renew'                => __( 'Auto Renew', 'advanced-form-integration' ),
            'campaign_id'               => __( 'Campaign ID', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

function adfoin_civicrms_normalize_object( $object ) {
    if ( empty( $object ) ) {
        return array();
    }

    if ( is_array( $object ) ) {
        return $object;
    }

    if ( is_object( $object ) ) {
        return get_object_vars( $object );
    }

    return array();
}

function adfoin_civicrms_prepare_contact_payload( $contact_id, $object_ref ) {
    $data = adfoin_civicrms_normalize_object( $object_ref );
    $payload = array(
        'contact_id'            => $contact_id,
        'contact_type'          => $data['contact_type'] ?? '',
        'contact_sub_type'      => $data['contact_sub_type'] ?? '',
        'display_name'          => $data['display_name'] ?? '',
        'first_name'            => $data['first_name'] ?? '',
        'middle_name'           => $data['middle_name'] ?? '',
        'last_name'             => $data['last_name'] ?? '',
        'nick_name'             => $data['nick_name'] ?? '',
        'preferred_language'    => $data['preferred_language'] ?? '',
        'source'                => $data['source'] ?? '',
        'job_title'             => $data['job_title'] ?? '',
        'birth_date'            => $data['birth_date'] ?? '',
        'gender_id'             => $data['gender_id'] ?? '',
        'preferred_mail_format' => $data['preferred_mail_format'] ?? '',
        'created_date'          => $data['created_date'] ?? '',
        'modified_date'         => $data['modified_date'] ?? '',
        'primary_email'         => adfoin_civicrms_get_primary_email( $contact_id ),
        'primary_phone'         => adfoin_civicrms_get_primary_phone( $contact_id ),
    );

    return $payload;
}

function adfoin_civicrms_prepare_contribution_payload( $contribution_id, $object_ref ) {
    $data = adfoin_civicrms_normalize_object( $object_ref );

    return array(
        'contribution_id'        => $contribution_id,
        'contact_id'             => $data['contact_id'] ?? '',
        'financial_type_id'      => $data['financial_type_id'] ?? '',
        'payment_instrument_id'  => $data['payment_instrument_id'] ?? '',
        'total_amount'           => $data['total_amount'] ?? '',
        'fee_amount'             => $data['fee_amount'] ?? '',
        'net_amount'             => $data['net_amount'] ?? '',
        'currency'               => $data['currency'] ?? '',
        'trxn_id'                => $data['trxn_id'] ?? '',
        'invoice_number'         => $data['invoice_number'] ?? '',
        'source'                 => $data['source'] ?? '',
        'contribution_status_id' => $data['contribution_status_id'] ?? '',
        'receive_date'           => $data['receive_date'] ?? '',
        'receipt_date'           => $data['receipt_date'] ?? '',
        'campaign_id'            => $data['campaign_id'] ?? '',
    );
}

function adfoin_civicrms_prepare_membership_payload( $membership_id, $object_ref ) {
    $data = adfoin_civicrms_normalize_object( $object_ref );

    return array(
        'membership_id'            => $membership_id,
        'contact_id'               => $data['contact_id'] ?? '',
        'membership_type_id'       => $data['membership_type_id'] ?? '',
        'status_id'                => $data['status_id'] ?? '',
        'source'                   => $data['source'] ?? '',
        'join_date'                => $data['join_date'] ?? '',
        'start_date'               => $data['start_date'] ?? '',
        'end_date'                 => $data['end_date'] ?? '',
        'is_override'              => $data['is_override'] ?? '',
        'status_override_end_date' => $data['status_override_end_date'] ?? '',
        'is_test'                  => $data['is_test'] ?? '',
        'auto_renew'               => $data['auto_renew'] ?? '',
        'campaign_id'              => $data['campaign_id'] ?? '',
    );
}

function adfoin_civicrms_get_primary_email( $contact_id ) {
    if ( ! class_exists( 'CRM_Core_DAO' ) || empty( $contact_id ) ) {
        return '';
    }

    try {
        $email = CRM_Core_DAO::singleValueQuery(
            'SELECT email FROM civicrm_email WHERE contact_id = %1 AND is_primary = 1 ORDER BY id ASC LIMIT 1',
            array(
                1 => array( $contact_id, 'Integer' ),
            )
        );
    } catch ( Exception $e ) {
        $email = '';
    }

    return $email ? $email : '';
}

function adfoin_civicrms_get_primary_phone( $contact_id ) {
    if ( ! class_exists( 'CRM_Core_DAO' ) || empty( $contact_id ) ) {
        return '';
    }

    try {
        $phone = CRM_Core_DAO::singleValueQuery(
            'SELECT phone FROM civicrm_phone WHERE contact_id = %1 AND is_primary = 1 ORDER BY id ASC LIMIT 1',
            array(
                1 => array( $contact_id, 'Integer' ),
            )
        );
    } catch ( Exception $e ) {
        $phone = '';
    }

    return $phone ? $phone : '';
}

add_action( 'civicrm_post', 'adfoin_civicrms_handle_post', 10, 4 );

function adfoin_civicrms_handle_post( $op, $object_name, $object_id, &$object_ref ) {
    if ( empty( $object_id ) || empty( $object_name ) ) {
        return;
    }

    if ( in_array( $object_name, array( 'Individual', 'Organization', 'Household' ), true ) ) {
        if ( in_array( $op, array( 'create', 'edit' ), true ) ) {
            $integration = new Advanced_Form_Integration_Integration();
            $trigger_key = ( 'edit' === $op ) ? 'contactUpdated' : 'contactCreated';
            $records = $integration->get_by_trigger( 'civicrms', $trigger_key );

            if ( ! empty( $records ) ) {
                $integration->send( $records, adfoin_civicrms_prepare_contact_payload( $object_id, $object_ref ) );
            }
        }

        return;
    }

    if ( 'Contribution' === $object_name && 'create' === $op ) {
        $integration = new Advanced_Form_Integration_Integration();
        $records = $integration->get_by_trigger( 'civicrms', 'contributionCreated' );

        if ( ! empty( $records ) ) {
            $integration->send( $records, adfoin_civicrms_prepare_contribution_payload( $object_id, $object_ref ) );
        }

        return;
    }

    if ( 'Membership' === $object_name && 'create' === $op ) {
        $integration = new Advanced_Form_Integration_Integration();
        $records = $integration->get_by_trigger( 'civicrms', 'membershipCreated' );

        if ( ! empty( $records ) ) {
            $integration->send( $records, adfoin_civicrms_prepare_membership_payload( $object_id, $object_ref ) );
        }
    }
}
