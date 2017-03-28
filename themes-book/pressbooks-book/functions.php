<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

// Turn off admin bar
add_filter( 'show_admin_bar', function () { return false; } );

/**
 * Set up array of metadata keys for display in web book footer.
 */
global $metakeys;
$metakeys = array(
	'pb_author' => __( 'Author', 'pressbooks' ),
	'pb_contributing_authors' => __( 'Contributing Author', 'pressbooks' ),
 	'pb_publisher'  => __( 'Publisher', 'pressbooks' ),
	'pb_print_isbn'  => __( 'Print ISBN', 'pressbooks' ),
	'pb_keywords_tags'  => __( 'Keywords/Tags', 'pressbooks' ),
	'pb_publication_date'  => __( 'Publication Date', 'pressbooks' ),
	'pb_hashtag'  => __( 'Hashtag', 'pressbooks' ),
	'pb_ebook_isbn'  => __( 'Ebook ISBN', 'pressbooks' ),
);

/* ------------------------------------------------------------------------ *
 * Scripts and styles for Book Info Page (cover page)
 * ------------------------------------------------------------------------ */

function pressbooks_book_info_page () {

	if ( is_front_page() ) {
		wp_enqueue_style( 'pressbooks-book-info', get_template_directory_uri() . '/css/book-info.css', array(), '20130713', 'all' );
		wp_enqueue_style( 'book-info-fonts', 'https://fonts.googleapis.com/css?family=Droid+Serif:400,700|Oswald:300,400,700' );

		// Book info page Table of Content columns
		wp_enqueue_script( 'columnizer',  \Pressbooks\Utility\asset_path( 'scripts/columnizer.js' ), [ 'jquery' ] );
		wp_enqueue_script( 'columnizer-load', get_template_directory_uri() . '/js/columnizer-load.js', array( 'jquery', 'columnizer' ), '20130819', false );

		// Sharer.js
		wp_enqueue_script( 'sharer', \Pressbooks\Utility\asset_path( 'scripts/sharer.js' ) );
	}
}
add_action('wp_enqueue_scripts', 'pressbooks_book_info_page');

/* ------------------------------------------------------------------------ *
 * Asyncronous loading to improve speed of page load
 * ------------------------------------------------------------------------ */

function pressbooks_async_scripts( $tag, $handle, $src ) {
	$async = array(
		'pressbooks-script',
		'keyboard-nav',
		'pb-pop-out-toc',
		'pressbooks_toc_collapse',
		'pressbooks-accessibility',
		'columnizer',
		'columnizer-load',
		'sharer',
		'jquery-migrate',
	);

	if ( in_array( $handle, $async ) ) {
		return "<script async type='text/javascript' src='{$src}'></script>" . "\n";
	}

	return $tag;
}

add_filter( 'script_loader_tag', 'pressbooks_async_scripts', 10, 3 );

/* ------------------------------------------------------------------------ *
 * Register and enqueue scripts and stylesheets.
 * ------------------------------------------------------------------------ */
