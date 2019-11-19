<?php
/**
 * User and book registration
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Registration;

use function Pressbooks\Sanitize\safer_unserialize;

/**
 * Customize text on the user/book registration page
 *
 * @param string $translated_text The modified text string.
 * @param string $untranslated_text The original WordPress core text string.
 * @param string $domain The textdomain.
 *
 * @return string $translated_text
 */

function custom_signup_text( $translated_text, $untranslated_text, $domain ) {

	global $pagenow;

	if ( 'wp-signup.php' === $pagenow ) {
		switch ( $untranslated_text ) {
			case 'Gimme a site!':
				$translated_text = __( 'Register my book now', 'pressbooks' );
				break;
			case 'Just a username, please.':
				$translated_text = __( 'Register my book later', 'pressbooks' );
				break;
			case 'Site Name:':
			case 'Site Domain:':
				$translated_text = __( 'Webbook Address:', 'pressbooks' );
				break;
			case 'Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!':
				$translated_text = __( 'Your webbook address is the web address where you will access and create your book. It must be at least 4 characters, letters and numbers only. It <strong>cannot be changed</strong>, so choose carefully! We suggest using the title of your book with no spaces.', 'pressbooks' );
				break;
			case 'Site Title:':
				$translated_text = __( 'Book Title:', 'pressbooks' );
				break;
			case 'Site Language:':
				$translated_text = __( 'Book Language:', 'pressbooks' );
				break;
			case 'Allow search engines to index this site.':
				$translated_text = __( 'Would you like your webbook to be visible to the public?', 'pressbooks' );
				break;
			case 'Create Site':
			case 'Signup':
				$translated_text = __( 'Create Book', 'pressbooks' );
				break;
			case 'Get your own %s account in seconds':
				$translated_text = __( 'Register a %s account', 'pressbooks' );
				break;
			case 'Get <em>another</em> %s site in seconds':
				$translated_text = __( 'Create a new book', 'pressbooks' );
				break;
			case 'Welcome back, %s. By filling out the form below, you can <strong>add another site to your account</strong>. There is no limit to the number of sites you can have, so create to your heart&#8217;s content, but write responsibly!':
				$translated_text = __( 'Welcome, %s. Fill out the form below to <strong>add a new book to your account</strong>.', 'pressbooks' );
				break;
			case 'domain':
			case 'sitename':
				$translated_text = __( 'yourwebbookname', 'pressbooks' );
				break;
			case 'Sites you are already a member of:':
				$translated_text = __( 'Books you are already a member of:', 'pressbooks' );
				break;
			case 'If you&#8217;re not going to use a great site domain, leave it for a new user. Now have at it!':
				$translated_text = __( 'Your webbook address is the web address where you will access and create your book. It must be at least 4 characters, letters and numbers only. It <strong>cannot be changed</strong>, so choose carefully! We suggest using the title of your book with no spaces.', 'pressbooks' );
				break;
			case 'Congratulations! Your new site, %s, is almost ready.':
				$translated_text = __( 'Congratulations! Your new book, %s, is almost ready', 'pressbooks' );
				break;
			case 'But, before you can start using your site, <strong>you must activate it</strong>.':
				$translated_text = __( 'But, before you can start writing your book, <strong>you must activate it</strong>.', 'pressbooks' );
				break;
			case 'Your account has been activated. You may now <a href="%1$s">log in</a> to the site using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.':
				$translated_text = __( 'Your account has been activated. You may now <a href="%1$s">log in</a> to your book using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.', 'pressbooks' );
				break;
			case 'Your site at <a href="%1$s">%2$s</a> is active. You may now log in to your site using your chosen username of &#8220;%3$s&#8221;. Please check your email inbox at %4$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%5$s">reset your password</a>.':
				$translated_text = __( 'Your book at <a href="%1$s">%2$s</a> is active. You may now log in to your book using your chosen username of &#8220;%3$s&#8221;. Please check your email inbox at %4$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%5$s">reset your password</a>.', 'pressbooks' );
				break;
			case 'Your account is now activated. <a href="%1$s">View your site</a> or <a href="%2$s">Log in</a>':
				$translated_text = __( 'Your account is now activated. <a href="%1$s">View your book</a> or <a href="%2$s">Log in</a>', 'pressbooks' );
				break;
		}
	}

	return $translated_text;
}

/**
 * Add fields to allow new users to specify a password upon registration
 *
 * @param object $errors
 */
