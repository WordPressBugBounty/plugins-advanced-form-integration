<?php
if( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Connection List Table class.
class Advanced_Form_Integration_Log_Table extends WP_List_Table {

    public $log;

    /**
     * Construct function
     * Set default settings.
     */
    function __construct() {
        global $status, $page;
        parent::__construct( array(
            'ajax'     => FALSE,
            'singular' => 'log',
            'plural'   => 'logs',
        ) );

        $this->log = new Advanced_Form_Integration_Log();
    }

    /**
     * Renders the columns.
     */
    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
                $value = $item['id'];
                break;
            case 'response_code':
                $value = $item['response_code'];
                break;
            case 'integration_id':
                $value = $item['integration_id'];
                break;
            case 'request_data':
                $value = $item['request_data'];
                break;
            case 'response_data':
                $value = $item['response_data'];
                break;
            case 'action':
                $value = $item['action'];
                break;
            default:
                $value = '';
        }

        return apply_filters( 'adfoin_log_table_column_value', $value, $item, $column_name );
    }

    /**
     * Retrieve the table columns.
     */
    function get_columns() {
        $columns = array(
            'cb'             => '<input type="checkbox" />',
            'response_code'  => esc_html__( 'Status', 'advanced-form-integration' ),
            'integration_id' => esc_html__( 'Integration', 'advanced-form-integration' ),
            'request_data'   => esc_html__( 'Request', 'advanced-form-integration' ),
            'response_data'  => esc_html__( 'Response', 'advanced-form-integration' ),
            'time'           => esc_html__( 'Date', 'advanced-form-integration' ),
            'actions'        => esc_html__( 'Actions', 'advanced-form-integration' ),
        );

        return apply_filters( 'adfoin_log_table_columns', $columns );
    }

    /**
     * Render the checkbox column.
     */
    public function column_cb( $item ) {
        return '<input type="checkbox" name="log_id[]" value="' . absint( $item['id'] ) . '" />';
    }

    /**
     * Render the Integration column — ID shown, full title as tooltip.
     * If the integration has been deleted, show the ID as plain text
     * with a muted "(deleted)" note instead of a broken link.
     */
    public function column_integration_id( $item ) {
        $int_id      = absint( $item['integration_id'] );
        $integration = new Advanced_Form_Integration_Integration();
        $title       = $integration->get_title( $int_id );

        if ( $title ) {
            $edit_url = admin_url( 'admin.php?page=advanced-form-integration&action=edit&id=' . $int_id );
            return sprintf(
                '<a href="%s" title="%s">#%d</a>',
                esc_url( $edit_url ),
                esc_attr( $title ),
                $int_id
            );
        }

        return sprintf(
            '<span title="%s">#%d <span class="afi-log-deleted-badge">%s</span></span>',
            esc_attr__( 'This integration no longer exists', 'advanced-form-integration' ),
            $int_id,
            esc_html__( 'deleted', 'advanced-form-integration' )
        );
    }

    /**
     * Render the Status / response-code column (badge only — no date).
     */
    public function column_response_code( $item ) {
        $code     = ! empty( $item['response_code'] ) ? $item['response_code'] : __( 'Unknown', 'advanced-form-integration' );
        $starting = substr( (string) $code, 0, 1 );
        $class    = 'code-200';

        if ( '4' === $starting ) {
            $class = 'code-400';
        } elseif ( '5' === $starting ) {
            $class = 'code-500';
        } elseif ( ! is_numeric( $code ) ) {
            $class = 'code-500';
        }

        $label = esc_html( $code );
        if ( isset( $item['response_message'] ) && ! empty( $item['response_message'] ) ) {
            $label .= ' <span class="afi-log-response-msg">' . esc_html( $item['response_message'] ) . '</span>';
        }

        return sprintf( '<mark class="afi-log-response-code %s"><span>%s</span></mark>', $class, $label );
    }

    /**
     * Render the Date column.
     */
    public function column_time( $item ) {
        if ( empty( $item['time'] ) ) {
            return '—';
        }
        $ts      = strtotime( $item['time'] );
        $display = date_i18n( 'Y/m/d', $ts ) . '<br><span class="afi-log-date">' . date_i18n( 'g:i a', $ts ) . '</span>';
        return sprintf( '<span title="%s">%s</span>', esc_attr( $item['time'] ), $display );
    }

    /**
     * Prepare sanitized preview for log values.
     *
     * @param mixed $raw_value Stored value.
     * @return array{title:string, display:string}
     */
    protected function prepare_log_preview( $raw_value ) {
        if ( is_string( $raw_value ) ) {
            $prepared = wp_unslash( $raw_value );
        } else {
            $prepared = wp_json_encode( $raw_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        }

        if ( ! is_string( $prepared ) ) {
            $prepared = '';
        }

        $stripped = trim( wp_strip_all_tags( $prepared ) );

        if ( '' === $stripped ) {
            return array(
                'title'   => '',
                'display' => esc_html__( '(empty)', 'advanced-form-integration' ),
            );
        }

        if ( function_exists( 'mb_substr' ) ) {
            $snippet = mb_substr( $stripped, 0, 60 );
            if ( mb_strlen( $stripped ) > 60 ) {
                $snippet .= '…';
            }
        } else {
            $snippet = substr( $stripped, 0, 60 );
            if ( strlen( $stripped ) > 60 ) {
                $snippet .= '…';
            }
        }

        return array(
            'title'   => esc_attr( $stripped ),
            'display' => esc_html( $snippet ),
        );
    }

    /**
     * Render the request data column.
     */
    public function column_request_data( $item ) {
        $preview = $this->prepare_log_preview( $item['request_data'] );
        printf( '<span title="%s" class="afi-log-data-preview">%s</span>', $preview['title'], $preview['display'] );
    }

    /**
     * Render the response data column.
     */
    public function column_response_data( $item ) {
        $preview = $this->prepare_log_preview( $item['response_data'] );
        printf( '<span title="%s" class="afi-log-data-preview">%s</span>', $preview['title'], $preview['display'] );
    }

    /**
     * Render the Actions column — View, Copy, Delete.
     */
    public function column_actions( $item ) {
        $log_id    = absint( $item['id'] );
        $admin_url = admin_url( 'admin.php?page=advanced-form-integration-log' );

        $full_log = json_encode( array(
            'integration_id'   => $item['integration_id'],
            'response_code'    => $item['response_code'],
            'response_message' => $item['response_message'],
            'request_data'     => json_decode( $item['request_data'], true ),
            'response_data'    => json_decode( $item['response_data'], true ),
            'time'             => $item['time'],
        ) );

        $delete_url = wp_nonce_url(
            add_query_arg( array( 'action' => 'delete', 'log_id' => $log_id ), $admin_url ),
            'adfoin_delete_log_nonce'
        );

        $view_url = add_query_arg( array( 'action' => 'view', 'id' => $log_id ), $admin_url );

        // Lucide-style stroke SVG icons — modern, consistent 16 × 16 viewBox.
        $icon_view = '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';

        $icon_copy = '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>';

        $icon_delete = '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>';

        echo '<div class="afi-log-actions-wrap">';

        printf(
            '<a href="%s" class="afi-icon-btn" title="%s">%s</a>',
            esc_url( $view_url ),
            esc_attr__( 'View Full Log', 'advanced-form-integration' ),
            $icon_view
        );

        printf(
            '<button type="button" class="afi-icon-btn afi-icon-copy-full-log" title="%s" data-full-log=\'%s\'>%s</button>',
            esc_attr__( 'Copy Full Log', 'advanced-form-integration' ),
            esc_attr( $full_log ),
            $icon_copy
        );

        printf(
            '<a href="%s" class="afi-icon-btn afi-icon-btn-delete" title="%s" onclick="return confirm(\'%s\')">%s</a>',
            esc_url( $delete_url ),
            esc_attr__( 'Delete Log', 'advanced-form-integration' ),
            esc_js( __( 'Delete this log entry? This cannot be undone.', 'advanced-form-integration' ) ),
            $icon_delete
        );

        echo '</div>';
    }

    /**
     * Define bulk actions.
     */
    public function get_bulk_actions() {
        return array(
            'delete' => esc_html__( 'Delete', 'advanced-form-integration' ),
        );
    }

    /**
     * Process bulk actions and single-row delete.
     */
    public function process_bulk_actions() {
        $action = $this->current_action();

        if ( 'delete' !== $action ) {
            return;
        }

        // Single-row delete (GET link with nonce).
        if ( isset( $_GET['log_id'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'adfoin_delete_log_nonce' ) ) {
                $ids = array_map( 'absint', (array) $_GET['log_id'] );
                foreach ( $ids as $id ) {
                    $this->log->delete( $id );
                }
                advanced_form_integration_redirect( admin_url( 'admin.php?page=advanced-form-integration-log' ) );
                exit;
            }
        }

        // Bulk delete (POST form with nonce).
        $ids = isset( $_REQUEST['log_id'] ) ? (array) $_REQUEST['log_id'] : array();
        $ids = array_map( 'absint', $ids );

        if ( empty( $ids ) ) {
            return;
        }

        if (
            wp_verify_nonce( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '', 'bulk-logs' ) ||
            wp_verify_nonce( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '', 'adfoin_delete_log_nonce' )
        ) {
            foreach ( $ids as $id ) {
                $this->log->delete( $id );
            }
            advanced_form_integration_redirect( admin_url( 'admin.php?page=advanced-form-integration-log' ) );
            exit;
        }
    }

    /**
     * Filter controls above the table — dropdowns for integration and status.
     */
    public function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        global $wpdb;

        $selected_integration = isset( $_REQUEST['integration_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['integration_id'] ) ) : '';
        $selected_code_family = isset( $_REQUEST['code_family'] )    ? sanitize_text_field( wp_unslash( $_REQUEST['code_family'] ) )    : '';

        // Fetch all distinct integrations that appear in the log.
        $log_table = $wpdb->prefix . 'adfoin_log';
        $int_table = $wpdb->prefix . 'adfoin_integration';
        $integrations = $wpdb->get_results(
            "SELECT DISTINCT l.integration_id, i.title
             FROM {$log_table} l
             LEFT JOIN {$int_table} i ON i.id = l.integration_id
             ORDER BY l.integration_id ASC",
            ARRAY_A
        );
        ?>
        <div class="alignleft actions adfoin-log-filters">
            <?php if ( ! empty( $integrations ) ) : ?>
                <label class="screen-reader-text" for="adfoin_filter_integration">
                    <?php esc_html_e( 'Filter by Integration', 'advanced-form-integration' ); ?>
                </label>
                <select name="integration_id" id="adfoin_filter_integration">
                    <option value=""><?php esc_html_e( 'All Integrations', 'advanced-form-integration' ); ?></option>
                    <?php foreach ( $integrations as $row ) :
                        $int_id    = absint( $row['integration_id'] );
                        $int_title = ! empty( $row['title'] ) ? $row['title'] : sprintf( __( 'Integration #%d', 'advanced-form-integration' ), $int_id );
                    ?>
                        <option value="<?php echo esc_attr( $int_id ); ?>" <?php selected( $selected_integration, $int_id ); ?>>
                            <?php echo esc_html( $int_title ); ?> (#<?php echo esc_html( $int_id ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input type="hidden" name="integration_id" value="">
            <?php endif; ?>

            <label class="screen-reader-text" for="adfoin_filter_code_family">
                <?php esc_html_e( 'Filter by Status', 'advanced-form-integration' ); ?>
            </label>
            <select name="code_family" id="adfoin_filter_code_family">
                <option value=""                        <?php selected( $selected_code_family, '' ); ?>><?php esc_html_e( 'All Statuses', 'advanced-form-integration' ); ?></option>
                <option value="success"                 <?php selected( $selected_code_family, 'success' ); ?>><?php esc_html_e( '2xx Success', 'advanced-form-integration' ); ?></option>
                <option value="client_error"            <?php selected( $selected_code_family, 'client_error' ); ?>><?php esc_html_e( '4xx Client Error', 'advanced-form-integration' ); ?></option>
                <option value="server_error"            <?php selected( $selected_code_family, 'server_error' ); ?>><?php esc_html_e( '5xx Server Error', 'advanced-form-integration' ); ?></option>
                <option value="other_error"             <?php selected( $selected_code_family, 'other_error' ); ?>><?php esc_html_e( 'Other / Unknown', 'advanced-form-integration' ); ?></option>
            </select>

            <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e( 'Filter', 'advanced-form-integration' ); ?>" />
        </div>
        <?php
    }

    /**
     * Status tabs: All / Success / Error with counts.
     */
    public function get_views() {
        global $wpdb;

        $log_table   = $wpdb->prefix . 'adfoin_log';
        $total       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table}" );
        $success     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE response_code LIKE '2%'" );
        $client_err  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE response_code LIKE '4%'" );
        $server_err  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE response_code LIKE '5%'" );
        $other       = $total - $success - $client_err - $server_err;

        $current      = isset( $_REQUEST['code_family'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['code_family'] ) ) : '';
        $base_url     = remove_query_arg( array( 'code_family', 'paged' ) );

        $views = array();

        $views['all'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url( $base_url ),
            '' === $current ? ' class="current"' : '',
            esc_html__( 'All', 'advanced-form-integration' ),
            $total
        );

        if ( $success > 0 ) {
            $views['success'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'code_family', 'success', $base_url ) ),
                'success' === $current ? ' class="current"' : '',
                esc_html__( 'Success', 'advanced-form-integration' ),
                $success
            );
        }

        if ( $client_err > 0 ) {
            $views['client_error'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'code_family', 'client_error', $base_url ) ),
                'client_error' === $current ? ' class="current"' : '',
                esc_html__( 'Client Error', 'advanced-form-integration' ),
                $client_err
            );
        }

        if ( $server_err > 0 ) {
            $views['server_error'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'code_family', 'server_error', $base_url ) ),
                'server_error' === $current ? ' class="current"' : '',
                esc_html__( 'Server Error', 'advanced-form-integration' ),
                $server_err
            );
        }

        if ( $other > 0 ) {
            $views['other_error'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'code_family', 'other_error', $base_url ) ),
                'other_error' === $current ? ' class="current"' : '',
                esc_html__( 'Other', 'advanced-form-integration' ),
                $other
            );
        }

        return $views;
    }

    /**
     * Sortable columns: integration, time (default desc), response_code.
     */
    function get_sortable_columns() {
        return array(
            'integration_id' => array( 'integration_id', false ),
            'time'           => array( 'time', true ),   // true = already sorted desc by default
            'response_code'  => array( 'response_code', false ),
        );
    }

    public function fetch_table_data( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'number'         => 20,
            'offset'         => 0,
            'orderby'        => 'id',
            'order'          => 'DESC',
            'count'          => false,
            'integration_id' => '',
            'response_code'  => '',
            'code_family'    => '',
        );

        $args  = wp_parse_args( $args, $defaults );
        $log   = new Advanced_Form_Integration_Log();
        $sql   = "SELECT * FROM {$log->table}";
        $where = array();

        if ( isset( $args['s'] ) && ! empty( $args['s'] ) ) {
            $arg_s   = $args['s'];
            $where[] = $wpdb->prepare(
                "(`response_message` LIKE %s OR `request_data` LIKE %s OR `response_data` LIKE %s)",
                '%' . $arg_s . '%',
                '%' . $arg_s . '%',
                '%' . $arg_s . '%'
            );
        }

        if ( ! empty( $args['integration_id'] ) ) {
            $where[] = $wpdb->prepare( "`integration_id` = %s", sanitize_text_field( $args['integration_id'] ) );
        }

        // Legacy direct response_code filter (used internally).
        if ( ! empty( $args['response_code'] ) ) {
            $where[] = $wpdb->prepare( "`response_code` = %s", sanitize_text_field( $args['response_code'] ) );
        }

        // New code_family filter.
        if ( ! empty( $args['code_family'] ) ) {
            switch ( $args['code_family'] ) {
                case 'success':
                    $where[] = "`response_code` LIKE '2%'";
                    break;
                case 'client_error':
                    $where[] = "`response_code` LIKE '4%'";
                    break;
                case 'server_error':
                    $where[] = "`response_code` LIKE '5%'";
                    break;
                case 'other_error':
                    $where[] = "(`response_code` NOT LIKE '2%' AND `response_code` NOT LIKE '4%' AND `response_code` NOT LIKE '5%')";
                    break;
            }
        }

        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        if ( ! empty( $args['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $args['orderby'] );
            $sql .= ! empty( $args['order'] ) ? ' ' . esc_sql( $args['order'] ) : ' ASC';
        }

        if ( $args['count'] ) {
            $count_sql = "SELECT COUNT(*) FROM {$log->table}";
            if ( ! empty( $where ) ) {
                $count_sql .= ' WHERE ' . implode( ' AND ', $where );
            }
            $result = $log->get_var( $count_sql );
        } else {
            $sql .= " LIMIT {$args['number']}";
            $sql .= ' OFFSET ' . $args['offset'];
            $result = $log->get_results( $sql, 'ARRAY_A' );
        }

        return $result;
    }

    /**
     * Handles filtered count.
     */
    public function count() {
        $args = array( 'count' => true );

        if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
        }

        if ( isset( $_REQUEST['integration_id'] ) && ! empty( $_REQUEST['integration_id'] ) ) {
            $args['integration_id'] = sanitize_text_field( wp_unslash( $_REQUEST['integration_id'] ) );
        }

        if ( isset( $_REQUEST['code_family'] ) && ! empty( $_REQUEST['code_family'] ) ) {
            $args['code_family'] = sanitize_text_field( wp_unslash( $_REQUEST['code_family'] ) );
        } elseif ( isset( $_REQUEST['response_code'] ) && is_numeric( $_REQUEST['response_code'] ) ) {
            $args['response_code'] = absint( $_REQUEST['response_code'] );
        }

        return $this->fetch_table_data( $args );
    }

    /**
     * Prepare items for display.
     */
    public function prepare_items() {
        $this->process_bulk_actions();

        $count                 = $this->count();
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $current_page = $this->get_pagenum();
        $per_page     = $this->get_items_per_page( 'adfoin_log_per_page', 20 );
        $offset       = ( $current_page - 1 ) * $per_page;

        $args = array(
            'offset' => $offset,
            'number' => $per_page,
        );

        if ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
        }

        if ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) {
            $args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
        }

        if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
        }

        if ( isset( $_REQUEST['integration_id'] ) && ! empty( $_REQUEST['integration_id'] ) ) {
            $args['integration_id'] = sanitize_text_field( wp_unslash( $_REQUEST['integration_id'] ) );
        }

        if ( isset( $_REQUEST['code_family'] ) && ! empty( $_REQUEST['code_family'] ) ) {
            $args['code_family'] = sanitize_text_field( wp_unslash( $_REQUEST['code_family'] ) );
        } elseif ( isset( $_REQUEST['response_code'] ) && is_numeric( $_REQUEST['response_code'] ) ) {
            $args['response_code'] = absint( $_REQUEST['response_code'] );
        }

        $this->items = $this->fetch_table_data( $args );

        $this->set_pagination_args( array(
            'total_items' => $count,
            'per_page'    => $per_page,
            'total_pages' => ceil( $count / $per_page ),
        ) );
    }

    /**
     * admin_header — column widths now live in asset.css; kept as a
     * no-op so any external calls don't fatal.
     */
    public function admin_header() {}
}
