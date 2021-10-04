<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\DataCollector;

class User {

	// Meta Key Constants:

	const LAST_LOGIN = 'pb_last_login';

	const HIGHEST_ROLE = 'pb_highest_role';

	const TOTAL_BOOKS = 'pb_total_books';

	const BOOKS_AS_ADMIN = 'pb_books_as_admin';

	const BOOKS_AS_EDITOR = 'pb_books_as_editor';

	const BOOKS_AS_AUTHOR = 'pb_books_as_author';

	const BOOKS_AS_CONTRIBUTOR = 'pb_books_as_contributor';

	const BOOKS_AS_SUBSCRIBER = 'pb_books_as_subscriber';

	/**
	 * @var User
	 */
	private static $instance = null;

	/**
	 * @var array role => weight
	 */
	private $roles = [
		'subscriber' => 10,
		'contributor' => 20,
		'author' => 30,
		'editor' => 40,
		'administrator' => 50,
	];

	/**
	 * @return User
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param User $obj
	 */
	static public function hooks( User $obj ) {
		add_action( 'wp_login', [ $obj, 'setLastLogin' ], 0, 2 );
		add_action( 'wp_login', [ $obj, 'setSubscriberRole' ], 0, 2 );
		add_action( 'profile_update', [ $obj, 'updateMetaData' ] );
	}

	/**
	 * @return \Generator
	 */
	public function updateAllUsersMetadata(): \Generator {
		// Try to stop a Cache Stampede, Dog-Pile, Cascading Failure...
		$in_progress_transient = 'pb_user_sync_cron_in_progress';
		if ( ! get_transient( $in_progress_transient ) ) {
			set_transient( $in_progress_transient, 1, 15 * MINUTE_IN_SECONDS );

			set_time_limit( 0 );
			ini_set( 'memory_limit', -1 );
			ignore_user_abort( true );

			global $wpdb;
			$users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE spam = 0 AND spam = 0" );

			foreach ( $users as $user_id ) {
				$this->updateMetaData( $user_id );
				yield;
			}

			// Timestamp
			update_site_option( 'pb_user_sync_cron_timestamp', gmdate( 'Y-m-d H:i:s' ) );
			delete_transient( $in_progress_transient );
		}
	}

	public function updateMetaData( $user_id ) {
		global $wpdb;

		$metadata = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key REGEXP '{$wpdb->base_prefix}([0-9]+)_capabilities' AND user_id = %d", $user_id ) );

		$deleted_books = $this->getDeletedBooks();

		$totals = [
			'books' => 0,
			'administrator' => 0,
			'editor' => 0,
			'author' => 0,
			'contributor' => 0,
			'subscriber' => 0,
		];

		foreach ( $metadata as $meta ) {
			$book_id = $this->extractBookId( $meta->meta_key );

			// Skip books that are deleted.
			if ( array_key_exists( $book_id, $deleted_books ) ) {
				continue;
			}

			$highest_score_in_book = 0;
			$highest_role_in_book = null;

			$user_roles = maybe_unserialize( $meta->meta_value );

			// Skip if there are no roles.
			if ( ! is_iterable( $user_roles ) ) {
				continue;
			}

			foreach ( $user_roles as $role => $bool ) {
				$score = $this->roles[ $role ] ?? 0;

				if ( $score >= $highest_score_in_book ) {
					$highest_role_in_book = $role;
					$highest_score_in_book = $score;
				}
			}

			$totals['books'] += 1;
			$totals[ $highest_role_in_book ] += 1;
		}

		$highest_role = $this->getHighestRole( $totals );

		update_user_meta( $user_id, self::TOTAL_BOOKS, $totals['books'] );
		update_user_meta( $user_id, self::BOOKS_AS_ADMIN, $totals['administrator'] );
		update_user_meta( $user_id, self::BOOKS_AS_EDITOR, $totals['editor'] );
		update_user_meta( $user_id, self::BOOKS_AS_AUTHOR, $totals['author'] );
		update_user_meta( $user_id, self::BOOKS_AS_CONTRIBUTOR, $totals['contributor'] );
		update_user_meta( $user_id, self::BOOKS_AS_SUBSCRIBER, $totals['subscriber'] );
		update_user_meta( $user_id, self::HIGHEST_ROLE, $highest_role );
	}

	/**
	 * Add last login date to user meta
	 *
	 * Hooked into: wp_login
	 *
	 * @param string $user_login
	 * @param \WP_User $user
	 */
	public function setLastLogin( $user_login, $user ) {
		update_user_meta( $user->ID, self::LAST_LOGIN, gmdate( 'Y-m-d H:i:s' ) );
	}

	public function setSubscriberRole( $user_login, $user ) {
		$caps = $user->get_role_caps();
		if ( ! in_array( 'read', $caps, true ) ) {
			$user->add_role( 'subscriber' );
		}
	}

	/**
	 * Blog ids of archived, spam, & deleted books. Flipped.
	 *
	 * @return array
	 */
	private function getDeletedBooks() {
		global $wpdb;

		$deleted_books = $wpdb->get_col( "SELECT blog_id from {$wpdb->blogs} WHERE archived = 1 OR spam = 1 OR deleted = 1" );

		return is_array( $deleted_books ) ? array_flip( $deleted_books ) : [];
	}

	/**
	 * Get the highest role the user is associated with.
	 *
	 * @param array $roles
	 * @return false|int|string
	 */
	private function getHighestRole( array $roles ) {
		// Get all roles that the user is associated.
		$all_roles = array_filter(
			$roles, function ( $value ) {
				return $value;
			}
		);

		// Get the highest score based on the user roles.
		$highest_score = array_reduce(
			array_intersect_key( $this->roles, $all_roles ), function ( $carry, $value ) {
				return $value > $carry ? $value : $carry;
			}
		);

		return array_search( $highest_score, $this->roles, true ) ?: '';
	}

	/**
	 * @param string $key
	 * @return int|null
	 */
	private function extractBookId( $key ) {
		global $wpdb;

		preg_match( "~$wpdb->base_prefix(\d+)_capabilities~", $key, $matches );

		return $matches[1] ?? null;
	}

}