function pb_enqueue_scripts() {
	wp_enqueue_style( 'structure', PB_PLUGIN_URL . 'themes-book/pressbooks-book/css/structure.css', [], null, 'screen, print' );

	if ( pb_is_custom_theme() ) { // Custom CSS
		$deps = array();
		if ( ! pb_custom_stylesheet_imports_base() ) {
			// Use default stylesheet as base (to avoid horribly broken webbook)
			wp_register_style( 'pressbooks-book', PB_PLUGIN_URL . 'themes-book/pressbooks-book/style.css', array(), null, 'screen, print' );
			wp_enqueue_style( 'pressbooks-book' );
			$deps = array( 'pressbooks-book' );
		}
		wp_register_style( 'pressbooks-custom-css', pb_get_custom_stylesheet_url(), $deps, get_option( 'pressbooks_last_custom_css' ), 'screen' );
		wp_enqueue_style( 'pressbooks-custom-css' );
	} elseif ( get_stylesheet() == 'pressbooks-book' ) {
		$fullpath = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . '/style.css';
		if ( is_file( $fullpath ) ) {
			wp_register_style( 'pressbooks-theme', \Pressbooks\Container::get( 'Sass' )->urlToUserGeneratedCss() . '/style.css', array(), null, 'screen, print' );
			wp_enqueue_style( 'pressbooks-theme' );
		} else {
			wp_register_style( 'pressbooks', PB_PLUGIN_URL . 'themes-book/pressbooks-book/style.css', array(), null, 'screen, print' );
			wp_enqueue_style( 'pressbooks' );
		}
	} elseif ( get_stylesheet() !== 'pressbooks-book' ) { // If not pressbooks-book, we need to register and enqueue the theme stylesheet too
		wp_register_style( 'pressbooks', PB_PLUGIN_URL . 'themes-book/pressbooks-book/style.css', array(), null, 'screen, print' );
		$deps = array( 'pressbooks' );
		$fullpath = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . '/style.css';
		if ( is_file( $fullpath ) && \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 1 ) ) { // SASS theme & custom webbook style has been generated
			wp_register_style( 'pressbooks-theme', \Pressbooks\Container::get( 'Sass' )->urlToUserGeneratedCss() . '/style.css', $deps, null, 'screen, print' );
			wp_enqueue_style( 'pressbooks-theme' );
		} elseif ( is_file( $fullpath ) && \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) { // SASS theme & custom webbook style has been generated
			wp_register_style( 'pressbooks-theme', \Pressbooks\Container::get( 'Sass' )->urlToUserGeneratedCss() . '/style.css', $deps, null, 'screen, print' );
			wp_enqueue_style( 'pressbooks-theme' );
		} else { // Use the bundled stylesheet
			wp_register_style( 'pressbooks-theme', get_stylesheet_directory_uri() . '/style.css', $deps, null, 'screen, print' );
			wp_enqueue_style( 'pressbooks-theme' );
		}
	}

	if ( ! is_front_page() ) {
		wp_enqueue_script( 'pressbooks-script', get_template_directory_uri() . "/js/script.js", array( 'jquery' ), '1.0', false );
	}
	wp_enqueue_script( 'keyboard-nav', get_template_directory_uri() . '/js/keyboard-nav.js', array( 'jquery' ), '20130306', true );

	if ( is_single() ) {
		wp_enqueue_script( 'pb-pop-out-toc', get_template_directory_uri() . '/js/pop-out.js', array( 'jquery' ), '1.0', false );
	}

	wp_enqueue_script( 'pressbooks_toc_collapse',	get_template_directory_uri() . '/js/toc_collapse.js', array( 'jquery' ) );
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_script( 'pressbooks-accessibility', get_template_directory_uri() . '/js/a11y.js', array( 'jquery' ) );
	wp_enqueue_style( 'pressbooks-accessibility-toolbar', get_template_directory_uri() . '/css/a11y.css', array( 'dashicons' ), null, 'screen' );
}
add_action( 'wp_enqueue_scripts', 'pb_enqueue_scripts' );

/**
 * Update web book stylesheet.
 */

function pressbooks_update_webbook_stylesheet() {
	if ( false == \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 1 ) && false == \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
		return false;
	}

	if ( \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 1 ) ) {
		$inputs = array(
			get_stylesheet_directory() . '/_fonts-web.scss',
			get_stylesheet_directory() . '/_mixins.scss',
			get_stylesheet_directory() . '/style.scss',
		);
	} elseif ( \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
		$inputs = array(
			get_stylesheet_directory() . '/assets/styles/web/_fonts.scss',
			get_stylesheet_directory() . '/assets/styles/web/style.scss',
		);
		foreach ( glob( get_stylesheet_directory() . '/assets/styles/components/*.scss' ) as $import ) {
			$inputs[] = realpath( $import );
		}
	}

	$output = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . '/style.css';

	$recompile = false;

	foreach ( $inputs as $input ) {
		if ( filemtime( $input ) > filemtime( $output ) ) {
			$recompile = true;
			break;
		}
	}

	if ( true == $recompile ) {
		error_log( 'Updating web book stylesheet.' );
		\Pressbooks\Container::get( 'GlobalTypography' )->updateWebBookStyleSheet();
	} else {
		error_log( 'No update needed.' );
	}
}

if ( defined( 'WP_ENV' ) && 'development' == WP_ENV ) {
	add_action( 'template_redirect', 'pressbooks_update_webbook_stylesheet' );
}

/* ------------------------------------------------------------------------ *
 * Replaces the excerpt "more" text by a link
 * ------------------------------------------------------------------------ */

