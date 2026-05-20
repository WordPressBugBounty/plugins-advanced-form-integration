<?php
/**
 * General Settings tab — Activate Platforms list + the five global toggles.
 *
 * Included from adfoin_general_settings_view() (includes/functions-adfoin.php).
 * Expects these vars in scope: $nonce, $reset_nonce, $log_settings, $log_retention,
 * $error_email, $st_settings, $utm_settings, $job_queue, $job_queue_stats,
 * $platform_settings, $platforms.
 */

defined( 'ABSPATH' ) || exit;
?>

<form name="general_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
      method="post" class="afi-container">

    <input type="hidden" name="action" value="adfoin_save_general_settings">
    <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

    <!-- ── Card 1: Activate Platforms ── -->
    <div class="afi-card">
        <div class="afi-card-header afi-settings-card-header">
            <h3 class="afi-card-title"><?php esc_html_e( 'Activate Platforms', 'advanced-form-integration' ); ?></h3>

            <div class="afi-filter-controls">
                <div class="afi-filter-links">
                    <button type="button" class="afi-filter-btn active" data-filter="all"><?php esc_html_e( 'All', 'advanced-form-integration' ); ?></button>
                    <button type="button" class="afi-filter-btn" data-filter="active"><?php esc_html_e( 'Active', 'advanced-form-integration' ); ?></button>
                    <button type="button" class="afi-filter-btn" data-filter="inactive"><?php esc_html_e( 'Inactive', 'advanced-form-integration' ); ?></button>
                </div>
                <div class="afi-search-wrapper">
                    <input type="search"
                           id="adfoin-platform-search"
                           class="afi-input afi-platform-search"
                           placeholder="<?php esc_attr_e( 'Search platforms...', 'advanced-form-integration' ); ?>"
                           autocomplete="off">
                </div>
                <input type="submit" name="submit" class="afi-save-button" value="<?php esc_attr_e( 'Save Changes', 'advanced-form-integration' ); ?>">
            </div>
        </div>

        <div class="afi-checkbox-container" data-platform-list>
            <?php foreach ( $platforms as $key => $platform ) :
                $status    = isset( $platform_settings[ $key ] ) ? $platform_settings[ $key ] : '';
                $is_active = ( 1 == $status ) ? 'active' : 'inactive';
                $safe_key  = esc_attr( $key );
            ?>
                <div class="afi-checkbox" data-status="<?php echo esc_attr( $is_active ); ?>">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="<?php echo $safe_key; ?>"><?php echo esc_html( $platform ); ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="<?php echo $safe_key; ?>" name="platforms[<?php echo $safe_key; ?>]" <?php checked( $status, 1 ); ?>>
                        <span class="afi-slider round"></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="afi-no-results" class="afi-no-results" hidden>
            <p class="afi-no-results-text"><?php esc_html_e( 'No platforms found matching your criteria.', 'advanced-form-integration' ); ?></p>
        </div>
    </div>

    <!-- ── Card 2: General Settings ── -->
    <div class="afi-card">
        <div class="afi-card-header">
            <h3 class="afi-card-title"><?php esc_html_e( 'General Settings', 'advanced-form-integration' ); ?></h3>
        </div>

        <div class="afi-checkbox-container afi-settings-toggles">

            <div class="afi-checkbox">
                <div class="afi-elements-info">
                    <p class="afi-el-title">
                        <label for="adfoin_disable_log"><?php esc_html_e( 'Disable Log', 'advanced-form-integration' ); ?></label>
                    </p>
                    <p class="afi-helper-text"><?php esc_html_e( 'Stop recording integration activity. Useful on high-traffic sites where log storage is a concern.', 'advanced-form-integration' ); ?></p>
                </div>
                <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_disable_log" name="adfoin_disable_log" <?php checked( $log_settings, 1 ); ?>>
                    <span class="afi-slider round"></span>
                </label>
            </div>

            <div class="afi-checkbox">
                <div class="afi-elements-info">
                    <p class="afi-el-title">
                        <label for="adfoin_log_retention"><?php esc_html_e( 'Auto-delete logs after', 'advanced-form-integration' ); ?></label>
                    </p>
                    <p class="afi-helper-text"><?php esc_html_e( 'Run a daily cleanup that removes log rows older than this many days. Set to 0 to keep logs forever.', 'advanced-form-integration' ); ?></p>
                </div>
                <div class="afi-inline-field">
                    <input type="number" min="0" step="1" id="adfoin_log_retention" name="adfoin_log_retention" value="<?php echo esc_attr( $log_retention ); ?>" class="afi-input afi-input-small">
                    <span class="afi-inline-suffix"><?php esc_html_e( 'days', 'advanced-form-integration' ); ?></span>
                </div>
            </div>

            <div class="afi-checkbox">
                <div class="afi-elements-info">
                    <p class="afi-el-title">
                        <label for="adfoin_disable_st"><?php esc_html_e( 'Disable Special Tags', 'advanced-form-integration' ); ?></label>
                    </p>
                    <p class="afi-helper-text"><?php esc_html_e( 'Turn off built-in special tags such as {{_date}} and {{_user_ip}} from being processed on submissions.', 'advanced-form-integration' ); ?></p>
                </div>
                <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_disable_st" name="adfoin_disable_st" <?php checked( $st_settings, 1 ); ?>>
                    <span class="afi-slider round"></span>
                </label>
            </div>

            <div class="afi-checkbox">
                <div class="afi-elements-info">
                    <p class="afi-el-title">
                        <label for="adfoin_enable_utm"><?php esc_html_e( 'Send UTM Variables', 'advanced-form-integration' ); ?></label>
                    </p>
                    <p class="afi-helper-text"><?php esc_html_e( 'Automatically append UTM tracking parameters from the visitor\'s URL to each form submission sent to integrations.', 'advanced-form-integration' ); ?></p>
                </div>
                <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_enable_utm" name="adfoin_enable_utm" <?php checked( $utm_settings, 1 ); ?>>
                    <span class="afi-slider round"></span>
                </label>
            </div>

            <div class="afi-checkbox">
                <div class="afi-elements-info">
                    <p class="afi-el-title">
                        <label for="adfoin_job_queue"><?php esc_html_e( 'Enable Job Queue', 'advanced-form-integration' ); ?></label>
                    </p>
                    <p class="afi-helper-text"><?php esc_html_e( 'Process integrations asynchronously in the background instead of during the form submission request, improving response time.', 'advanced-form-integration' ); ?></p>
                    <?php if ( is_array( $job_queue_stats ) ) :
                        $health_classes = array( 'afi-helper-text', 'afi-queue-health' );
                        if ( $job_queue_stats['failed'] > 0 ) {
                            $health_classes[] = 'is-error';
                        } elseif ( $job_queue_stats['pending'] > 100 ) {
                            $health_classes[] = 'is-warn';
                        }

                        if ( ! empty( $job_queue_stats['last_run'] ) ) {
                            $last_run_ts    = strtotime( $job_queue_stats['last_run'] . ' UTC' );
                            $last_run_label = $last_run_ts
                                ? sprintf(
                                    /* translators: %s: human-readable duration like "5 minutes" */
                                    __( 'last run %s ago', 'advanced-form-integration' ),
                                    human_time_diff( $last_run_ts, time() )
                                )
                                : __( 'last run unknown', 'advanced-form-integration' );
                        } else {
                            $last_run_label = __( 'never run yet', 'advanced-form-integration' );
                        }
                    ?>
                    <p class="<?php echo esc_attr( implode( ' ', $health_classes ) ); ?>">
                        <span><?php
                            printf(
                                /* translators: %d: number of pending queued actions */
                                esc_html( _n( '%d pending', '%d pending', $job_queue_stats['pending'], 'advanced-form-integration' ) ),
                                (int) $job_queue_stats['pending']
                            );
                        ?></span>
                        <span aria-hidden="true">·</span>
                        <span><?php
                            printf(
                                /* translators: %d: number of failed queued actions */
                                esc_html( _n( '%d failed', '%d failed', $job_queue_stats['failed'], 'advanced-form-integration' ) ),
                                (int) $job_queue_stats['failed']
                            );
                        ?></span>
                        <span aria-hidden="true">·</span>
                        <span><?php echo esc_html( $last_run_label ); ?></span>
                        <span aria-hidden="true">·</span>
                        <a href="<?php echo esc_url( admin_url( 'tools.php?page=action-scheduler&s=adfoin&orderby=schedule&order=desc' ) ); ?>"><?php esc_html_e( 'View queue', 'advanced-form-integration' ); ?></a>
                    </p>
                    <?php endif; ?>
                </div>
                <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_job_queue" name="adfoin_job_queue" <?php checked( $job_queue, 1 ); ?>>
                    <span class="afi-slider round"></span>
                </label>
            </div>

            <div class="afi-checkbox">
                <div class="afi-elements-info">
                    <p class="afi-el-title">
                        <label for="adfoin_error_email"><?php esc_html_e( 'Send Error Email', 'advanced-form-integration' ); ?></label>
                    </p>
                    <p class="afi-helper-text"><?php esc_html_e( 'Receive an email notification at the site admin address whenever an integration encounters an error.', 'advanced-form-integration' ); ?></p>
                    <p class="afi-helper-text">
                        <button type="button" id="adfoin-test-email-btn" class="button button-secondary"><?php esc_html_e( 'Send test email', 'advanced-form-integration' ); ?></button>
                        <span id="adfoin-test-email-result" class="afi-test-email-result" role="status" aria-live="polite"></span>
                    </p>
                </div>
                <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_error_email" name="adfoin_error_email" <?php checked( $error_email, 1 ); ?>>
                    <span class="afi-slider round"></span>
                </label>
            </div>

        </div>
    </div>

</form>

<form id="adfoin-reset-general-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="afi-container afi-reset-form">
    <input type="hidden" name="action" value="adfoin_reset_general_settings">
    <input type="hidden" name="_nonce" value="<?php echo esc_attr( $reset_nonce ); ?>"/>
    <p class="afi-reset-row">
        <button type="submit" class="button-link afi-reset-link"><?php esc_html_e( 'Reset general settings to defaults', 'advanced-form-integration' ); ?></button>
        <span class="afi-helper-text afi-reset-hint"><?php esc_html_e( 'Platform activations are not affected.', 'advanced-form-integration' ); ?></span>
    </p>
</form>
