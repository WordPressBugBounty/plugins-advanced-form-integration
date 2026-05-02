<?php
/**
 * Edit integration page.
 *
 * Hydrates the Vue app from the saved DB row, renders the error
 * notice + Resend link when the last submission failed, and includes
 * the shared form partial in 'edit' mode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$table           = $wpdb->prefix . 'adfoin_integration';
$result          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
$result['title'] = esc_html( $result['title'] );
$data            = json_decode( $result['data'], true );
$trigger_data    = isset( $data['trigger_data'] ) ? $data['trigger_data'] : array();
$action_data     = isset( $data['action_data'] )  ? $data['action_data']  : array();
$field_data      = isset( $data['field_data'] )   ? $data['field_data']   : array();

// Self-heal: if a row was stored during a brief window where field_data
// was persisted as a JSON-encoded JSON string (rather than a real array),
// decode it here so the mapping section renders on first open. Re-saving
// the integration after this load will rewrite it in the proper shape.
if ( is_string( $field_data ) ) {
    $decoded    = json_decode( $field_data, true );
    $field_data = is_array( $decoded ) ? $decoded : array();
} elseif ( ! is_array( $field_data ) ) {
    $field_data = array();
}

$nonce             = wp_create_nonce( 'adfoin-integration' );
$integration_title = $result['title'];
$mode              = 'edit';

// Alphabetically sorted option lists for the searchable pickers.
$form_providers_list   = adfoin_get_form_providers_array();
$action_providers_list = adfoin_get_action_providers_array();

// Health stats for the status strip (last 30 days).
$stats   = adfoin_get_integration_stats( $id, 30 );
$log_url = admin_url( 'admin.php?page=advanced-form-integration-log&id=' . $id );
?>

<script type="text/javascript">
    var triggerData = <?php echo wp_json_encode( $trigger_data ); ?>;
    var actionData  = <?php echo wp_json_encode( $action_data ); ?>;
    var fieldData   = <?php echo wp_json_encode( $field_data ); ?>;
    window.adfoinIntegrationId   = <?php echo (int) $id; ?>;
    window.adfoinFormProviders   = <?php echo wp_json_encode( $form_providers_list ); ?>;
    window.adfoinActionProviders = <?php echo wp_json_encode( $action_providers_list ); ?>;
</script>

<div class="wrap">
    <?php adfoin_display_admin_header( $id, $integration_title ); ?>

    <?php
    if ( $stats ) {
        // Determine overall state for the left-border accent + sparkline color.
        $strip_state = 'empty';
        if ( $stats['total'] > 0 ) {
            if ( ! $stats['last_run_ok'] ) {
                $strip_state = 'error';
            } elseif ( null !== $stats['success_rate'] && $stats['success_rate'] < 100 ) {
                $strip_state = 'warning';
            } else {
                $strip_state = 'success';
            }
        }

        // Success-rate emphasis class.
        $rate_class = 'is-success';
        if ( null !== $stats['success_rate'] ) {
            if ( $stats['success_rate'] < 80 ) {
                $rate_class = 'is-error';
            } elseif ( $stats['success_rate'] < 100 ) {
                $rate_class = 'is-warning';
            }
        }

        $last_run_label = $stats['last_run_time']
            ? sprintf(
                /* translators: %s: human-readable time difference */
                __( '%s ago', 'advanced-form-integration' ),
                human_time_diff( strtotime( $stats['last_run_time'] ), current_time( 'timestamp' ) )
            )
            : __( 'Never', 'advanced-form-integration' );
        $last_run_iso = $stats['last_run_time']
            ? mysql2date( 'c', $stats['last_run_time'] )
            : '';
        $last_run_full = $stats['last_run_time']
            ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stats['last_run_time'] )
            : '';
    ?>
        <div class="afi-status-strip" data-state="<?php echo esc_attr( $strip_state ); ?>" role="region" aria-label="<?php esc_attr_e( 'Integration health (last 30 days)', 'advanced-form-integration' ); ?>">
            <?php if ( $stats['total'] === 0 ) : ?>
                <p class="afi-status-empty"><?php esc_html_e( 'No submissions yet for this integration. Stats will appear here once data starts flowing.', 'advanced-form-integration' ); ?></p>
                <div class="afi-status-actions">
                    <a href="<?php echo esc_url( $log_url ); ?>"><?php esc_html_e( 'View Log', 'advanced-form-integration' ); ?></a>
                </div>
            <?php else : ?>
                <div class="afi-status-cell">
                    <span class="afi-status-label"><?php
                        /* translators: %d: number of days */
                        printf( esc_html__( 'Submissions (%d days)', 'advanced-form-integration' ), (int) $stats['window_days'] );
                    ?></span>
                    <span class="afi-status-value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                </div>

                <div class="afi-status-cell">
                    <span class="afi-status-label"><?php esc_html_e( 'Success rate', 'advanced-form-integration' ); ?></span>
                    <span class="afi-status-value afi-status-rate <?php echo esc_attr( $rate_class ); ?>">
                        <?php echo (int) $stats['success_rate']; ?>%
                    </span>
                    <?php if ( $stats['failure'] > 0 ) : ?>
                        <span class="afi-status-meta"><?php
                            /* translators: %d: number of failed submissions */
                            printf( esc_html( _n( '%d failed', '%d failed', $stats['failure'], 'advanced-form-integration' ) ), (int) $stats['failure'] );
                        ?></span>
                    <?php endif; ?>
                </div>

                <div class="afi-status-cell">
                    <span class="afi-status-label"><?php esc_html_e( 'Last run', 'advanced-form-integration' ); ?></span>
                    <span class="afi-status-value">
                        <?php if ( $last_run_iso ) : ?>
                            <time datetime="<?php echo esc_attr( $last_run_iso ); ?>" title="<?php echo esc_attr( $last_run_full ); ?>"><?php echo esc_html( $last_run_label ); ?></time>
                        <?php else : ?>
                            <?php echo esc_html( $last_run_label ); ?>
                        <?php endif; ?>
                    </span>
                    <?php if ( $stats['last_run_time'] ) : ?>
                        <span class="afi-status-meta">
                            <?php echo $stats['last_run_ok']
                                ? esc_html__( 'OK', 'advanced-form-integration' )
                                : esc_html__( 'Failed', 'advanced-form-integration' ); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="afi-status-cell afi-status-cell-spark">
                    <span class="afi-status-label"><?php
                        /* translators: %d: number of days */
                        printf( esc_html__( 'Activity (%d days)', 'advanced-form-integration' ), (int) $stats['window_days'] );
                    ?></span>
                    <?php echo adfoin_render_sparkline( $stats['series'], 120, 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — function returns trusted SVG. ?>
                </div>

                <div class="afi-status-actions">
                    <a href="<?php echo esc_url( $log_url ); ?>"><?php esc_html_e( 'View Log', 'advanced-form-integration' ); ?></a>
                    <?php if ( ! $stats['last_run_ok'] && ! empty( $stats['last_request'] ) ) :
                        $resend_nonce = wp_create_nonce( 'adfoin-resend-log' );
                    ?>
                        <span aria-hidden="true">&middot;</span>
                        <form method="post"
                              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                              class="afi-inline-resend">
                            <input type="hidden" name="action" value="adfoin_resend_log_data" />
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $resend_nonce ); ?>" />
                            <input type="hidden" name="log_id" value="<?php echo esc_attr( (int) $stats['last_log_id'] ); ?>" />
                            <input type="hidden" name="integration_id" value="<?php echo esc_attr( (int) $id ); ?>" />
                            <input type="hidden" name="request-data" value="<?php echo esc_attr( $stats['last_request'] ); ?>" />
                            <button type="submit"><?php esc_html_e( 'Resend last submission', 'advanced-form-integration' ); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php } ?>

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
