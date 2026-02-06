<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve Site Reviews triggers.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_sitereviews_get_forms( $form_provider ) {
    if ( 'sitereviews' !== $form_provider ) {
        return;
    }

    return array(
        'reviewCreated'      => __( 'Review Created', 'advanced-form-integration' ),
        'reviewUpdated'      => __( 'Review Updated', 'advanced-form-integration' ),
        'reviewTransitioned' => __( 'Review Status Changed', 'advanced-form-integration' ),
        'reviewApproved'     => __( 'Review Approved', 'advanced-form-integration' ),
    );
}

/**
 * Common review fields available to every trigger.
 *
 * @return array<string,string>
 */
function adfoin_sitereviews_base_fields() {
    return array(
        'review_id'               => __( 'Review ID', 'advanced-form-integration' ),
        'review_post_id'          => __( 'Review Post ID', 'advanced-form-integration' ),
        'review_title'            => __( 'Review Title', 'advanced-form-integration' ),
        'review_content'          => __( 'Review Content', 'advanced-form-integration' ),
        'review_rating'           => __( 'Review Rating', 'advanced-form-integration' ),
        'review_score'            => __( 'Review Score', 'advanced-form-integration' ),
        'review_status'           => __( 'Review Status', 'advanced-form-integration' ),
        'review_type'             => __( 'Review Type', 'advanced-form-integration' ),
        'review_type_key'         => __( 'Review Type Key', 'advanced-form-integration' ),
        'review_date'             => __( 'Review Date', 'advanced-form-integration' ),
        'review_date_gmt'         => __( 'Review Date GMT', 'advanced-form-integration' ),
        'review_url'              => __( 'Review Permalink', 'advanced-form-integration' ),
        'review_author'           => __( 'Reviewer Name', 'advanced-form-integration' ),
        'review_author_email'     => __( 'Reviewer Email', 'advanced-form-integration' ),
        'review_author_id'        => __( 'Reviewer User ID', 'advanced-form-integration' ),
        'review_ip_address'       => __( 'Reviewer IP Address', 'advanced-form-integration' ),
        'review_is_approved'      => __( 'Review Approved', 'advanced-form-integration' ),
        'review_is_verified'      => __( 'Review Verified', 'advanced-form-integration' ),
        'review_is_pinned'        => __( 'Review Pinned', 'advanced-form-integration' ),
        'review_assigned_posts'   => __( 'Assigned Posts', 'advanced-form-integration' ),
        'review_assigned_terms'   => __( 'Assigned Terms', 'advanced-form-integration' ),
        'review_assigned_users'   => __( 'Assigned Users', 'advanced-form-integration' ),
        'review_custom_fields'    => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
        'review_response'         => __( 'Review Response', 'advanced-form-integration' ),
        'review_response_by'      => __( 'Review Response Author ID', 'advanced-form-integration' ),
        'review_url_source'       => __( 'Review Source URL', 'advanced-form-integration' ),
    );
}

/**
 * Retrieve mapped fields for Site Reviews triggers.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_sitereviews_get_form_fields( $form_provider, $form_id ) {
    if ( 'sitereviews' !== $form_provider ) {
        return;
    }

    $fields = adfoin_sitereviews_base_fields();

    switch ( $form_id ) {
        case 'reviewCreated':
            $fields['form_id']            = __( 'Form ID', 'advanced-form-integration' );
            $fields['form_referer']       = __( 'Form Referer', 'advanced-form-integration' );
            $fields['submission_success'] = __( 'Submission Successful', 'advanced-form-integration' );
            $fields['submission_message'] = __( 'Submission Message', 'advanced-form-integration' );
            $fields['request_data']       = __( 'Submitted Data (JSON)', 'advanced-form-integration' );
            break;

        case 'reviewUpdated':
            $fields['updated_fields'] = __( 'Updated Fields (JSON)', 'advanced-form-integration' );
            $fields['previous_status'] = __( 'Previous Status', 'advanced-form-integration' );
            break;

        case 'reviewTransitioned':
            $fields['new_status']  = __( 'New Status', 'advanced-form-integration' );
            $fields['old_status']  = __( 'Old Status', 'advanced-form-integration' );
            break;

        case 'reviewApproved':
            $fields['previous_status'] = __( 'Previous Status', 'advanced-form-integration' );
            break;
    }

    return $fields;
}

/**
 * Normalize a value for payload transport.
 *
 * @param mixed $value Value to normalize.
 *
 * @return string
 */
