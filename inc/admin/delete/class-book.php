<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Delete;

class Book {
	private static ?\Pressbooks\Admin\Delete\Book $instance = null;

	/**
	 * @return \Pressbooks\Admin\Delete\Book
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	public static function hooks( Book $obj ) {
		// Hide from side menu
		remove_submenu_page( 'tools.php', 'ms-delete-site.php' );
		// Add to top menu
		if ( current_user_can( 'delete_site' ) ) {
			add_action( 'admin_bar_menu', [ $obj, 'addMenu' ], 31 );
		}
		// Override delete site email
		add_filter( 'delete_site_email_content', [ $obj, 'deleteBookEmailContent' ] );
	}

	/**
	 *
	 */
	public function __construct() {
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

		/* translators: Do not translate USERNAME, URL_DELETE, SITE_NAME: those are placeholders. */
		$content = __(
			"Howdy ###USERNAME###,

You recently clicked the 'Delete Book' link on your book and filled in a
form on that page.

If you really want to delete your book, click the link below. You will not
be asked to confirm again so only click this link if you are absolutely certain:
###URL_DELETE###

If you delete your book, please consider starting a new book project with us
some time in the future! (But remember your current book
is gone forever.)

Thanks for using Pressbooks,
###SITE_NAME###", 'pressbooks'
		);

		return $content;
	}
}