function add_password_field( $errors ) {
	if ( $errors && method_exists( $errors, 'get_error_message' ) ) {
		$error = $errors->get_error_message( 'password_1' );
	} else {
		$error = false;
	} ?>

	<label for="password_1"><?php _e( 'Password', 'pressbooks' ); ?>:</label>
	<?php if ( $error ) { ?>
		<p class="error"><?php echo $error; ?></p>
	<?php } ?>
	<input name="password_1" type="password" id="password_1" value="<?php echo esc_attr( $_REQUEST['password_1'] ?? '' ); ?>" autocomplete="off" maxlength="20"/><br/>
	<?php _e( 'Type in your password.', 'pressbooks' ); ?>
	<label for="password_2"><?php _e( 'Confirm Password', 'pressbooks' ); ?>:</label>
	<input name="password_2" type="password" id="password_2" value="" autocomplete="off" maxlength="20"/><br/>
	<?php _e( 'Type in your password again.', 'pressbooks' ); ?>
	<?php _e( 'Password must be at least 12 characters in length, include at least one upper case letter, and have at least one number.', 'pressbooks' ); ?>

	<?php
}

/**
 * Validate user submitted passwords
 *
 * @param array $content
 *
 * @return array
 */
function validate_passwords( $content ) {
	if ( isset( $_POST['_signup_form'] ) && ! wp_verify_nonce( $_POST['_signup_form'], 'signup_form_' . $_POST['signup_form_id'] ) ) {
		wp_die( __( 'Please try again.', 'pressbooks' ) );
	}

	$password_1 = isset( $_POST['password_1'] ) ? $_POST['password_1'] : '';
	$password_2 = isset( $_POST['password_2'] ) ? $_POST['password_2'] : '';

	if ( isset( $_POST['stage'] ) && 'validate-user-signup' === $_POST['stage'] ) {

		// No primary password entered
		if ( trim( $password_1 ) === '' ) {
			$content['errors']->add( 'password_1', __( 'You have to enter a password.', 'pressbooks' ) );
			return $content;
		}

		// Passwords do not match
		if ( $password_1 !== $password_2 ) {
			$content['errors']->add( 'password_1', __( 'Passwords do not match.', 'pressbooks' ) );
			return $content;
		}

		// Check for strong password
		$strong_password_errors = check_for_strong_password( $password_1 );
		if ( ! empty( $strong_password_errors ) ) {
			$content['errors']->add( 'password_1', $strong_password_errors );
			return $content;
		}
	}

	return $content;
}

/**
 * Add password to temporary user meta
 *
 * @param array $meta
 *
 * @return array
 */
function add_temporary_password( $meta ) {
	if ( isset( $_POST['_signup_form'] ) && ! wp_verify_nonce( $_POST['_signup_form'], 'signup_form_' . $_POST['signup_form_id'] ) ) {
		wp_die( __( 'Please try again.', 'pressbooks' ) );
	}

	if ( isset( $_POST['password_1'] ) ) {
		$add_meta = [
			'password' => ( isset( $_POST['password_1_base64'] ) ? $_POST['password_1'] : put_in_storage( $_POST['password_1'] ) ),
		];
		$meta = array_merge( $add_meta, $meta );
	}

	return $meta;
}

/**
 * Add hidden password field to blog registration page
 */

function add_hidden_password_field() {
	if ( isset( $_POST['_signup_form'] ) && ! wp_verify_nonce( $_POST['_signup_form'], 'signup_form_' . $_POST['signup_form_id'] ) ) {
		wp_die( __( 'Please try again.', 'pressbooks' ) );
	}
	if ( isset( $_POST['password_1'] ) ) {
		?>
		<input type="hidden" name="password_1_base64" value="1"/>
		<input type="hidden" name="password_1" value="<?php echo( isset( $_POST['password_1_base64'] ) ? $_POST['password_1'] : put_in_storage( $_POST['password_1'] ) ); ?>"/>
		<?php
	}
}

/**
 * Override wp_generate_password() once when we're generating our form
 *
 * @param string $generated_password
 *
 * @return string
 */
