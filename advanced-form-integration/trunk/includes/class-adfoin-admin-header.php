<?php
/**
 * Header for the AFI pages
 *
 * @since      1.0.44
 * @package    AFI
 * @subpackage AFI\Admin
 * @author     Nasir Ahmed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Header class.
 */
class Advanced_Form_Integration_Admin_Header {

	/**
	 * Hold current screen ID.
	 *
	 * @var Current screen ID.
	 */
	private $screen_id = '';

    /**
	 * Hold Integration ID.
	 *
	 * @var Integration ID.
	 */
    private $id = '';

    /**
     * Hold Page Title.
     * 
     * @var Page Title.
     */
    private $page_title = '';

	/**
	 * Display Header.
	 */
	public function display( $id = '', $title = '' ) {
        $main_url = admin_url( 'admin.php?page=advanced-form-integration' );
		$this->screen_id = $this->get_current_screen();
        $this->id = $id;
        $this->page_title = $title;
		?>
		<div class="afi-header">
            <div class="afi-logo">
                <a href="<?php echo $main_url; ?>"><img src="<?php echo ADVANCED_FORM_INTEGRATION_URL . '/assets/images/afilogo.png'; ?>" alt="Advanced Form Integration">
                <h1 class="afi-logo-text">
				    AFI
			    </h1>
                </a>
            </div>
            
            <a href="" title="<?php esc_attr_e( 'AFI Notifications', 'advanced-form-integration' ); ?>" target="_blank" class="button afi-help afi-notification"><i class="dashicons dashicons-bell"></i></a>
            <a href="https://advancedformintegration.com/docs/afi/" title="<?php esc_attr_e( 'AFI Knowledge Base', 'advanced-form-integration' ); ?>" target="_blank" class="button afi-help"><i class="dashicons dashicons-admin-site-alt3"></i></a>
        </div>
		<?php
        $this->get_page_titles();
	}

	/**
	 * Get Search Options.
	 */
	private function get_page_titles() {
        ?>
        <h1 id="afi-page-title" class="wrap">
            <?php
            switch ($this->screen_id) {
                case 'toplevel_page_advanced-form-integration':
                    if ( $this->id ) {
                        echo 'Edit: ' . $this->page_title . '(ID: ' . $this->id . ')';
                    } else {
                        echo 'Integrations <a href="' . esc_url(admin_url('admin.php?page=advanced-form-integration-new')) . '" class="afi-add-new"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__('Add New', 'advanced-form-integration') . '</a>';
                    }
                    break;
    
                case 'afi_page_advanced-form-integration-new':
                    echo esc_html__('New Integration', 'advanced-form-integration');
                    break;
    
                case 'afi_page_advanced-form-integration-settings':
                    echo esc_html__('Settings', 'advanced-form-integration');
                    break;
    
                case 'afi_page_advanced-form-integration-log':
                    echo esc_html__('Log', 'advanced-form-integration');
                    break;

                case 'afi_page_advanced-form-integration-import-export':
                    echo esc_html__( 'Export / Import', 'advanced-form-integration' );
                    break;

                default:
                    echo esc_html__('Advanced Form Integration', 'advanced-form-integration');
                    break;
            }
            ?>
        </h1>
        <?php
    }
    
	/**
	 * Get Current Screen ID.
	 */
	private function get_current_screen() {
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return '';
		}

		return $screen->id;
	}
}
