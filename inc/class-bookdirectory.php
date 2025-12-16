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
 *
 * @package Pressbooks
 */
class BookDirectory {

	const DEFAULT_DELETE_BOOK_ENDPOINT = 'https://api.pressbooks.com/book-directory-fetcher/api/books/delete';

	const DELETION_PREFIX = 'remove-';

	const DELETIONS_META_KEY = 'book_directory_removals';

	/**
	 * @var BookDirectory
	 */
	protected static $instance = null;

	/**
	 * @var BookDirectory
	 */
	protected static $delete_book_endpoint = null;

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

		self::$delete_book_endpoint = env( 'CUSTOM_DELETE_BOOK_ENDPOINT', self::DEFAULT_DELETE_BOOK_ENDPOINT );

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
		return $this->deleteBookFromDirectory( [ $site->blog_id ] );
	}

	/**
	 * Detects when a book is made private and triggers a book deletion request to the book fetcher API.
	 *
	 * @param string $stored_value
	 * @param int $new_value
	 *
	 * @return bool
	 * @since 5.14.3
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
	 */
	public function softDeleteActions( $updated_config, $previous_config ) {
		$is_archived = ! $previous_config->archived && '1' === $updated_config->archived;
		// deactivating a book updates the 'deleted' site config (Soft delete)
		$is_deactivated = ! $previous_config->deleted && '1' === $updated_config->deleted;
		$is_spam = ! $previous_config->spam && '1' === $updated_config->spam;
		$url_changed = $previous_config->path !== $updated_config->path;

		if ( $is_archived || $is_deactivated || $is_spam || $url_changed ) {
			return $this->deleteBookFromDirectory( [ $updated_config->blog_id ] );
		}
	}

	/**
	 * Delete book from directory.
	 *
	 * @param array|null $book_ids
	 * @return bool
	 * @since 5.14.3
	 */
	public function deleteBookFromDirectory( ?array $book_ids = null ): bool {

		if ( ! filter_var( self::$delete_book_endpoint, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$book_ids = $book_ids ?? [ get_current_blog_id() ];

		$sid = sprintf(
			'%s-%s-%s',
			uniqid( self::DELETION_PREFIX, true ),
			wp_rand( 1, 99 ),
			$book_ids[0]
		);

		$data = [
			'sid'      => $sid,
			'network'  => network_home_url(),
			'book_ids' => $book_ids,
		];

		$removals = get_site_option( self::DELETIONS_META_KEY, [] );
		update_site_option( self::DELETIONS_META_KEY, array_merge( $removals, [ $sid ] ) );

		$response = wp_remote_post(
			self::$delete_book_endpoint,
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $data ),
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			update_site_option( self::DELETIONS_META_KEY, $removals );
			error_log( 'Book deletion failed: ' . $response->get_error_message() );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 === $status_code ) {
			return true;
		}

		update_site_option( self::DELETIONS_META_KEY, $removals );

		if ( ! empty( $body ) ) {
			$result = json_decode( $body, true );

			if ( json_last_error() === JSON_ERROR_NONE && isset( $result['message'] ) ) {
				$error_message = $result['message'];

				// Log validation errors if present
				if ( 422 === $status_code && isset( $result['errors'] ) ) {
					$error_details = wp_json_encode( $result['errors'] );
					error_log( sprintf(
						'Book deletion validation failed: %s - Details: %s',
						$error_message,
						$error_details
					) );
				} else {
					error_log( sprintf(
						'Book deletion failed (status %d): %s',
						$status_code,
						$error_message
					) );
				}
			} else {
				error_log( sprintf( 'Book deletion failed with status code: %d', $status_code ) );
			}
		} else {
			error_log( sprintf( 'Book deletion failed with status code: %d (empty response)', $status_code ) );
		}

		return false;
	}
}

