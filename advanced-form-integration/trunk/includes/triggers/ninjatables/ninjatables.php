<?php

// Get Ninja Tables triggers.
function adfoin_ninjatables_get_forms( $form_provider ) {
    if ( $form_provider !== 'ninjatables' ) {
        return;
    }

    return array(
        'rowAdded'   => __( 'Row Added', 'advanced-form-integration' ),
        'rowUpdated' => __( 'Row Updated', 'advanced-form-integration' ),
        'rowDeleted' => __( 'Row Deleted', 'advanced-form-integration' ),
    );
}

// Get Ninja Tables fields.
function adfoin_ninjatables_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'ninjatables' ) {
        return;
    }

    $fields = array(
        'table_id'        => __( 'Table ID', 'advanced-form-integration' ),
        'table_name'      => __( 'Table Name', 'advanced-form-integration' ),
        'table_slug'      => __( 'Table Slug', 'advanced-form-integration' ),
        'table_status'    => __( 'Table Status', 'advanced-form-integration' ),
        'table_shortcode' => __( 'Table Shortcode', 'advanced-form-integration' ),
        'table_url'       => __( 'Table URL', 'advanced-form-integration' ),
        'event_action'    => __( 'Event Action', 'advanced-form-integration' ),

        'row_id'          => __( 'Row ID', 'advanced-form-integration' ),
        'row_attribute'   => __( 'Row Attribute', 'advanced-form-integration' ),
        'row_values'      => __( 'Row Values (JSON)', 'advanced-form-integration' ),
        'row_settings'    => __( 'Row Settings (JSON)', 'advanced-form-integration' ),
        'created_at'      => __( 'Row Created At', 'advanced-form-integration' ),
        'updated_at'      => __( 'Row Updated At', 'advanced-form-integration' ),

        'owner_id'        => __( 'Owner ID', 'advanced-form-integration' ),
        'owner_email'     => __( 'Owner Email', 'advanced-form-integration' ),
        'owner_login'     => __( 'Owner Login', 'advanced-form-integration' ),
        'owner_display'   => __( 'Owner Display Name', 'advanced-form-integration' ),
        'owner_first_name'=> __( 'Owner First Name', 'advanced-form-integration' ),
        'owner_last_name' => __( 'Owner Last Name', 'advanced-form-integration' ),
        'owner_role'      => __( 'Owner Role', 'advanced-form-integration' ),
        'owner_registered'=> __( 'Owner Registered', 'advanced-form-integration' ),

        'raw_attributes'  => __( 'Raw Attributes (JSON)', 'advanced-form-integration' ),
    );

    if ( 'rowUpdated' === $form_id ) {
        $fields['updated_attributes'] = __( 'Updated Attributes (JSON)', 'advanced-form-integration' );
    }

    return $fields;
}

/**
 * Dispatch Ninja Tables payloads.
 *
 * @param string $trigger Trigger name.
 * @param array  $payload Payload data.
 *
 * @return void
 */
function adfoin_ninjatables_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $records     = $integration->get_by_trigger( 'ninjatables', $trigger );

    if ( empty( $records ) ) {
        return;
    }

    $integration->send( $records, $payload );
}

/**
 * Prepare row payload from database.
 *
 * @param int    $row_id Row ID.
 * @param int    $table_id Table ID.
 * @param string $action Event action.
 *
 * @return array
 */
