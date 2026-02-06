<?php
adfoin_display_admin_header();

global $wpdb;
$integration_table = $wpdb->prefix . 'adfoin_integration';
$integration_options = $wpdb->get_results( "SELECT id, title FROM {$integration_table} ORDER BY title ASC", ARRAY_A );
?>
<div class="afi-container">


    <div class="afi-row">
        <!-- Export Section -->
        <div class="afi-col afi-col-6">
            <div class="afi-card" style="height: 100%; box-sizing: border-box;">
                <div class="afi-card-header">
                    <h2 class="afi-card-title">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Export Integrations', 'advanced-form-integration' ); ?>
                    </h2>
                </div>
                
                <p style="margin-top: 0; color: #646970; margin-bottom: 20px;">
                    <?php esc_html_e( 'Download all saved integrations as a JSON file for backup or migration.', 'advanced-form-integration' ); ?>
                </p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'adfoin-export-integrations' ); ?>
                    <input type="hidden" name="action" value="adfoin_export_integrations" />
                    
                    <div class="afi-form-group">
                        <label for="adfoin-export-integration" class="afi-label">
                            <?php esc_html_e( 'Choose integration', 'advanced-form-integration' ); ?>
                        </label>
                        <select id="adfoin-export-integration" name="integration_id" class="afi-select">
                            <option value="all"><?php esc_html_e( 'All integrations', 'advanced-form-integration' ); ?></option>
                            <?php if ( ! empty( $integration_options ) ) : ?>
                                <?php foreach ( $integration_options as $integration ) : ?>
                                    <option value="<?php echo esc_attr( $integration['id'] ); ?>">
                                        <?php echo esc_html( sprintf( '#%1$d — %2$s', $integration['id'], $integration['title'] ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button type="submit" class="afi-btn-primary">
                        <span class="dashicons dashicons-download" style="line-height: 1.5; margin-right: 5px;"></span>
                        <?php esc_html_e( 'Export', 'advanced-form-integration' ); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Import Section -->
        <div class="afi-col afi-col-6">
            <div class="afi-card" style="height: 100%; box-sizing: border-box;">
                <div class="afi-card-header">
                    <h2 class="afi-card-title">
                         <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e( 'Import Integrations', 'advanced-form-integration' ); ?>
                    </h2>
                </div>

                <p style="margin-top: 0; color: #646970; margin-bottom: 20px;">
                    <?php esc_html_e( 'Upload a JSON export created by this plugin. Imported integrations remain inactive until you review them.', 'advanced-form-integration' ); ?>
                </p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'adfoin-import-integrations' ); ?>
                    <input type="hidden" name="action" value="adfoin_import_integrations" />
                    
                    <div class="afi-form-group">
                        <label class="afi-label"><?php esc_html_e( 'Select File', 'advanced-form-integration' ); ?></label>
                        <input type="file" name="adfoin_import_file" accept=".json,application/json" required class="afi-input" style="padding: 8px; height: auto;" />
                    </div>

                    <div class="afi-form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="activate_imported" value="1" />
                            <span style="font-weight: 500; color: #3c434a;"><?php esc_html_e( 'Activate imported integrations immediately', 'advanced-form-integration' ); ?></span>
                        </label>
                    </div>

                    <button type="submit" class="afi-btn-primary">
                        <span class="dashicons dashicons-upload" style="line-height: 1.5; margin-right: 5px;"></span>
                        <?php esc_html_e( 'Import', 'advanced-form-integration' ); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
