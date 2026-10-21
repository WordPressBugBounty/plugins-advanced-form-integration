<?php

/**
 * Jetpack CRM (formerly Zero BS CRM) action platform — local same-site
 * integration (no REST/API keys).
 *
 * Contact create/update goes through the plugin's own public integration
 * entry point, zeroBS_integrations_addOrUpdateCustomer() (includes/
 * ZeroBSCRM.IntegrationFuncs.php) — the same function the plugin's own
 * api/create_customer.php REST endpoint calls internally. It expects a
 * $customerFields array keyed by zbsc_-prefixed field names (zbsc_email,
 * zbsc_fname, zbsc_lname, zbsc_addr1, etc, confirmed against the function's
 * own field-by-field switch/handling) and looks up an existing contact by
 * email before deciding whether to create or update.
 *
 * The mappable field list is read from Jetpack CRM's contact field model
 * ($zbsCustomerFields), which includes the site's custom fields keyed by
 * slug, so custom fields are sent as zbsc_<slug> like any standard field.
 *
 * Company (only when B2B mode is on) isn't a contact field: after the contact
 * is saved, a company with that name is found or created via
 * zeroBS_integrations_addOrUpdateCompany() and linked to the contact,
 * keeping any companies it's already linked to.
 *
 * Tags are not part of that helper's field set, so they're applied
 * separately via the DAL contact object ($zbs->DAL->contacts->addUpdateContact(),
 * includes/ZeroBSCRM.DAL3.Obj.Contacts.php), whose $args['data'] accepts
 * 'tags' (array of tag IDs or tag name strings) + 'tag_mode' ('append' here
 * so existing tags on an updated contact are preserved). The tag list itself
 * comes from $zbs->DAL->getAllTags() (includes/ZeroBSCRM.DAL3.php), which
 * returns every tag regardless of object type (Jetpack CRM tags aren't
 * scoped per-object-type at the definition level).
 *
 * @link https://plugins.trac.wordpress.org/browser/zero-bs-crm/trunk/includes/ZeroBSCRM.IntegrationFuncs.php
 * @link https://plugins.trac.wordpress.org/browser/zero-bs-crm/trunk/includes/ZeroBSCRM.DAL3.Obj.Contacts.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_jetpackcrmac_actions', 10, 1 );

function adfoin_jetpackcrmac_actions( $actions ) {

    $actions['jetpackcrmac'] = array(
        'title' => __( 'Jetpack CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_jetpackcrmac_action_fields' );

function adfoin_jetpackcrmac_action_fields() {
    ?>
    <script type="text/template" id="jetpackcrmac-action-template">
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

add_action( 'wp_ajax_adfoin_get_jetpackcrmac_tags', 'adfoin_get_jetpackcrmac_tags', 10, 0 );

/*
 * Get Jetpack CRM tags
 */
function adfoin_get_jetpackcrmac_tags() {
    adfoin_verify_nonce();

    global $zbs;

    if ( ! isset( $zbs ) || ! isset( $zbs->DAL ) || ! method_exists( $zbs->DAL, 'getAllTags' ) ) {
        wp_send_json_error( __( 'Jetpack CRM is not active.', 'advanced-form-integration' ) );
    }

    $raw_tags = $zbs->DAL->getAllTags( array( 'perPage' => 999 ) );
    $tags     = array();

    if ( is_array( $raw_tags ) ) {
        foreach ( $raw_tags as $tag ) {
            $tag_id   = isset( $tag['ID'] ) ? $tag['ID'] : ( isset( $tag['id'] ) ? $tag['id'] : '' );
            $tag_name = isset( $tag['name'] ) ? $tag['name'] : ( isset( $tag['zbstag_name'] ) ? $tag['zbstag_name'] : '' );

            if ( '' === $tag_id ) {
                continue;
            }

            $tags[ $tag_id ] = $tag_name;
        }
    }

    wp_send_json_success( $tags );
}

