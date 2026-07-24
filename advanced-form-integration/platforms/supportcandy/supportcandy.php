<?php

/**
 * SupportCandy action platform — local same-site integration (no REST/API
 * keys; SupportCandy's own REST ticket-creation endpoint also requires
 * is_user_logged_in, which doesn't fit anonymous form submissions anyway).
 *
 * Ticket creation is done directly against SupportCandy's own model classes,
 * confirmed against the plugin's real source:
 *
 * - WPSC_Customer::insert( [ 'user' => 0, 'name' => ..., 'email' => ... ] )
 *   (includes/models/class-wpsc-customer.php) — looks up an existing
 *   customer by email first (get_by_email()) and returns it if found,
 *   otherwise inserts a new psmsc_customers row. Same find-or-create
 *   contract used internally by every ticket-creation path in the plugin.
 * - WPSC_Ticket::insert( $data ) (includes/models/class-wpsc-ticket.php) —
 *   raw insert into psmsc_tickets. Required NOT NULL columns confirmed from
 *   the CREATE TABLE statement in class-wpsc-installation.php: customer,
 *   subject, status, priority, category, date_created, date_updated,
 *   user_type, last_reply_by.
 * - WPSC_Thread::insert( $data ) (includes/models/class-wpsc-thread.php) —
 *   creates the ticket's initial message ('type' => 'report'), the same
 *   call the REST create() handler (WPSC_REST_Individual_Ticket::create())
 *   makes right after WPSC_Ticket::insert().
 * - Default status/priority/category are resolved via each model's own
 *   find() (WPSC_Status::find(), WPSC_Priority::find(), WPSC_Category::find())
 *   which all default to `orderby => 'load_order', order => 'ASC'` — taking
 *   the first result mirrors "whatever the admin configured as first" rather
 *   than a hardcoded ID.
 *
 * @link https://plugins.trac.wordpress.org/browser/supportcandy/trunk/includes/models/class-wpsc-ticket.php
 * @link https://plugins.trac.wordpress.org/browser/supportcandy/trunk/includes/models/class-wpsc-customer.php
 * @link https://plugins.trac.wordpress.org/browser/supportcandy/trunk/includes/rest-api/class-wpsc-rest-individual-ticket.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_supportcandy_actions', 10, 1 );

function adfoin_supportcandy_actions( $actions ) {

    $actions['supportcandy'] = array(
        'title' => __( 'SupportCandy', 'advanced-form-integration' ),
        'tasks' => array(
            'add_ticket' => __( 'Create Ticket', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_supportcandy_action_fields' );

function adfoin_supportcandy_action_fields() {
    ?>
    <script type="text/template" id="supportcandy-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_ticket'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ticket'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Category', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[categoryId]" v-model="fielddata.categoryId">
                        <option value=""><?php _e( 'Use Default...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.categoryList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': categoryLoading}"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_ticket'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Priority', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[priorityId]" v-model="fielddata.priorityId">
                        <option value=""><?php _e( 'Use Default...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.priorityList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': priorityLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_ticket'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Status', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[statusId]" v-model="fielddata.statusId">
                        <option value=""><?php _e( 'Use Default...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.statusList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': statusLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_supportcandy_categories', 'adfoin_get_supportcandy_categories', 10, 0 );

function adfoin_get_supportcandy_categories() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'WPSC_Category' ) ) {
        wp_send_json_error( __( 'SupportCandy is not active.', 'advanced-form-integration' ) );
    }

    $result = WPSC_Category::find( array( 'items_per_page' => 999 ) );
    $items  = array();

    if ( ! empty( $result['results'] ) ) {
        foreach ( $result['results'] as $category ) {
            $items[ $category->id ] = $category->name;
        }
    }

    wp_send_json_success( $items );
}

add_action( 'wp_ajax_adfoin_get_supportcandy_priorities', 'adfoin_get_supportcandy_priorities', 10, 0 );

function adfoin_get_supportcandy_priorities() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'WPSC_Priority' ) ) {
        wp_send_json_error( __( 'SupportCandy is not active.', 'advanced-form-integration' ) );
    }

    $result = WPSC_Priority::find( array( 'items_per_page' => 999 ) );
    $items  = array();

    if ( ! empty( $result['results'] ) ) {
        foreach ( $result['results'] as $priority ) {
            $items[ $priority->id ] = $priority->name;
        }
    }

    wp_send_json_success( $items );
}

add_action( 'wp_ajax_adfoin_get_supportcandy_statuses', 'adfoin_get_supportcandy_statuses', 10, 0 );

function adfoin_get_supportcandy_statuses() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'WPSC_Status' ) ) {
        wp_send_json_error( __( 'SupportCandy is not active.', 'advanced-form-integration' ) );
    }

    $result = WPSC_Status::find( array( 'items_per_page' => 999 ) );
    $items  = array();

    if ( ! empty( $result['results'] ) ) {
        foreach ( $result['results'] as $status ) {
            $items[ $status->id ] = $status->name;
        }
    }

    wp_send_json_success( $items );
}

add_action( 'wp_ajax_adfoin_get_supportcandy_ticket_fields', 'adfoin_get_supportcandy_ticket_fields', 10, 0 );

function adfoin_get_supportcandy_ticket_fields() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'WPSC_Ticket' ) ) {
        wp_send_json_error( __( 'SupportCandy is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'name', 'value' => __( 'Customer Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'email', 'value' => __( 'Customer Email', 'advanced-form-integration' ), 'description' => __( 'Required. Used to find or create the customer.', 'advanced-form-integration' ) ),
        array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'description' => __( 'Required. Becomes the ticket\'s first message.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_supportcandy_job_queue', 'adfoin_supportcandy_job_queue', 10, 1 );

function adfoin_supportcandy_job_queue( $data ) {
    adfoin_supportcandy_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a SupportCandy ticket
 */