function adfoin_sitereviews_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Collect review data for payloads.
 *
 * @param \GeminiLabs\SiteReviews\Review $review Review object.
 *
 * @return array<string,string>
 */
function adfoin_sitereviews_collect_review_data( $review ) {
    if ( ! $review instanceof \GeminiLabs\SiteReviews\Review ) {
        return array();
    }

    $data = $review->toArray();

    $payload = array(
        'review_id'             => adfoin_sitereviews_normalize_value( $review->get( 'rating_id' ) ?: $review->ID ),
        'review_post_id'        => adfoin_sitereviews_normalize_value( $review->ID ),
        'review_title'          => adfoin_sitereviews_normalize_value( $review->get( 'title' ) ),
        'review_content'        => adfoin_sitereviews_normalize_value( $review->get( 'content' ) ),
        'review_rating'         => adfoin_sitereviews_normalize_value( $review->get( 'rating' ) ),
        'review_score'          => adfoin_sitereviews_normalize_value( $review->get( 'score' ) ),
        'review_status'         => adfoin_sitereviews_normalize_value( $review->get( 'status' ) ),
        'review_type'           => adfoin_sitereviews_normalize_value( method_exists( $review, 'type' ) ? $review->type() : $review->get( 'type' ) ),
        'review_type_key'       => adfoin_sitereviews_normalize_value( $review->get( 'type' ) ),
        'review_date'           => adfoin_sitereviews_normalize_value( $review->get( 'date' ) ),
        'review_date_gmt'       => adfoin_sitereviews_normalize_value( $review->get( 'date_gmt' ) ),
        'review_url'            => adfoin_sitereviews_normalize_value( $review->get( 'url' ) ),
        'review_author'         => adfoin_sitereviews_normalize_value( $review->get( 'author' ) ),
        'review_author_email'   => adfoin_sitereviews_normalize_value( $review->get( 'email' ) ),
        'review_author_id'      => adfoin_sitereviews_normalize_value( $review->get( 'author_id' ) ),
        'review_ip_address'     => adfoin_sitereviews_normalize_value( $review->get( 'ip_address' ) ),
        'review_is_approved'    => adfoin_sitereviews_normalize_value( $review->get( 'is_approved' ) ),
        'review_is_verified'    => adfoin_sitereviews_normalize_value( $review->get( 'is_verified' ) ),
        'review_is_pinned'      => adfoin_sitereviews_normalize_value( $review->get( 'is_pinned' ) ),
        'review_assigned_posts' => adfoin_sitereviews_normalize_value( $review->get( 'assigned_posts' ) ),
        'review_assigned_terms' => adfoin_sitereviews_normalize_value( $review->get( 'assigned_terms' ) ),
        'review_assigned_users' => adfoin_sitereviews_normalize_value( $review->get( 'assigned_users' ) ),
        'review_response'       => adfoin_sitereviews_normalize_value( $review->get( 'response' ) ),
        'review_response_by'    => adfoin_sitereviews_normalize_value( $review->get( 'response_by' ) ),
        'review_url_source'     => adfoin_sitereviews_normalize_value( $review->get( 'referer' ) ),
    );

    $custom_fields = array();

    if ( method_exists( $review, 'custom' ) ) {
        $custom = $review->custom();
        if ( $custom instanceof \GeminiLabs\SiteReviews\Arguments ) {
            foreach ( $custom->toArray() as $key => $value ) {
                $custom_fields[ $key ] = adfoin_sitereviews_normalize_value( $value );
            }
        }
    }

    if ( ! empty( $custom_fields ) ) {
        $payload['review_custom_fields'] = wp_json_encode( $custom_fields );
    }

    return $payload;
}

/**
 * Collect additional request data from the submission command.
 *
 * @param \GeminiLabs\SiteReviews\Commands\CreateReview $command Command.
 *
 * @return array<string,string>
 */
