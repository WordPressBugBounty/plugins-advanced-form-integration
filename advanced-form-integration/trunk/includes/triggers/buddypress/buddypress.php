<?php

function adfoin_buddypress_get_forms(  $form_provider  ) {
    if ( 'buddypress' !== $form_provider ) {
        return;
    }
    return array(
        'registration' => __( 'BuddyPress Registration', 'advanced-form-integration' ),
    );
}

function adfoin_buddypress_get_form_fields(  $form_provider, $form_id  ) {
    if ( 'buddypress' !== $form_provider ) {
        return;
    }
    if ( 'registration' !== $form_id ) {
        return array();
    }
    $fields = array();
    if ( adfoin_fs()->is_not_paying() ) {
        $fields['user_login'] = __( 'Username', 'advanced-form-integration' );
        $fields['user_email'] = __( 'Email', 'advanced-form-integration' );
    }
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_buddypress_get_all_fields() {
    $fields = array(
        'user_login'        => __( 'Username', 'advanced-form-integration' ),
        'user_email'        => __( 'Email', 'advanced-form-integration' ),
        'user_password'     => __( 'Password', 'advanced-form-integration' ),
        'user_id'           => __( 'User ID', 'advanced-form-integration' ),
        'profile_field_ids' => __( 'Profile Field IDs', 'advanced-form-integration' ),
        'public'            => __( 'Blog Visibility', 'advanced-form-integration' ),
    );
    $profile_fields = adfoin_buddypress_get_profile_field_labels();
    if ( !empty( $profile_fields ) ) {
        $fields = $fields + $profile_fields;
    }
    return $fields;
}

function adfoin_buddypress_get_profile_field_labels() {
    static $labels = null;
    if ( null !== $labels ) {
        return $labels;
    }
    $labels = array();
    if ( !function_exists( 'bp_is_active' ) || !bp_is_active( 'xprofile' ) ) {
        return $labels;
    }
    if ( !function_exists( 'bp_xprofile_get_groups' ) ) {
        return $labels;
    }
    $groups = bp_xprofile_get_groups( array(
        'hide_empty_groups' => false,
        'fetch_fields'      => true,
        'fetch_field_data'  => false,
    ) );
    if ( empty( $groups ) ) {
        return $labels;
    }
    foreach ( $groups as $group ) {
        if ( empty( $group->fields ) ) {
            continue;
        }
        foreach ( $group->fields as $field ) {
            if ( empty( $field->id ) || empty( $field->name ) ) {
                continue;
            }
            $labels['field_' . $field->id] = $field->name;
        }
    }
    return $labels;
}

add_action(
    'bp_core_signup_user',
    'adfoin_buddypress_after_signup',
    10,
    5
);
function adfoin_buddypress_after_signup(
    $user_id,
    $user_login,
    $user_password,
    $user_email,
    $usermeta
) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'buddypress', 'registration' );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array(
        'user_login'      => $user_login,
        'user_email'      => $user_email,
        'form_id'         => 'registration',
        'submission_date' => current_time( 'mysql' ),
        'user_ip'         => adfoin_get_user_ip(),
    );
    if ( is_array( $usermeta ) ) {
        foreach ( $usermeta as $meta_key => $meta_value ) {
            $posted_data[$meta_key] = adfoin_buddypress_normalize_meta_value( $meta_value );
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( null );
    if ( is_array( $special_tag_values ) ) {
        $posted_data = array_merge( $posted_data, $special_tag_values );
    }
    $integration->send( $saved_records, $posted_data );
}

function adfoin_buddypress_normalize_meta_value(  $value  ) {
    if ( is_array( $value ) ) {
        $flat = array();
        array_walk_recursive( $value, function ( $item ) use(&$flat) {
            if ( is_scalar( $item ) && '' !== $item ) {
                $flat[] = (string) $item;
            }
        } );
        return implode( ', ', array_filter( $flat ) );
    }
    if ( is_object( $value ) ) {
        return wp_json_encode( $value );
    }
    return (string) $value;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_buddypress_trigger_fields' );
}
function adfoin_buddypress_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'buddypress'" is="buddypress" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php 
}

add_action( 'adfoin_trigger_templates', 'adfoin_buddypress_trigger_template' );
function adfoin_buddypress_trigger_template() {
    ?>
    <script type="text/template" id="buddypress-template">
        <tr valign="top" class="alternate" v-if="trigger.formId == 'registration'">
            <td scope="row-title">
                <label for="tablecell">
                    <span class="dashicons dashicons-info-outline"></span>
                </label>
            </td>
            <td>
                <p>
                    <?php 
    esc_attr_e( 'The basic AFI plugin supports BuddyPress username and email fields only', 'advanced-form-integration' );
    ?>
                </p>
            </td>
        </tr>
    </script>
    <?php 
}
