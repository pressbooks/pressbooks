<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\DataCollector;

class User {

	// Meta Key Constants:

	const LAST_LOGIN = 'pb_last_login';

	/**
	 * @var User
	 */
	private static $instance = null;

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
	}

	/**
	 *
	 */
	public function __construct() {
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

}