add_action( 'wp_ajax_adfoin_get_jetpackcrmac_contact_fields', 'adfoin_get_jetpackcrmac_contact_fields', 10, 0 );

/*
 * Get Jetpack CRM contact fields
 */
function adfoin_get_jetpackcrmac_contact_fields() {
    adfoin_verify_nonce();

    if ( ! function_exists( 'zeroBS_integrations_addOrUpdateCustomer' ) ) {
        wp_send_json_error( __( 'Jetpack CRM is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'zbsc_email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Used to find or create the contact.', 'advanced-form-integration' ) ),
    );

    // Standard + custom contact fields, straight from Jetpack CRM's own field
    // model ($zbsCustomerFields). Custom fields are merged into that global by
    // zeroBSCRM_unpackCustomFields() keyed by their slug and flagged with
    // 'custom-field' => true, and zeroBS_buildObjArr() saves any zbsc_<slug>
    // key found in it, so the same zbsc_ prefix works for both.
    foreach ( adfoin_jetpackcrmac_get_field_model() as $field_key => $field ) {
        if ( 'email' === $field_key ) {
            continue;
        }

        $type = isset( $field[0] ) ? $field[0] : 'text';

        // Autonumber values are generated by Jetpack CRM itself.
        if ( 'autonumber' === $type ) {
            continue;
        }

        $is_custom   = ! empty( $field['custom-field'] );
        $label       = isset( $field[1] ) && '' !== $field[1] ? $field[1] : $field_key;
        $description = adfoin_jetpackcrmac_field_hint( $type, $field );

        if ( $is_custom ) {
            $description = trim( __( 'Custom field.', 'advanced-form-integration' ) . ' ' . $description );
        }

        $fields[] = array(
            'key'         => 'zbsc_' . $field_key,
            'value'       => $label,
            'description' => $description,
        );
    }

    if ( adfoin_jetpackcrmac_companies_enabled() ) {
        $fields[] = array(
            'key'         => 'company_name',
            'value'       => jpcrm_label_company(),
            'description' => __( 'Links the contact to an existing company with this name, or creates one.', 'advanced-form-integration' ),
        );
    }

    $fields[] = array( 'key' => 'zbsc_tw', 'value' => __( 'Twitter / X Handle', 'advanced-form-integration' ), 'description' => '' );
    $fields[] = array( 'key' => 'zbsc_fb', 'value' => __( 'Facebook Page', 'advanced-form-integration' ), 'description' => '' );
    $fields[] = array( 'key' => 'zbsc_li', 'value' => __( 'LinkedIn Profile', 'advanced-form-integration' ), 'description' => '' );

    wp_send_json_success( $fields );
}

/*
 * Jetpack CRM contact field model (standard + custom fields), minus fields
 * whose setting is switched off (e.g. second address).
 */
function adfoin_jetpackcrmac_get_field_model() {
    global $zbs;

    $var_name = 'zbsCustomerFields';

    if ( isset( $zbs ) && isset( $zbs->DAL ) && method_exists( $zbs->DAL, 'objFieldVarName' ) && defined( 'ZBS_TYPE_CONTACT' ) ) {
        $var_name = $zbs->DAL->objFieldVarName( ZBS_TYPE_CONTACT );
    }

    if ( empty( $var_name ) || ! isset( $GLOBALS[ $var_name ] ) || ! is_array( $GLOBALS[ $var_name ] ) ) {
        return array();
    }

    $model = array();

    foreach ( $GLOBALS[ $var_name ] as $field_key => $field ) {
        if ( ! is_array( $field ) ) {
            continue;
        }

        if ( ! empty( $field['opt'] ) && isset( $zbs->settings ) && ! $zbs->settings->get( $field['opt'] ) ) {
            continue;
        }

        $model[ $field_key ] = $field;
    }

    return $model;
}

/*
 * Whether Jetpack CRM's B2B mode (Companies module) is on
 */
function adfoin_jetpackcrmac_companies_enabled() {
    global $zbs;

    return function_exists( 'zeroBSCRM_getSetting' )
        && function_exists( 'zeroBS_integrations_addOrUpdateCompany' )
        && isset( $zbs->DAL->companies )
        && 1 == zeroBSCRM_getSetting( 'companylevelcustomers' );
}

/*
 * Finds a company by name (case-insensitive exact match) or creates it, and
 * links it to the contact without removing any companies already linked.
 * Returns the company ID, or false on failure.
 */
function adfoin_jetpackcrmac_link_company( $contact_id, $company_name ) {
    global $zbs, $wpdb;

    // get_company_id_by_name() matches with LIKE, so escape wildcards for an exact match.
    $company_id = $zbs->DAL->companies->get_company_id_by_name( $wpdb->esc_like( $company_name ) );

    if ( ! $company_id ) {
        $company_id = zeroBS_integrations_addOrUpdateCompany(
            'adfoin',
            $company_name,
            array( 'zbsco_name' => $company_name ),
            '',
            'auto',
            false,
            false,
            'update',
            'zbsco_'
        );
    }

    if ( ! $company_id ) {
        return false;
    }

    // Not addUpdateObjLinks( 'mode' => 'append' ): it checks the new ID against
    // the existing link-row IDs rather than the linked company IDs, so it skips
    // the link whenever those numbers happen to collide.
    $linked_ids = wp_list_pluck( (array) $zbs->DAL->contacts->getContactCompanies( $contact_id ), 'id' );

    if ( ! in_array( (int) $company_id, array_map( 'intval', $linked_ids ), true ) ) {
        $zbs->DAL->addUpdateObjLink(
            array(
                'data' => array(
                    'objtypefrom' => ZBS_TYPE_CONTACT,
                    'objtypeto'   => ZBS_TYPE_COMPANY,
                    'objfromid'   => $contact_id,
                    'objtoid'     => $company_id,
                ),
            )
        );
    }

    return (int) $company_id;
}

/*
 * Mapping hint shown under a field, based on its Jetpack CRM field type
 */
function adfoin_jetpackcrmac_field_hint( $type, $field ) {
    $options = array();

    if ( isset( $field[3] ) && is_array( $field[3] ) ) {
        $options = array_filter( array_map( 'trim', $field[3] ) );
    }

    switch ( $type ) {
        case 'select':
        case 'radio':
            return $options ? sprintf( __( 'Options: %s', 'advanced-form-integration' ), implode( ', ', $options ) ) : '';
        case 'checkbox':
            return $options
                ? sprintf( __( 'Comma-separated for multiple. Options: %s', 'advanced-form-integration' ), implode( ', ', $options ) )
                : __( 'Comma-separated for multiple.', 'advanced-form-integration' );
        case 'date':
            return __( 'Date, e.g. 2026-01-31.', 'advanced-form-integration' );
        case 'numberint':
            return __( 'Whole number.', 'advanced-form-integration' );
        case 'numberfloat':
        case 'price':
            return __( 'Number.', 'advanced-form-integration' );
        case 'selectcountry':
            return __( 'Country name.', 'advanced-form-integration' );
    }

    return '';
}

/*
 * Normalizes mapped values to the format Jetpack CRM expects for each field type
 */
function adfoin_jetpackcrmac_format_value( $key, $value, $model ) {
    $field_key = 0 === strpos( $key, 'zbsc_' ) ? substr( $key, 5 ) : $key;

    if ( ! isset( $model[ $field_key ][0] ) ) {
        return $value;
    }

    switch ( $model[ $field_key ][0] ) {
        case 'date':
            // zeroBS_buildObjArr() only parses '!Y-m-d' for date fields.
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
                $timestamp = is_numeric( $value ) ? (int) $value : strtotime( $value );

                if ( $timestamp ) {
                    $value = gmdate( 'Y-m-d', $timestamp );
                }
            }
            break;

        case 'checkbox':
            // Jetpack CRM splits checkbox values on ',' only.
            $value = implode( ',', array_filter( array_map( 'trim', preg_split( '/[,|\n]+/', $value ) ), 'strlen' ) );
            break;
    }

    return $value;
}

add_action( 'adfoin_jetpackcrmac_job_queue', 'adfoin_jetpackcrmac_job_queue', 10, 1 );

function adfoin_jetpackcrmac_job_queue( $data ) {
    adfoin_jetpackcrmac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating/updating a Jetpack CRM contact
 */
function adfoin_jetpackcrmac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'zeroBS_integrations_addOrUpdateCustomer' ) ) {
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

    $tag_id = isset( $field_data['tagId'] ) ? $field_data['tagId'] : '';
    unset( $field_data['tagId'] );

    $prepared_data = array();
    $field_model   = adfoin_jetpackcrmac_get_field_model();

    foreach ( $field_data as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed_value || null === $parsed_value ) {
            continue;
        }

        $prepared_data[ $key ] = adfoin_jetpackcrmac_format_value( $key, $parsed_value, $field_model );
    }

    $request_payload = $prepared_data;

    // Company isn't a contact field; it's linked separately once the contact exists.
    $company_name = isset( $prepared_data['company_name'] ) ? trim( $prepared_data['company_name'] ) : '';
    unset( $prepared_data['company_name'] );
    $response_body   = array( 'success' => false );
    $status_code     = 400;

    if ( empty( $prepared_data['zbsc_email'] ) || ! is_email( $prepared_data['zbsc_email'] ) ) {
        $response_body['message'] = __( 'A valid email address is required to create a Jetpack CRM contact.', 'advanced-form-integration' );
    } else {
        // $do_not_update_blanks = true so updating an existing contact only
        // touches the fields this submission mapped, instead of blanking the rest.
        $customer_id = zeroBS_integrations_addOrUpdateCustomer(
            'adfoin',
            $prepared_data['zbsc_email'],
            $prepared_data,
            '',
            'auto',
            false,
            false,
            'update',
            'zbsc_',
            array(),
            true
        );

        if ( $customer_id ) {
            global $zbs;

            if ( $tag_id && isset( $zbs ) && isset( $zbs->DAL ) && isset( $zbs->DAL->contacts ) ) {
                // Use the dedicated tag-only method, not addUpdateContact() — passing
                // 'tags'/'tag_mode' through addUpdateContact()'s generic $args['data']
                // merges in that method's OTHER default (blank) fields and overwrites
                // the email/name/etc. that zeroBS_integrations_addOrUpdateCustomer()
                // just set above. 'tagIDs' (not 'tags') because the dropdown supplies a
                // tag ID — 'tags' expects tag name strings and looks them up by name.
                $zbs->DAL->contacts->addUpdateContactTags(
                    array(
                        'id'     => $customer_id,
                        'tagIDs' => array( $tag_id ),
                        'mode'   => 'append',
                    )
                );
            }

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Contact created/updated successfully.', 'advanced-form-integration' );
            $response_body['id']      = $customer_id;

            if ( '' !== $company_name ) {
                if ( ! adfoin_jetpackcrmac_companies_enabled() ) {
                    $response_body['company_error'] = __( 'Company not linked: B2B mode is disabled in Jetpack CRM.', 'advanced-form-integration' );
                } else {
                    $company_id = adfoin_jetpackcrmac_link_company( $customer_id, $company_name );

                    if ( $company_id ) {
                        $response_body['company_id'] = $company_id;
                    } else {
                        $response_body['company_error'] = __( 'Company could not be found or created.', 'advanced-form-integration' );
                    }
                }
            }
        } else {
            $response_body['message'] = __( 'Failed to create/update Jetpack CRM contact. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'jetpackcrmac', $log_args, $record );
}
