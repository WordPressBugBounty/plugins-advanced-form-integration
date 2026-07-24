<?php

/**
 * Site Reviews action platform — local same-site integration (no REST/API
 * keys). Slug is `sitereviewsac`, not `sitereviews` — the trigger side
 * already uses that slug (includes/triggers/sitereviews/sitereviews.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Review creation goes through the real, documented global function
 * glsr_create_review( array $values ) (helpers.php), confirmed against the
 * plugin's own source — it validates the request via a CreateReview command
 * and returns a Review object (a WP_Post subclass) on success, or false on
 * failure.
 *
 * @link https://plugins.trac.wordpress.org/browser/site-reviews/trunk/helpers.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_sitereviewsac_actions', 10, 1 );

function adfoin_sitereviewsac_actions( $actions ) {

    $actions['sitereviewsac'] = array(
        'title' => __( 'Site Reviews', 'advanced-form-integration' ),
        'tasks' => array(
            'add_review' => __( 'Add Review', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_sitereviewsac_action_fields' );

function adfoin_sitereviewsac_action_fields() {
    ?>
    <script type="text/template" id="sitereviewsac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_review'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_sitereviewsac_fields', 'adfoin_get_sitereviewsac_fields', 10, 0 );

function adfoin_get_sitereviewsac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'name', 'value' => __( 'Name', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'title', 'value' => __( 'Review Title', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'content', 'value' => __( 'Review Content', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'rating', 'value' => __( 'Rating', 'advanced-form-integration' ), 'description' => __( 'Required. A number from 1 to 5.', 'advanced-form-integration' ) ),
        array( 'key' => 'post_id', 'value' => __( 'Assigned Post ID', 'advanced-form-integration' ), 'description' => __( 'Optional. The post this review is about.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_sitereviewsac_job_queue', 'adfoin_sitereviewsac_job_queue', 10, 1 );

function adfoin_sitereviewsac_job_queue( $data ) {
    adfoin_sitereviewsac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a Site Reviews review
 */
function adfoin_sitereviewsac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'glsr_create_review' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_review' !== $task ) {
        return;
    }

    $prepared_data = array();

    foreach ( $field_data as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed_value || null === $parsed_value ) {
            continue;
        }

        $prepared_data[ $key ] = $parsed_value;
    }

    $request_payload = $prepared_data;
    $response_body   = array( 'success' => false );
    $status_code     = 400;

    if ( empty( $prepared_data['name'] ) || empty( $prepared_data['content'] ) || empty( $prepared_data['rating'] ) ) {
        $response_body['message'] = __( 'Name, content, and rating are all required.', 'advanced-form-integration' );
    } else {
        $review = glsr_create_review( $prepared_data );

        if ( $review ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Review added successfully.', 'advanced-form-integration' );
            $response_body['id']      = is_object( $review ) && isset( $review->ID ) ? $review->ID : true;
        } else {
            $response_body['message'] = __( 'Failed to add the review. Please verify the supplied data.', 'advanced-form-integration' );
        }
    }

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    $log_response = array(
        'response' => array(
            'code'    => $status_code,
            'message' => $response_body['message'],
        ),
        'body'     => $response_body,
    );

    adfoin_add_to_log( $log_response, 'sitereviewsac', $log_args, $record );
}
