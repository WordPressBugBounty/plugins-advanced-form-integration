<?php

/**
 * Restrict Content Pro trigger.
 *
 * Confirmed against RCP's own developer docs (RCP 3.x's RCP_Membership
 * object model): `rcp_membership_post_activate($membership_id, $membership)`
 * and the dynamic `rcp_transition_membership_status_{$status}` hooks are
 * both real. Two methods this file previously called do NOT exist on
 * RCP_Membership, though: `get_user_id()` actually lives on RCP_Customer
 * (reached via `$membership->get_customer()->get_user_id()`), and
 * `get_membership_level()` isn't a real method at all — the real one,
 * `get_object_id()`, returns the level's numeric ID, which then needs
 * `rcp_get_membership_level($id)->get_name()` to get a display name.
 *
 * @link https://restrictcontentpro.com/knowledgebase/rcp-actions-list/
 * @link https://restrictcontentpro.com/knowledgebase/rcp_membership/
 */
// Get RCP Triggers
function adfoin_restrictcontentpro_get_forms(  $form_provider  ) {
    if ( $form_provider != 'restrictcontentpro' ) {
        return;
    }
    $triggers = array(
        'purchaseMembership'     => __( 'Membership Purchased', 'advanced-form-integration' ),
        'cancelMembership'       => __( 'Membership Cancelled', 'advanced-form-integration' ),
        'activateFreeMembership' => __( 'Free Membership Activated', 'advanced-form-integration' ),
    );
    return $triggers;
}

// Get RCP Fields
function adfoin_restrictcontentpro_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'restrictcontentpro' ) {
        return;
    }
    $fields = array();
    if ( $form_id === 'purchaseMembership' || $form_id === 'cancelMembership' || $form_id === 'activateFreeMembership' ) {
        if ( adfoin_fs()->is_not_paying() ) {
            // Free plan: identity fields only — email/first/last name, plus the
            // membership_id identifier (kept free like entry_id/form_id
            // elsewhere) — matches the submission handler and upgrade notice.
            $fields = array(
                'membership_id' => __( 'Membership ID', 'advanced-form-integration' ),
                'user_email'    => __( 'User Email', 'advanced-form-integration' ),
                'first_name'    => __( 'First Name', 'advanced-form-integration' ),
                'last_name'     => __( 'Last Name', 'advanced-form-integration' ),
            );
        }
    }
    return $fields;
}

/**
 * Free-plan field keys for the RCP trigger — email/first/last name plus the
 * membership_id identifier. Shared by the field list above and the posted
 * data filter below so they can't drift apart.
 */
function adfoin_rcp_free_field_keys() {
    return array(
        'membership_id',
        'user_email',
        'first_name',
        'last_name'
    );
}

/**
 * Restrict the posted data to the free-plan field set when not on Pro.
 * RCP's fields are a fixed schema (not a per-field-type loop like the
 * dynamic form builders), so filtering the assembled array is simpler and
 * less error-prone than gating each field individually while building it.
 */
function adfoin_rcp_filter_posted_data(  $posted_data  ) {
    if ( adfoin_fs()->is_not_paying() ) {
        return array_intersect_key( $posted_data, array_flip( adfoin_rcp_free_field_keys() ) );
    }
    return $posted_data;
}

/**
 * Resolve the real user_id + display-name level for an RCP_Membership,
 * matching the confirmed API (not the guessed one). Shared by all three
 * handlers below.
 */
function adfoin_rcp_get_membership_details(  $membership  ) {
    $user_id = 0;
    $customer = ( method_exists( $membership, 'get_customer' ) ? $membership->get_customer() : null );
    if ( $customer && method_exists( $customer, 'get_user_id' ) ) {
        $user_id = $customer->get_user_id();
    }
    $level_id = ( method_exists( $membership, 'get_object_id' ) ? $membership->get_object_id() : 0 );
    $level_name = '';
    if ( $level_id && function_exists( 'rcp_get_membership_level' ) ) {
        $level = rcp_get_membership_level( $level_id );
        if ( $level && method_exists( $level, 'get_name' ) ) {
            $level_name = $level->get_name();
        }
    }
    $user = ( $user_id ? get_userdata( $user_id ) : false );
    return array(
        'user_id'             => $user_id,
        'user_email'          => ( $user ? $user->user_email : '' ),
        'first_name'          => ( $user ? $user->first_name : '' ),
        'last_name'           => ( $user ? $user->last_name : '' ),
        'membership_level_id' => $level_id,
        'membership_name'     => $level_name,
    );
}

// Handle Membership Purchased
function adfoin_rcp_handle_membership_purchase(  $membership_id, $membership  ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'restrictcontentpro', 'purchaseMembership' );
    if ( empty( $saved_records ) ) {
        return;
    }
    // Bail if it's not a paid membership
    if ( !$membership->is_paid() ) {
        return;
    }
    $details = adfoin_rcp_get_membership_details( $membership );
    $posted_data = array_merge( $details, array(
        'membership_id' => $membership_id,
        'status'        => __( 'Purchased', 'advanced-form-integration' ),
    ) );
    $posted_data = adfoin_rcp_filter_posted_data( $posted_data );
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

add_action(
    'rcp_membership_post_activate',
    'adfoin_rcp_handle_membership_purchase',
    10,
    2
);
// Handle Membership Cancelled
function adfoin_rcp_handle_membership_cancel(  $old_status, $membership_id  ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'restrictcontentpro', 'cancelMembership' );
    if ( empty( $saved_records ) ) {
        return;
    }
    $membership = rcp_get_membership( $membership_id );
    if ( !$membership ) {
        return;
    }
    $details = adfoin_rcp_get_membership_details( $membership );
    $posted_data = array_merge( $details, array(
        'membership_id' => $membership_id,
        'status'        => __( 'Cancelled', 'advanced-form-integration' ),
    ) );
    $posted_data = adfoin_rcp_filter_posted_data( $posted_data );
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

add_action(
    'rcp_transition_membership_status_cancelled',
    'adfoin_rcp_handle_membership_cancel',
    10,
    2
);
// Handle Free Membership Activated
function adfoin_rcp_handle_free_membership(  $membership_id, $membership  ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'restrictcontentpro', 'activateFreeMembership' );
    if ( empty( $saved_records ) ) {
        return;
    }
    // Bail if it's not a free membership
    if ( $membership->is_paid() ) {
        return;
    }
    $details = adfoin_rcp_get_membership_details( $membership );
    $posted_data = array_merge( $details, array(
        'membership_id' => $membership_id,
        'status'        => __( 'Free Activated', 'advanced-form-integration' ),
    ) );
    $posted_data = adfoin_rcp_filter_posted_data( $posted_data );
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

add_action(
    'rcp_membership_post_activate',
    'adfoin_rcp_handle_free_membership',
    10,
    2
);
if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_restrictcontentpro_trigger_fields' );
}
function adfoin_restrictcontentpro_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'restrictcontentpro' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
