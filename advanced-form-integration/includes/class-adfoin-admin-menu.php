<?php

/**
 * Class Admin_Menu
 *
 */
class Advanced_Form_Integration_Admin_Menu {
    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_filter( 'set-screen-option', array( $this, 'adfoin_log_table_set_option' ), 10, 3 );

        // Clear-all-logs POST handler (admin-post).
        add_action( 'admin_post_adfoin_clear_all_logs', array( $this, 'adfoin_clear_all_logs' ) );

        // WP 5.4.2+ split per-option filters out into
        // set_screen_option_{$option}; the legacy `set-screen-option`
        // still works but emits a deprecation notice in newer cores.
        // Register both filter names for the integrations list so
        // either core dispatches reach our handler.
        add_filter( 'set-screen-option', array( $this, 'adfoin_integrations_set_option' ), 10, 3 );
        add_filter( 'set_screen_option_adfoin_integrations_per_page', array( $this, 'adfoin_integrations_set_option_v2' ), 10, 3 );

        // Register Help-tab content on every AFI screen. Replaces the
        // bell + KB icons that used to live in the .afi-header bar.
        add_action( 'current_screen', array( $this, 'register_help_tabs' ) );
    }

    /**
     * Add a "Documentation" Help tab + a Help-sidebar with the KB
     * link to every AFI admin screen. The Help tab is the
     * WordPress-native place for "where to learn more about this
     * page" content — users already know to look there.
     *
     * @param WP_Screen $screen
     */
    public function register_help_tabs( $screen ) {
        if ( empty( $screen ) || empty( $screen->id ) ) {
            return;
        }

        // Match every AFI page: the toplevel + each submenu suffix.
        $afi_screens = array(
            'toplevel_page_advanced-form-integration',
            'afi_page_advanced-form-integration-new',
            'afi_page_advanced-form-integration-settings',
            'afi_page_advanced-form-integration-log',
            'afi_page_advanced-form-integration-import-export',
            'afi_page_advanced-form-integration-help',
        );

        if ( ! in_array( $screen->id, $afi_screens, true ) ) {
            return;
        }

        $kb_url = 'https://advancedformintegration.com/docs/afi/';

        $screen->add_help_tab( array(
            'id'      => 'adfoin_overview',
            'title'   => __( 'Overview', 'advanced-form-integration' ),
            'content' =>
                '<p>' . esc_html__( 'Advanced Form Integration sends form submissions from popular WordPress form plugins to dozens of CRMs, email-marketing tools, and webhooks — without writing any code.', 'advanced-form-integration' ) . '</p>' .
                '<p>' . sprintf(
                    /* translators: %s: link to documentation */
                    wp_kses(
                        __( 'For setup walkthroughs, supported platforms, and troubleshooting tips, visit the <a href="%s" target="_blank" rel="noopener">Knowledge Base</a>.', 'advanced-form-integration' ),
                        array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
                    ),
                    esc_url( $kb_url )
                ) . '</p>',
        ) );

        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__( 'For more information:', 'advanced-form-integration' ) . '</strong></p>' .
            '<p><a href="' . esc_url( $kb_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Knowledge Base', 'advanced-form-integration' ) . '</a></p>' .
            '<p><a href="' . esc_url( admin_url( 'admin.php?page=advanced-form-integration-help' ) ) . '">' . esc_html__( 'Get Help (in-plugin)', 'advanced-form-integration' ) . '</a></p>'
        );
    }

    /**
     * Persist screen-meta value for the log list table's per-page setting.
     */
    public function adfoin_log_table_set_option( $status, $option, $value ) {
        if ( 'adfoin_log_per_page' == $option ) return $value;

        return $status;
    }

    /**
     * Persist the integrations-list per-page screen option (legacy
     * filter name; pre-5.4.2 cores).
     */
    public function adfoin_integrations_set_option( $status, $option, $value ) {
        if ( 'adfoin_integrations_per_page' === $option ) {
            return (int) $value;
        }
        return $status;
    }

    /**
     * Per-option filter (WP 5.4.2+). $status here is the raw incoming
     * value, not a bool, so we just clamp + return.
     */
    public function adfoin_integrations_set_option_v2( $value ) {
        return (int) $value;
    }


    /**
     * Register the admin menu.
     *
     * @return void
     */
    public function admin_menu() {
        global $submenu;

        $hook1 = add_menu_page( esc_html__( 'Advanced Form Integration', 'advanced-form-integration' ), esc_html__( 'AFI', 'advanced-form-integration' ), 'manage_options', 'advanced-form-integration', array( $this, 'adfoin_routing' ), 'data:image/svg+xml;base64,' . base64_encode( '<svg width="25" height="25" viewBox="-20 -24 240 292" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M0 36.1064C2.88992e-06 16.1654 16.2806 -2.86948e-06 36.3636 0L163.636 1.14779e-05C183.719 1.14779e-05 200 16.1654 200 36.1064C200 56.0474 183.719 72.2127 163.636 72.2127L36.3636 72.2127C16.2806 72.2127 -2.88992e-06 56.0473 0 36.1064ZM0 122.491C2.88992e-06 102.55 16.2806 86.3844 36.3636 86.3844L127.479 86.3845C147.562 86.3845 163.843 102.55 163.843 122.491C163.843 142.432 147.562 158.597 127.479 158.597L36.3636 158.597C16.2805 158.597 -2.88992e-06 142.432 0 122.491ZM0 207.894C2.88992e-06 187.953 16.2806 171.787 36.3636 171.787L91.3223 171.787C111.405 171.787 127.686 187.953 127.686 207.894C127.686 227.835 111.405 244 91.3223 244H36.3636C16.2805 244 -2.88992e-06 227.835 0 207.894Z" fill="#FF6B6B"/>
</svg>' ) );
        add_submenu_page( 'advanced-form-integration', esc_html__( 'Advanced Form Integration', 'advanced-form-integration' ), esc_html__( 'Integrations', 'advanced-form-integration' ), 'manage_options', 'advanced-form-integration', array( $this, 'adfoin_routing' ) );
        $hook2 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Integrations', 'advanced-form-integration' ), esc_html__( 'Add New', 'advanced-form-integration' ), 'manage_options', 'advanced-form-integration-new', array( $this, 'adfoin_new_integration' ) );
        $hook3 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Settings', 'advanced-form-integration' ), esc_html__( 'Settings', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-settings', array( $this,'adfoin_settings') );
        $hook4 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Log', 'advanced-form-integration' ), esc_html__( 'Log', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-log', array( $this,'adfoin_log') );
        $hook5 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Export / Import', 'advanced-form-integration' ), esc_html__( 'Export / Import', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-import-export', array( $this,'adfoin_import_export' ) );
        add_submenu_page( 'advanced-form-integration', esc_html__( 'Get Help', 'advanced-form-integration' ), esc_html__( 'Get Help', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-help', array( $this,'adfoin_get_help') );

        add_action( 'admin_head-' . $hook1, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook2, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook3, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook4, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook5, array( $this, 'enqueue_assets' ) );
    
        // Add screen options for log page
        add_action( "load-$hook4", array( $this, 'adfoin_log_screen_options' ) );

        // Add screen options for the integrations list page (top-level
        // hook). Suppressed for the edit/duplicate flows since those
        // aren't list views — only the action=list default needs it.
        add_action( "load-$hook1", array( $this, 'adfoin_integrations_screen_options' ) );
    }

    /**
     * Add screen options for log list table
     */
    public function adfoin_log_screen_options() {
        if ( isset( $_GET['action'] ) && 'view' == $_GET['action'] ) {
            return;
        }

        $option = 'per_page';
        $args = array(
            'label'   => esc_html__( 'Logs per page', 'advanced-form-integration' ),
            'default' => 20,
            'option'  => 'adfoin_log_per_page'
        );
        add_screen_option( $option, $args );
    }

    /**
     * Screen Options tab for the integrations list page. WP renders
     * the tab automatically once the option is registered; users can
     * change per-page count and column visibility from there. The
     * value is read in Advanced_Form_Integration_List_Table::prepare_items
     * via $this->get_items_per_page().
     *
     * Skipped on edit/duplicate sub-actions so the tab doesn't show
     * an irrelevant "per page" knob next to the form.
     */
    public function adfoin_integrations_screen_options() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        if ( '' !== $action && 'list' !== $action ) {
            return;
        }

        add_screen_option( 'per_page', array(
            'label'   => esc_html__( 'Integrations per page', 'advanced-form-integration' ),
            'default' => 20,
            'option'  => 'adfoin_integrations_per_page',
        ) );
    }

    public function enqueue_assets() {
        wp_enqueue_script( 'adfoin-vuejs' );

        // Determine current page context
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

        // Shared Vue components (registered globally before the app boots)
        wp_enqueue_script(
            'adfoin-searchable-select',
            ADVANCED_FORM_INTEGRATION_ASSETS . '/js/components/searchable-select.js',
            array( 'adfoin-vuejs' ),
            ADVANCED_FORM_INTEGRATION_VERSION,
            true
        );

        // Always load core utilities
        wp_enqueue_script(
            'adfoin-core',
            ADVANCED_FORM_INTEGRATION_ASSETS . '/js/core.js',
            array( 'adfoin-vuejs', 'adfoin-searchable-select' ),
            ADVANCED_FORM_INTEGRATION_VERSION,
            true
        );
        
        // Load triggers and actions only on new/edit integration pages
        $needs_integration_scripts = (
            $page === 'advanced-form-integration-new' || 
            ( $page === 'advanced-form-integration' && $action === 'edit' )
        );
        
        if ( $needs_integration_scripts ) {
            // Load trigger components (always needed for new/edit)
            wp_enqueue_script( 
                'adfoin-triggers', 
                ADVANCED_FORM_INTEGRATION_ASSETS . '/js/triggers.js', 
                array( 'adfoin-core' ), 
                ADVANCED_FORM_INTEGRATION_VERSION, 
                true 
            );
            
            // Load Vue app initialization (handles lazy loading of action components)
            wp_enqueue_script( 
                'adfoin-app', 
                ADVANCED_FORM_INTEGRATION_ASSETS . '/js/app.js', 
                array( 'adfoin-triggers' ), 
                ADVANCED_FORM_INTEGRATION_VERSION, 
                true 
            );
            
            // Action components are now loaded one platform at a time from
            // platforms/<provider>/<provider>-component.js when the user picks
            // an action provider. Triggered from app.js via
            // adfoinComponentLoader.loadPlatform(actionProviderId).
        }
        
        // Allow plugins to add custom scripts
        do_action( 'adfoin_custom_script' );
        
        // For settings, log, and list pages - only load core (no action components needed)
        // This significantly reduces load time on these pages
    }

    /**
     * Display the Tasks page.
     *
     * @return void
     */
    public function adfoin_routing() {
        include ADVANCED_FORM_INTEGRATION_INCLUDES . '/class-adfoin-list-table.php';
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        switch ( $action ) {
            case 'edit':
                $this->adfoin_edit( $id );
                break;
            case 'duplicate':
                $this->adfoin_duplicate_integration($id);
                break;
            default:
                $this->adfoin_list_page() ;
                break;
        }
    }

    /*
     * This function generates the list of connections
     */
    public function adfoin_list_page() {
        if ( isset( $_GET['status'] ) ) {
            $status = $_GET['status'];
        }

        $list_table = new Advanced_Form_Integration_List_Table();
        $list_table->prepare_items();

        // ---- Admin notice for bulk-action redirects (?bulk_done=…) ----
        if ( ! empty( $_GET['bulk_done'] ) ) {
            $bulk_done = sanitize_key( wp_unslash( $_GET['bulk_done'] ) );
            $messages  = array(
                'activated'   => __( 'Integrations activated.',   'advanced-form-integration' ),
                'deactivated' => __( 'Integrations deactivated.', 'advanced-form-integration' ),
                'activate'    => __( 'Integrations activated.',   'advanced-form-integration' ),
                'deactivate'  => __( 'Integrations deactivated.', 'advanced-form-integration' ),
                'duplicated'  => __( 'Integration duplicated.',   'advanced-form-integration' ),
                'deleted'     => __( 'Integrations deleted.',     'advanced-form-integration' ),
            );
            if ( isset( $messages[ $bulk_done ] ) ) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html( $messages[ $bulk_done ] )
                );
            }
        }

        // The duplicated row IDs come back as ?duplicated=12 or
        // ?duplicated=12,15,18 — JS picks them up and adds a
        // .afi-row-highlight class to the matching <tr> for a brief
        // fade-in effect.
        $highlight_ids = array();
        if ( ! empty( $_GET['duplicated'] ) ) {
            $highlight_ids = array_filter( array_map(
                'intval',
                explode( ',', sanitize_text_field( wp_unslash( $_GET['duplicated'] ) ) )
            ) );
        }
        ?>

        <div class="wrap">
            <?php adfoin_display_admin_header(); ?>

            <?php if ( ! empty( $highlight_ids ) ) : ?>
                <script type="text/javascript">
                    /* Brief fade highlight on freshly-duplicated rows. */
                    window.adfoinHighlightRows = <?php echo wp_json_encode( array_values( $highlight_ids ) ); ?>;
                </script>
            <?php endif; ?>

            <?php $list_table->views(); ?>

            <?php
            // method="get" so search + filter URLs are bookmarkable
            // (matches WP-core list pages like edit.php). Bulk delete
            // still works because process_bulk_actions() reads from
            // $_REQUEST and the row-action delete already used GET
            // with its own nonce.
            ?>
            <form id="form-list" method="get">
                <input type="hidden" name="page" value="advanced-form-integration"/>

                <?php
                // Preserve `view` across search submissions so a user
                // searching from inside the "Failing" tab keeps that
                // scope. Provider filters are submitted via the
                // <select>s in extra_tablenav() and don't need a
                // hidden field.
                if ( ! empty( $_GET['view'] ) ) {
                    printf(
                        '<input type="hidden" name="view" value="%s"/>',
                        esc_attr( sanitize_key( wp_unslash( $_GET['view'] ) ) )
                    );
                }

                $list_table->search_box(
                    __( 'Search Integrations', 'advanced-form-integration' ),
                    'adfoin-integration'
                );
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /*
     * Handles new connection
     */
    public function adfoin_new_integration(){

        $form_providers   = adfoin_get_form_providers();
        $action_providers = adfoin_get_action_porviders();
        ksort( $action_providers );

        require_once ADVANCED_FORM_INTEGRATION_VIEWS . '/new_integration.php';
    }

    /*
     * Handles connection view
     */
    public function adfoin_view( $id='' ) {
    }

    /*
     * Handles connection edit
     */
    public function adfoin_edit( $id='' ) {

        if ( $id ) {
            require_once ADVANCED_FORM_INTEGRATION_VIEWS . '/edit_integration.php';
        }
    }

    /*
     * Settings Submenu View
     */
    public function adfoin_settings( $value = '' ) {
        $tabs = adfoin_get_settings_tabs();

        include ADVANCED_FORM_INTEGRATION_VIEWS . '/settings.php';
    }

    /*
     * Import/Export Submenu View
     */
    public function adfoin_import_export() {
        include ADVANCED_FORM_INTEGRATION_VIEWS . '/import_export.php';
    }

    /*
     * Log Submenu View
     */
    public function adfoin_log( $value = '' ) {
        include ADVANCED_FORM_INTEGRATION_INCLUDES . '/class-adfoin-log-table.php';
        
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        switch ( $action ) {
            case 'view':
                $this->adfoin_log_view( $id );
                break;
            default:
                $this->adfoin_log_list_page() ;
                break;
        }

    }

    /*
     * Get Help Submenu View
     */
    public function adfoin_get_help( $value = '' ) {
        include ADVANCED_FORM_INTEGRATION_VIEWS . '/get_help.php';
    }

    /*
    * This function generates the list of connections
    */
    public function adfoin_log_list_page() {
        $clear_nonce = wp_create_nonce( 'adfoin_clear_all_logs_nonce' );
        ?>

        <div class="wrap afi-container">
            <?php adfoin_display_admin_header(); ?>

            <form id="form-list" method="post">
                <input type="hidden" name="page" value="advanced-form-integration-log"/>

                <?php
                $list_table = new Advanced_Form_Integration_Log_Table();
                $list_table->prepare_items();
                $list_table->search_box( __( 'Search Log', 'advanced-form-integration' ), 'afi-log-search' );
                $list_table->views();
                $list_table->display();
                ?>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="afi-clear-logs-form"
                  onsubmit="return confirm('<?php echo esc_js( __( 'Delete ALL log entries? This cannot be undone.', 'advanced-form-integration' ) ); ?>');">
                <input type="hidden" name="action"    value="adfoin_clear_all_logs">
                <input type="hidden" name="_wpnonce"  value="<?php echo esc_attr( $clear_nonce ); ?>">
                <button type="submit" class="afi-btn-danger afi-clear-logs-btn">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e( 'Clear All Logs', 'advanced-form-integration' ); ?>
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Handles the Clear All Logs POST action.
     */
    public function adfoin_clear_all_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'advanced-form-integration' ) );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'adfoin_clear_all_logs_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'advanced-form-integration' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'adfoin_log';
        $wpdb->query( "TRUNCATE TABLE {$table}" );

        advanced_form_integration_redirect( admin_url( 'admin.php?page=advanced-form-integration-log&cleared=1' ) );
        exit;
    }

    /*
     * Handles log view
     */
    public function adfoin_log_view( $id='' ) {

        if ( $id ) {
            require_once ADVANCED_FORM_INTEGRATION_VIEWS . '/view_log.php';
        }
    }

    /*
     * Relation Status Change adfoin_status
     */
    public function adfoin_duplicate_integration( $id = '' ) {

        // verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'adfoin_duplicate_integration_nonce' ) ) {
            wp_die( 'Security check' );
        }

        global $wpdb;

        $table         = $wpdb->prefix . "adfoin_integration";
        $sql           = $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id );
        $data          = $wpdb->get_row( $sql, ARRAY_A );

        if ( ! $data ) {
            advanced_form_integration_redirect( admin_url( 'admin.php?page=advanced-form-integration' ) );
            exit;
        }

        $data['title'] = __( 'Copy of ', 'advanced-form-integration' ) . $data['title'];
        $result        = $wpdb->insert(
            $table,
            array(
                'title'           => $data['title'],
                'form_provider'   => $data['form_provider'],
                'form_id'         => $data['form_id'],
                'form_name'       => $data['form_name'],
                'action_provider' => $data['action_provider'],
                'task'            => $data['task'],
                'data'            => $data['data'],
                'status'          => 0,
            )
        );

        // Carry the new ID + a "duplicated" flag back to the listing
        // so the row can highlight briefly and an admin notice can
        // confirm the action — single-row duplicate previously
        // succeeded silently and the user had to scan the list to
        // confirm anything happened.
        $redirect = admin_url( 'admin.php?page=advanced-form-integration' );
        $redirect = add_query_arg( 'bulk_done', 'duplicated', $redirect );
        if ( $result ) {
            $redirect = add_query_arg( 'duplicated', (int) $wpdb->insert_id, $redirect );
        }
        advanced_form_integration_redirect( $redirect );

        exit;
    }
}
