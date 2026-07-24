<?php

/**
 * WP Job Manager action platform — local same-site integration (no REST/API
 * keys). A job listing is just a `job_listing` custom post type with a small
 * set of meta keys, confirmed against the plugin's own source
 * (includes/class-wp-job-manager-post-types.php, get_job_listing_fields()):
 * `_company_name`, `_company_website`, `_job_location`, `_application`.
 * `_filled` / `_featured` default meta is auto-added by the plugin itself on
 * save (maybe_add_default_meta_data(), hooked to post save for the
 * `job_listing` post type), so it isn't set here. The optional job type is a
 * real taxonomy, `job_listing_type` (WP_Job_Manager_Post_Types::TAX_LISTING_TYPE).
 *
 * @link https://plugins.trac.wordpress.org/browser/wp-job-manager/trunk/includes/class-wp-job-manager-post-types.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_wpjobmanagerac_actions', 10, 1 );

function adfoin_wpjobmanagerac_actions( $actions ) {

    $actions['wpjobmanagerac'] = array(
        'title' => __( 'WP Job Manager', 'advanced-form-integration' ),
        'tasks' => array(
            'add_job_listing' => __( 'Create Job Listing', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_wpjobmanagerac_action_fields' );

function adfoin_wpjobmanagerac_action_fields() {
    ?>
    <script type="text/template" id="wpjobmanagerac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_job_listing'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_job_listing'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Job Type', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[jobTypeId]" v-model="fielddata.jobTypeId">
                        <option value=""><?php _e( 'Use None... (optional)', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.jobTypeList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': jobTypeLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_wpjobmanagerac_job_types', 'adfoin_get_wpjobmanagerac_job_types', 10, 0 );

function adfoin_get_wpjobmanagerac_job_types() {
    adfoin_verify_nonce();

    if ( ! post_type_exists( 'job_listing' ) ) {
        wp_send_json_error( __( 'WP Job Manager is not active.', 'advanced-form-integration' ) );
    }

    $terms = get_terms(
        array(
            'taxonomy'   => 'job_listing_type',
            'hide_empty' => false,
        )
    );
    $types = array();

    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $types[ $term->term_id ] = $term->name;
        }
    }

    wp_send_json_success( $types );
}

add_action( 'wp_ajax_adfoin_get_wpjobmanagerac_fields', 'adfoin_get_wpjobmanagerac_fields', 10, 0 );

function adfoin_get_wpjobmanagerac_fields() {
    adfoin_verify_nonce();

    if ( ! post_type_exists( 'job_listing' ) ) {
        wp_send_json_error( __( 'WP Job Manager is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'company_website', 'value' => __( 'Company Website', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'job_location', 'value' => __( 'Job Location', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'application', 'value' => __( 'Application Email or URL', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_wpjobmanagerac_job_queue', 'adfoin_wpjobmanagerac_job_queue', 10, 1 );

function adfoin_wpjobmanagerac_job_queue( $data ) {
    adfoin_wpjobmanagerac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a WP Job Manager job listing
 */
function adfoin_wpjobmanagerac_send_data( $record, $posted_data ) {

    if ( ! post_type_exists( 'job_listing' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_job_listing' !== $task ) {
        return;
    }

    $job_type_id = isset( $field_data['jobTypeId'] ) ? absint( $field_data['jobTypeId'] ) : 0;
    unset( $field_data['jobTypeId'] );

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

    if ( empty( $prepared_data['job_title'] ) ) {
        $response_body['message'] = __( 'A job title is required to create a WP Job Manager listing.', 'advanced-form-integration' );
    } else {
        $post_id = wp_insert_post(
            array(
                'post_type'    => 'job_listing',
                'post_title'   => $prepared_data['job_title'],
                'post_content' => isset( $prepared_data['description'] ) ? $prepared_data['description'] : '',
                'post_status'  => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $response_body['message'] = __( 'Failed to create the job listing. Please verify the supplied data.', 'advanced-form-integration' );
        } else {
            $meta_map = array(
                'company_name'    => '_company_name',
                'company_website' => '_company_website',
                'job_location'    => '_job_location',
                'application'     => '_application',
            );

            foreach ( $meta_map as $field_key => $meta_key ) {
                if ( isset( $prepared_data[ $field_key ] ) ) {
                    update_post_meta( $post_id, $meta_key, $prepared_data[ $field_key ] );
                }
            }

            if ( $job_type_id ) {
                wp_set_object_terms( $post_id, array( $job_type_id ), 'job_listing_type' );
            }

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Job listing created successfully.', 'advanced-form-integration' );
            $response_body['id']      = $post_id;
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

    adfoin_add_to_log( $log_response, 'wpjobmanagerac', $log_args, $record );
}
