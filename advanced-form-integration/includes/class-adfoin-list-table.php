<?php
if( !class_exists( 'WP_List_Table' ) ) {
    // require_once ABSPATH . 'wp-admin/includes/template.php';
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Connection List Table class.
class Advanced_Form_Integration_List_Table extends WP_List_Table {

    /**
     * Per-page cache for the Health column. Populated in
     * prepare_items() with a single bulk query so column_health()
     * can render each row without hitting the database again.
     *
     * Shape: see adfoin_get_integration_health_bulk().
     *
     * @var array<int, array>
     */
    protected $health_cache = array();

    /**
     * Construct function
     * Set default settings.
     */
    function __construct() {
        global $status, $page;
        //Set parent defaults
        parent::__construct( array(
            'ajax'     => FALSE,
            'singular' => 'integration',
            'plural'   => 'integrations',
        ) );
    }

    /**
     * Renders the columns.
     *
     * @since 1.0.0
     */
    function column_default( $item, $column_name ) {
        // Direct lookup for the columns registered in get_columns()
        // that don't have their own column_*() method. The previous
        // switch listed columns (id, action) that aren't even
        // registered, plus columns (title, form_provider, task) that
        // are handled by dedicated methods and never reach this
        // fallback. The slim version below is equivalent for every
        // real call site and keeps the apply_filters() extension
        // point intact.
        $value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';

        return apply_filters( 'adfoin_integration_table_column_value', $value, $item, $column_name );
    }

    /**
     * Retrieve the table columns.
     *
     * @since 1.0.0
     * @return array $columns Array of all the list table columns.
     */
    function get_columns() {
        $columns = array(
            'cb'              => '<input type="checkbox" />',
            'title'           => esc_html__( 'Title', 'advanced-form-integration' ),
            'form_provider'   => esc_html__( 'Form Provider', 'advanced-form-integration' ),
            'form_name'       => esc_html__( 'Form', 'advanced-form-integration' ),
            'action_provider' => esc_html__( 'Action', 'advanced-form-integration' ),
            'task'            => esc_html__( 'Task', 'advanced-form-integration' ),
            'health'          => esc_html__( 'Health (7d)', 'advanced-form-integration' ),
            'status'          => esc_html__( 'Active', 'advanced-form-integration' ),
        );

        return apply_filters( 'adfoin_integration_table_columns', $columns );
    }

    /**
     * Render the checkbox column.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function column_cb( $item ) {
        return '<input type="checkbox" name="id[]" value="' . absint( $item['id'] ) . '" />';
    }

    public function column_form_provider( $item ) {
        $form_providers = adfoin_get_form_providers();

        if( array_key_exists( $item['form_provider'], $form_providers ) ) {
            return $form_providers[$item['form_provider']];
        } else {
            return __( 'Deactivated?', 'advanced-form-integration');
        }

    }

    /**
     * Render the form name column with action links.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function column_title( $item ) {

        // Title fallback: prefer the user-typed title, then the
        // friendly provider label (resolved through the same map
        // column_form_provider uses), and only as a last resort the
        // raw slug. Previously this leaked slugs like "wpforms" into
        // the UI when an integration had no title.
        if ( ! empty( $item['title'] ) ) {
            $name = $item['title'];
        } else {
            $form_providers = adfoin_get_form_providers();
            $name = isset( $form_providers[ $item['form_provider'] ] )
                ? $form_providers[ $item['form_provider'] ]
                : $item['form_provider'];
        }

        // Make the title itself a link to the edit screen — standard
        // WP list-table pattern (Posts, Users, etc.) and the only
        // path that works for touch users since row actions are
        // hover-revealed. esc_html() (no underscore) because the
        // title is user content, not a translatable string.
        $edit_url = add_query_arg(
            array(
                'action' => 'edit',
                'id'     => (int) $item['id'],
            ),
            admin_url( 'admin.php?page=advanced-form-integration' )
        );
        $name = sprintf(
            '<strong><a href="%1$s" class="row-title">%2$s</a></strong>',
            esc_url( $edit_url ),
            esc_html( $name )
        );

        // Build all of the row action links.
        $row_actions = array();

        // Edit.
        $row_actions['edit'] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            add_query_arg(
                array(
                    'action' => 'edit',
                    'id'     => $item['id'],
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            ),
            esc_html__( 'Edit This Integration', 'advanced-form-integration' ),
            esc_html__( 'Edit', 'advanced-form-integration' )
        );

        // Duplicate.
        $row_actions['duplicate'] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'duplicate',
                        'id'     => $item['id'],
                    ),
                    admin_url( 'admin.php?page=advanced-form-integration' )
                ),
                'adfoin_duplicate_integration_nonce'
            ),
            esc_html__( 'Duplicate This Integration', 'advanced-form-integration' ),
            esc_html__( 'Duplicate', 'advanced-form-integration' )
        );

        // Delete.
        $row_actions['delete'] = sprintf(
            '<a href="%s" class="adfoin-integration-delete" title="%s">%s</a>',
            wp_nonce_url(
                add_query_arg(
                    array(
                        'action'  => 'delete',
                        'id' => $item['id'],
                    ),
                    admin_url( 'admin.php?page=advanced-form-integration' )
                ),
                'adfoin_delete_integration_nonce'
            ),
            esc_html__( 'Delete this integration', 'advanced-form-integration' ),
            esc_html__( 'Delete', 'advanced-form-integration' )
        );

        // Build the row action links and return the value.
        return $name . $this->row_actions( apply_filters( 'adfoin_integration_row_actions', $row_actions, $item ) );
    }

    /*
     * Renders action provider column
     */
    public function column_action_provider( $item ) {
        $actions = adfoin_get_action_porviders();
        $action  = isset( $actions[$item['action_provider']] ) ? $actions[$item['action_provider']] : '';

        return $action;
    }

    /*
 * Renders task column
 */
    public function column_task( $item ) {
        $tasks = adfoin_get_action_tasks( $item["action_provider"] );
        $task  = isset( $tasks[$item['task']] ) ? $tasks[$item['task']] : '';

        return $task;
    }

    /**
     * Define bulk actions available for our table listing.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_bulk_actions() {

        $actions = array(
            'activate'   => esc_html__( 'Activate', 'advanced-form-integration' ),
            'deactivate' => esc_html__( 'Deactivate', 'advanced-form-integration' ),
            'duplicate'  => esc_html__( 'Duplicate', 'advanced-form-integration' ),
            'export'     => esc_html__( 'Export to JSON', 'advanced-form-integration' ),
            'delete'     => esc_html__( 'Delete', 'advanced-form-integration' ),
        );

        return $actions;
    }

    /**
     * Filter pills above the table — All / Active / Inactive /
     * Failing (last 7d). Each pill links back to the same screen with
     * a `view=` query arg so search + provider filters are preserved
     * via the rest of the URL.
     *
     * Counts are computed independently of the active view so the
     * numbers don't collapse to zero after clicking a tab. Search and
     * provider filters DO scope the counts so the user can see, for
     * example, "active 4 / inactive 2" inside their current search.
     *
     * @since 1.128.3
     * @return array<string,string> Map of view-key => anchor HTML.
     */
    public function get_views() {
        global $wpdb;

        $table   = $wpdb->prefix . 'adfoin_integration';
        $base    = admin_url( 'admin.php?page=advanced-form-integration' );
        $current = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'all';

        // Build a WHERE clause that captures only the non-view filters
        // (search + provider dropdowns) by temporarily clearing $_GET['view'].
        $saved_view = isset( $_GET['view'] ) ? $_GET['view'] : null;
        unset( $_GET['view'] );
        $base_filter = $this->build_filters_clause();
        if ( null !== $saved_view ) {
            $_GET['view'] = $saved_view;
        }

        $with_extra = function ( $extra_where ) use ( $base_filter, $table, $wpdb ) {
            $sql_where    = $base_filter['sql'];
            $sql_params   = $base_filter['params'];
            if ( $extra_where !== '' ) {
                $sql_where = $sql_where === ''
                    ? ' WHERE ' . $extra_where
                    : $sql_where . ' AND ' . $extra_where;
            }
            $sql = "SELECT COUNT(*) FROM {$table}{$sql_where}";
            $sql = empty( $sql_params ) ? $sql : $wpdb->prepare( $sql, $sql_params );
            return (int) $wpdb->get_var( $sql );
        };

        $counts = array(
            'all'      => $with_extra( '' ),
            'active'   => $with_extra( 'status = 1' ),
            'inactive' => $with_extra( 'status = 0' ),
        );

        // Failing count needs the dynamic id list — do it separately.
        $failing_ids = adfoin_get_failing_integration_ids( 7 );
        if ( empty( $failing_ids ) ) {
            $counts['failing'] = 0;
        } else {
            $placeholders = implode( ', ', array_fill( 0, count( $failing_ids ), '%d' ) );
            $sql_where    = $base_filter['sql'];
            $sql_params   = $base_filter['params'];
            $extra        = "id IN ({$placeholders})";
            $sql_where    = $sql_where === ''
                ? ' WHERE ' . $extra
                : $sql_where . ' AND ' . $extra;
            $sql_params   = array_merge( $sql_params, $failing_ids );
            $sql          = "SELECT COUNT(*) FROM {$table}{$sql_where}";
            $counts['failing'] = (int) $wpdb->get_var( $wpdb->prepare( $sql, $sql_params ) );
        }

        $labels = array(
            'all'      => __( 'All', 'advanced-form-integration' ),
            'active'   => __( 'Active', 'advanced-form-integration' ),
            'inactive' => __( 'Inactive', 'advanced-form-integration' ),
            'failing'  => __( 'Failing (7d)', 'advanced-form-integration' ),
        );

        $views = array();
        foreach ( $labels as $key => $label ) {
            $url   = ( 'all' === $key ) ? remove_query_arg( 'view', $base ) : add_query_arg( 'view', $key, $base );
            // Preserve search + provider filters when switching tabs.
            foreach ( array( 's', 'form_provider_filter', 'action_provider_filter' ) as $param ) {
                if ( ! empty( $_GET[ $param ] ) ) {
                    $url = add_query_arg( $param, sanitize_text_field( wp_unslash( $_GET[ $param ] ) ), $url );
                }
            }
            $class = ( $current === $key ) ? 'current' : '';
            $views[ $key ] = sprintf(
                '<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$s)</span></a>',
                esc_url( $url ),
                esc_attr( $class ),
                esc_html( $label ),
                number_format_i18n( $counts[ $key ] )
            );
        }

        return $views;
    }

    /**
     * Provider filter dropdowns + Filter button rendered in the top
     * tablenav row. Only shown on the top so they don't double up
     * above + below the table.
     *
     * @since 1.128.3
     * @param string $which 'top' | 'bottom'
     */
    public function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        $form_providers = adfoin_get_form_providers();
        if ( ! is_array( $form_providers ) ) {
            $form_providers = array();
        }
        asort( $form_providers );

        $action_providers = function_exists( 'adfoin_get_action_porviders' )
            ? adfoin_get_action_porviders()
            : array();
        if ( ! is_array( $action_providers ) ) {
            $action_providers = array();
        }
        asort( $action_providers );

        $current_form   = isset( $_GET['form_provider_filter'] )   ? sanitize_text_field( wp_unslash( $_GET['form_provider_filter'] ) )   : '';
        $current_action = isset( $_GET['action_provider_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['action_provider_filter'] ) ) : '';

        echo '<div class="alignleft actions adfoin-provider-filters">';

        echo '<label for="adfoin-form-provider-filter" class="screen-reader-text">' . esc_html__( 'Filter by form provider', 'advanced-form-integration' ) . '</label>';
        echo '<select name="form_provider_filter" id="adfoin-form-provider-filter">';
        echo '<option value="">' . esc_html__( 'All form providers', 'advanced-form-integration' ) . '</option>';
        foreach ( $form_providers as $key => $label ) {
            printf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr( $key ),
                selected( $current_form, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';

        echo '<label for="adfoin-action-provider-filter" class="screen-reader-text">' . esc_html__( 'Filter by action provider', 'advanced-form-integration' ) . '</label>';
        echo '<select name="action_provider_filter" id="adfoin-action-provider-filter">';
        echo '<option value="">' . esc_html__( 'All action providers', 'advanced-form-integration' ) . '</option>';
        foreach ( $action_providers as $key => $label ) {
            printf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr( $key ),
                selected( $current_action, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';

        submit_button( __( 'Filter', 'advanced-form-integration' ), '', 'adfoin_filter_action', false );

        echo '</div>';
    }

    /**
     * Process the bulk actions.
     *
     * @since 1.0.0
     */
    public function process_bulk_actions() {

        // Defense-in-depth: the menu hook already checks this cap at
        // registration time, so callers reach this method only when
        // authorized — but a future refactor that exposes the table
        // through a different surface (REST, shortcode, …) would
        // bypass that check. Fail closed here regardless.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();

        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        $ids    = array_map( 'absint', $ids );
        $ids    = array_values( array_filter( $ids ) ); // drop zeroes / empty rows
        $action = $this->current_action();

        if ( empty( $ids ) || empty( $action ) ) {
            return;
        }

        // Bulk-actions form posts a `bulk-integrations` nonce; the
        // single-row Delete link uses its own
        // `adfoin_delete_integration_nonce`. Either is accepted for
        // delete; the rest require the bulk nonce because they only
        // come from the bulk-action select.
        $bulk_nonce_ok = isset( $_REQUEST['_wpnonce'] )
            && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-integrations' );

        switch ( $action ) {
            case 'delete':
                $delete_nonce_ok = isset( $_REQUEST['_wpnonce'] )
                    && wp_verify_nonce( $_REQUEST['_wpnonce'], 'adfoin_delete_integration_nonce' );
                if ( ! $bulk_nonce_ok && ! $delete_nonce_ok ) {
                    return;
                }
                foreach ( $ids as $id ) {
                    $this->delete( $id );
                }
                advanced_form_integration_redirect(
                    add_query_arg( 'bulk_done', 'deleted', admin_url( 'admin.php?page=advanced-form-integration' ) )
                );
                exit;

            case 'activate':
            case 'deactivate':
                if ( ! $bulk_nonce_ok ) {
                    return;
                }
                global $wpdb;
                $table = $wpdb->prefix . 'adfoin_integration';
                $value = ( 'activate' === $action ) ? 1 : 0;
                foreach ( $ids as $id ) {
                    $wpdb->update( $table, array( 'status' => $value ), array( 'id' => $id ) );
                }
                advanced_form_integration_redirect(
                    add_query_arg( 'bulk_done', $action, admin_url( 'admin.php?page=advanced-form-integration' ) )
                );
                exit;

            case 'duplicate':
                if ( ! $bulk_nonce_ok ) {
                    return;
                }
                $new_ids = array();
                foreach ( $ids as $id ) {
                    $new_id = $this->duplicate_row( $id );
                    if ( $new_id ) {
                        $new_ids[] = $new_id;
                    }
                }
                $url = admin_url( 'admin.php?page=advanced-form-integration' );
                $url = add_query_arg( 'bulk_done', 'duplicated', $url );
                if ( ! empty( $new_ids ) ) {
                    // Comma-joined list — read by JS to highlight all
                    // newly duplicated rows briefly.
                    $url = add_query_arg( 'duplicated', implode( ',', $new_ids ), $url );
                }
                advanced_form_integration_redirect( $url );
                exit;

            case 'export':
                if ( ! $bulk_nonce_ok ) {
                    return;
                }
                if ( class_exists( 'Advanced_Form_Integration_Import_Export' ) ) {
                    Advanced_Form_Integration_Import_Export::get_instance()->export_integrations( $ids );
                    exit; // export_integrations() emits headers + exits, but be explicit.
                }
                return;
        }
    }

    /**
     * Duplicate a single integration row in-place, returning the new
     * ID. Mirrors the row-action duplicate handler in
     * Advanced_Form_Integration_Admin_Menu so bulk + single-row use
     * the same shape (status=0, "Copy of " prefix), but without the
     * single-row nonce check — the caller is responsible for nonce
     * verification at the bulk-action layer.
     *
     * @param int $id Source integration ID.
     * @return int|false  New ID on success, false on failure.
     */
    protected function duplicate_row( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'adfoin_integration';
        $row   = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ),
            ARRAY_A
        );
        if ( ! $row ) {
            return false;
        }
        $inserted = $wpdb->insert(
            $table,
            array(
                'title'           => __( 'Copy of ', 'advanced-form-integration' ) . $row['title'],
                'form_provider'   => $row['form_provider'],
                'form_id'         => $row['form_id'],
                'form_name'       => $row['form_name'],
                'action_provider' => $row['action_provider'],
                'task'            => $row['task'],
                'data'            => $row['data'],
                'status'          => 0,
            )
        );
        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * Message to be displayed when there are no rows. Distinguishes
     * between "no integrations exist at all" (cold start, gets a
     * full empty-state with illustration + CTA) and "no integrations
     * match the current search/filter" (compact one-liner with a
     * reset link).
     *
     * @since 1.0.0
     */
    public function no_items() {
        $is_filtered =
            ! empty( $_REQUEST['s'] ) ||
            ! empty( $_GET['form_provider_filter'] ) ||
            ! empty( $_GET['action_provider_filter'] ) ||
            ( ! empty( $_GET['view'] ) && 'all' !== $_GET['view'] );

        // Refine prompt — small, in-row, not a celebration of empty state.
        if ( $is_filtered ) {
            $reset_url = admin_url( 'admin.php?page=advanced-form-integration' );
            printf(
                wp_kses(
                    /* translators: %s: URL to clear filters */
                    __( 'No integrations match your search or filters. <a href="%s">Clear filters</a>.', 'advanced-form-integration' ),
                    array( 'a' => array( 'href' => array() ) )
                ),
                esc_url( $reset_url )
            );
            return;
        }

        // First-run / empty-database state — full illustration, CTA,
        // and link to the docs index of supported platforms so the
        // user understands what they can connect before clicking
        // through to the picker.
        $new_url  = admin_url( 'admin.php?page=advanced-form-integration-new' );
        $docs_url = 'https://advancedformintegration.com/docs/afi/';
        ?>
        <div class="afi-empty-state" role="region" aria-label="<?php esc_attr_e( 'Get started', 'advanced-form-integration' ); ?>">
            <svg class="afi-empty-icon" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                <!-- Soft pale-blue disc as a quiet backdrop -->
                <circle cx="48" cy="48" r="44" fill="#f0f6fc"/>
                <!-- Solid ring + plus in WP admin blue -->
                <circle cx="48" cy="48" r="32" fill="none" stroke="#2271b1" stroke-width="3"/>
                <path d="M48 32 V64 M32 48 H64"
                      stroke="#2271b1" stroke-width="4" stroke-linecap="round"/>
            </svg>

            <h2 class="afi-empty-title"><?php esc_html_e( 'Connect your first form', 'advanced-form-integration' ); ?></h2>
            <p class="afi-empty-subtitle">
                <?php esc_html_e( 'Send form submissions to Mailchimp, ActiveCampaign, Slack, and dozens of other services — no code required.', 'advanced-form-integration' ); ?>
            </p>

            <p class="afi-empty-actions">
                <a class="button button-primary button-hero" href="<?php echo esc_url( $new_url ); ?>">
                    <?php esc_html_e( 'Create your first integration', 'advanced-form-integration' ); ?>
                </a>
            </p>

            <p class="afi-empty-link">
                <a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener">
                    <?php esc_html_e( 'Browse supported platforms', 'advanced-form-integration' ); ?>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Sortable settings.
     */
    function get_sortable_columns() {
        return array(
            'title'           => array( 'title', true ),
            'form_provider'   => array( 'form_provider', true ),
            'action_provider' => array( 'action_provider', true ),
            'status'          => array( 'status', false ),
        );
    }

    /**
     * Build the SQL fragment + bound parameters for the active filter
     * set (view tab, provider dropdowns, search). Reused by both
     * fetch_table_data() and count() so result rows and the pagination
     * total stay in sync. Returns a 2-tuple:
     *
     *   array(
     *     'sql'    => "WHERE foo = %s AND bar IN (%d, %d)",
     *     'params' => array( 'value', 1, 2 ),
     *   )
     *
     * The caller is responsible for passing $sql + $params through
     * $wpdb->prepare(). When there are no filters, 'sql' is the empty
     * string.
     *
     * @since 1.128.3
     * @return array{sql:string, params:array}
     */
    protected function build_filters_clause() {
        global $wpdb;

        $where  = array();
        $params = array();

        // ---- View tab (active / inactive / failing / all) ----
        $view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'all';

        if ( 'active' === $view ) {
            $where[]  = 'status = %d';
            $params[] = 1;
        } elseif ( 'inactive' === $view ) {
            $where[]  = 'status = %d';
            $params[] = 0;
        } elseif ( 'failing' === $view ) {
            $failing_ids = adfoin_get_failing_integration_ids( 7 );
            if ( empty( $failing_ids ) ) {
                // Force an empty result without breaking the prepare()
                // contract: 1=0 is a constant, no placeholders needed.
                $where[] = '1 = 0';
            } else {
                $placeholders = implode( ', ', array_fill( 0, count( $failing_ids ), '%d' ) );
                $where[]      = "id IN ({$placeholders})";
                $params       = array_merge( $params, $failing_ids );
            }
        }

        // ---- Provider filter dropdowns ----
        if ( ! empty( $_GET['form_provider_filter'] ) ) {
            $where[]  = 'form_provider = %s';
            $params[] = sanitize_text_field( wp_unslash( $_GET['form_provider_filter'] ) );
        }
        if ( ! empty( $_GET['action_provider_filter'] ) ) {
            $where[]  = 'action_provider = %s';
            $params[] = sanitize_text_field( wp_unslash( $_GET['action_provider_filter'] ) );
        }

        // ---- Search ('s' is the WP convention for list tables) ----
        if ( ! empty( $_REQUEST['s'] ) ) {
            $term     = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
            $like     = '%' . $wpdb->esc_like( $term ) . '%';
            $where[]  = '( title LIKE %s OR form_provider LIKE %s OR action_provider LIKE %s )';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return array(
            'sql'    => empty( $where ) ? '' : ' WHERE ' . implode( ' AND ', $where ),
            'params' => $params,
        );
    }

    public function fetch_table_data( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'number'  => 20,
            'offset'  => 0,
            'orderby' => 'id',
            'order'   => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $log_table = $wpdb->prefix . 'adfoin_integration';

        // $wpdb->prepare's %s placeholder *quotes* values, which produces
        // invalid SQL like `ORDER BY 'title' 'ASC'` and silently kills
        // sorting. Identifiers/keywords have to be whitelisted and
        // interpolated directly; the LIMIT/OFFSET values stay on %d.
        $allowed_orderby = array( 'id', 'title', 'form_provider', 'action_provider', 'status' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $filter = $this->build_filters_clause();
        $params = array_merge( $filter['params'], array( (int) $args['number'], (int) $args['offset'] ) );

        $sql = "SELECT * FROM {$log_table}{$filter['sql']} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        // Only call prepare() when there are placeholders; otherwise it
        // produces a "Use of undefined …" notice on bare queries.
        $prepared = empty( $params ) ? $sql : $wpdb->prepare( $sql, $params );

        return $wpdb->get_results( $prepared, 'ARRAY_A' );
    }



    //Query, filter data, handle sorting, pagination, and any other data-manipulation required prior to rendering
    public function prepare_items() {
        // Process bulk actions if found.
        $this->process_bulk_actions();


        $count                 = $this->count();
        $columns               = $this->get_columns();
        $hidden                = get_hidden_columns( $this->screen );
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->admin_header();

        // Honor the user's Screen Options choice. get_items_per_page()
        // resolves precedence: user_meta > screen-option default > 20.
        // Falls back gracefully when the option hasn't been registered
        // yet (e.g., if a third party loads the table outside the
        // intended hook context).
        $per_page              = (int) $this->get_items_per_page( 'adfoin_integrations_per_page', 20 );
        if ( $per_page < 1 ) {
            $per_page = 20;
        }
        $current_page          = $this->get_pagenum();
        $offset                = ( $current_page - 1 ) * $per_page;

        $args = array(
            'offset' => $offset,
            'number' => $per_page,
        );

        if ( isset( $_REQUEST['orderby'] ) && !empty( $_REQUEST['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
        }

        if ( isset( $_REQUEST['order'] ) && !empty( $_REQUEST['order'] ) ) {
            $args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
        }

        $this->items = $this->fetch_table_data( $args );

        // Preload Health column data in a single bulk query so
        // column_health() doesn't fan out into N+1 lookups across
        // the page's 20 rows.
        $row_ids = array();
        if ( is_array( $this->items ) ) {
            foreach ( $this->items as $row ) {
                if ( ! empty( $row['id'] ) ) {
                    $row_ids[] = (int) $row['id'];
                }
            }
        }
        $this->health_cache = $row_ids
            ? adfoin_get_integration_health_bulk( $row_ids, 7 )
            : array();

        $this->set_pagination_args(
            array(
                'total_items' => $count,
                'per_page'    => $per_page,
                'total_pages' => ceil( $count / $per_page ),
            )
        );
    }

    /**
     * Override single_row so each <tr> carries data-id="N" and a
     * stable CSS class. Lets JS highlight freshly-duplicated rows
     * via window.adfoinHighlightRows without depending on column
     * markup details.
     */
    public function single_row( $item ) {
        printf(
            '<tr class="afi-integration-row" data-id="%d">',
            isset( $item['id'] ) ? (int) $item['id'] : 0
        );
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    /**
     * Renders the Health column — a colored dot summarising recent
     * activity plus the human-readable last-run time. The whole cell
     * links to the per-integration log so users can drill into a
     * problematic run with one click.
     *
     * Color rules (last 7 days):
     *   - gray   : never run
     *   - red    : latest run failed
     *   - amber  : latest run ok but window has 1+ failures
     *   - green  : latest run ok and zero failures
     *
     * @since 1.128.3
     */
    public function column_health( $item ) {
        $id     = (int) $item['id'];
        $health = isset( $this->health_cache[ $id ] ) ? $this->health_cache[ $id ] : null;

        if ( ! is_array( $health ) || null === $health['last_run_time'] ) {
            $state    = 'never';
            $label    = __( 'Never run', 'advanced-form-integration' );
            $time_str = '';
            $tooltip  = $label;
        } else {
            $failure = (int) $health['failure'];
            $ok      = (bool) $health['last_run_ok'];

            if ( ! $ok ) {
                $state = 'error';
            } elseif ( $failure > 0 ) {
                $state = 'warning';
            } else {
                $state = 'success';
            }

            $time_str = sprintf(
                /* translators: %s: human-readable time difference */
                __( '%s ago', 'advanced-form-integration' ),
                human_time_diff( strtotime( $health['last_run_time'] ), current_time( 'timestamp' ) )
            );

            $rate    = isset( $health['success_rate'] ) ? $health['success_rate'] : null;
            $tooltip = $rate !== null
                ? sprintf(
                    /* translators: 1: success rate %, 2: total runs in window */
                    __( '%1$d%% success across %2$d runs in last 7 days', 'advanced-form-integration' ),
                    (int) $rate,
                    (int) $health['total']
                )
                : $time_str;
        }

        $log_url = add_query_arg(
            array(
                'page'           => 'advanced-form-integration-log',
                'integration_id' => $id,
            ),
            admin_url( 'admin.php' )
        );

        return sprintf(
            '<a href="%1$s" class="afi-health-link" title="%2$s"><span class="afi-health-dot afi-health-%3$s" aria-hidden="true"></span><span class="afi-health-label">%4$s</span></a>',
            esc_url( $log_url ),
            esc_attr( $tooltip ),
            esc_attr( $state ),
            $time_str ? esc_html( $time_str ) : esc_html__( 'Never run', 'advanced-form-integration' )
        );
    }

    /**
     * Renders the status column — just the toggle. The pill text was
     * redundant given the toggle's on/off state is already visible.
     */
    public function column_status( $item ) {
        $status = (int) $item['status'];
        $id     = (int) $item['id'];

        return sprintf(
            '<label class="adfoin-toggle-form form-enabled">'
                . '<input type="checkbox" data-id="%1$d" value="1" %2$s/>'
                . '<span class="afi-slider round"></span>'
            . '</label>',
            $id,
            checked( 1, $status, false )
        );
    }

    /*
     * Handles delete
     */
    public function delete( $id='' ) {
        global $wpdb;
        $relation_table = $wpdb->prefix.'adfoin_integration';
        $action_status  = $wpdb->delete( $relation_table, array( 'id' => $id ) );

        return $action_status;
    }

    /**
     * Total rows for the *current* filter/search set, used by
     * pagination. View-tab counts (the small numbers in parentheses
     * next to "All / Active / Inactive / Failing") use raw SQL inside
     * get_views() so they stay independent of the active view.
     *
     * @return int
     */
    public function count() {
        global $wpdb;

        $relation_table = $wpdb->prefix . 'adfoin_integration';
        $filter         = $this->build_filters_clause();

        $sql = "SELECT COUNT(*) FROM {$relation_table}{$filter['sql']}";
        $sql = empty( $filter['params'] )
            ? $sql
            : $wpdb->prepare( $sql, $filter['params'] );

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Reserved for future per-page header markup. Column widths used
     * to be emitted here as inline <style>; that's now in
     * assets/css/asset.css under the "Integrations list table" section
     * so the rules are cacheable and discoverable.
     */
    public function admin_header() {
        // Intentionally empty — kept as an extension point.
    }
}