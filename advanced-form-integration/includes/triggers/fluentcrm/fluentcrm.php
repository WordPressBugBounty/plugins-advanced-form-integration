<?php

// Get FluentCRM triggers.
function adfoin_fluentcrm_get_forms( $form_provider ) {
    if ( $form_provider !== 'fluentcrm' ) {
        return;
    }

    return array(
        'contactCreated'   => __( 'Contact Created', 'advanced-form-integration' ),
        'contactUpdated'   => __( 'Contact Updated', 'advanced-form-integration' ),
        'tagsAdded'        => __( 'Tags Added to Contact', 'advanced-form-integration' ),
        'listsAdded'       => __( 'Lists Added to Contact', 'advanced-form-integration' ),
        'statusChanged'    => __( 'Contact Status Changed', 'advanced-form-integration' ),
    );
}

// Get FluentCRM fields.
function adfoin_fluentcrm_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'fluentcrm' ) {
        return;
    }

    $base_fields = array(
        'contact_id'       => __( 'Contact ID', 'advanced-form-integration' ),
        'hash'             => __( 'Contact Hash', 'advanced-form-integration' ),
        'email'            => __( 'Email', 'advanced-form-integration' ),
        'status'           => __( 'Status', 'advanced-form-integration' ),
        'contact_type'     => __( 'Contact Type', 'advanced-form-integration' ),
        'first_name'       => __( 'First Name', 'advanced-form-integration' ),
        'last_name'        => __( 'Last Name', 'advanced-form-integration' ),
        'full_name'        => __( 'Full Name', 'advanced-form-integration' ),
        'prefix'           => __( 'Name Prefix', 'advanced-form-integration' ),
        'user_id'          => __( 'User ID', 'advanced-form-integration' ),
        'phone'            => __( 'Phone', 'advanced-form-integration' ),
        'timezone'         => __( 'Timezone', 'advanced-form-integration' ),
        'source'           => __( 'Source', 'advanced-form-integration' ),
        'life_time_value'  => __( 'Lifetime Value', 'advanced-form-integration' ),
        'last_activity'    => __( 'Last Activity', 'advanced-form-integration' ),
        'total_points'     => __( 'Total Points', 'advanced-form-integration' ),
        'address_line_1'   => __( 'Address Line 1', 'advanced-form-integration' ),
        'address_line_2'   => __( 'Address Line 2', 'advanced-form-integration' ),
        'city'             => __( 'City', 'advanced-form-integration' ),
        'state'            => __( 'State', 'advanced-form-integration' ),
        'postal_code'      => __( 'Postal Code', 'advanced-form-integration' ),
        'country'          => __( 'Country', 'advanced-form-integration' ),
        'date_of_birth'    => __( 'Date of Birth', 'advanced-form-integration' ),
        'latitude'         => __( 'Latitude', 'advanced-form-integration' ),
        'longitude'        => __( 'Longitude', 'advanced-form-integration' ),
        'ip'               => __( 'IP Address', 'advanced-form-integration' ),
        'avatar'           => __( 'Avatar URL', 'advanced-form-integration' ),
        'contact_url'      => __( 'Contact Profile URL', 'advanced-form-integration' ),
        'created_at'       => __( 'Created At', 'advanced-form-integration' ),
        'updated_at'       => __( 'Updated At', 'advanced-form-integration' ),
        'tags'             => __( 'Tags (JSON)', 'advanced-form-integration' ),
        'lists'            => __( 'Lists (JSON)', 'advanced-form-integration' ),
        'companies'        => __( 'Companies (JSON)', 'advanced-form-integration' ),
        'custom_fields'    => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
    );

    if ( 'contactCreated' === $form_id ) {
        return $base_fields;
    }

    if ( 'contactUpdated' === $form_id ) {
        return array_merge(
            $base_fields,
            array(
                'updated_fields' => __( 'Updated Fields (JSON)', 'advanced-form-integration' ),
            )
        );
    }

    if ( 'tagsAdded' === $form_id ) {
        return array_merge(
            $base_fields,
            array(
                'tag_ids'        => __( 'Tag IDs', 'advanced-form-integration' ),
                'tags_added'     => __( 'Tags Added (JSON)', 'advanced-form-integration' ),
            )
        );
    }

    if ( 'listsAdded' === $form_id ) {
        return array_merge(
            $base_fields,
            array(
                'list_ids'       => __( 'List IDs', 'advanced-form-integration' ),
                'lists_added'    => __( 'Lists Added (JSON)', 'advanced-form-integration' ),
            )
        );
    }

    if ( 'statusChanged' === $form_id ) {
        return array_merge(
            $base_fields,
            array(
                'old_status'     => __( 'Old Status', 'advanced-form-integration' ),
                'new_status'     => __( 'New Status', 'advanced-form-integration' ),
            )
        );
    }

    return $base_fields;
}

