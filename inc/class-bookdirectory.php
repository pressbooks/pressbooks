<?php
/**
 * Generic utility functions for Book directory.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use WP_Site;

/**
 * Class BookDirectory
 * @package Pressbooks
 */
class BookDirectory {

	const DELETE_BOOK_ENDPOINT = 'https://api.pressbooks.com/book-directory-fetcher/api/books/delete';

	const DELETION_PREFIX = 'remove-';

	const DELETIONS_META_KEY = 'book_directory_removals';

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
		add_filter( 'update_option_blog_public', [ $obj, 'setBookPrivate' ], 10, 2 );
		add_action( 'wp_update_site', [ $obj, 'softDeleteActions' ], 10, 2 );
		add_action( 'wp_delete_site', [ $obj, 'deleteAction' ], 10, 2 );
	}

	/**
	 * Detects a book deletion and triggers a book deletion request to the book fetcher API.
	 *
	 * @param WP_Site $site
	 *
	 * @return bool
	 * @since 5.14.3
	 */
	public function deleteAction( WP_Site $site ) {
		return $this->deleteBookFromDirectory( $site->blog_id );
	}

	/**
	 * Detects when a book is made private and triggers a book deletion request to the book fetcher API.
	 *
	 * @param string $stored_value
	 * @param int $new_value
	 *
	 * @return bool
	 * @since 5.14.3
	 *
	 */
	public function setBookPrivate( $stored_value, $new_value ) {
		// Book changes from public to private
		if ( 0 === $new_value ) {
			return $this->deleteBookFromDirectory();
		}
	}

	/**
	 * Detects when a book is deleted, deactivated, marked as spam or archived
	 *
	 * @param WP_Site $updated_config
	 * @param WP_Site $previous_config
	 *
	 * @return bool
	 *@since 5.14.3
	 *
	 */
	public function softDeleteActions( $updated_config, $previous_config ) {
		$is_archived = ! $previous_config->archived && '1' === $updated_config->archived;
		// deactivating a book updates the 'deleted' site config (Soft delete)
		$is_deactivated = ! $previous_config->deleted && '1' === $updated_config->deleted;
		$is_spam = ! $previous_config->spam && '1' === $updated_config->spam;
		$url_changed = $previous_config->path !== $updated_config->path;

		if ( $is_archived || $is_deactivated || $is_spam || $url_changed ) {
			return $this->deleteBookFromDirectory( $updated_config->blog_id );
		}
	}

	/**
	 * Delete book from directory.
	 *
	 * @param string $book_id Blog ID
	 *
	 * @return bool
	 * @since 5.14.3
	 *
	 */
	public function deleteBookFromDirectory( string $book_id = null ) {
		if ( filter_var( self::DELETE_BOOK_ENDPOINT, FILTER_VALIDATE_URL ) ) {
			$book_id = $book_id ?? get_current_blog_id();
			$sid = sprintf( '%s-%s-%s', uniqid( self::DELETION_PREFIX, true ), rand( 1, 99 ), $book_id );

			$header = [
				'Content-Type' => 'application/json',
			];

			$data = [
				'sid'       => $sid,
				'network'   => 'https://' . $_SERVER['HTTP_HOST'],
				'book_id'   => $book_id,
			];

			$result = \Requests::post( self::DELETE_BOOK_ENDPOINT, $header, wp_json_encode( $data ) );

			if ( 200 === $result->status_code && true === $result->success ) {
				$removals = get_site_option( self::DELETIONS_META_KEY, [] );
				array_push( $removals, $sid );
				update_site_option( self::DELETIONS_META_KEY, $removals );
				return true;
			}
		}

		return false;
	}
}

