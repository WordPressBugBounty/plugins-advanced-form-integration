<?php

/**
 * Eventin action platform — local same-site integration (no REST/API keys).
 * Slug is `eventinac`, not `eventin` — the trigger side already uses that
 * slug (includes/triggers/eventin/eventin.php); this codebase's convention
 * for a same-slug trigger/action pair is an `ac` suffix on the action (see
 * gravityformsac, wpformsac, buddypressac). wp.org slug is `wp-event-solution`
 * (the plugin folder/display name "Eventin" differs from its wp.org slug).
 *
 * Attendee creation goes through the real \Etn\Core\Attendee\Attendee_Model
 * class (core/Attendee/attendee-model.php), which extends the plugin's own
 * Post_Model base (base/post-model.php) — confirmed by reading both files
 * directly from the plugin's own source. Its create_and_return_post_id()
 * method wraps wp_insert_post() +
 * per-field postmeta and returns the new post ID (or false). The field keys
 * (etn_name, etn_email, etn_phone, etn_event_id) match exactly what the
 * plugin's own CSV/JSON AttendeeImporter uses to create attendees. Events
 * are a plain custom post type, 'etn' (confirmed via core/event/cpt.php's
 * get_name() -> register_post_type() call).
 *
 * @link https://plugins.trac.wordpress.org/browser/wp-event-solution/trunk/core/Attendee/attendee-model.php
 * @link https://plugins.trac.wordpress.org/browser/wp-event-solution/trunk/base/post-model.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_eventinac_actions', 10, 1 );

function adfoin_eventinac_actions( $actions ) {

    $actions['eventinac'] = array(
        'title' => __( 'Eventin', 'advanced-form-integration' ),
        'tasks' => array(
            'add_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_eventinac_action_fields' );

function adfoin_eventinac_action_fields() {
    ?>
    <script type="text/template" id="eventinac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_attendee'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_attendee'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Event', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[eventId]" v-model="fielddata.eventId" required="required">
                        <option value=""><?php _e( 'Select Event...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.eventList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': eventLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_eventinac_events', 'adfoin_get_eventinac_events', 10, 0 );

function adfoin_get_eventinac_events() {
    adfoin_verify_nonce();

    if ( ! post_type_exists( 'etn' ) ) {
        wp_send_json_error( __( 'Eventin is not active.', 'advanced-form-integration' ) );
    }

    $posts  = get_posts(
        array(
            'post_type'      => 'etn',
            'post_status'    => 'publish',
            'posts_per_page' => 999,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
    $events = array();

    foreach ( $posts as $post ) {
        $events[ $post->ID ] = $post->post_title;
    }

    wp_send_json_success( $events );
}

add_action( 'wp_ajax_adfoin_get_eventinac_fields', 'adfoin_get_eventinac_fields', 10, 0 );

function adfoin_get_eventinac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'name', 'value' => __( 'Attendee Name', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Attendee Email', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Attendee Phone', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_eventinac_job_queue', 'adfoin_eventinac_job_queue', 10, 1 );

function adfoin_eventinac_job_queue( $data ) {
    adfoin_eventinac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles registering an Eventin attendee
 */
function adfoin_eventinac_send_data( $record, $posted_data ) {

    if ( ! class_exists( '\Etn\Core\Attendee\Attendee_Model' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_attendee' !== $task ) {
        return;
    }

    $event_id = isset( $field_data['eventId'] ) ? absint( $field_data['eventId'] ) : 0;
    unset( $field_data['eventId'] );

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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) || empty( $prepared_data['name'] ) ) {
        $response_body['message'] = __( 'A valid name and email are required.', 'advanced-form-integration' );
    } elseif ( ! $event_id ) {
        $response_body['message'] = __( 'An event is required.', 'advanced-form-integration' );
    } else {
        $attendee    = new \Etn\Core\Attendee\Attendee_Model();
        $attendee_id = $attendee->create_and_return_post_id(
            array(
                'etn_name'     => $prepared_data['name'],
                'etn_email'    => $prepared_data['email'],
                'etn_phone'    => isset( $prepared_data['phone'] ) ? $prepared_data['phone'] : '',
                'etn_event_id' => $event_id,
                'post_status'  => 'publish',
            )
        );

        if ( $attendee_id ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Attendee registered successfully.', 'advanced-form-integration' );
            $response_body['id']      = $attendee_id;
        } else {
            $response_body['message'] = __( 'Failed to register the attendee. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'eventinac', $log_args, $record );
}
