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
    }

    /**
     * Register the admin menu.
     *
     * @return void
     */
    public function admin_menu() {
        global $submenu;

        $hook1 = add_menu_page( esc_html__( 'Advanced Form Integration', 'advanced-form-integration' ), esc_html__( 'AFI', 'advanced-form-integration' ), 'manage_options', 'advanced-form-integration', array( $this, 'adfoin_routing' ), 'data:image/svg+xml;base64,' . base64_encode( '<svg width="30" height="30" viewBox="0 0 200 244" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 36.1064C2.88992e-06 16.1654 16.2806 -2.86948e-06 36.3636 0L163.636 1.14779e-05C183.719 1.14779e-05 200 16.1654 200 36.1064C200 56.0474 183.719 72.2127 163.636 72.2127L36.3636 72.2127C16.2806 72.2127 -2.88992e-06 56.0473 0 36.1064ZM0 122.491C2.88992e-06 102.55 16.2806 86.3844 36.3636 86.3844L127.479 86.3845C147.562 86.3845 163.843 102.55 163.843 122.491C163.843 142.432 147.562 158.597 127.479 158.597L36.3636 158.597C16.2805 158.597 -2.88992e-06 142.432 0 122.491ZM0 207.894C2.88992e-06 187.953 16.2806 171.787 36.3636 171.787L91.3223 171.787C111.405 171.787 127.686 187.953 127.686 207.894C127.686 227.835 111.405 244 91.3223 244H36.3636C16.2805 244 -2.88992e-06 227.835 0 207.894Z" fill="#FF6B6B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 36.1064C2.88992e-06 16.1654 16.2806 -2.86948e-06 36.3636 0L163.636 1.14779e-05C183.719 1.14779e-05 200 16.1654 200 36.1064C200 56.0474 183.719 72.2127 163.636 72.2127L36.3636 72.2127C16.2806 72.2127 -2.88992e-06 56.0473 0 36.1064ZM0 122.491C2.88992e-06 102.55 16.2806 86.3844 36.3636 86.3844L127.479 86.3845C147.562 86.3845 163.843 102.55 163.843 122.491C163.843 142.432 147.562 158.597 127.479 158.597L36.3636 158.597C16.2805 158.597 -2.88992e-06 142.432 0 122.491ZM0 207.894C2.88992e-06 187.953 16.2806 171.787 36.3636 171.787L91.3223 171.787C111.405 171.787 127.686 187.953 127.686 207.894C127.686 227.835 111.405 244 91.3223 244H36.3636C16.2805 244 -2.88992e-06 227.835 0 207.894Z" fill="#FF6B6B"/>
            </svg>' ) );
        add_submenu_page( 'advanced-form-integration', esc_html__( 'Advanced Form Integration', 'advanced-form-integration' ), esc_html__( 'Integrations', 'advanced-form-integration' ), 'manage_options', 'advanced-form-integration', array( $this, 'adfoin_routing' ) );
        $hook2 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Integrations', 'advanced-form-integration' ), esc_html__( 'Add New', 'advanced-form-integration' ), 'manage_options', 'advanced-form-integration-new', array( $this, 'adfoin_new_integration' ) );
        $hook3 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Settings', 'advanced-form-integration' ), esc_html__( 'Settings', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-settings', array( $this,'adfoin_settings') );
        $hook4 = add_submenu_page( 'advanced-form-integration', esc_html__( 'Log', 'advanced-form-integration' ), esc_html__( 'Log', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-log', array( $this,'adfoin_log') );
        add_submenu_page( 'advanced-form-integration', esc_html__( 'Get Help', 'advanced-form-integration' ), esc_html__( 'Get Help', 'advanced-form-integration'), 'manage_options', 'advanced-form-integration-help', array( $this,'adfoin_get_help') );

        add_action( 'admin_head-' . $hook1, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook2, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook3, array( $this, 'enqueue_assets' ) );
        add_action( 'admin_head-' . $hook4, array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'adfoin-main-style' );
        wp_enqueue_script( 'adfoin-vuejs' );
        do_action( 'adfoin_custom_script' );
        wp_enqueue_script( 'adfoin-main-script' );
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

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e( 'Integrations', 'advanced-form-integration' ); ?>
            </h1>
            <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-new' ); ?>" class="page-title-action"><?php _e( 'Add New', 'advanced-form-integration' ); ?></a>

            <form id="form-list" method="post">
                <input type="hidden" name="page" value="advanced-form-integration"/>

                <?php
                $list_table = new Advanced_Form_Integration_List_Table();
                $list_table->prepare_items();
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
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e( 'Log', 'advanced-form-integration' ); ?>
            </h1>

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
        </div>
        <?php
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
        $data['title'] =  __( 'Copy of ', 'advanced-form-integration') . $data['title'];
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
                'status'          => 0
            )
        );

        advanced_form_integration_redirect( admin_url( 'admin.php?page=advanced-form-integration' ) );

        exit;
    }
}