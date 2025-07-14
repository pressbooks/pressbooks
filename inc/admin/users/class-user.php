<?php

namespace Pressbooks\Admin\Users;

use Pressbooks\Contributors;

class User {

	protected static ?User $instance = null;

	public static function init(): User {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	public static function hooks( User $obj ): void {
		add_filter( 'pre_user_login', [ $obj, 'sanitizeUsernameByEmail' ], 10, 1 );
		add_action( 'pb_new_blog', [ $obj, 'setBookAdminAsAuthor' ], 10 );
	}

	public function sanitizeUsernameByEmail( string $user_login ): string {
		if ( ! $this->isSiteNetworkScreen() ) {
			return $user_login;
		}

		$email = filter_var( INPUT_POST['blog']['email'] ?? '', FILTER_SANITIZE_EMAIL );

		$user_details = self::generateUserNameFromEmail( $email );

		if ( is_wp_error( $user_details['errors'] ) && $user_details['errors']->has_errors() ) {
			return $user_login;
		}

		return $user_details['user_name'];
	}

	private function isSiteNetworkScreen(): bool {
		global $current_screen;
		return $current_screen->id === 'site-new-network' && $current_screen->base === 'site-new-network';
	}

	/**
	 * @param string $email
	 * @return array
	 */
	public static function generateUserNameFromEmail( string $email ): array {
		$email = sanitize_email( $email );
		if ( ! $email ) {
			return [ 'errors' => new \WP_Error( 'pb_email', __( 'Invalid email address', 'pressbooks' ) ) ];
		}

		$i = 1;
		$username = explode( '@', $email )[0];
		$unique_username = self::sanitizeUser( $username );
		while ( username_exists( $unique_username ) ) {
			$unique_username = "{$username}{$i}";
			++$i;
		}

		return wpmu_validate_user_signup( $unique_username, $email );
	}

	/**
	 * Multisite has more restrictions on user login character set
	 *
	 * @see https://core.trac.wordpress.org/ticket/17904
	 *
	 * @param string $username
	 *
	 * @return string
	 */
	public static function sanitizeUser( string $username ) : string {
		$unique_username = sanitize_user( $username, true );
		$unique_username = strtolower( $unique_username );
		$unique_username = preg_replace( '/[^a-z0-9]/', '', $unique_username );

		if ( preg_match( '/^[0-9]*$/', $unique_username ) ) {
			$unique_username .= 'a'; // usernames must have letters too
		}

		return str_pad( $unique_username, 4, '1' );
	}

	/**
	 * Set the default book author to the specified admin user.
	 */
	public function setBookAdminAsAuthor(): void {
		if ( ! $this->isSiteNetworkScreen() ) {
			return;
		}

		$book_admin = get_users( [
			'role'    => 'administrator',
			'blog_id' => get_current_blog_id(),
		] );

		$contributors = new Contributors();
		$contributors->addBlogUser( $book_admin[0]->ID );

		$metadata_post_id = ( new \Pressbooks\Metadata )->getMetaPostId();

		$term = get_term_by( 'slug', $book_admin[0]->data->user_login, Contributors::TAXONOMY, ARRAY_A );

		$contributors->link( $term['term_id'], $metadata_post_id );

		$user_data = get_userdata( get_current_user_id() );
		$contributors->unlink( $user_data->user_nicename, $metadata_post_id );
	}
}