function new_pressbooks_excerpt_more($more) {
       global $post;
	return '<a class="more-tag" href="'. get_permalink($post->ID) . '"> Read more &raquo;</a>';
}
add_filter('excerpt_more', 'new_pressbooks_excerpt_more');

/**
 * Render Previous and next buttons
 *
 * @param bool $echo
 */
function pb_get_links($echo=true) {
  global $first_chapter, $prev_chapter, $next_chapter;
  $first_chapter = pb_get_first();
  $prev_chapter = pb_get_prev();
  $next_chapter = pb_get_next();
  if ($echo):
?><div class="nav">
  <?php if ($prev_chapter != '/') : ?>
	<span class="previous"><a href="<?php echo $prev_chapter; ?>"><?php _e('Previous', 'pressbooks'); ?></a></span>
  <?php endif; ?>
<!-- 	<h2 class="entry-title"><?php the_title(); ?></h2> -->
  <?php if ($next_chapter != '/') : ?>
	<span class="next"><a href="<?php echo $next_chapter ?>"><?php _e('Next', 'pressbooks'); ?></a></span>
  <?php endif; ?>
  </div><?php
  endif;
}


/**
 * Prevent access by unregistered user if the book in question is private.
 */
function pb_private() {
	$bloginfourl= get_bloginfo('url'); ?>
  <div <?php post_class(); ?>>

				<h2 class="entry-title denied-title"><?php _e('Access Denied', 'pressbooks'); ?></h2>
				<!-- Table of content loop goes here. -->
				<div class="entry_content denied-text"><?php _e('This book is private, and accessible only to registered users. If you have an account you can <a href="'. $bloginfourl .'/wp-login.php" class="login">login here</a>  <p class="sign-up">You can also set up your own Pressbooks book at: <a href="http://pressbooks.com">Pressbooks.com</a>.', 'pressbooks'); ?></p></div>
			</div><!-- #post-## -->
<?php
}


if ( ! function_exists( 'pressbooks_comment' ) ) :

/**
 * Template for comments and pingbacks.
 *
 * To override this walker in a child theme without modifying the comments template
 * simply create your own pressbooks_comment(), and that function will be used instead.
 *
 * Used as a callback by wp_list_comments() for displaying the comments.
 *
 */

function pressbooks_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case '' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
		<div class="comment-author vcard">
			<?php echo get_avatar( $comment, 40 ); ?>
			<?php printf( __( '%s on', 'pressbooks' ), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?> <?php printf( __( '%1$s at %2$s', 'pressbooks' ), get_comment_date(),  get_comment_time() ); ?> <span class="says">says:</span><?php edit_comment_link( __( '(Edit)', 'pressbooks' ), ' ' ); ?>
		</div><!-- .comment-author .vcard -->
		<?php if ( $comment->comment_approved == '0' ) : ?>
			<em><?php _e( 'Your comment is awaiting moderation.', 'pressbooks' ); ?></em>
			<br />
		<?php endif; ?>

		<div class="comment-body"><?php comment_text(); ?></div>

		<div class="reply">
			<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
		</div><!-- .reply -->
	</div><!-- #comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', 'pressbooks' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __('(Edit)', 'pressbooks'), ' ' ); ?></p>
	<?php
			break;
	endswitch;
}
endif;

/* ------------------------------------------------------------------------ *
 * Copyright License
 * ------------------------------------------------------------------------ */