function adfoin_ninjatables_prepare_row_payload( $row_id, $table_id, $action ) {
    global $wpdb;

    $table_items = $wpdb->prefix . 'ninja_table_items';
    $row         = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_items} WHERE id = %d AND table_id = %d", $row_id, $table_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    $payload = array(
        'table_id'     => $table_id,
        'row_id'       => $row_id,
        'event_action' => $action,
    );

    if ( $table = get_post( $table_id ) ) {
        $payload['table_name']      = $table->post_title;
        $payload['table_slug']      = $table->post_name;
        $payload['table_status']    = $table->post_status;
        $payload['table_shortcode'] = sprintf( '[ninja_table id="%d"]', $table_id );
        $payload['table_url']       = get_permalink( $table_id );
    }

    if ( ! $row ) {
        return array_filter( $payload );
    }

    $payload['row_attribute'] = $row['attribute'] ?? '';
    $payload['owner_id']      = $row['owner_id'] ?? '';
    $payload['created_at']    = $row['created_at'] ?? '';
    $payload['updated_at']    = $row['updated_at'] ?? '';

    if ( ! empty( $row['settings'] ) ) {
        $settings = maybe_unserialize( $row['settings'] );
        $payload['row_settings'] = wp_json_encode( $settings );
    }

    $row_values = adfoin_ninjatables_decode_value( $row['value'] ?? '' );
    if ( ! empty( $row_values ) ) {
        $payload['row_values'] = wp_json_encode( $row_values );
    }

    // Owner information.
    if ( ! empty( $row['owner_id'] ) ) {
        $owner = get_userdata( (int) $row['owner_id'] );
        if ( $owner ) {
            $payload['owner_email']      = $owner->user_email;
            $payload['owner_login']      = $owner->user_login;
            $payload['owner_display']    = $owner->display_name;
            $payload['owner_first_name'] = get_user_meta( $owner->ID, 'first_name', true );
            $payload['owner_last_name']  = get_user_meta( $owner->ID, 'last_name', true );
            $payload['owner_role']       = ! empty( $owner->roles ) ? implode( ',', (array) $owner->roles ) : '';
            $payload['owner_registered'] = $owner->user_registered;
        }
    }

    // Map raw values by column key.
    foreach ( $row_values as $key => $value ) {
        $payload[ 'row_' . $key ] = adfoin_ninjatables_stringify( $value );
    }

    // Map values by column label.
    if ( function_exists( 'ninja_table_get_table_columns' ) ) {
        $columns = ninja_table_get_table_columns( $table_id, 'admin' );
        if ( is_array( $columns ) ) {
            foreach ( $columns as $column ) {
                $column_key = $column['key'] ?? '';
                if ( ! $column_key || ! isset( $row_values[ $column_key ] ) ) {
                    continue;
                }

                $label = $column['name'] ?? ( $column['label'] ?? $column_key );
                $slug  = adfoin_ninjatables_slugify( $label );

                $payload[ 'column_' . $slug ] = adfoin_ninjatables_stringify( $row_values[ $column_key ] );
            }
        }
    }

    return array_filter( $payload, 'adfoin_ninjatables_filter_empty_values' );
}

/**
 * Decode stored Ninja Tables row values.
 *
 * @param string $value Stored value.
 *
 * @return array
 */
function adfoin_ninjatables_decode_value( $value ) {
    if ( empty( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );
    if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
        return $decoded;
    }

    $maybe = maybe_unserialize( $value );
    if ( is_array( $maybe ) ) {
        return $maybe;
    }

    return array();
}

/**
 * Stringify values for payload.
 *
 * @param mixed $value Value to stringify.
 *
 * @return string
 */
function adfoin_ninjatables_stringify( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? '1' : '0';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    if ( is_array( $value ) || is_object( $value ) ) {
        return wp_json_encode( $value );
    }

    return '';
}

/**
 * Create slug-like key for column labels.
 *
 * @param string $label Column label.
 *
 * @return string
 */
function adfoin_ninjatables_slugify( $label ) {
    $slug = sanitize_title( $label );
    if ( ! $slug ) {
        $slug = preg_replace( '/[^a-z0-9]+/i', '_', $label );
    }

    return trim( $slug, '_' );
}

/**
 * Filter callback to keep non-empty values.
 *
 * @param mixed $value Value to test.
 *
 * @return bool
 */
function adfoin_ninjatables_filter_empty_values( $value ) {
    return $value !== null && $value !== '';
}

// Handle row additions.
add_action( 'ninja_table_after_add_item', 'adfoin_ninjatables_handle_row_added', 10, 3 );
function adfoin_ninjatables_handle_row_added( $insert_id, $table_id, $attributes ) {
    $payload = adfoin_ninjatables_prepare_row_payload( $insert_id, $table_id, 'added' );

    if ( ! empty( $attributes ) ) {
        $payload['raw_attributes'] = wp_json_encode( $attributes );
    }

    adfoin_ninjatables_dispatch( 'rowAdded', $payload );
}

// Handle row updates.
add_action( 'ninja_table_after_update_item', 'adfoin_ninjatables_handle_row_updated', 10, 3 );
function adfoin_ninjatables_handle_row_updated( $row_id, $table_id, $attributes ) {
    $payload = adfoin_ninjatables_prepare_row_payload( $row_id, $table_id, 'updated' );

    if ( ! empty( $attributes ) ) {
        $payload['raw_attributes']    = wp_json_encode( $attributes );
        $payload['updated_attributes'] = wp_json_encode( $attributes );
    }

    adfoin_ninjatables_dispatch( 'rowUpdated', $payload );
}

// Handle row deletions.
add_action( 'ninja_table_before_items_deleted', 'adfoin_ninjatables_handle_row_deleted', 10, 2 );
function adfoin_ninjatables_handle_row_deleted( $row_ids, $table_id ) {
    if ( empty( $row_ids ) ) {
        return;
    }

    foreach ( (array) $row_ids as $row_id ) {
        $payload = adfoin_ninjatables_prepare_row_payload( $row_id, $table_id, 'deleted' );
        adfoin_ninjatables_dispatch( 'rowDeleted', $payload );
    }
}
