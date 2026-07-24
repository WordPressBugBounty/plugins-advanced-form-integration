<?php

/**
 * Groundhogg action platform — local same-site integration (no REST/API
 * keys). Contact creation goes through Groundhogg's own
 * \Groundhogg\generate_contact_with_map() helper (includes/functions.php),
 * which is the same function Groundhogg's own form integrations use: passed
 * an associative array with an identity field map, it looks up/creates the
 * Contact by email, updates core + meta fields, and applies tags. Tag
 * listing uses \Groundhogg\get_db( 'tags' )->query() (wraps the gh_tags
 * table); custom field listing uses \Groundhogg\Properties::instance()->get_fields().
 *
 * @link https://plugins.trac.wordpress.org/browser/groundhoggac/trunk/includes/functions.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_groundhoggac_actions', 10, 1 );

function adfoin_groundhoggac_actions( $actions ) {

    $actions['groundhoggac'] = array(
        'title' => __( 'Groundhogg', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_groundhoggac_action_fields' );

function adfoin_groundhoggac_action_fields() {
    ?>
    <script type="text/template" id="groundhoggac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Tag', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[tagId]" v-model="fielddata.tagId">
                        <option value=""><?php _e( 'Select Tag... (optional)', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.tagList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': tagLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_groundhoggac_tags', 'adfoin_get_groundhoggac_tags', 10, 0 );

/*
 * Get Groundhogg tags
 */
function adfoin_get_groundhoggac_tags() {
    adfoin_verify_nonce();

    if ( ! function_exists( '\Groundhogg\get_db' ) ) {
        wp_send_json_error( __( 'Groundhogg is not active.', 'advanced-form-integration' ) );
    }

    $raw_tags = \Groundhogg\get_db( 'tags' )->query();
    $tags     = array();

    if ( is_array( $raw_tags ) ) {
        foreach ( $raw_tags as $tag ) {
            $tags[ $tag->tag_id ] = $tag->tag_name;
        }
    }

    wp_send_json_success( $tags );
}

add_action( 'wp_ajax_adfoin_get_groundhoggac_contact_fields', 'adfoin_get_groundhoggac_contact_fields', 10, 0 );

/*
 * Get Groundhogg contact fields
 */
function adfoin_get_groundhoggac_contact_fields() {
    adfoin_verify_nonce();

    if ( ! function_exists( '\Groundhogg\get_db' ) ) {
        wp_send_json_error( __( 'Groundhogg is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Used to find or create the contact.', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'primary_phone', 'value' => __( 'Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'mobile_phone', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'street_address_1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'street_address_2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'region', 'value' => __( 'State / Region', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'postal_zip', 'value' => __( 'Postal / ZIP Code', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ), 'description' => __( '2-letter country code (e.g. US).', 'advanced-form-integration' ) ),
        array( 'key' => 'lead_source', 'value' => __( 'Lead Source', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'notes', 'value' => __( 'Note', 'advanced-form-integration' ), 'description' => '' ),
    );

    $custom_fields = adfoin_get_groundhoggac_custom_fields();

    foreach ( $custom_fields as $key => $label ) {
        $fields[] = array( 'key' => $key, 'value' => $label, 'description' => '' );
    }

    wp_send_json_success( $fields );
}

function adfoin_get_groundhoggac_custom_fields() {
    if ( ! class_exists( '\Groundhogg\Properties' ) ) {
        return array();
    }

    $properties = \Groundhogg\Properties::instance()->get_fields();
    $fields     = array();

    if ( is_array( $properties ) ) {
        foreach ( $properties as $property ) {
            if ( empty( $property['name'] ) ) {
                continue;
            }

            $fields[ $property['name'] ] = ! empty( $property['label'] ) ? $property['label'] : $property['name'];
        }
    }

    return $fields;
}

add_action( 'adfoin_groundhoggac_job_queue', 'adfoin_groundhoggac_job_queue', 10, 1 );

function adfoin_groundhoggac_job_queue( $data ) {
    adfoin_groundhoggac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating/updating a Groundhogg contact
 */
function adfoin_groundhoggac_send_data( $record, $posted_data ) {

    if ( ! function_exists( '\Groundhogg\generate_contact_with_map' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_contact' !== $task ) {
        return;
    }

    $tag_id = isset( $field_data['tagId'] ) ? absint( $field_data['tagId'] ) : 0;
    unset( $field_data['tagId'] );

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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) ) {
        $response_body['message'] = __( 'A valid email address is required to create a Groundhogg contact.', 'advanced-form-integration' );
    } else {
        $contact = \Groundhogg\generate_contact_with_map( $prepared_data );

        if ( $contact && method_exists( $contact, 'get_id' ) && $contact->get_id() ) {
            if ( $tag_id ) {
                $contact->apply_tag( array( $tag_id ) );
            }

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Contact created/updated successfully.', 'advanced-form-integration' );
            $response_body['id']      = $contact->get_id();
        } else {
            $response_body['message'] = __( 'Failed to create/update Groundhogg contact. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'groundhoggac', $log_args, $record );
}
