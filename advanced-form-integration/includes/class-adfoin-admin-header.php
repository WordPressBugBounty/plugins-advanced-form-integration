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
	 *
	 * Renders the page title using the WordPress-native pattern
	 * (h1.wp-heading-inline + a.page-title-action + hr.wp-header-end)
	 * so it slots into core admin styling without a custom bar. The
	 * old .afi-header bar (logo + bell + KB icons) was removed in
	 * 1.128.4 — the bell linked to nowhere, the logo duplicated the
	 * sidebar branding, and the bar collided with WordPress's
	 * Screen Options / Help tabs. The KB link now lives in the
	 * standard WP Help tab.
	 *
	 * Caller responsibility: invoke this *inside* the page's
	 * `<div class="wrap">` so the H1 inherits wrap padding and
	 * admin-notice placement (notices auto-inject after the
	 * `wp-header-end` marker hr).
	 *
	 * @param string $id    Integration ID — only meaningful for the
	 *                      edit screen; controls the "Edit: …" title.
	 * @param string $title Integration title for the edit screen.
	 */
	public function display( $id = '', $title = '' ) {
		$this->screen_id  = $this->get_current_screen();
		$this->id         = $id;
		$this->page_title = $title;

		$this->render_page_title();
	}

	/**
	 * Render the H1 + optional page-title-action + wp-header-end
	 * marker. Switching on $_GET['action'] for the edit detection
	 * (rather than just $this->id) means future routes — duplicate,
	 * view, etc. — that also pass an ID won't accidentally render
	 * "Edit:" headings.
	 */
	private function render_page_title() {
		$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$heading = '';
		$action_link = ''; // optional .page-title-action button next to the H1

		switch ( $this->screen_id ) {
			case 'toplevel_page_advanced-form-integration':
				if ( 'edit' === $action && $this->id ) {
					/* translators: 1: integration title, 2: integration ID */
					$heading = sprintf(
						__( 'Edit: %1$s (ID: %2$d)', 'advanced-form-integration' ),
						$this->page_title,
						(int) $this->id
					);
				} else {
					$heading = __( 'Integrations', 'advanced-form-integration' );
					$action_link = sprintf(
						'<a href="%1$s" class="page-title-action">%2$s</a>',
						esc_url( admin_url( 'admin.php?page=advanced-form-integration-new' ) ),
						esc_html__( 'Add New', 'advanced-form-integration' )
					);
				}
				break;

			case 'afi_page_advanced-form-integration-new':
				$heading = __( 'New Integration', 'advanced-form-integration' );
				break;

			case 'afi_page_advanced-form-integration-settings':
				$heading = __( 'Settings', 'advanced-form-integration' );
				break;

			case 'afi_page_advanced-form-integration-log':
				$heading = __( 'Log', 'advanced-form-integration' );
				break;

			case 'afi_page_advanced-form-integration-import-export':
				$heading = __( 'Export / Import', 'advanced-form-integration' );
				break;

			case 'afi_page_advanced-form-integration-help':
				$heading = __( 'Get Help', 'advanced-form-integration' );
				break;

			default:
				$heading = __( 'Advanced Form Integration', 'advanced-form-integration' );
				break;
		}

		printf(
			'<h1 class="wp-heading-inline">%s</h1>',
			esc_html( $heading )
		);
		if ( $action_link ) {
			echo $action_link; // already escaped above
		}
		echo '<hr class="wp-header-end">';
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
