<?php

/**
 * Teachable — online course platform integration.
 *
 *   - create_user  → POST /v1/users
 *   - enroll_user  → GET  /v1/users?email=...     (lookup; create if missing)
 *                  → POST /v1/courses/{id}/enrollments
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: API key sent in the `apiKey` header (NOT Authorization: Bearer).
 *
 * The enroll_user flow exists because Teachable enrollments require a
 * numeric user_id, but form submissions typically only have an email. We
 * resolve the email to a user_id via /users?email=..., creating the user
 * first if they don't already exist.
 *
 * @link https://docs.teachable.com/reference/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_teachable_actions', 10, 1 );

function adfoin_teachable_actions( $actions ) {
    $actions['teachable'] = array(
        'title' => __( 'Teachable', 'advanced-form-integration' ),
        'tasks' => array(
            'create_user' => __( 'Create User', 'advanced-form-integration' ),
            'enroll_user' => __( 'Enroll User in Course', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_teachable_settings_tab', 10, 1 );

function adfoin_teachable_settings_tab( $providers ) {
    $providers['teachable'] = __( 'Teachable', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_teachable_settings_view', 10, 1 );

function adfoin_teachable_settings_view( $current_tab ) {
    if ( 'teachable' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Teachable API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Sign in to your Teachable school as the school owner.', 'advanced-form-integration' ),
        esc_html__( 'Open Settings → API in the school admin sidebar.', 'advanced-form-integration' ),
        esc_html__( 'Click "Create API key", give it a descriptive name (e.g. WordPress), and copy the generated key.', 'advanced-form-integration' ),
        esc_html__( 'Paste the key below. AFI calls https://developers.teachable.com/v1/ with this key in the apiKey header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'teachable', __( 'Teachable', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_teachable_credentials', 'adfoin_get_teachable_credentials', 10, 0 );

function adfoin_get_teachable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'teachable' );
}

add_action( 'wp_ajax_adfoin_save_teachable_credentials', 'adfoin_save_teachable_credentials', 10, 0 );

function adfoin_save_teachable_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'teachable', array( 'apiKey' ) );
}

function adfoin_teachable_credentials_list() {
    foreach ( adfoin_read_credentials( 'teachable' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_teachable_action_fields' );

function adfoin_teachable_action_fields() {
    ?>
    <script type="text/template" id="teachable-action-template">
        <table class="form-table" v-if="action.task == 'create_user' || action.task == 'enroll_user'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Teachable Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=teachable' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_teachable_fields', 'adfoin_get_teachable_fields' );

function adfoin_get_teachable_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_user';

    if ( 'enroll_user' === $task ) {
        $fields = array(
            array( 'key' => 'email',     'value' => __( 'Email (required — used to look up or create the user)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'name',      'value' => __( 'Name (used when creating a new user if email is not found)', 'advanced-form-integration' ) ),
            array( 'key' => 'course_id', 'value' => __( 'Course ID (required, integer)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'found_via', 'value' => __( 'Found Via (defaults to wordpress_form)', 'advanced-form-integration' ) ),
        );
    } else {
        // create_user (default)
        $fields = array(
            array( 'key' => 'name',     'value' => __( 'Name (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'email',    'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'password', 'value' => __( 'Password (optional — leave blank to let Teachable send a signup email)', 'advanced-form-integration' ) ),
            array( 'key' => 'role',     'value' => __( 'Role (student / owner / admin — defaults to student)', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_teachable_job_queue', 'adfoin_teachable_job_queue', 10, 1 );

function adfoin_teachable_job_queue( $data ) {
    adfoin_teachable_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_teachable_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'create_user', 'enroll_user' ), true ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    // Resolve all field-mapped values up-front.
    $reserved = array( 'credId' => 1 );
    $values   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    if ( 'create_user' === $task ) {
        if ( empty( $values['name'] ) || empty( $values['email'] ) ) {
            return;
        }

        $payload = array(
            'name'  => (string) $values['name'],
            'email' => (string) $values['email'],
            'role'  => ! empty( $values['role'] ) ? (string) $values['role'] : 'student',
        );

        if ( ! empty( $values['password'] ) ) {
            $payload['password'] = (string) $values['password'];
        }

        adfoin_teachable_request( 'users', 'POST', $payload, $record, $cred_id );
        return;
    }

    // enroll_user
    if ( empty( $values['email'] ) || empty( $values['course_id'] ) ) {
        return;
    }

    $course_id = (int) $values['course_id'];
    if ( $course_id <= 0 ) {
        return;
    }

    $email   = (string) $values['email'];
    $user_id = 0;

    // 1) Look up existing user by email.
    $lookup = adfoin_teachable_request( 'users', 'GET', array( 'email' => $email ), $record, $cred_id );

    if ( ! is_wp_error( $lookup ) ) {
        $body = json_decode( wp_remote_retrieve_body( $lookup ), true );

        // Teachable returns { users: [ { id, ... } ] } — but defensively
        // handle both that and a bare array response.
        $candidates = array();
        if ( is_array( $body ) ) {
            if ( isset( $body['users'] ) && is_array( $body['users'] ) ) {
                $candidates = $body['users'];
            } elseif ( isset( $body[0] ) ) {
                $candidates = $body;
            }
        }

        foreach ( $candidates as $candidate ) {
            if ( is_array( $candidate ) && ! empty( $candidate['id'] ) ) {
                // Prefer an exact email match if multiple come back.
                if ( ! empty( $candidate['email'] ) && strcasecmp( (string) $candidate['email'], $email ) === 0 ) {
                    $user_id = (int) $candidate['id'];
                    break;
                }
                if ( ! $user_id ) {
                    $user_id = (int) $candidate['id'];
                }
            }
        }
    }

    // 2) If no user, create one. Name is helpful but not strictly required
    //    by every Teachable endpoint — fall back to the email local-part.
    if ( ! $user_id ) {
        $create_name = ! empty( $values['name'] ) ? (string) $values['name'] : substr( $email, 0, strpos( $email, '@' ) ?: strlen( $email ) );

        $create_payload = array(
            'name'  => $create_name,
            'email' => $email,
            'role'  => 'student',
        );

        $created = adfoin_teachable_request( 'users', 'POST', $create_payload, $record, $cred_id );

        if ( is_wp_error( $created ) ) {
            return;
        }

        $created_body = json_decode( wp_remote_retrieve_body( $created ), true );
        if ( is_array( $created_body ) ) {
            if ( ! empty( $created_body['id'] ) ) {
                $user_id = (int) $created_body['id'];
            } elseif ( ! empty( $created_body['user']['id'] ) ) {
                $user_id = (int) $created_body['user']['id'];
            }
        }
    }

    if ( ! $user_id ) {
        return;
    }

    // 3) Enroll.
    $enroll_payload = array(
        'user_id'   => $user_id,
        'found_via' => ! empty( $values['found_via'] ) ? (string) $values['found_via'] : 'wordpress_form',
    );

    adfoin_teachable_request( 'courses/' . $course_id . '/enrollments', 'POST', $enroll_payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_teachable_request' ) ) :
/**
 * Authenticated JSON request against https://developers.teachable.com/v1/.
 * Auth header is `apiKey: {key}` (Teachable-specific — not Authorization).
 */
function adfoin_teachable_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'teachable', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'teachable_missing_credentials', __( 'Teachable API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://developers.teachable.com/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'apiKey' => $credentials['apiKey'],
            'Accept' => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( array_map( 'rawurlencode', $data ), $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
