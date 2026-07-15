<?php

add_filter( 'adfoin_action_providers', 'adfoin_breezechms_actions', 10, 1 );
function adfoin_breezechms_actions( $actions ) {
    $actions['breezechms'] = array(
        'title' => 'Breeze ChMS',
        'tasks' => array( 'create_person' => 'Create Person' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_breezechms_settings_tab', 10, 1 );
function adfoin_breezechms_settings_tab( $providers ) { $providers['breezechms'] = 'Breeze ChMS'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_breezechms_settings_view', 10, 1 );
function adfoin_breezechms_settings_view( $current_tab ) {
    if ( $current_tab !== 'breezechms' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'breezechms',
        'fields'   => array(
            array( 'key' => 'subdomain', 'label' => __( 'Subdomain (e.g. yourchurch)', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Breeze ChMS, go to Account Settings > API. Generate an API key and copy your Breeze subdomain (the part before .breezechms.com).', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Breeze ChMS', 'advanced-form-integration' ), 'breezechms', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_breezechms_action_fields' );
function adfoin_breezechms_action_fields() {
    ?>
    <script type="text/template" id="breezechms-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_person'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Breeze Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_breezechms_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_person', 'Breeze ChMS [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_breezechms_credentials', 'adfoin_get_breezechms_credentials' );
function adfoin_get_breezechms_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'breezechms' ) );
}

add_action( 'wp_ajax_adfoin_save_breezechms_credentials', 'adfoin_save_breezechms_credentials' );
function adfoin_save_breezechms_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'breezechms' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'breezechms', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_breezechms_fields', 'adfoin_get_breezechms_fields' );
function adfoin_get_breezechms_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Mobile Phone', 'description' => '' ),
        array( 'key' => 'home_phone', 'value' => 'Home Phone',   'description' => '' ),
        array( 'key' => 'work_phone', 'value' => 'Work Phone',   'description' => '' ),
        array( 'key' => 'birthdate',  'value' => 'Birthdate',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',     'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'marital',    'value' => 'Marital Status', 'description' => '' ),
        array( 'key' => 'street',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',        'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_breezechms_credentials_list() {
    foreach ( adfoin_read_credentials( 'breezechms' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_breezechms_request( $endpoint, $method = 'GET', $params = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'breezechms', $cred_id );
    $subdomain   = isset( $credentials['subdomain'] ) ? $credentials['subdomain'] : '';
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    if ( ! $subdomain || ! $api_key ) return;

    $url = 'https://' . $subdomain . '.breezechms.com/api/' . ltrim( $endpoint, '/' );
    if ( $params ) $url .= '?' . http_build_query( $params );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Api-Key' => $api_key,
            'Accept'  => 'application/json',
        ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * Breeze's profile fields are church-specific — every field beyond first/last
 * name (even built-ins like Email or Mobile Phone) has an org-defined
 * field_id that must be looked up via GET /profile before it can be set.
 * Confirmed via Breeze's own official JS/TS client (github.com/Notebird-App/breeze-chms):
 * fields_json entries are `{field_id, field_type, response, details}`, where
 * phone/email/address values live inside `details` (not `response` directly),
 * and dropdown fields (gender, marital status) require resolving the typed
 * label to an `option_id` from the field's `options` list.
 */
function adfoin_breezechms_get_profile_fields( $cred_id ) {
    $response = adfoin_breezechms_request( 'profile', 'GET', array(), array(), $cred_id );
    if ( is_wp_error( $response ) ) return array();
    $sections = json_decode( wp_remote_retrieve_body( $response ), true );
    $fields = array();
    if ( is_array( $sections ) ) {
        foreach ( $sections as $section ) {
            if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
                $fields = array_merge( $fields, $section['fields'] );
            }
        }
    }
    return $fields;
}

function adfoin_breezechms_find_field( $profile_fields, $type, $name_contains = '' ) {
    foreach ( $profile_fields as $field ) {
        if ( ! isset( $field['field_type'] ) || $field['field_type'] !== $type ) continue;
        if ( $name_contains === '' || stripos( isset( $field['name'] ) ? $field['name'] : '', $name_contains ) !== false ) {
            return $field;
        }
    }
    return null;
}

function adfoin_breezechms_find_option_id( $field, $label ) {
    if ( empty( $field['options'] ) ) return '';
    $target = strtolower( trim( $label ) );
    foreach ( $field['options'] as $option ) {
        if ( strtolower( trim( $option['name'] ) ) === $target ) return $option['option_id'];
    }
    // Common shorthand, e.g. gender 'M'/'F' matching 'Male'/'Female'.
    foreach ( $field['options'] as $option ) {
        if ( strtolower( substr( trim( $option['name'] ), 0, 1 ) ) === substr( $target, 0, 1 ) ) return $option['option_id'];
    }
    return '';
}

function adfoin_breezechms_build_fields_json( $fields, $cred_id ) {
    $profile_fields = adfoin_breezechms_get_profile_fields( $cred_id );
    $fields_json = array();

    $phone_field = adfoin_breezechms_find_field( $profile_fields, 'phone' );
    if ( $phone_field ) {
        $details = array();
        if ( ! empty( $fields['phone'] ) )      $details['phone_mobile'] = $fields['phone'];
        if ( ! empty( $fields['home_phone'] ) ) $details['phone_home']   = $fields['home_phone'];
        if ( ! empty( $fields['work_phone'] ) ) $details['phone_work']   = $fields['work_phone'];
        if ( $details ) $fields_json[] = array( 'field_id' => $phone_field['field_id'], 'field_type' => 'phone', 'response' => true, 'details' => $details );
    }
    if ( ! empty( $fields['email'] ) ) {
        $email_field = adfoin_breezechms_find_field( $profile_fields, 'email' );
        if ( $email_field ) $fields_json[] = array( 'field_id' => $email_field['field_id'], 'field_type' => 'email', 'response' => true, 'details' => array( 'address' => $fields['email'] ) );
    }
    if ( ! empty( $fields['birthdate'] ) ) {
        $birthdate_field = adfoin_breezechms_find_field( $profile_fields, 'birthdate' );
        if ( $birthdate_field ) $fields_json[] = array( 'field_id' => $birthdate_field['field_id'], 'field_type' => 'birthdate', 'response' => $fields['birthdate'] );
    }
    if ( ! empty( $fields['street'] ) || ! empty( $fields['city'] ) || ! empty( $fields['state'] ) || ! empty( $fields['zip'] ) ) {
        $address_field = adfoin_breezechms_find_field( $profile_fields, 'address' );
        if ( $address_field ) {
            $fields_json[] = array(
                'field_id'   => $address_field['field_id'],
                'field_type' => 'address',
                'response'   => true,
                'details'    => array(
                    'street_address' => isset( $fields['street'] ) ? $fields['street'] : '',
                    'city'           => isset( $fields['city'] )   ? $fields['city']   : '',
                    'state'          => isset( $fields['state'] )  ? $fields['state']  : '',
                    'zip'            => isset( $fields['zip'] )    ? $fields['zip']    : '',
                ),
            );
        }
    }
    if ( ! empty( $fields['gender'] ) ) {
        $gender_field = adfoin_breezechms_find_field( $profile_fields, 'dropdown', 'gender' );
        if ( ! $gender_field ) $gender_field = adfoin_breezechms_find_field( $profile_fields, 'multiple_choice', 'gender' );
        if ( $gender_field ) {
            $option_id = adfoin_breezechms_find_option_id( $gender_field, $fields['gender'] );
            if ( $option_id ) $fields_json[] = array( 'field_id' => $gender_field['field_id'], 'field_type' => $gender_field['field_type'], 'response' => $option_id );
        }
    }
    if ( ! empty( $fields['marital'] ) ) {
        $marital_field = adfoin_breezechms_find_field( $profile_fields, 'dropdown', 'marital' );
        if ( ! $marital_field ) $marital_field = adfoin_breezechms_find_field( $profile_fields, 'multiple_choice', 'marital' );
        if ( $marital_field ) {
            $option_id = adfoin_breezechms_find_option_id( $marital_field, $fields['marital'] );
            if ( $option_id ) $fields_json[] = array( 'field_id' => $marital_field['field_id'], 'field_type' => $marital_field['field_type'], 'response' => $option_id );
        }
    }
    return $fields_json;
}

add_action( 'adfoin_breezechms_job_queue', 'adfoin_breezechms_job_queue', 10, 1 );
function adfoin_breezechms_job_queue( $data ) {
    adfoin_breezechms_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_breezechms_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_person' ) return;

    // Confirmed pattern (matches Breeze's own client): add the person with
    // just first/last to get an id, then a separate people/update call to
    // set every other field via the resolved fields_json.
    $response = adfoin_breezechms_request( 'people/add', 'GET', array(
        'first' => isset( $fields['first_name'] ) ? $fields['first_name'] : '',
        'last'  => isset( $fields['last_name'] )  ? $fields['last_name']  : '',
    ), $record, $cred_id );
    if ( is_wp_error( $response ) ) return;
    $person = json_decode( wp_remote_retrieve_body( $response ), true );
    $person_id = isset( $person[0]['id'] ) ? $person[0]['id'] : '';
    if ( ! $person_id ) return;

    $fields_json = adfoin_breezechms_build_fields_json( $fields, $cred_id );
    if ( $fields_json ) {
        adfoin_breezechms_request( 'people/update', 'GET', array(
            'person_id'   => $person_id,
            'fields_json' => wp_json_encode( $fields_json ),
        ), $record, $cred_id );
    }
}