function adfoin_sitereviews_collect_command_data( $command ) {
    if ( ! $command instanceof \GeminiLabs\SiteReviews\Commands\CreateReview ) {
        return array();
    }

    $response = method_exists( $command, 'response' ) ? $command->response() : array();

    $payload = array(
        'form_id'            => adfoin_sitereviews_normalize_value( $command->form_id ?? '' ),
        'form_referer'       => adfoin_sitereviews_normalize_value( $command->referer() ),
        'submission_success' => adfoin_sitereviews_normalize_value( $command->successful() ),
        'submission_message' => adfoin_sitereviews_normalize_value( isset( $response['message'] ) ? $response['message'] : '' ),
    );

    if ( isset( $command->request ) && $command->request instanceof \GeminiLabs\SiteReviews\Request ) {
        $payload['request_data'] = adfoin_sitereviews_normalize_value( $command->request->toArray() );
    }

    return $payload;
}

/**
 * Dispatch payload to saved automation records.
 *
 * @param string               $trigger Trigger key.
 * @param array<string,string> $payload Payload.
 *
 * @return void
 */
function adfoin_sitereviews_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'sitereviews', $trigger );

    if ( empty( $saved_records ) ) {
        return;
    }

    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];

        if ( $job_queue ) {
            as_enqueue_async_action(
                "adfoin_{$action_provider}_job_queue",
                array(
                    'data' => array(
                        'record'      => $record,
                        'posted_data' => $payload,
                    ),
                )
            );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $payload );
        }
    }
}

add_action( 'site-reviews/review/created', 'adfoin_sitereviews_on_review_created', 20, 2 );

/**
 * Handle review creation.
 *
 * @param \GeminiLabs\SiteReviews\Review                 $review  Review object.
 * @param \GeminiLabs\SiteReviews\Commands\CreateReview $command Submission command.
 *
 * @return void
 */
function adfoin_sitereviews_on_review_created( $review, $command ) {
    $payload = adfoin_sitereviews_collect_review_data( $review );
    $payload = array_merge( $payload, adfoin_sitereviews_collect_command_data( $command ) );
    adfoin_sitereviews_dispatch( 'reviewCreated', array_map( 'trim', $payload ) );
}

add_action( 'site-reviews/review/updated', 'adfoin_sitereviews_on_review_updated', 20, 3 );

/**
 * Handle review updates.
 *
 * @param \GeminiLabs\SiteReviews\Review $review  Review object.
 * @param array                          $data    Updated values.
 * @param \WP_Post|null                  $oldPost Previous post object.
 *
 * @return void
 */
function adfoin_sitereviews_on_review_updated( $review, $data, $oldPost ) {
    $payload = adfoin_sitereviews_collect_review_data( $review );
    $payload['updated_fields'] = adfoin_sitereviews_normalize_value( $data );

    if ( $oldPost instanceof \WP_Post ) {
        $payload['previous_status'] = adfoin_sitereviews_normalize_value( $oldPost->post_status );
    }

    adfoin_sitereviews_dispatch( 'reviewUpdated', array_map( 'trim', $payload ) );
}

add_action( 'site-reviews/review/transitioned', 'adfoin_sitereviews_on_review_transitioned', 20, 3 );

/**
 * Handle review status transitions.
 *
 * @param \GeminiLabs\SiteReviews\Review $review Review object.
 * @param string                         $new    New status.
 * @param string                         $old    Previous status.
 *
 * @return void
 */
function adfoin_sitereviews_on_review_transitioned( $review, $new, $old ) {
    $payload = adfoin_sitereviews_collect_review_data( $review );
    $payload['new_status'] = adfoin_sitereviews_normalize_value( $new );
    $payload['old_status'] = adfoin_sitereviews_normalize_value( $old );

    adfoin_sitereviews_dispatch( 'reviewTransitioned', array_map( 'trim', $payload ) );
}

add_action( 'site-reviews/review/approved', 'adfoin_sitereviews_on_review_approved', 20, 3 );

/**
 * Handle review approvals.
 *
 * @param \GeminiLabs\SiteReviews\Review $review Review object.
 * @param string                         $old    Previous status.
 * @param string                         $new    New status.
 *
 * @return void
 */
function adfoin_sitereviews_on_review_approved( $review, $old, $new ) {
    $payload = adfoin_sitereviews_collect_review_data( $review );
    $payload['previous_status'] = adfoin_sitereviews_normalize_value( $old );
    $payload['new_status']      = adfoin_sitereviews_normalize_value( $new );

    adfoin_sitereviews_dispatch( 'reviewApproved', array_map( 'trim', $payload ) );
}