/**
 * Normalize contact data.
 *
 * @param mixed $contact Contact object or array.
 *
 * @return \FluentCrm\App\Models\Subscriber|null
 */
function adfoin_fluentcrm_resolve_contact_model( $contact ) {
    if ( empty( $contact ) ) {
        return null;
    }

    if ( is_object( $contact ) && $contact instanceof \FluentCrm\App\Models\Subscriber ) {
        return $contact;
    }

    if ( is_numeric( $contact ) && class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
        return \FluentCrm\App\Models\Subscriber::with( array( 'tags', 'lists', 'companies' ) )->find( $contact );
    }

    if ( is_array( $contact ) && ! empty( $contact['id'] ) && class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
        return \FluentCrm\App\Models\Subscriber::with( array( 'tags', 'lists', 'companies' ) )->find( (int) $contact['id'] );
    }

    return null;
}

/**
 * Prepare contact payload.
 *
 * @param mixed $contact Contact data.
 *
 * @return array
 */
function adfoin_fluentcrm_prepare_contact_payload( $contact ) {
    if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
        return array();
    }

    $contact_model = adfoin_fluentcrm_resolve_contact_model( $contact );

    if ( ! $contact_model ) {
        return array();
    }

    if ( method_exists( $contact_model, 'loadMissing' ) ) {
        $contact_model->loadMissing( array( 'tags', 'lists', 'companies' ) );
    }

    $contact_array = $contact_model->toArray();

    $payload = array(
        'contact_id'      => $contact_array['id'] ?? '',
        'hash'            => $contact_array['hash'] ?? '',
        'email'           => $contact_array['email'] ?? '',
        'status'          => $contact_array['status'] ?? '',
        'contact_type'    => $contact_array['contact_type'] ?? '',
        'first_name'      => $contact_array['first_name'] ?? '',
        'last_name'       => $contact_array['last_name'] ?? '',
        'full_name'       => $contact_model->full_name ?? '',
        'prefix'          => $contact_array['prefix'] ?? '',
        'user_id'         => $contact_array['user_id'] ?? '',
        'phone'           => $contact_array['phone'] ?? '',
        'timezone'        => $contact_array['timezone'] ?? '',
        'source'          => $contact_array['source'] ?? '',
        'life_time_value' => $contact_array['life_time_value'] ?? '',
        'last_activity'   => $contact_array['last_activity'] ?? '',
        'total_points'    => $contact_array['total_points'] ?? '',
        'address_line_1'  => $contact_array['address_line_1'] ?? '',
        'address_line_2'  => $contact_array['address_line_2'] ?? '',
        'city'            => $contact_array['city'] ?? '',
        'state'           => $contact_array['state'] ?? '',
        'postal_code'     => $contact_array['postal_code'] ?? '',
        'country'         => $contact_array['country'] ?? '',
        'date_of_birth'   => $contact_array['date_of_birth'] ?? '',
        'latitude'        => $contact_array['latitude'] ?? '',
        'longitude'       => $contact_array['longitude'] ?? '',
        'ip'              => $contact_array['ip'] ?? '',
        'avatar'          => $contact_array['avatar'] ?? '',
        'created_at'      => $contact_array['created_at'] ?? '',
        'updated_at'      => $contact_array['updated_at'] ?? '',
    );

    if ( function_exists( 'fluentcrm_menu_url_base' ) && ! empty( $payload['contact_id'] ) ) {
        $payload['contact_url'] = fluentcrm_menu_url_base( 'subscribers/' . $payload['contact_id'] );
    }

    $tags = array();
    if ( ! empty( $contact_model->tags ) ) {
        foreach ( $contact_model->tags as $tag ) {
            $tags[] = array(
                'id'    => $tag->id,
                'title' => $tag->title,
                'slug'  => $tag->slug,
            );
        }
    }

    $lists = array();
    if ( ! empty( $contact_model->lists ) ) {
        foreach ( $contact_model->lists as $list ) {
            $lists[] = array(
                'id'    => $list->id,
                'title' => $list->title,
                'slug'  => $list->slug ?? '',
            );
        }
    }

    $companies = array();
    if ( ! empty( $contact_model->companies ) ) {
        foreach ( $contact_model->companies as $company ) {
            $companies[] = array(
                'id'    => $company->id,
                'title' => $company->title ?? '',
                'email' => $company->email ?? '',
            );
        }
    }

    if ( method_exists( $contact_model, 'custom_fields' ) ) {
        $custom_fields = $contact_model->custom_fields();
        if ( ! empty( $custom_fields ) ) {
            $payload['custom_fields'] = wp_json_encode( $custom_fields );
        }
    }

    if ( $tags ) {
        $payload['tags'] = wp_json_encode( $tags );
    }

    if ( $lists ) {
        $payload['lists'] = wp_json_encode( $lists );
    }

    if ( $companies ) {
        $payload['companies'] = wp_json_encode( $companies );
    }

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Prepare collection payload (tags/lists).
 *
 * @param array  $ids   Entity IDs.
 * @param string $class Model class.
 *
 * @return array
 */
