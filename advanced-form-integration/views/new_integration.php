<?php
/**
 * New integration page.
 *
 * Hydrates the Vue app with sensible defaults for a fresh integration
 * and renders the shared form partial in 'new' mode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$integration_table = $wpdb->prefix . 'adfoin_integration';
$last_id           = $wpdb->get_var( "SELECT MAX(id) FROM {$integration_table}" );
$last_id           = empty( $last_id ) ? 0 : $last_id;
$integration_title = 'Integration #' . ( $last_id + 1 );
$nonce             = wp_create_nonce( 'adfoin-integration' );
$field_data        = array();
$id                = 0;
$mode              = 'new';

// Alphabetically sorted option lists for the searchable pickers.
$form_providers_list   = adfoin_get_form_providers_array();
$action_providers_list = adfoin_get_action_providers_array();
?>
<script type="text/javascript">
    var integrationTitle = <?php echo wp_json_encode( $integration_title ); ?>;
    window.adfoinIntegrationId = 0;
    window.adfoinFormProviders   = <?php echo wp_json_encode( $form_providers_list ); ?>;
    window.adfoinActionProviders = <?php echo wp_json_encode( $action_providers_list ); ?>;
</script>

<?php
    do_action( 'adfoin_add_js_fields', $field_data );
?>

<div class="wrap">
    <?php adfoin_display_admin_header(); ?>

    <div id="adfoin-new-integration" v-cloak class="afi-container">

        <?php include ADVANCED_FORM_INTEGRATION_VIEWS . '/partials/integration-form.php'; ?>

        <!-- Toast notifications anchored to the bottom-right -->
        <div class="afi-toast-container" aria-live="polite" aria-atomic="true">
            <div v-for="t in toasts"
                 :key="t.id"
                 class="afi-toast"
                 :class="['afi-toast-' + t.type, { 'is-leaving': t.leaving }]"
                 role="status">
                <span class="afi-toast-icon dashicons"
                      :class="t.type === 'success' ? 'dashicons-yes' : (t.type === 'error' ? 'dashicons-warning' : 'dashicons-info')"
                      aria-hidden="true"></span>
                <span class="afi-toast-body">{{ t.message }}</span>
                <button type="button" class="afi-toast-close" aria-label="<?php esc_attr_e( 'Dismiss', 'advanced-form-integration' ); ?>" @click="dismissToast(t.id)">&times;</button>
            </div>
        </div>

    </div>
</div>

<?php do_action( 'adfoin_action_fields' ); ?>

<?php include ADVANCED_FORM_INTEGRATION_VIEWS . '/partials/integration-templates.php'; ?>

<?php do_action( 'adfoin_trigger_templates' ); ?>
