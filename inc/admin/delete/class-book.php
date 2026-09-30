<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
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
		// Send the delete confirmation email to the requesting user too (if book admin and not already a recipient)
		add_filter( 'wp_mail', [ $obj, 'addCurrentUserToDeleteEmail' ] );
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
	 * If the current user's email differs from the book's admin_email,
	 * add them as a recipient so they also receive the delete confirmation link.
	 *
	 * @param array $args {
	 *     Array of wp_mail() arguments.
	 *
	 *     @type string|string[] $to          Email recipients.
	 *     @type string          $subject     Email subject.
	 *     @type string          $message     Email body.
	 *     @type string|string[] $headers     Email headers.
	 *     @type string|string[] $attachments Files to attach.
	 * }
	 *
	 * @return array
	 */
	public function addCurrentUserToDeleteEmail( $args ) {
		$subject_pattern = sprintf(
			/* translators: %s: Site title. */
			__( '[%s] Delete My Site' ),
			wp_specialchars_decode( get_option( 'blogname' ) )
		);

		if ( $args['subject'] !== $subject_pattern ) {
			return $args;
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $args;
		}

		$admin_email = get_option( 'admin_email' );
		$current_user_email = $current_user->user_email;

		if ( strcasecmp( $current_user_email, $admin_email ) === 0 ) {
			return $args;
		}

		$recipients = is_array( $args['to'] ) ? $args['to'] : [ $args['to'] ];

		if ( ! in_array( $current_user_email, $recipients, true ) ) {
			$recipients[] = $current_user_email;
		}

		$args['to'] = $recipients;

		return $args;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function deleteBookEmailContent( $content ) {

		/* translators: Do not translate USERNAME, URL_DELETE, SITENAME: those are placeholders. */
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
###SITENAME###", 'pressbooks'
		);

		return $content;
	}
}
