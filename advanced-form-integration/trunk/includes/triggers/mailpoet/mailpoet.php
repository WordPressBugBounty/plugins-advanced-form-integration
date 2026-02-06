<?php

// Get Forms List
function adfoin_mailpoet_get_forms( $form_provider ) {
    if( $form_provider != 'mailpoet' ) {
        return;
    }

    global $wpdb;

    $result = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}mailpoet_forms" );
    $forms = wp_list_pluck( $result, 'name', 'id' );

    return $forms;

}

// Get Form Fields
function adfoin_mailpoet_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'mailpoet' ) {
        return;
    }

    global $wpdb;

    $form = $wpdb->get_row( $wpdb->prepare( "SELECT body FROM {$wpdb->prefix}mailpoet_forms WHERE id = %d", $form_id ) );
    $fields = array();

    if( $form ) {
        $form_body = maybe_unserialize( $form->body );

        if( is_array( $form_body ) && $form_body ) {
            foreach( $form_body as $field ) {
                if( $field['type'] == 'submit') {
                    continue;
                }

                if( $field['id'] == 'email' || $field['id'] == 'first_name' || $field['id'] == 'last_name') {
                    $field_id = $field['id'];
                } else {
                    $field_id = 'cf_' . $field['id'];
                }

                $fields[ $field_id ] = $field['name'];
            }
        }
    }

    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $fields['form_name'] = __( 'Form Name', 'advanced-form-integration' );

    return $fields;
}

function adfoin_mailpoet_get_userdata( $user_id ) {
    $user_data = array();
    $user      = get_userdata($user_id);

    if ($user) {
        $user_data['user_id']    = $user->ID;
        $user_data['first_name'] = $user->first_name;
        $user_data['last_name']  = $user->last_name;
        $user_data['email'] = $user->user_email;
    }

    return $user_data;
}

// Form Submission
add_action( 'mailpoet_subscription_before_subscribe', 'adfoin_mailpoet_user_subscribed', 10, 3 );

function adfoin_mailpoet_user_subscribed( $data, $segment_id, $form ) {
    $form_id = $form->getId();
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'mailpoet', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array();

    foreach ( $data as $key => $value ) {
        if( is_array( $value ) && isset( $value['year'] ) ) {
            $year = $value['year'];
            $month = $value['month'] < 10 ? '0' . $value['month'] : $value['month'];
            $day = isset( $value['day'] ) ? ( $value['day'] < 10 ? '0' . $value['day'] : $value['day'] ) : '01';
            $value = $year . '-' . $month . '-' . $day;
        }

        $posted_data[$key] = $value;
    }

    $posted_data['form_id'] = $form_id;
    $posted_data['form_name'] = $form->getName();

    $integration->send( $saved_records, $posted_data );
}