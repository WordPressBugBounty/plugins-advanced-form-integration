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
     */
    public function export_from_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to export integrations.', 'advanced-form-integration' ) );
        }

        check_admin_referer( 'adfoin-export-integrations' );

        $ids = array();

        if ( isset( $_POST['integration_id'] ) ) {
            $selected = sanitize_text_field( wp_unslash( $_POST['integration_id'] ) );
            if ( '' !== $selected && 'all' !== $selected ) {
                $ids = array( absint( $selected ) );
            }
        } elseif ( ! empty( $_POST['integration_ids'] ) ) {
            $ids = array_map( 'absint', (array) $_POST['integration_ids'] );
        }

        $this->export_integrations( $ids );
    }

    /**
     * Exports the requested integrations as a JSON download.
     *
     * @param array $ids Integration IDs.
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

            $this->redirect_to_integrations();
        }

        $payload = array(
            'afi_version' => defined( 'ADVANCED_FORM_INTEGRATION_VERSION' ) ? ADVANCED_FORM_INTEGRATION_VERSION : '',
            'site'        => home_url(),
            'generated_at'=> gmdate( 'c' ),
            'integrations'=> array(),
        );

        foreach ( $records as $record ) {
            $payload['integrations'][] = array(
                'original_id'    => (int) $record['id'],
                'title'          => $record['title'],
                'form_provider'  => $record['form_provider'],
                'form_id'        => $record['form_id'],
                'form_name'      => $record['form_name'],
                'action_provider'=> $record['action_provider'],
                'task'           => $record['task'],
                'data'           => $record['data'],
                'extra_data'     => $record['extra_data'],
                'status'         => (int) $record['status'],
                'created_at'     => $record['time'],
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

            $this->redirect_to_integrations();
        }

        $file = $_FILES['adfoin_import_file'];

        if ( ! function_exists( 'wp_check_filetype_and_ext' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'json' => 'application/json' ) );

        if ( ! empty( $file_type['ext'] ) && 'json' !== $file_type['ext'] ) {
            $this->add_notice(
                'error',
                __( 'The selected file is not a JSON export from Advanced Form Integration.', 'advanced-form-integration' )
            );

            $this->redirect_to_integrations();
        }

        $contents = file_get_contents( $file['tmp_name'] );

        if ( empty( $contents ) ) {
            $this->add_notice(
                'error',
                __( 'Unable to read the uploaded file.', 'advanced-form-integration' )
            );

            $this->redirect_to_integrations();
        }

        $decoded = json_decode( $contents, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->add_notice(
                'error',
                __( 'The uploaded file contains invalid JSON.', 'advanced-form-integration' )
            );

            $this->redirect_to_integrations();
        }

        if ( empty( $decoded['integrations'] ) || ! is_array( $decoded['integrations'] ) ) {
            $this->add_notice(
                'error',
                __( 'No integrations were found inside the uploaded file.', 'advanced-form-integration' )
            );

            $this->redirect_to_integrations();
        }

        $activate = ! empty( $_POST['activate_imported'] );
        $report   = $this->import_integrations( $decoded['integrations'], $activate );

        $message = sprintf(
            /* translators: 1: number of imported integrations, 2: number of skipped integrations */
            __( 'Imported %1$d integration(s). %2$d record(s) were skipped.', 'advanced-form-integration' ),
            $report['imported'],
            $report['skipped']
        );
        if ( ! $activate ) {
            $message .= ' ' . __( 'Imported integrations are currently inactive.', 'advanced-form-integration' );
        }

        $notice_type = $report['imported'] > 0 ? 'success' : 'warning';

        $this->add_notice( $notice_type, $message, $report['messages'] );

        $this->redirect_to_integrations();
    }

    /**
     * Performs the import of decoded integrations.
     *
     * @param array $entries  Integration entries.
     * @param bool  $activate Whether to keep imported integrations active.
     *
     * @return array
     */
    protected function import_integrations( $entries, $activate = false ) {
        $result = array(
            'imported' => 0,
            'skipped'  => 0,
            'messages' => array(),
        );

        foreach ( $entries as $entry ) {
            $response = $this->insert_integration( $entry, $activate );

            if ( is_wp_error( $response ) ) {
                $result['skipped']++;
                $result['messages'][] = $response->get_error_message();
            } else {
                $result['imported']++;
            }
        }

        return $result;
    }

    /**
     * Inserts a single integration row.
     *
     * @param array $entry    Integration data.
     * @param bool  $activate Whether to activate the imported integration.
     *
     * @return int|\WP_Error
     */
    protected function insert_integration( $entry, $activate = false ) {
        global $wpdb;

        $form_provider   = isset( $entry['form_provider'] ) ? sanitize_text_field( $entry['form_provider'] ) : '';
        $action_provider = isset( $entry['action_provider'] ) ? sanitize_text_field( $entry['action_provider'] ) : '';

        $forms   = adfoin_get_form_providers();
        $actions = adfoin_get_action_porviders();

        if ( empty( $form_provider ) || ! isset( $forms[ $form_provider ] ) ) {
            return new WP_Error(
                'adfoin-invalid-form-provider',
                __( 'Skipped an integration because its form provider is not available on this site.', 'advanced-form-integration' )
            );
        }

        if ( empty( $action_provider ) || ! isset( $actions[ $action_provider ] ) ) {
            return new WP_Error(
                'adfoin-invalid-action-provider',
                __( 'Skipped an integration because its action provider is not available on this site.', 'advanced-form-integration' )
            );
        }

        $title     = isset( $entry['title'] ) ? sanitize_text_field( $entry['title'] ) : '';
        $form_id   = isset( $entry['form_id'] ) ? sanitize_text_field( $entry['form_id'] ) : '';
        $form_name = isset( $entry['form_name'] ) ? sanitize_text_field( $entry['form_name'] ) : '';
        $task      = isset( $entry['task'] ) ? sanitize_text_field( $entry['task'] ) : '';

        $data_field = isset( $entry['data'] ) ? $entry['data'] : '';
        if ( is_array( $data_field ) || is_object( $data_field ) ) {
            $data_field = wp_json_encode( $data_field );
        } else {
            $data_field = wp_unslash( (string) $data_field );
        }

        $extra_data = isset( $entry['extra_data'] ) ? $entry['extra_data'] : '';
        if ( is_array( $extra_data ) || is_object( $extra_data ) ) {
            $extra_data = maybe_serialize( $extra_data );
        } else {
            $extra_data = wp_unslash( (string) $extra_data );
        }

        $status = $activate ? ( isset( $entry['status'] ) ? absint( $entry['status'] ) : 1 ) : 0;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'adfoin_integration',
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
            $error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Database error while importing an integration.', 'advanced-form-integration' );

            return new WP_Error( 'adfoin-db-error', $error_message );
        }

        return $wpdb->insert_id;
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

        $type   = isset( $notice['type'] ) ? $notice['type'] : 'success';
        $class  = 'notice-success';

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
     * @param string $type    Notice type.
     * @param string $message Main message.
     * @param array  $details Optional details.
     */
    protected function add_notice( $type, $message, $details = array() ) {
        set_transient(
            $this->get_notice_key(),
            array(
                'type'    => $type,
                'message' => $message,
                'details' => $details,
            ),
            MINUTE_IN_SECONDS
        );
    }

    /**
     * Returns the user specific transient key.
     *
     * @return string
     */
    protected function get_notice_key() {
        return self::NOTICE_KEY . get_current_user_id();
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
