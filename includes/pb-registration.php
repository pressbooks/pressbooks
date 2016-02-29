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
				$translated_text = __( 'Gimme a book!', 'pressbooks' );
				break;
            case 'Site Name:' :
            case 'Site Domain:' :
            	$translated_text = __( 'Web Book Address:', 'pressbooks' );
				break;
            case 'Site Title:' :
            	$translated_text = __( 'Book Title:', 'pressbooks' );
				break;
			case 'Site Language:' :
				$translated_text = __( 'Book Language:', 'pressbooks' );
				break;
			case 'Allow search engines to index this site.' :
				$translated_text = __( 'Allow search engines to index this book.', 'pressbooks' );
				break;
			case 'Create Site' :
				$translated_text = __( 'Create Book', 'pressbooks' );
				break;
			case 'Get <em>another</em> %s site in seconds' :
				$translated_text = __( 'Get <em>another</em> book in seconds', 'pressbooks' );
				break;
			case 'Welcome back, %s. By filling out the form below, you can <strong>add another site to your account</strong>. There is no limit to the number of sites you can have, so create to your heart&#8217;s content, but write responsibly!' :
				$translated_text = __( 'Welcome back, %s. By filling out the form below, you can <strong>add another book to your account</strong>. There is no limit to the number of books you can have, so create to your heart&#8217;s content, but write responsibly!', 'pressbooks' );
				break;
			case 'Sites you are already a member of:' :
				$translated_text = __( 'Books you are already a member of:', 'pressbooks' );
				break;
			case 'If you&#8217;re not going to use a great site domain, leave it for a new user. Now have at it!' :
				$translated_text = __( 'If you&#8217;re not going to use a great web book address, leave it for a new user. Now have at it!', 'pressbooks' );
				break;

      	}
    }

    return $translated_text;
}
