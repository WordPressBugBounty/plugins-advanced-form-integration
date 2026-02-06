<?php

// Get WP Post Ratings Triggers
function adfoin_wppostratings_get_forms( $form_provider ) {
    if ( $form_provider !== 'wppostratings' ) {
        return;
    }

    $triggers = array(
        'ratePost' => __( 'Post Rated', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get WP Post Ratings Fields
function adfoin_wppostratings_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wppostratings' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'ratePost' ) {
        $fields = [
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'post_id' => __( 'Post ID', 'advanced-form-integration' ),
            'post_title' => __( 'Post Title', 'advanced-form-integration' ),
            'rating_value' => __( 'Rating Value', 'advanced-form-integration' ),
            'rating_date' => __( 'Rating Date', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_wppostratings_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_id'] = $user_id;
        $user_data['user_email'] = $user->user_email;
    }

    return $user_data;
}

// Handle Post Rating
add_action( 'rate_post', 'adfoin_wppostratings_handle_rate_post', 10, 3 );
function adfoin_wppostratings_handle_rate_post( $user_id, $post_id, $rating_value ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wppostratings', 'ratePost' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // Bail if user is not logged in
    if ( $user_id === 0 ) {
        return;
    }

    $post = get_post( $post_id );

    if ( ! $post ) {
        return;
    }

    $posted_data = [
        'user_id' => $user_id,
        'post_id' => $post_id,
        'post_title' => $post->post_title,
        'rating_value' => $rating_value,
        'rating_date' => current_time( 'mysql' ),
    ];

    $user_data = adfoin_wppostratings_get_userdata( $user_id );
    $posted_data = array_merge( $posted_data, $user_data );

    $posted_data['post_id'] = $post_id;

    $integration->send( $saved_records, $posted_data );
}