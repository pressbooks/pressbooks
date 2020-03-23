<?php
/**
 * Generic utility functions for Book directory.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class BookDirectory {
	const DELETE_BOOK_ENDPOINT = 'http://httpbin.org/post';

	/**
	 * @var BookDirectory
	 */
	protected static $instance = null;

	/**
	 * @since 5.14.3
	 *
	 * @return BookDirectory
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @since 5.14.3
	 *
	 * @param BookDirectory $obj
	 */
	static public function hooks( BookDirectory $obj ) {
		add_filter( 'update_option_blog_public', [ $obj, 'set_book_private' ], 10, 2 );
		add_action( 'wp_update_site', [ $obj, 'soft_delete_actions'], 10, 2 );
		add_action( 'wp_delete_site', [ $obj, 'delete_action' ], 10, 2 );
	}

	/**
	 * Detects a book deletion and triggers a book deletion request to the book fetcher API.
	 *
	 * @since 5.14.3
	 *
	 * @param string $blog_id
	 *
	 * @return void
	 */
	function delete_action( \WP_Site $site ) {
		$this->delete_book_from_directory( $site->blog_id );
	}

	/**
	 * Detects when a book is made private and triggers a book deletion request to the book fetcher API.
	 *
	 * @since 5.14.3
	 *
	 * @param string $stored_value
	 * @param int $new_value
	 *
	 * @return void
	 */
	function set_book_private( $stored_value, $new_value ) {
		// Book changes from public to private
		if ( 0 === $new_value ) {
			$this->delete_book_from_directory();
		}
	}

	/**
	 * Detects when a book is deleted, deactivated, marked as spam or archived
	 *
	 * @since 5.14.3
	 *
	 * @param \WP_Site $updated_config
	 * @param \WP_Site $previous_config
	 *
	 * @return void
	 */
	function soft_delete_actions( $updated_config, $previous_config ) {
		$is_archived = ! $previous_config->archived && '1' === $updated_config->archived;
		// deactivating a book updates the 'deleted' site config (Soft delete)
		$is_deactivated = ! $previous_config->deleted && '1' === $updated_config->deleted;
		$is_spam = ! $previous_config->spam && '1' === $updated_config->spam;

		if ( $is_archived || $is_deactivated || $is_spam ) {
			$this->delete_book_from_directory( $updated_config->blog_id );
		}
	}

	/**
	 * Delete book from directory.
	 *
	 * @since 5.14.3
	 *
	 * @param string $book_id Blog ID
	 *
	 * @return void
	 */
	function delete_book_from_directory( $book_id = null ) {
		$data = [
			'network' => DOMAIN_CURRENT_SITE,
			'book-id' => $book_id ?? get_current_blog_id(),
			];

		\Requests::post( self::DELETE_BOOK_ENDPOINT, array(), $data );
	}
}