function adfoin_fluentcrm_prepare_collection_payload( $ids, $class ) {
    if ( empty( $ids ) || ! class_exists( $class ) ) {
        return array();
    }

    $items = $class::whereIn( 'id', (array) $ids )
        ->get( array( 'id', 'title', 'slug' ) )
        ->map( function ( $item ) {
            return array(
                'id'    => $item->id,
                'title' => $item->title,
                'slug'  => $item->slug ?? '',
            );
        } )
        ->values()
        ->toArray();

    return $items;
}

/**
 * Dispatch data to FluentCRM integrations.
 *
 * @param string $trigger Trigger key.
 * @param array  $payload Payload data.
 *
 * @return void
 */
function adfoin_fluentcrm_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $records     = $integration->get_by_trigger( 'fluentcrm', $trigger );

    if ( empty( $records ) ) {
        return;
    }

    $integration->send( $records, $payload );
}

// Handle contact created events.
add_action( 'fluent_crm/contact_created', 'adfoin_fluentcrm_handle_contact_created', 10, 1 );
function adfoin_fluentcrm_handle_contact_created( $contact ) {
    $payload = adfoin_fluentcrm_prepare_contact_payload( $contact );

    adfoin_fluentcrm_dispatch( 'contactCreated', $payload );
}

// Handle contact updated events.
add_action( 'fluent_crm/contact_updated', 'adfoin_fluentcrm_handle_contact_updated', 10, 2 );
function adfoin_fluentcrm_handle_contact_updated( $contact, $updated_fields = array() ) {
    $payload = adfoin_fluentcrm_prepare_contact_payload( $contact );

    if ( ! empty( $updated_fields ) ) {
        $payload['updated_fields'] = wp_json_encode( $updated_fields );
    }

    adfoin_fluentcrm_dispatch( 'contactUpdated', $payload );
}

// Handle tags added events.
add_action( 'fluentcrm_contact_added_to_tags', 'adfoin_fluentcrm_handle_tags_added', 10, 2 );
function adfoin_fluentcrm_handle_tags_added( $tag_ids, $contact ) {
    $payload = adfoin_fluentcrm_prepare_contact_payload( $contact );

    $tag_ids = array_map( 'intval', (array) $tag_ids );
    $tag_ids = array_filter( $tag_ids );

    if ( $tag_ids ) {
        $payload['tag_ids']    = implode( ',', $tag_ids );
        $tags                  = adfoin_fluentcrm_prepare_collection_payload( $tag_ids, '\FluentCrm\App\Models\Tag' );
        $payload['tags_added'] = $tags ? wp_json_encode( $tags ) : '';
    }

    adfoin_fluentcrm_dispatch( 'tagsAdded', $payload );
}

// Handle lists added events.
add_action( 'fluentcrm_contact_added_to_lists', 'adfoin_fluentcrm_handle_lists_added', 10, 2 );
function adfoin_fluentcrm_handle_lists_added( $list_ids, $contact ) {
    $payload = adfoin_fluentcrm_prepare_contact_payload( $contact );

    $list_ids = array_map( 'intval', (array) $list_ids );
    $list_ids = array_filter( $list_ids );

    if ( $list_ids ) {
        $payload['list_ids']    = implode( ',', $list_ids );
        $lists                  = adfoin_fluentcrm_prepare_collection_payload( $list_ids, '\FluentCrm\App\Models\Lists' );
        $payload['lists_added'] = $lists ? wp_json_encode( $lists ) : '';
    }

    adfoin_fluentcrm_dispatch( 'listsAdded', $payload );
}

// Handle subscriber status change events.
add_action( 'fluent_crm/subscriber_status_changed', 'adfoin_fluentcrm_handle_status_changed', 10, 3 );
function adfoin_fluentcrm_handle_status_changed( $contact, $old_status, $new_status ) {
    $payload = adfoin_fluentcrm_prepare_contact_payload( $contact );

    $payload['old_status'] = $old_status;
    $payload['new_status'] = $new_status;

    adfoin_fluentcrm_dispatch( 'statusChanged', $payload );
}
