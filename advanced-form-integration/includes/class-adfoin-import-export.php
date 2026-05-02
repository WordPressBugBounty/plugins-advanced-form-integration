<?php

class Advanced_Form_Integration_Import_Export {

    /**
     * Singleton holder.
     *
     * @var Advanced_Form_Integration_Import_Export|null
     */
    protected static $instance = null;

    /**
     * Transient key prefix for admin notices.
     */
    const NOTICE_KEY = 'adfoin_import_export_notice_';

    /**
     * Maximum allowed import file size (5 MB). Fix #9.
     */
    const MAX_IMPORT_FILE_SIZE = 5242880;

    /**
     * Bootstraps hooks.
     */
    private function __construct() {
        add_action( 'admin_post_adfoin_export_integrations', array( $this, 'export_from_request' ) );
        add_action( 'admin_post_adfoin_import_integrations', array( $this, 'import_from_request' ) );
        add_action( 'admin_notices', array( $this, 'render_notices' ) );
    }

    /**
     * Returns the singleton instance.
     *
     * @return Advanced_Form_Integration_Import_Export
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Handles manual export form submissions.
     *
     * Fix #4: reads integration_ids[] multi-select instead of single integration_id.
     * Fix #11: redirects back to the Import/Export page on error.
     */
    public function export_from_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to export integrations.', 'advanced-form-integration' ) );
        }

        check_admin_referer( 'adfoin-export-integrations' );

        $ids = array();

        if ( ! empty( $_POST['integration_ids'] ) ) {
            $raw = (array) $_POST['integration_ids'];
            // Filter out the 'all' sentinel value; empty array = export all.
            $raw = array_filter( $raw, function ( $v ) {
                return 'all' !== $v;
            } );
            $ids = array_map( 'absint', array_values( $raw ) );
        }

        $this->export_integrations( $ids );
    }

    /**
     * Exports the requested integrations as a JSON download.
     *
     * Fix #11: on empty results, redirects back to the Import/Export page.
     *
     * @param array $ids Integration IDs to export; empty array exports all.
     */
    public function export_integrations( $ids = array() ) {
        global $wpdb;

        $table = $wpdb->prefix . 'adfoin_integration';

        if ( ! empty( $ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
            $sql          = $wpdb->prepare( "SELECT * FROM {$table} WHERE id IN ({$placeholders})", $ids );
        } else {
            $sql = "SELECT * FROM {$table}";
        }

        $records = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $records ) ) {
            $this->add_notice(
                'warning',
                __( 'No integrations were found to export.', 'advanced-form-integration' )
            );

            // Fix #11: export errors stay on the Import/Export page.
            $this->redirect_to_import_export();
        }

        $payload = array(
            'afi_version'  => defined( 'ADVANCED_FORM_INTEGRATION_VERSION' ) ? ADVANCED_FORM_INTEGRATION_VERSION : '',
            'site'         => home_url(),
            'generated_at' => gmdate( 'c' ),
            'integrations' => array(),
        );

        foreach ( $records as $record ) {
            $payload['integrations'][] = array(
                'original_id'     => (int) $record['id'],
                'title'           => $record['title'],
                'form_provider'   => $record['form_provider'],
                'form_id'         => $record['form_id'],
                'form_name'       => $record['form_name'],
                'action_provider' => $record['action_provider'],
                'task'            => $record['task'],
                'data'            => $record['data'],
                'extra_data'      => $record['extra_data'],
                'status'          => (int) $record['status'],
                'created_at'      => $record['time'],
            );
        }

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="afi-integrations-' . gmdate( 'Ymd-His' ) . '.json"' );

        echo wp_json_encode( $payload );
        exit;
    }

    /**
     * Handles import form submissions.
     *
     * Fix #1:  tightened file-type validation.
     * Fix #3:  version compatibility warning.
     * Fix #5:  post-import row highlighting via ?duplicated= query arg.
     * Fix #6:  transient TTL raised to 5 minutes (in add_notice).
     * Fix #7:  dry-run mode support.
     * Fix #9:  file size limit enforced before reading file contents.
     * Fix #10: overwrite/update existing mode.
     * Fix #11: error paths redirect back to the Import/Export page.
     */
    public function import_from_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to import integrations.', 'advanced-form-integration' ) );
        }

        check_admin_referer( 'adfoin-import-integrations' );

        if ( empty( $_FILES['adfoin_import_file'] ) || ! empty( $_FILES['adfoin_import_file']['error'] ) ) {
            $this->add_notice(
                'error',
                __( 'No import file received. Please choose a valid JSON file.', 'advanced-form-integration' )
            );

            $this->redirect_to_import_export();
        }

        $file = $_FILES['adfoin_import_file'];

        // Fix #9: enforce upload size limit before touching the file.
        if ( ! empty( $file['size'] ) && (int) $file['size'] > self::MAX_IMPORT_FILE_SIZE ) {
            $this->add_notice(
                'error',
                sprintf(
                    /* translators: %s: human-readable max file size */
                    __( 'The uploaded file exceeds the %s maximum size limit.', 'advanced-form-integration' ),
                    size_format( self::MAX_IMPORT_FILE_SIZE )
                )
            );

            $this->redirect_to_import_export();
        }

        // Fix #1: validate file extension from the original filename first.
        $ext_from_name = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( 'json' !== $ext_from_name ) {
            $this->add_notice(
                'error',
                __( 'The selected file is not a JSON export from Advanced Form Integration.', 'advanced-form-integration' )
            );

            $this->redirect_to_import_export();
        }

        // Fix #1: additionally verify MIME type when detectable, allowing all
        // common JSON MIME variants but blocking obviously wrong types.
        if ( ! function_exists( 'wp_check_filetype_and_ext' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $file_type     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'json' => 'application/json' ) );
        $allowed_mimes = array( 'application/json', 'text/plain', 'text/javascript', 'application/octet-stream' );

        if ( ! empty( $file_type['type'] ) && ! in_array( $file_type['type'], $allowed_mimes, true ) ) {
            $this->add_notice(
                'error',
                __( 'The selected file is not a JSON export from Advanced Form Integration.', 'advanced-form-integration' )
            );

            $this->redirect_to_import_export();
        }

        $contents = file_get_contents( $file['tmp_name'] );

        if ( empty( $contents ) ) {
            $this->add_notice(
                'error',
                __( 'Unable to read the uploaded file.', 'advanced-form-integration' )
            );

            $this->redirect_to_import_export();
        }

        $decoded = json_decode( $contents, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->add_notice(
                'error',
                __( 'The uploaded file contains invalid JSON.', 'advanced-form-integration' )
            );

            $this->redirect_to_import_export();
        }

        if ( empty( $decoded['integrations'] ) || ! is_array( $decoded['integrations'] ) ) {
            $this->add_notice(
                'error',
                __( 'No integrations were found inside the uploaded file.', 'advanced-form-integration' )
            );

            $this->redirect_to_import_export();
        }

        // Fix #3: warn when the export was made with a different major version.
        $version_warnings = array();
        if ( ! empty( $decoded['afi_version'] ) && defined( 'ADVANCED_FORM_INTEGRATION_VERSION' ) ) {
            $src_parts  = explode( '.', (string) $decoded['afi_version'] );
            $curr_parts = explode( '.', ADVANCED_FORM_INTEGRATION_VERSION );
            $src_major  = isset( $src_parts[0] ) ? (int) $src_parts[0] : 0;
            $curr_major = isset( $curr_parts[0] ) ? (int) $curr_parts[0] : 0;

            if ( $src_major !== $curr_major ) {
                $version_warnings[] = sprintf(
                    /* translators: 1: version the export was created with, 2: current plugin version */
                    __( 'Version mismatch: the export was created with AFI %1$s; this site runs %2$s. Some fields may not map correctly.', 'advanced-form-integration' ),
                    esc_html( $decoded['afi_version'] ),
                    esc_html( ADVANCED_FORM_INTEGRATION_VERSION )
                );
            }
        }

        $activate  = ! empty( $_POST['activate_imported'] );
        $overwrite = ! empty( $_POST['overwrite_existing'] ); // Fix #10
        $dry_run   = ! empty( $_POST['dry_run'] );            // Fix #7

        $report = $this->import_integrations( $decoded['integrations'], $activate, $overwrite, $dry_run );

        // Fix #7: tailor the notice message for dry-run vs real import.
        if ( $dry_run ) {
            $message = sprintf(
                /* translators: 1: would-import count, 2: would-update count, 3: would-skip count */
                __( 'Dry run complete: %1$d integration(s) would be imported, %2$d would be updated, %3$d would be skipped. No changes were saved.', 'advanced-form-integration' ),
                $report['imported'],
                $report['updated'],
                $report['skipped']
            );
            $notice_type = 'warning';
        } else {
            $message = sprintf(
                /* translators: 1: imported count, 2: updated count, 3: skipped count */
                __( 'Imported %1$d integration(s), updated %2$d. %3$d record(s) were skipped.', 'advanced-form-integration' ),
                $report['imported'],
                $report['updated'],
                $report['skipped']
            );
            if ( ! $activate ) {
                $message .= ' ' . __( 'Imported integrations are currently inactive.', 'advanced-form-integration' );
            }
            $notice_type = ( $report['imported'] + $report['updated'] ) > 0 ? 'success' : 'warning';
        }

        $details = array_merge( $version_warnings, $report['messages'] );
        $this->add_notice( $notice_type, $message, $details );

        // Fix #5: after a real import redirect to the list and highlight new rows
        // using the existing ?duplicated= mechanism that the list-table JS already
        // handles (window.adfoinHighlightRows).
        if ( ! $dry_run && ! empty( $report['new_ids'] ) ) {
            $url = admin_url( 'admin.php?page=advanced-form-integration' );
            $url = add_query_arg( 'duplicated', implode( ',', array_map( 'absint', $report['new_ids'] ) ), $url );
            wp_safe_redirect( $url );
            exit;
        }

        // Dry-runs and import-with-no-new-rows stay on the Import/Export page.
        $this->redirect_to_import_export();
    }

    /**
     * Performs the import of decoded integrations.
     *
     * Fix #7:  dry_run param — simulate without writing.
     * Fix #10: overwrite param — update existing rows instead of skipping.
     *
     * @param array $entries   Integration entries from the decoded JSON payload.
     * @param bool  $activate  Whether to activate imported integrations.
     * @param bool  $overwrite Whether to update a matching existing integration.
     * @param bool  $dry_run   If true, count what would happen without saving.
     *
     * @return array {
     *     @type int   $imported Number of newly inserted integrations.
     *     @type int   $updated  Number of overwritten integrations.
     *     @type int   $skipped  Number of skipped integrations.
     *     @type array $messages Per-entry skip/error messages.
     *     @type array $new_ids  IDs of newly inserted rows (empty on dry run).
     * }
     */
    protected function import_integrations( $entries, $activate = false, $overwrite = false, $dry_run = false ) {
        $result = array(
            'imported' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'messages' => array(),
            'new_ids'  => array(),
        );

        foreach ( $entries as $entry ) {
            $response = $this->insert_integration( $entry, $activate, $overwrite, $dry_run );

            if ( is_wp_error( $response ) ) {
                $result['skipped']++;
                $result['messages'][] = $response->get_error_message();
            } elseif ( is_array( $response ) ) {
                if ( 'updated' === $response['action'] ) {
                    $result['updated']++;
                } else {
                    $result['imported']++;
                    if ( ! $dry_run && ! empty( $response['id'] ) ) {
                        $result['new_ids'][] = (int) $response['id'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Inserts or updates a single integration row.
     *
     * Fix #2:  duplicate detection — matches on title + providers + form_id + task.
     * Fix #7:  dry_run skips the actual DB write.
     * Fix #8:  validates the data field as JSON before inserting.
     * Fix #10: overwrite mode — runs UPDATE on the matched existing row.
     *
     * @param array $entry     Integration data from the JSON payload.
     * @param bool  $activate  Whether to activate the integration.
     * @param bool  $overwrite Whether to overwrite a duplicate instead of skipping.
     * @param bool  $dry_run   If true, skip the actual DB write.
     *
     * @return array|\WP_Error Array with 'action' (imported|updated) and 'id', or WP_Error on failure.
     */
    protected function insert_integration( $entry, $activate = false, $overwrite = false, $dry_run = false ) {
        global $wpdb;

        $form_provider   = isset( $entry['form_provider'] ) ? sanitize_text_field( $entry['form_provider'] ) : '';
        $action_provider = isset( $entry['action_provider'] ) ? sanitize_text_field( $entry['action_provider'] ) : '';

        $forms   = adfoin_get_form_providers();
        $actions = adfoin_get_action_porviders();

        $entry_label = isset( $entry['title'] ) && '' !== $entry['title']
            ? $entry['title']
            : ( $form_provider . ' → ' . $action_provider );

        if ( empty( $form_provider ) || ! isset( $forms[ $form_provider ] ) ) {
            return new WP_Error(
                'adfoin-invalid-form-provider',
                sprintf(
                    /* translators: %s: integration title or slug */
                    __( 'Skipped "%s": its form provider is not available on this site.', 'advanced-form-integration' ),
                    esc_html( $entry_label )
                )
            );
        }

        if ( empty( $action_provider ) || ! isset( $actions[ $action_provider ] ) ) {
            return new WP_Error(
                'adfoin-invalid-action-provider',
                sprintf(
                    /* translators: %s: integration title or slug */
                    __( 'Skipped "%s": its action provider is not available on this site.', 'advanced-form-integration' ),
                    esc_html( $entry_label )
                )
            );
        }

        $title     = isset( $entry['title'] ) ? sanitize_text_field( $entry['title'] ) : '';
        $form_id   = isset( $entry['form_id'] ) ? sanitize_text_field( $entry['form_id'] ) : '';
        $form_name = isset( $entry['form_name'] ) ? sanitize_text_field( $entry['form_name'] ) : '';
        $task      = isset( $entry['task'] ) ? sanitize_text_field( $entry['task'] ) : '';

        // Fix #8: validate that the data field is valid JSON before inserting.
        $data_field = isset( $entry['data'] ) ? $entry['data'] : '';
        if ( is_array( $data_field ) || is_object( $data_field ) ) {
            $data_field = wp_json_encode( $data_field );
        } else {
            $data_field = wp_unslash( (string) $data_field );
            if ( '' !== $data_field ) {
                json_decode( $data_field );
                if ( JSON_ERROR_NONE !== json_last_error() ) {
                    return new WP_Error(
                        'adfoin-invalid-data-json',
                        sprintf(
                            /* translators: %s: integration title */
                            __( 'Skipped "%s": the data field contains invalid JSON.', 'advanced-form-integration' ),
                            esc_html( $title )
                        )
                    );
                }
            }
        }

        $extra_data = isset( $entry['extra_data'] ) ? $entry['extra_data'] : '';
        if ( is_array( $extra_data ) || is_object( $extra_data ) ) {
            $extra_data = maybe_serialize( $extra_data );
        } else {
            $extra_data = wp_unslash( (string) $extra_data );
        }

        $status = $activate ? ( isset( $entry['status'] ) ? absint( $entry['status'] ) : 1 ) : 0;

        $table = $wpdb->prefix . 'adfoin_integration';

        // Fix #2: detect a duplicate by matching on the logical identity of the integration.
        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE title = %s
                   AND form_provider = %s
                   AND action_provider = %s
                   AND form_id = %s
                   AND task = %s
                 LIMIT 1",
                $title,
                $form_provider,
                $action_provider,
                $form_id,
                $task
            )
        );

        if ( $existing_id > 0 ) {
            if ( ! $overwrite ) {
                // Fix #2: clear message naming the existing ID so the user knows
                // how to act on it.
                return new WP_Error(
                    'adfoin-duplicate',
                    sprintf(
                        /* translators: 1: integration title, 2: existing row ID */
                        __( 'Skipped "%1$s": a matching integration already exists (ID %2$d). Enable "Overwrite existing" to update it.', 'advanced-form-integration' ),
                        esc_html( $title ),
                        $existing_id
                    )
                );
            }

            // Fix #10: overwrite mode — update the existing row in place.
            if ( ! $dry_run ) {
                $wpdb->update(
                    $table,
                    array(
                        'title'           => $title,
                        'form_provider'   => $form_provider,
                        'form_id'         => $form_id,
                        'form_name'       => $form_name,
                        'action_provider' => $action_provider,
                        'task'            => $task,
                        'data'            => $data_field,
                        'extra_data'      => $extra_data,
                        'status'          => $status,
                    ),
                    array( 'id' => $existing_id ),
                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ),
                    array( '%d' )
                );
            }

            return array( 'action' => 'updated', 'id' => $existing_id );
        }

        // Fix #7: dry-run stops here — no DB write.
        if ( $dry_run ) {
            return array( 'action' => 'imported', 'id' => 0 );
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'title'           => $title,
                'form_provider'   => $form_provider,
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'action_provider' => $action_provider,
                'task'            => $task,
                'data'            => $data_field,
                'extra_data'      => $extra_data,
                'status'          => $status,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        if ( false === $inserted ) {
            $error_message = $wpdb->last_error
                ? $wpdb->last_error
                : __( 'Database error while importing an integration.', 'advanced-form-integration' );

            return new WP_Error( 'adfoin-db-error', $error_message );
        }

        return array( 'action' => 'imported', 'id' => $wpdb->insert_id );
    }

    /**
     * Outputs saved admin notices.
     */
    public function render_notices() {
        if ( empty( $_GET['page'] ) || false === strpos( sanitize_text_field( $_GET['page'] ), 'advanced-form-integration' ) ) {
            return;
        }

        $notice = get_transient( $this->get_notice_key() );

        if ( empty( $notice ) ) {
            return;
        }

        delete_transient( $this->get_notice_key() );

        $type  = isset( $notice['type'] ) ? $notice['type'] : 'success';
        $class = 'notice-success';

        if ( 'error' === $type ) {
            $class = 'notice-error';
        } elseif ( 'warning' === $type ) {
            $class = 'notice-warning';
        }
        ?>
        <div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
            <p><?php echo esc_html( $notice['message'] ); ?></p>
            <?php if ( ! empty( $notice['details'] ) ) : ?>
                <ul>
                    <?php foreach ( $notice['details'] as $detail ) : ?>
                        <li><?php echo esc_html( $detail ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Stores a notice for later display.
     *
     * Fix #6: TTL raised from 1 minute to 5 minutes so notices survive on
     * slow servers or large imports.
     *
     * @param string $type    Notice type: success | warning | error.
     * @param string $message Main message text.
     * @param array  $details Optional per-entry detail lines.
     */
    protected function add_notice( $type, $message, $details = array() ) {
        set_transient(
            $this->get_notice_key(),
            array(
                'type'    => $type,
                'message' => $message,
                'details' => $details,
            ),
            5 * MINUTE_IN_SECONDS // Fix #6: was MINUTE_IN_SECONDS (60 s).
        );
    }

    /**
     * Returns the user-specific transient key.
     *
     * @return string
     */
    protected function get_notice_key() {
        return self::NOTICE_KEY . get_current_user_id();
    }

    /**
     * Redirects back to the Import / Export admin page.
     *
     * Fix #11: export-origin errors and dry-run results should stay on the
     * Import/Export page rather than sending the user to the integrations list.
     */
    protected function redirect_to_import_export() {
        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-import-export' ) );
        exit;
    }

    /**
     * Redirects back to the integrations list.
     */
    protected function redirect_to_integrations() {
        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration' ) );
        exit;
    }
}

Advanced_Form_Integration_Import_Export::get_instance();
