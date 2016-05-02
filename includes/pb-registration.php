<?php
/**
 * User and book registration
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Registration;

/**
 * Customize text on the user/book registration page
 *
 * @param string $translated_text The modified text string.
 * @param string $untranslated_text The original WordPress core text string.
 * @param string $domain The textdomain.
 * @return string $translated_text
 */

function custom_signup_text( $translated_text, $untranslated_text, $domain ) {

  global $pagenow;

  if ( $pagenow === 'wp-signup.php' ) {

    switch ( $untranslated_text ) {
      case 'Gimme a site!' :
        $translated_text = __( 'Register my book now', 'pressbooks' );
        break;
        case 'Just a username, please.' :
          $translated_text = __( 'Register my book later', 'pressbooks' );
          break;
      case 'Site Name:' :
      case 'Site Domain:' :
      	$translated_text = __( 'Web Book Address:', 'pressbooks' );
        break;
      case 'Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!' :
        $translated_text = __( 'Must be at least 4 characters, letters and numbers only. Your web book address <strong>cannot be changed</strong>, so choose carefully!', 'pressbooks' );
        break;
      case 'Site Title:' :
      	$translated_text = __( 'Book Title:', 'pressbooks' );
        break;
      case 'Site Language:' :
        $translated_text = __( 'Book Language:', 'pressbooks' );
        break;
      case 'Allow search engines to index this site.' :
        $translated_text = __( 'Would you like your web book to be visible to the public?', 'pressbooks' );
        break;
      case 'Create Site' :
      case 'Signup' :
        $translated_text = __( 'Create Book', 'pressbooks' );
        break;
      case 'Get your own %s account in seconds' :
        $translated_text = __( 'Register a %s account', 'pressbooks' );
        break;
      case 'Get <em>another</em> %s site in seconds' :
        $translated_text = __( 'Create a new book', 'pressbooks' );
        break;
      case 'Welcome back, %s. By filling out the form below, you can <strong>add another site to your account</strong>. There is no limit to the number of sites you can have, so create to your heart&#8217;s content, but write responsibly!' :
        $translated_text = __( 'Welcome, %s. Fill out the form below to <strong>add a new book to your account</strong>.', 'pressbooks' );
        break;
      case 'domain' :
      case 'sitename' :
        $translated_text = __( 'yourwebbookname', 'pressbooks' );
        break;
      case 'Sites you are already a member of:' :
        $translated_text = __( 'Books you are already a member of:', 'pressbooks' );
        break;
      case 'If you&#8217;re not going to use a great site domain, leave it for a new user. Now have at it!' :
        $translated_text = __( 'Your web book address must be at least 4 characters, letters and numbers only. It <strong>cannot be changed</strong>, so choose carefully!', 'pressbooks' );
        break;
      case 'Congratulations! Your new site, %s, is almost ready.' :
        $translated_text = __( 'Congratulations! Your new book, %s, is almost ready', 'pressbooks' );
        break;
      case 'But, before you can start using your site, <strong>you must activate it</strong>.' :
        $translated_text = __( 'But, before you can start writing your book, <strong>you must activate it</strong>.', 'pressbooks' );
        break;
      case 'Your account has been activated. You may now <a href="%1$s">log in</a> to the site using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.' :
        $translated_text = __( 'Your account has been activated. You may now <a href="%1$s">log in</a> to your book using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.', 'pressbooks' );
        break;
      case 'Your site at <a href="%1$s">%2$s</a> is active. You may now log in to your site using your chosen username of &#8220;%3$s&#8221;. Please check your email inbox at %4$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%5$s">reset your password</a>.' :
        $translated_text = __( 'Your book at <a href="%1$s">%2$s</a> is active. You may now log in to your book using your chosen username of &#8220;%3$s&#8221;. Please check your email inbox at %4$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%5$s">reset your password</a>.', 'pressbooks' );
        break;
      case 'Your account is now activated. <a href="%1$s">View your site</a> or <a href="%2$s">Log in</a>' :
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
	<?php if ( $error ) { ?><p class="error"><?php echo $error; ?></p><?php } ?>
	<input name="password_1" type="password" id="password_1" value="" autocomplete="off" maxlength="20"/><br/>
	<?php _e( 'Type in your password.', 'pressbooks' ); ?>

	<label for="password_2"><?php _e( 'Confirm Password', 'pressbooks' ); ?>:</label>
	<input name="password_2" type="password" id="password_2" value="" autocomplete="off" maxlength="20"/><br/>
	<?php _e( 'Type in your password again.', 'pressbooks' );
}

/**
 * Validate user submitted passwords
 *
 * @param array $content
 */
function validate_passwords( $content ) {

	$password_1 = isset( $_POST['password_1'] ) ? $_POST['password_1'] : '';
	$password_2 = isset( $_POST['password_2'] ) ? $_POST['password_2'] : '';

	if ( isset( $_POST['stage'] ) && $_POST['stage'] == 'validate-user-signup' ) {

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
	}

	return $content;
}

/**
 * Add password to temporary user meta
 *
 * @param array $meta
 */

function add_temporary_password( $meta ) {

	if ( isset( $_POST['password_1'] ) ) {

		// Store as base64 to avoid injections
		$add_meta = array( 'password' => ( isset( $_POST['password_1_base64'] ) ? $_POST['password_1'] : base64_encode( $_POST['password_1'] ) ) );
		$meta = array_merge( $add_meta, $meta );
	}

	// This should never happen.
	return $meta;
}

/**
 * Add hidden password field to blog registration page
 */

function add_hidden_password_field() {
	if ( isset( $_POST['password_1'] ) ) { ?><input type="hidden" name="password_1_base64" value="1" />
<input type="hidden" name="password_1" value="<?php echo ( isset( $_POST['password_1_base64'] ) ? $_POST['password_1'] : base64_encode( $_POST['password_1'] ) ); ?>" />
	<?php }
}

/**
 * Override wp_generate_password() once when we're generating our form
 */

function override_password_generation( $password ) {

	global $wpdb;

	// Check key in GET and then fallback to POST.
	if ( isset($_GET['key'] ) ) {
		$key = $_GET['key'];
	} elseif ( isset( $_POST['key'] ) ) {
		$key = $_POST['key'];
	} else {
		$key = null;
	}

	// Look for active signup
	$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = '%s'", $key ) );

	// Only override filter on wp-activate.php screen
	if ( strpos( $_SERVER['PHP_SELF'], 'wp-activate.php' ) && $key !== null && ( !( empty( $signup ) || $signup->active ) ) ) {
		$meta = maybe_unserialize( $signup->meta );
		if ( isset( $meta['password'] ) ) {

			// Set the "random" password to our predefined one
			$password = base64_decode( $meta['password'] );

			// Remove old password from signup meta
			unset( $meta['password'] );
			$meta = maybe_serialize( $meta );
			$wpdb->update( $wpdb->signups, array( 'meta' => $meta ), array( 'activation_key' => $key ), array( '%s' ), array( '%s' ) );
			return $password;
		} else {
			return $password; // No password meta set = just activate user as normal with random password
		}
	} else {
		return $password; // Regular usage, don't touch the password generation
	}
}
