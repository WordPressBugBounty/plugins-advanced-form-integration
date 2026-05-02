<?php
global $wpdb;
$integration_table   = $wpdb->prefix . 'adfoin_integration';
$integration_options = $wpdb->get_results( "SELECT id, title FROM {$integration_table} ORDER BY title ASC", ARRAY_A );
// Fix #4: clamp the visible list height so the card doesn't balloon on large sites.
$select_size = min( 8, max( 4, count( $integration_options ) + 1 ) );
?>
<div class="wrap afi-container">
    <?php adfoin_display_admin_header(); ?>

    <div class="afi-row">

        <!-- ===================== Export Section ===================== -->
        <div class="afi-col afi-col-6">
            <div class="afi-card" style="height: 100%; box-sizing: border-box;">
                <div class="afi-card-header">
                    <h2 class="afi-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <?php esc_html_e( 'Export Integrations', 'advanced-form-integration' ); ?>
                    </h2>
                </div>

                <p style="margin-top: 0; color: #646970; margin-bottom: 20px;">
                    <?php esc_html_e( 'Download saved integrations as a JSON file for backup or migration.', 'advanced-form-integration' ); ?>
                </p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'adfoin-export-integrations' ); ?>
                    <input type="hidden" name="action" value="adfoin_export_integrations" />

                    <div class="afi-form-group">
                        <label for="adfoin-export-integration" class="afi-label">
                            <?php esc_html_e( 'Choose integrations', 'advanced-form-integration' ); ?>
                        </label>

                        <?php /* Fix #4: multi-select lets users export any subset without going to the list table. */ ?>
                        <select
                            id="adfoin-export-integration"
                            name="integration_ids[]"
                            class="afi-select"
                            multiple
                            size="<?php echo esc_attr( $select_size ); ?>"
                            style="height: auto; width: 100%;"
                        >
                            <option value="all" selected><?php esc_html_e( '— All integrations —', 'advanced-form-integration' ); ?></option>
                            <?php if ( ! empty( $integration_options ) ) : ?>
                                <?php foreach ( $integration_options as $integration ) : ?>
                                    <option value="<?php echo esc_attr( $integration['id'] ); ?>">
                                        <?php echo esc_html( sprintf( '#%1$d — %2$s', $integration['id'], $integration['title'] ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <p class="description" style="margin-top: 6px; color: #646970; font-size: 12px;">
                            <?php esc_html_e( 'Hold Ctrl / ⌘ to select individual integrations. Leave "All integrations" selected to export everything.', 'advanced-form-integration' ); ?>
                        </p>
                    </div>

                    <button type="submit" class="afi-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0; margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <?php esc_html_e( 'Export', 'advanced-form-integration' ); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- ===================== Import Section ===================== -->
        <div class="afi-col afi-col-6">
            <div class="afi-card" style="height: 100%; box-sizing: border-box;">
                <div class="afi-card-header">
                    <h2 class="afi-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <?php esc_html_e( 'Import Integrations', 'advanced-form-integration' ); ?>
                    </h2>
                </div>

                <p style="margin-top: 0; color: #646970; margin-bottom: 20px;">
                    <?php esc_html_e( 'Upload a JSON export created by this plugin. Maximum file size: 5 MB.', 'advanced-form-integration' ); ?>
                </p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'adfoin-import-integrations' ); ?>
                    <input type="hidden" name="action" value="adfoin_import_integrations" />

                    <div class="afi-form-group">
                        <label class="afi-label"><?php esc_html_e( 'Select File', 'advanced-form-integration' ); ?></label>
                        <input
                            type="file"
                            name="adfoin_import_file"
                            accept=".json,application/json"
                            required
                            class="afi-input"
                            style="padding: 8px; height: auto;"
                        />
                    </div>

                    <!-- Activate immediately -->
                    <div class="afi-form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="activate_imported" value="1" />
                            <span style="font-weight: 500; color: #3c434a;">
                                <?php esc_html_e( 'Activate imported integrations immediately', 'advanced-form-integration' ); ?>
                            </span>
                        </label>
                    </div>

                    <!-- Fix #10: overwrite existing integrations -->
                    <div class="afi-form-group">
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="overwrite_existing" value="1" style="margin-top: 3px;" />
                            <span>
                                <span style="font-weight: 500; color: #3c434a;">
                                    <?php esc_html_e( 'Overwrite existing integrations', 'advanced-form-integration' ); ?>
                                </span>
                                <br />
                                <span style="color: #646970; font-size: 12px;">
                                    <?php esc_html_e( 'If an integration with the same title, form, and action already exists it will be updated in place instead of skipped.', 'advanced-form-integration' ); ?>
                                </span>
                            </span>
                        </label>
                    </div>

                    <!-- Fix #7: dry-run / preview mode -->
                    <div class="afi-form-group">
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="dry_run" value="1" style="margin-top: 3px;" />
                            <span>
                                <span style="font-weight: 500; color: #3c434a;">
                                    <?php esc_html_e( 'Dry run — preview only, no changes saved', 'advanced-form-integration' ); ?>
                                </span>
                                <br />
                                <span style="color: #646970; font-size: 12px;">
                                    <?php esc_html_e( 'Simulate the import and see how many integrations would be imported, updated, or skipped without making any changes.', 'advanced-form-integration' ); ?>
                                </span>
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="afi-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0; margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <?php esc_html_e( 'Import', 'advanced-form-integration' ); ?>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
