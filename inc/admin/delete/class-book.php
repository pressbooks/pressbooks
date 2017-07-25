<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Admin\Delete;

class Book {

	/**
	 * @var \Pressbooks\Admin\Delete\Book
	 */
	private static $instance = null;

	/**
	 * @return \Pressbooks\Admin\Delete\Book
	 */
	static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Private constructor, use init instead
	 */
	private function __construct() {

		// Hide from side menu
		remove_submenu_page( 'tools.php', 'ms-delete-site.php' );

		// Add to top menu
		if ( current_user_can( 'delete_site' ) ) {
			add_action( 'admin_bar_menu', [ $this, 'addMenu' ], 31 );
		}

		add_filter( 'delete_site_email_content', [ $this, 'deleteBookEmailContent' ] );
	}

	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 */
	public function addMenu( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			[
				'parent' => 'site-name',
				'id' => 'delete-book',
				'title' => __( 'Delete Book', 'pressbooks' ),
				'href' => admin_url( 'ms-delete-site.php' ),
			]
		);
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function deleteBookEmailContent( $content ) {
		// TODO: Change email text
		return $content;
	}
}
