<?php

namespace Pressbooks\Admin\Users;

use Pressbooks\Container;

class UserBulk {

	const SLUG = 'user_bulk_new';

	const PARENT_SLUG = 'users.php';

	const TEMPLATE = 'admin.user_bulk_new';

	/**
	 * @var UserBulk
	 */
	protected static $instance;

	/**
	 * @var Blade
	 */
	protected $blade;

	/**
	 * @return UserBulk
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param UserBulk $obj
	 */
	static public function hooks( UserBulk $obj ) {
		if ( \Pressbooks\Book::isBook() ) {
			add_action( 'admin_menu', [ $obj, 'addMenu' ] );
		}
	}

	public function __construct() {
		$this->blade = Container::get( 'Blade' );
	}

	/**
	 * Register 'Bulk add' submenu
	 */
	public function addMenu() {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Bulk Add', 'user' ),
			__( 'Bulk Add', 'user' ),
			'create_users',
			self::SLUG,
			[ $this, 'printMenu' ]
		);
	}

	/**
	 * Render menu template
	 */
	public function printMenu() {
		try {
			$results = $this->bulkAddUsers();
			if ( $results ) {
				echo $this->getBulkResultHtml( $results );
			}
		} catch ( \Exception $e ) {
			echo '<div id="message" role="alert" class="error notice is-dismissible"><p>' . $e->getMessage() . '</p></div>';
		}

		$html = $this->blade->render(
			self::TEMPLATE, [
				'form_url'  => self_admin_url( sprintf( '/users.php?page=%s', self::SLUG ) ),
				'nonce'     => self::SLUG,
			]
		);
		echo $html;
	}

	/**
	 * @return bool|array
	 */
	public function bulkAddUsers() {
		if ( empty( $_POST ) || empty( $_POST['role'] ) || empty( $_POST['users'] ) || ! check_admin_referer( self::SLUG ) ) {
			return false;
		}

		$_POST = array_map( 'trim', $_POST );
		$role = $_POST['role'];
		$emails_input = array_unique( preg_split( '/\r\n|\r|\n/', $_POST['users'] ) );
		$emails = array_map( 'sanitize_text_field', $emails_input );
		$results = [];

		foreach ( $emails as $email ) {
			$existing_user = get_user_by( 'email', $email );

			if ( false !== $existing_user ) {
				$result = add_existing_user_to_blog(
					[
						'user_id' => $existing_user->ID,
						'role'    => $role,
					]
				);
			} else {
				$result = $this->linkNewUserToBook( $email, $role );
			}

			array_push(
				$results, [
					'email'  => $email,
					'status' => $result,
				]
			);
		}

		return $results;
	}

	/**
	 * @param string $email
	 * @param string $role
	 * @return WP_Error|bool
	 */
	public function linkNewUserToBook( string $email, string $role ) {
		$user_details = $this->generateUserNameFromEmail( $email );

		if ( is_wp_error( $user_details['errors'] ) && $user_details['errors']->has_errors() ) {
			return $user_details['errors'];
		}

		$user_name = $user_details['user_name'];
		$unique_username = apply_filters( 'pre_user_login', $this->sanitizeUser( wp_unslash( $user_name ), true ) );

		// link newly created user to book
		wpmu_signup_user(
			$unique_username,
			$email,
			[
				'add_to_blog' => get_current_blog_id(),
				'new_role'    => $role,
			]
		);

		return true;
	}

	/**
	 * @param string $email
	 * @return array
	 */
	public function generateUserNameFromEmail( string $email ) {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return [ 'errors' => new \WP_Error( 'pb_email', __( 'Invalid email address', 'users' ) ) ];
		}

		$i = 1;
		$username = explode( '@', $email )[0];
		$unique_username = $this->sanitizeUser( $username );
		while ( username_exists( $unique_username ) ) {
			$unique_username = $this->sanitizeUser( "{$username}{$i}" );
			++$i;
		}

		$user_details = wpmu_validate_user_signup( $unique_username, $email );
		return $user_details;
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
	public function sanitizeUser( $username ) : string {
		$unique_username = sanitize_user( $username, true );
		$unique_username = strtolower( $unique_username );
		$unique_username = preg_replace( '/[^a-z0-9]/', '', $unique_username );

		if ( preg_match( '/^[0-9]*$/', $unique_username ) ) {
			$unique_username .= 'a'; // usernames must have letters too
		}

		$unique_username = str_pad( $unique_username, 4, '1' );

		return $unique_username;
	}

	/**
	 * @param array $results
	 * @return string
	 */
	public function getBulkResultHtml( array $results ) : string {
		$output_success = '';
		$output_errors = '';
		$success_subtitle = sprintf( '%s:%s', __( 'Users successfully added to this book', 'users' ), '<br />' );
		$error_subtitle = sprintf( '%s:%s', __( 'The following users could not be added', 'users' ), '<br />' );

		foreach ( $results as $result ) {
			if ( is_wp_error( $result['status'] ) ) {
				$error_messages = implode( ' ', $result['status']->get_error_messages() );
				$output_errors .= sprintf( '<b>%s</b>. %s %s', $result['email'], $error_messages, '<br />' );
			} else {
				$output_success .= sprintf( '<b>%s</b><br />', $result['email'] );
			}
		}

		$html_output = ! empty( $output_success ) ? sprintf( '<div role="status" id="bulk-success" class="updated notice is-dismissible"><p>%s%s</p></div>', $success_subtitle, $output_success ) : '';
		$html_output .= ! empty( $output_errors ) ? sprintf( '<div role="alert" id="bulk-errors" class="error notice is-dismissible"><p>%s%s</p></div>', $error_subtitle, $output_errors ) : '';
		return $html_output;
	}
}