function pressbooks_copyright_license() {

	$option = get_option( 'pressbooks_theme_options_global' );
	$book_meta = \Pressbooks\Book::getBookInformation();

	// if they don't want to see it, return
	// at minimum we need book copyright information set
	if ( isset ( $option['copyright_license'] ) && false == $option['copyright_license'] || !isset ( $option['copyright_license'] ) || !isset( $book_meta['pb_book_license'] ) ) {
		return '';
	}

	global $post;
	$id = $post->ID;
	$title = ( is_front_page() ) ? get_bloginfo('name') : $post->post_title ;
	$post_meta = get_post_meta( $id );
	$link = get_permalink( $id );
	$html = $license = $copyright_holder = '';
	$transient = get_transient("license-inf-$id" );
	$updated = array( $license, $copyright_holder, $title );
	$changed = false;
	$lang = $book_meta['pb_language'];


	// Copyright holder, set in order of precedence
	if ( isset( $post_meta['pb_section_author'] ) ) {
		// section author overrides book author, copyrightholder
		$copyright_holder = $post_meta['pb_section_author'][0] ;

	} elseif ( isset( $book_meta['pb_copyright_holder'] ) ) {
		// book copyright holder overrides book author
		$copyright_holder =  $book_meta['pb_copyright_holder'];

	} elseif ( isset( $book_meta['pb_author'] ) ) {
		// book author is the fallback, default
		$copyright_holder =  $book_meta['pb_author'];
	}

	// Copyright license, set in order of precedence
	if ( isset( $post_meta['pb_section_license'] ) ) {
		// section copyright overrides book
		$license = $post_meta['pb_section_license'][0];

	} elseif ( isset( $book_meta['pb_book_license'] ) ) {
		// book is the fallback, default
		$license = $book_meta['pb_book_license'];
	}

	 //delete_transient("license-inf-$id");
	 // check if the user has changed anything
	if ( is_array( $transient ) ) {
		foreach ( $updated as $val ) {
			if ( ! array_key_exists( $val, $transient ) ) {
				$changed = true;
			}
		}
	}
	// if the cache has expired, or the user changed the license
	if ( false === $transient || true == $changed ) {

		// get xml response from API
		$response = \Pressbooks\Metadata::getLicenseXml( $license, $copyright_holder, $link, $title, $lang );

		try {
			// convert to object
			$result = simplexml_load_string( $response );

			// evaluate it for errors
			if ( ! false === $result || ! isset( $result->html ) ) {
				throw new \Exception( 'Creative Commons license API not returning expected results at Pressbooks\Metadata::getLicenseXml' );
			} else {
				// process the response, return html
				$html = \Pressbooks\Metadata::getWebLicenseHtml( $result->html );
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}
		// store it with the license as a key
		$value = array(
		    $license => $html,
		    $copyright_holder => '',
		    $title => '',
		);
		// expires in 24 hours
		set_transient( "license-inf-$id", $value, 86400 );
	} else {
		$html = $transient[ $license ] ;
	}

	return $html;
}

/* ------------------------------------------------------------------------ *
 * Hooks, Actions and Filters
 * ------------------------------------------------------------------------ */

function pressbooks_theme_pdf_hacks( $hacks ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	$hacks['pdf_footnotes_style'] = $options['pdf_footnotes_style'];

	return $hacks;
}
add_filter( 'pb_pdf_hacks', 'pressbooks_theme_pdf_hacks' );


function pressbooks_theme_ebook_hacks( $hacks ) {

	// --------------------------------------------------------------------
	// Global Options

	$options = get_option( 'pressbooks_theme_options_global' );

	// Display chapter numbers?
	if ( $options['chapter_numbers'] ) {
		$hacks['chapter_numbers'] = true;
	}

	// --------------------------------------------------------------------
	// Ebook Options

	$options = get_option( 'pressbooks_theme_options_ebook' );

	// Compress images
	if ( $options['ebook_compress_images'] ) {
		$hacks['ebook_compress_images'] = true;
	}

	return $hacks;
}
add_filter( 'pb_epub_hacks', 'pressbooks_theme_ebook_hacks' );

function pressbooks_theme_add_metadata(){
	if ( is_front_page() ) {
		echo pb_get_seo_meta_elements();
		echo pb_get_microdata_elements();
	} else {
		echo pb_get_microdata_elements();
	}
}

add_action( 'wp_head', 'pressbooks_theme_add_metadata' );

function pressbooks_cover_promo() { ?>
	<?php if ( !defined( 'PB_HIDE_COVER_PROMO' ) || PB_HIDE_COVER_PROMO == false ) : ?>
	<a href="https://pressbooks.com" class="pressbooks-brand"><img src="<?php bloginfo('template_url'); ?>/images/pressbooks-branding-2x.png" alt="pressbooks-branding" width="186" height="123" /> <span><?php _e('Make your own books on Pressbooks', 'pressbooks'); ?></span></a>
	<?php else : ?>
	<div class="spacer"></div>
	<?php endif;
}

add_action( 'pb_cover_promo', 'pressbooks_cover_promo' );
