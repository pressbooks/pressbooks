<?php
/**
 * User and book registration
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Registration;

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
    }

  }

  return $translated_text;
}
