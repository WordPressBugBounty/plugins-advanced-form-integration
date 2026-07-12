<?php

add_filter( 'adfoin_action_providers', 'adfoin_planningcenter_actions', 10, 1 );
function adfoin_planningcenter_actions( $actions ) {
    $actions['planningcenter'] = array(
        'title' => 'Planning Center',
        'tasks' => array( 'create_person' => 'Add Contact (People, Workflow & Group)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_planningcenter_settings_tab', 10, 1 );
function adfoin_planningcenter_settings_tab( $providers ) { $providers['planningcenter'] = 'Planning Center'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_planningcenter_settings_view', 10, 1 );
function adfoin_planningcenter_settings_view( $current_tab ) {
    if ( $current_tab !== 'planningcenter' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'planningcenter',
        'fields'   => array(
            array( 'key' => 'appId',     'label' => __( 'Application ID', 'advanced-form-integration' ) ),
            array( 'key' => 'secret',    'label' => __( 'Secret', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Planning Center, go to your Personal Access Tokens page (api.planningcenteronline.com/oauth/applications). Create a Personal Access Token and copy the Application ID and Secret.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Planning Center', 'advanced-form-integration' ), 'planningcenter', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_planningcenter_action_fields' );
function adfoin_planningcenter_action_fields() {
    ?>
    <script type="text/template" id="planningcenter-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_person'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Planning Center Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_planningcenter_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Add to Workflow', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[workflowId]" v-model="fielddata.workflowId">
                        <option value=""><?php _e( 'None', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.workflows" v-bind:value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': workflowsLoading}"></div>
                    <p class="description"><?php esc_attr_e( 'New person is added as a card in this workflow (default step, unassigned).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Add to Group', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId">
                        <option value=""><?php _e( 'None', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.groups" v-bind:value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupsLoading}"></div>
                </td>
            </tr>
            <?php adfoin_pro_feature_notice( 'create_person', 'Planning Center [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_planningcenter_credentials', 'adfoin_get_planningcenter_credentials' );
function adfoin_get_planningcenter_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'planningcenter' ) );
}

add_action( 'wp_ajax_adfoin_save_planningcenter_credentials', 'adfoin_save_planningcenter_credentials' );
function adfoin_save_planningcenter_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'planningcenter' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'planningcenter', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_planningcenter_fields', 'adfoin_get_planningcenter_fields' );
function adfoin_get_planningcenter_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'middle_name','value' => 'Middle Name','description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'nickname',   'value' => 'Nickname',   'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'birthdate',  'value' => 'Birthdate',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',     'value' => 'Gender',     'description' => 'Male / Female' ),
        array( 'key' => 'street',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'status',     'value' => 'Status',     'description' => 'active / inactive' ),
        array( 'key' => 'membership', 'value' => 'Membership', 'description' => 'Member / Visitor / etc.' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_planningcenter_credentials_list() {
    foreach ( adfoin_read_credentials( 'planningcenter' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'wp_ajax_adfoin_get_planningcenter_workflows', 'adfoin_get_planningcenter_workflows' );
function adfoin_get_planningcenter_workflows() {
    adfoin_verify_nonce();
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    wp_send_json_success( adfoin_planningcenter_list_all( 'people/v2/workflows', $cred_id ) );
}

add_action( 'wp_ajax_adfoin_get_planningcenter_groups', 'adfoin_get_planningcenter_groups' );
function adfoin_get_planningcenter_groups() {
    adfoin_verify_nonce();
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    wp_send_json_success( adfoin_planningcenter_list_all( 'groups/v2/groups', $cred_id ) );
}

function adfoin_planningcenter_list_all( $endpoint, $cred_id ) {
    $all      = array();
    $offset   = 0;
    $per_page = 100;
    $has_more = true;

    while ( $has_more ) {
        $response = adfoin_planningcenter_request( $endpoint . '?per_page=' . $per_page . '&offset=' . $offset . '&order=name', 'GET', array(), array(), $cred_id );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) break;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['data'] ) ) break;

        foreach ( $body['data'] as $item ) {
            $all[ $item['id'] ] = $item['attributes']['name'];
        }

        $offset  += $per_page;
        $has_more = count( $body['data'] ) === $per_page;
    }

    return $all;
}

function adfoin_planningcenter_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'planningcenter', $cred_id );
    $app_id      = isset( $credentials['appId'] )  ? $credentials['appId']  : '';
    $secret      = isset( $credentials['secret'] ) ? $credentials['secret'] : '';
    if ( ! $app_id || ! $secret ) return;

    $url  = 'https://api.planningcenteronline.com/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $app_id . ':' . $secret ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * Looks up an existing person by exact email match (Planning Center's
 * search_name_or_email is a fuzzy match, so results are verified client-side
 * against the included Email resources). Returns '' when nothing matches so
 * callers fall back to creating a new person.
 */
function adfoin_planningcenter_find_person( $email, $record, $cred_id ) {
    $empty = array( 'id' => '', 'email_id' => '', 'phone_id' => '', 'address_id' => '' );
    if ( ! $email ) return $empty;

    $endpoint = 'people/v2/people?where[search_name_or_email]=' . rawurlencode( $email ) . '&include=emails,phone_numbers,addresses';
    $response = adfoin_planningcenter_request( $endpoint, 'GET', array(), $record, $cred_id );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) return $empty;

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['data'] ) ) return $empty;

    $included = array();
    foreach ( (array) ( isset( $body['included'] ) ? $body['included'] : array() ) as $inc ) {
        $included[ $inc['type'] . ':' . $inc['id'] ] = $inc;
    }

    foreach ( $body['data'] as $person ) {
        $email_refs       = isset( $person['relationships']['emails']['data'] ) ? $person['relationships']['emails']['data'] : array();
        $matched_email_id = '';
        foreach ( $email_refs as $ref ) {
            $inc = isset( $included[ 'Email:' . $ref['id'] ] ) ? $included[ 'Email:' . $ref['id'] ] : null;
            if ( $inc && isset( $inc['attributes']['address'] ) && strtolower( $inc['attributes']['address'] ) === strtolower( $email ) ) {
                $matched_email_id = $ref['id'];
                break;
            }
        }
        if ( ! $matched_email_id ) continue;

        $phone_id = '';
        foreach ( (array) ( isset( $person['relationships']['phone_numbers']['data'] ) ? $person['relationships']['phone_numbers']['data'] : array() ) as $ref ) {
            $inc = isset( $included[ 'PhoneNumber:' . $ref['id'] ] ) ? $included[ 'PhoneNumber:' . $ref['id'] ] : null;
            if ( $inc && ! empty( $inc['attributes']['primary'] ) ) { $phone_id = $ref['id']; break; }
            if ( ! $phone_id ) $phone_id = $ref['id'];
        }

        $address_id = '';
        foreach ( (array) ( isset( $person['relationships']['addresses']['data'] ) ? $person['relationships']['addresses']['data'] : array() ) as $ref ) {
            $inc = isset( $included[ 'Address:' . $ref['id'] ] ) ? $included[ 'Address:' . $ref['id'] ] : null;
            if ( $inc && ! empty( $inc['attributes']['primary'] ) ) { $address_id = $ref['id']; break; }
            if ( ! $address_id ) $address_id = $ref['id'];
        }

        return array( 'id' => $person['id'], 'email_id' => $matched_email_id, 'phone_id' => $phone_id, 'address_id' => $address_id );
    }

    return $empty;
}

/**
 * Creates a new person, or updates the existing one matched by email so
 * repeat submissions don't create duplicate People records.
 */
function adfoin_planningcenter_upsert_person( $fields, $record, $cred_id ) {
    $attrs = array();
    foreach ( array( 'first_name', 'middle_name', 'last_name', 'nickname', 'birthdate', 'gender', 'status', 'membership' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $attrs[ $k ] = $fields[ $k ];
    }

    $existing  = adfoin_planningcenter_find_person( isset( $fields['email'] ) ? $fields['email'] : '', $record, $cred_id );
    $person_id = $existing['id'];

    if ( $person_id ) {
        $body     = array( 'data' => array( 'type' => 'Person', 'id' => $person_id, 'attributes' => $attrs ) );
        $response = adfoin_planningcenter_request( "people/v2/people/{$person_id}", 'PATCH', $body, $record, $cred_id );
    } else {
        $body     = array( 'data' => array( 'type' => 'Person', 'attributes' => $attrs ) );
        $response = adfoin_planningcenter_request( 'people/v2/people', 'POST', $body, $record, $cred_id );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) return array( 'response' => $response, 'person_id' => '' );
        $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
        $person_id = isset( $resp_body['data']['id'] ) ? $resp_body['data']['id'] : '';
    }

    if ( ! $person_id ) return array( 'response' => $response, 'person_id' => '' );

    if ( ! empty( $fields['email'] ) && ! $existing['email_id'] ) {
        adfoin_planningcenter_request( "people/v2/people/{$person_id}/emails", 'POST', array(
            'data' => array( 'type' => 'Email', 'attributes' => array( 'address' => $fields['email'], 'primary' => true ) ),
        ), $record, $cred_id );
    }

    if ( ! empty( $fields['phone'] ) ) {
        $phone_body = array( 'data' => array( 'type' => 'PhoneNumber', 'attributes' => array( 'number' => $fields['phone'], 'primary' => true ) ) );
        if ( $existing['phone_id'] ) {
            $phone_body['data']['id'] = $existing['phone_id'];
            adfoin_planningcenter_request( "people/v2/people/{$person_id}/phone_numbers/{$existing['phone_id']}", 'PATCH', $phone_body, $record, $cred_id );
        } else {
            adfoin_planningcenter_request( "people/v2/people/{$person_id}/phone_numbers", 'POST', $phone_body, $record, $cred_id );
        }
    }

    if ( ! empty( $fields['street'] ) || ! empty( $fields['city'] ) || ! empty( $fields['zip'] ) ) {
        $addr_attrs = array();
        foreach ( array( 'street' => 'street_line_1', 'city' => 'city', 'state' => 'state', 'zip' => 'zip' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $addr_attrs[ $remote ] = $fields[ $local ];
        }
        $addr_body = array( 'data' => array( 'type' => 'Address', 'attributes' => $addr_attrs ) );
        if ( $existing['address_id'] ) {
            $addr_body['data']['id'] = $existing['address_id'];
            adfoin_planningcenter_request( "people/v2/people/{$person_id}/addresses/{$existing['address_id']}", 'PATCH', $addr_body, $record, $cred_id );
        } else {
            adfoin_planningcenter_request( "people/v2/people/{$person_id}/addresses", 'POST', $addr_body, $record, $cred_id );
        }
    }

    return array( 'response' => $response, 'person_id' => $person_id );
}

function adfoin_planningcenter_add_to_workflow( $person_id, $workflow_id, $record, $cred_id ) {
    if ( ! $person_id || ! $workflow_id ) return;
    $body = array(
        'data' => array(
            'type'          => 'WorkflowCard',
            'relationships' => array(
                'person' => array( 'data' => array( 'type' => 'Person', 'id' => $person_id ) ),
            ),
        ),
    );
    adfoin_planningcenter_request( "people/v2/workflows/{$workflow_id}/cards", 'POST', $body, $record, $cred_id );
}

function adfoin_planningcenter_add_to_group( $person_id, $group_id, $record, $cred_id ) {
    if ( ! $person_id || ! $group_id ) return;
    $body = array(
        'data' => array(
            'type'          => 'Membership',
            'attributes'    => array( 'role' => 'member' ),
            'relationships' => array(
                'person' => array( 'data' => array( 'type' => 'Person', 'id' => $person_id ) ),
            ),
        ),
    );
    adfoin_planningcenter_request( "groups/v2/groups/{$group_id}/memberships", 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_planningcenter_job_queue', 'adfoin_planningcenter_job_queue', 10, 1 );
function adfoin_planningcenter_job_queue( $data ) {
    adfoin_planningcenter_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_planningcenter_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    if ( $record['task'] !== 'create_person' ) return;

    $fields = array();
    foreach ( $data as $k => $v ) {
        if ( in_array( $k, array( 'credId', 'workflowId', 'groupId' ), true ) ) continue;
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    $result    = adfoin_planningcenter_upsert_person( $fields, $record, $cred_id );
    $person_id = $result['person_id'];
    if ( ! $person_id ) return;

    if ( ! empty( $data['workflowId'] ) ) {
        adfoin_planningcenter_add_to_workflow( $person_id, $data['workflowId'], $record, $cred_id );
    }
    if ( ! empty( $data['groupId'] ) ) {
        adfoin_planningcenter_add_to_group( $person_id, $data['groupId'], $record, $cred_id );
    }
}
