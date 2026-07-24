<?php

/**
 * Fluent Cart action platform — local same-site integration (no REST/API
 * keys). Slug is `fluentcartac`, not `fluentcart` — the trigger side
 * already uses that slug (includes/triggers/fluentcart/fluentcart.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Customer creation goes through the real
 * \FluentCart\App\Models\Customer::create([...]) method (app/Models/Customer.php),
 * confirmed against the plugin's own source — 'email', 'first_name', and
 * 'last_name' are all mass-assignable ($fillable). The model's base ORM
 * (FluentCart\Framework\Database\Orm\Model) is a custom framework, not
 * Laravel Eloquent, and doesn't expose a confirmed firstOrCreate() helper,
 * so this looks up an existing customer by email first via the query
 * builder (::where('email', ...)->first()) before creating a new one.
 *
 * @link https://plugins.trac.wordpress.org/browser/fluent-cart/trunk/app/Models/Customer.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_fluentcartac_actions', 10, 1 );

function adfoin_fluentcartac_actions( $actions ) {

    $actions['fluentcartac'] = array(
        'title' => __( 'Fluent Cart', 'advanced-form-integration' ),
        'tasks' => array(
            'add_customer' => __( 'Add Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_fluentcartac_action_fields' );

function adfoin_fluentcartac_action_fields() {
    ?>
    <script type="text/template" id="fluentcartac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_customer'">
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

add_action( 'wp_ajax_adfoin_get_fluentcartac_fields', 'adfoin_get_fluentcartac_fields', 10, 0 );

function adfoin_get_fluentcartac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing customer is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_fluentcartac_job_queue', 'adfoin_fluentcartac_job_queue', 10, 1 );

function adfoin_fluentcartac_job_queue( $data ) {
    adfoin_fluentcartac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles adding a Fluent Cart customer
 */
function adfoin_fluentcartac_send_data( $record, $posted_data ) {

    if ( ! class_exists( '\FluentCart\App\Models\Customer' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_customer' !== $task ) {
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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) ) {
        $response_body['message'] = __( 'A valid email address is required.', 'advanced-form-integration' );
    } else {
        $customer = \FluentCart\App\Models\Customer::where( 'email', $prepared_data['email'] )->first();

        if ( ! $customer ) {
            $customer = \FluentCart\App\Models\Customer::create(
                array(
                    'email'      => $prepared_data['email'],
                    'first_name' => isset( $prepared_data['first_name'] ) ? $prepared_data['first_name'] : '',
                    'last_name'  => isset( $prepared_data['last_name'] ) ? $prepared_data['last_name'] : '',
                )
            );
        }

        if ( $customer && $customer->id ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Customer added successfully.', 'advanced-form-integration' );
            $response_body['id']      = $customer->id;
        } else {
            $response_body['message'] = __( 'Failed to add the customer. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'fluentcartac', $log_args, $record );
}