function override_password_generation( $generated_password ) {
	if ( isset( $_POST['_signup_form'] ) && ! wp_verify_nonce( $_POST['_signup_form'], 'signup_form_' . $_POST['signup_form_id'] ) ) {
		wp_die( __( 'Please try again.', 'pressbooks' ) );
	}

	$activate_cookie = 'wp-activate-' . COOKIEHASH;
	if ( isset( $_REQUEST['key'] ) ) {
		$key = $_REQUEST['key'];
	} elseif ( isset( $_COOKIE[ $activate_cookie ] ) ) {
		$key = $_COOKIE[ $activate_cookie ];
	} else {
		return $generated_password; // Regular usage, don't touch the password generation
	}

	// Look for active signup
	global $wpdb;
	$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key ) );

	// Only override filter on wp-activate.php screen
	if ( strpos( $_SERVER['PHP_SELF'], 'wp-activate.php' ) !== false && $signup && ! $signup->active ) {
		$meta = safer_unserialize( $signup->meta );
		if ( is_array( $meta ) && isset( $meta['password'] ) ) {
			// Set the "random" password to our predefined one
			$password = unpack_from_storage( $meta['password'] );
			if ( ! empty( $password ) ) {
				// Remove old password from signup meta
				unset( $meta['password'] );
				$meta = maybe_serialize( $meta );
				$wpdb->update(
					$wpdb->signups,
					[ 'meta' => $meta ],
					[ 'activation_key' => $key ],
					'%s',
					'%s'
				);
				return $password;
			}
		}
	}
	return $generated_password;  // Regular usage, don't touch the password generation
}

/**
 * Hooked into activate_wp_head
 * WordPress prints the user's password on the screen, this hack hides it
 */
function hide_plaintext_password() {
	?>
	<style type="text/css">
		#signup-welcome p:nth-child(2) {
			visibility: hidden;
		}
	</style>
	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			var passwordField = $( '#signup-welcome p:nth-child(2)' );
			var passwordFieldText = $( '#signup-welcome p:nth-child(2) span' ).text();
			var passwordFieldValue = passwordField.html();
			var passwordFieldAsterix = '<span class="h3">' + passwordFieldText + '</span> #####';
			passwordField.html( passwordFieldAsterix ).css( 'visibility', 'visible' );
			passwordField.hover(
				function() {
					$( this ).html( passwordFieldValue );
					$( this ).css( 'cursor', 'pointer' );
				},
				function() {
					$( this ).html( passwordFieldAsterix );
					$( this ).css( 'cursor', 'auto' );
				}
			)
		} );
	</script>
	<?php
}

/**
 * @param string $data
 *
 * @return string
 */
function put_in_storage( $data ) {
	if ( function_exists( 'openssl_encrypt' ) ) {
		$method = 'aes-256-ctr';
		$iv_size = openssl_cipher_iv_length( $method );
		$iv = openssl_random_pseudo_bytes( $iv_size );
		$data = openssl_encrypt( $data, $method, NONCE_KEY, OPENSSL_RAW_DATA, $iv );
		$data = $iv . $data;  // For storage/transmission, we concatenate the IV and cipher text
	}
	return base64_encode( $data );
}

/**
 * @param string $data
 *
 * @return string
 */
function unpack_from_storage( $data ) {
	// Check if there are valid base64 characters
	if ( ! preg_match( '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data ) ) {
		return false;
	}
	// Decode the string in strict mode and check the results
	$decoded = base64_decode( $data, true );
	if ( false === $decoded ) {
		return false;
	}
	// Encode the string again
	if ( base64_encode( $decoded ) !== $data ) {
		return false;
	}

	if ( function_exists( 'openssl_decrypt' ) ) {
		$method = 'aes-256-ctr';
		$iv_size = openssl_cipher_iv_length( $method );
		$iv = substr( $decoded, 0, $iv_size );
		$data = @openssl_decrypt( substr( $decoded, $iv_size ), $method, NONCE_KEY, OPENSSL_RAW_DATA, $iv ); // @codingStandardsIgnoreLine
	}
	return $data;
}

/**
 * @param string $pwd
 *
 * @return string
 */
function check_for_strong_password( $pwd ) {
	$errors = '';

	if ( strlen( $pwd ) < 12 ) {
		$errors .= __( 'Password must be at least 12 characters.', 'pressbooks' ) . '<br>';
	}
	$uppercase = preg_match( '@[A-Z]@', $pwd );
	if ( ! $uppercase ) {
		$errors .= __( 'Password must include at least one upper case letter.', 'pressbooks' ) . '<br>';
	}
	$lowercase = preg_match( '@[a-z]@', $pwd );
	if ( ! $lowercase ) {
		$errors .= __( 'Password must include at least one lower case letter.', 'pressbooks' ) . '<br>';
	}
	$number = preg_match( '@[0-9]@', $pwd );
	if ( ! $number ) {
		$errors .= __( 'Password must include at least one number.', 'pressbooks' ) . '<br>';
	}

	return $errors;
}

/**
 * Modifies the html of sign up page
 */
function add_a11y() {

	echo '<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {

			//https://core.trac.wordpress.org/ticket/48657
			$(".mu_register.wp-signup-container").attr("role","main");

		} );
	</script>';

}