function adfoin_supportcandy_send_data( $record, $posted_data ) {

    if ( ! class_exists( 'WPSC_Ticket' ) || ! class_exists( 'WPSC_Customer' ) || ! class_exists( 'WPSC_Thread' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_ticket' !== $task ) {
        return;
    }

    $category_id = isset( $field_data['categoryId'] ) ? absint( $field_data['categoryId'] ) : 0;
    $priority_id = isset( $field_data['priorityId'] ) ? absint( $field_data['priorityId'] ) : 0;
    $status_id   = isset( $field_data['statusId'] ) ? absint( $field_data['statusId'] ) : 0;

    unset( $field_data['categoryId'], $field_data['priorityId'], $field_data['statusId'] );

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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) || empty( $prepared_data['subject'] ) || empty( $prepared_data['description'] ) ) {
        $response_body['message'] = __( 'A valid email, subject, and description are required to create a SupportCandy ticket.', 'advanced-form-integration' );
    } else {
        if ( ! $category_id ) {
            $category_id = adfoin_supportcandy_get_default_id( 'WPSC_Category' );
        }

        if ( ! $priority_id ) {
            $priority_id = adfoin_supportcandy_get_default_id( 'WPSC_Priority' );
        }

        if ( ! $status_id ) {
            $status_id = adfoin_supportcandy_get_default_id( 'WPSC_Status' );
        }

        if ( ! $category_id || ! $priority_id || ! $status_id ) {
            $response_body['message'] = __( 'SupportCandy has no categories, priorities, or statuses configured yet.', 'advanced-form-integration' );
        } else {
            $customer = WPSC_Customer::insert(
                array(
                    'user'  => 0,
                    'name'  => ! empty( $prepared_data['name'] ) ? $prepared_data['name'] : $prepared_data['email'],
                    'email' => $prepared_data['email'],
                )
            );

            if ( ! $customer || ! $customer->id ) {
                $response_body['message'] = __( 'Failed to create/find the SupportCandy customer record.', 'advanced-form-integration' );
            } else {
                $now = ( new DateTime() )->format( 'Y-m-d H:i:s' );

                $ticket = WPSC_Ticket::insert(
                    array(
                        'customer'          => $customer->id,
                        'subject'           => $prepared_data['subject'],
                        'status'            => $status_id,
                        'priority'          => $priority_id,
                        'category'          => $category_id,
                        'date_created'      => $now,
                        'date_updated'      => $now,
                        'ip_address'        => adfoin_get_user_ip(),
                        'source'            => 'adfoin',
                        'user_type'         => 'guest',
                        'last_reply_on'     => $now,
                        'last_reply_by'     => $customer->id,
                        'last_reply_source' => 'adfoin',
                    )
                );

                if ( ! $ticket || ! $ticket->id ) {
                    $response_body['message'] = __( 'Failed to create SupportCandy ticket. Please verify the supplied data.', 'advanced-form-integration' );
                } else {
                    WPSC_Thread::insert(
                        array(
                            'ticket'      => $ticket->id,
                            'customer'    => $customer->id,
                            'type'        => 'report',
                            'body'        => $prepared_data['description'],
                            'attachments' => array(),
                            'ip_address'  => adfoin_get_user_ip(),
                            'source'      => 'adfoin',
                            'os'          => '',
                            'browser'     => '',
                        )
                    );

                    if ( function_exists( 'do_action' ) ) {
                        do_action( 'wpsc_create_new_ticket', $ticket );
                    }

                    $status_code              = 200;
                    $response_body['success'] = true;
                    $response_body['message'] = __( 'Ticket created successfully.', 'advanced-form-integration' );
                    $response_body['id']      = $ticket->id;
                }
            }
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

    adfoin_add_to_log( $log_response, 'supportcandy', $log_args, $record );
}

function adfoin_supportcandy_get_default_id( $class_name ) {
    if ( ! class_exists( $class_name ) || ! method_exists( $class_name, 'find' ) ) {
        return 0;
    }

    $result = $class_name::find( array( 'items_per_page' => 1 ) );

    // Not empty()/isset() on ->id — SupportCandy's model classes define __get()
    // but not __isset(), so empty()/isset() on a magic property always read as
    // "unset" regardless of the real value and would make this always return 0.
    if ( ! isset( $result['results'][0] ) ) {
        return 0;
    }

    $id = $result['results'][0]->id;

    return ( '' !== $id && null !== $id ) ? absint( $id ) : 0;
}
