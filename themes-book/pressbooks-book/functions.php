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
 * Register and enqueue scripts and stylesheets.
 * ------------------------------------------------------------------------ */
function pb_enqueue_scripts() {

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
	} else  {
		wp_register_style( 'pressbooks', PB_PLUGIN_URL . 'themes-book/pressbooks-book/style.css', array(), null, 'screen, print' );
		wp_enqueue_style( 'pressbooks' );
		// Use default stylesheet as base (to avoid horribly broken webbook)
		$deps = array( 'pressbooks' );
		if ( get_stylesheet() !== 'pressbooks-book' ) { // If not pressbooks-book, we need to register and enqueue the theme stylesheet too
			$fullpath = \Pressbooks\Container::get('Sass')->pathToUserGeneratedCss() . '/style.css';
			if ( is_file( $fullpath ) && \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 1 ) ) { // SASS theme & custom webbook style has been generated
				wp_register_style( 'pressbooks-theme', \Pressbooks\Container::get('Sass')->urlToUserGeneratedCss() . '/style.css', $deps, null, 'screen, print' );
				wp_enqueue_style( 'pressbooks-theme' );
			} elseif ( is_file( $fullpath ) && \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) { // SASS theme & custom webbook style has been generated
					wp_register_style( 'pressbooks-theme', \Pressbooks\Container::get('Sass')->urlToUserGeneratedCss() . '/style.css', $deps, null, 'screen, print' );
					wp_enqueue_style( 'pressbooks-theme' );
			} else { // Use the bundled stylesheet
				wp_register_style( 'pressbooks-theme', get_stylesheet_directory_uri() . '/style.css', $deps, null, 'screen, print' );
				wp_enqueue_style( 'pressbooks-theme' );
			}
		}
	}

	if (! is_front_page() ) {
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
		$html = $transient[$license] ;
	}

	return $html;
}

/* ------------------------------------------------------------------------ *
 * Theme Options Display (Appearance -> Theme Options)
 * ------------------------------------------------------------------------ */

if ( ! function_exists( 'pressbooks_theme_options_display' ) ) :

/**
 * Function called by the Pressbooks plugin when user is on [ Appearance → Theme Options ] page
 */
function pressbooks_theme_options_display() { ?>
	<div class="wrap">
		<div id="icon-themes" class="icon32"></div>
		<h2><?php echo wp_get_theme(); ?> <?php _e('Theme Options', 'pressbooks'); ?></h2>
		<?php settings_errors(); ?>
		<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'global_options'; ?>
		<h2 class="nav-tab-wrapper">
		<a href="?page=pressbooks_theme_options&tab=global_options" class="nav-tab <?php echo $active_tab == 'global_options' ? 'nav-tab-active' : ''; ?>">Global Options</a>
		<a href="?page=pressbooks_theme_options&tab=web_options" class="nav-tab <?php echo $active_tab == 'web_options' ? 'nav-tab-active' : ''; ?>">Web Options</a>
		<?php if( true == \Pressbooks\Utility\check_prince_install() ){ ?>
		<a href="?page=pressbooks_theme_options&tab=pdf_options" class="nav-tab <?php echo $active_tab == 'pdf_options' ? 'nav-tab-active' : ''; ?>">PDF Options</a>
		<?php } ;?>
		<?php if ( true == \Pressbooks\Modules\Export\Mpdf\Pdf::isInstalled() ) { ?>
		<a href="?page=pressbooks_theme_options&tab=mpdf_options" class="nav-tab <?php echo $active_tab == 'mpdf_options' ? 'nav-tab-active' : ''; ?>">mPDF Options</a>
		<?php } ?>
		<a href="?page=pressbooks_theme_options&tab=ebook_options" class="nav-tab <?php echo $active_tab == 'ebook_options' ? 'nav-tab-active' : ''; ?>">Ebook Options</a>
		</h2>
		<!-- Create the form that will be used to render our options -->
		<form method="post" action="options.php">
			<?php if( $active_tab == 'global_options' ) {
				settings_fields( 'pressbooks_theme_options_global' );
				do_settings_sections( 'pressbooks_theme_options_global' );
			} elseif( $active_tab == 'web_options' ) {
				settings_fields( 'pressbooks_theme_options_web' );
				do_settings_sections( 'pressbooks_theme_options_web' );
			} elseif( $active_tab == 'pdf_options' ) {
				settings_fields( 'pressbooks_theme_options_pdf' );
				do_settings_sections( 'pressbooks_theme_options_pdf' );
			} elseif( $active_tab == 'mpdf_options' ) {
				settings_fields( 'pressbooks_theme_options_mpdf' );
				do_settings_sections( 'pressbooks_theme_options_mpdf' );
			} elseif( $active_tab == 'ebook_options' ) {
				settings_fields( 'pressbooks_theme_options_ebook' );
				do_settings_sections( 'pressbooks_theme_options_ebook' );
			} ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

endif;


/* ------------------------------------------------------------------------ *
 * Global Options Tab
 * ------------------------------------------------------------------------ */

// Global Options Registration
function pressbooks_theme_options_global_init() {

	$_page = $_option = 'pressbooks_theme_options_global';
	$_section = 'global_options_section';
	$defaults = array(
		'chapter_numbers' => 1,
	);

	if ( false == get_option( $_option ) ) {
		add_option( $_option, $defaults );
	}

	add_settings_section(
		$_section,
		__( 'Global Options', 'pressbooks' ),
		'pressbooks_theme_options_global_callback',
		$_page
	);

	add_settings_field(
		'chapter_numbers',
		__( 'Chapter Numbers', 'pressbooks' ),
		'pressbooks_theme_chapter_numbers_callback',
		$_page,
		$_section,
		array(
			 __( 'Display chapter numbers', 'pressbooks' )
		)
	);

	add_settings_field(
		'parse_sections',
		__( 'Two-Level TOC', 'pressbooks' ),
		'pressbooks_theme_parse_sections_callback',
		$_page,
		$_section,
		array(
			 __( 'Enable two-level table of contents (displays headings under chapter titles)', 'pressbooks' )
		)
	);

	if ( pb_is_scss( 1 ) == true || pb_is_scss( 2 ) == true ) { // we can only enable foreign language typography for themes that use SCSS

		add_settings_field(
			'pressbooks_global_typography',
			__( 'Language Support', 'pressbooks' ),
			'pressbooks_theme_global_typography_callback',
			$_page,
			$_section,
			array(
				 __( 'Include fonts to support the following languages:', 'pressbooks' )
			)
		);

		register_setting(
			$_page,
			'pressbooks_global_typography',
			'pressbooks_theme_pressbooks_global_typography_sanitize'
		);

	}

	add_settings_field(
		'copyright_license',
		__( 'Copyright License', 'pressbooks' ),
		'pressbooks_theme_copyright_license_callback',
		$_page,
		$_section,
		array(
			 __( 'Display the selected copyright license', 'pressbooks' )
		)
	);

	register_setting(
		$_page,
		$_option,
		'pressbooks_theme_options_global_sanitize'
	);

}
add_action('admin_init', 'pressbooks_theme_options_global_init');


// Global Options Section Callback
function pressbooks_theme_options_global_callback() {
	echo '<p>' . __( 'These options apply universally to webbook, PDF and ebook exports.', 'pressbooks' ) . '</p>';
}


// Global Options Field Callback
function pressbooks_theme_chapter_numbers_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! isset( $options['chapter_numbers'] ) ) {
		$options['chapter_numbers'] = 1;
	}

	$html = '<input type="checkbox" id="chapter_numbers" name="pressbooks_theme_options_global[chapter_numbers]" value="1" ' . checked( 1, $options['chapter_numbers'], false ) . '/>';
	$html .= '<label for="chapter_numbers"> ' . $args[0] . '</label>';
	echo $html;
}


// Global Options Field Callback
function pressbooks_theme_parse_sections_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! isset( $options['parse_sections'] ) ) {
		$options['parse_sections'] = 0;
	}

	$html = '<input type="checkbox" id="parse_sections" name="pressbooks_theme_options_global[parse_sections]" value="1" ' . checked( 1, $options['parse_sections'], false ) . '/>';
	$html .= '<label for="parse_sections"> ' . $args[0] . '</label>';
	echo $html;
}

// Global Options Field Callback
function pressbooks_theme_copyright_license_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! isset( $options['copyright_license'] ) ) {
		$options['copyright_license'] = 0;
	}

	$html = '<input type="checkbox" id="copyright_license" name="pressbooks_theme_options_global[copyright_license]" value="1" ' . checked( 1, $options['copyright_license'], false ) . '/>';
	$html .= '<label for="copyright_license"> ' . $args[0] . '</label>';
	echo $html;
}

// Global Options Field Callback
function pressbooks_theme_global_typography_callback( $args ) {

	$foreign_languages = get_option( 'pressbooks_global_typography' );

	if ( ! $foreign_languages ) {
		$foreign_languages = array();
	}

	$languages = \Pressbooks\Container::get( 'GlobalTypography' )->getSupportedLanguages();

	$already_supported_languages = \Pressbooks\Container::get( 'GlobalTypography' )->getThemeSupportedLanguages();

	if ( $already_supported_languages == false ) {
		$already_supported_languages = [];
	}

	$already_supported_languages_string = '';

	$i = 1;
	$c = count( $already_supported_languages );
	foreach ( $already_supported_languages as $lang ) {
		$already_supported_languages_string .= $languages[ $lang ];
		if ( $i < $c && $i == $c - 1 ) {
			$already_supported_languages_string .= ' ' . __( 'and', 'pressbooks' ) . ' ';
		} elseif ( $i < $c ) {
			$already_supported_languages_string .= ', ';
		}
		unset( $languages[ $lang ] );
		$i++;
	}

	$html = '<label for="global_typography"> ' . $args[0] . '</label><br /><br />';
	$html .= '<select id="global_typography" class="select2" style="width: 75%" data-placeholder="' . __( 'Select languages&hellip;', 'pressbooks' ) . '" name="pressbooks_global_typography[]" multiple>';
	foreach ( $languages as $key => $value ) {
		$selected = ( in_array( $key, $foreign_languages ) || in_array( $key, $already_supported_languages ) ) ? ' selected' : '';
		$html .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
	}
	$html .= '</select>';

	if ( $already_supported_languages_string ) {
		$html .= '<br /><br />' . sprintf( __( 'This theme includes built-in support for %s.', 'pressbooks' ), $already_supported_languages_string );
	}

	echo $html;
}

// Global Options Input Sanitization
function pressbooks_theme_options_global_sanitize( $input ) {

	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! isset( $input['chapter_numbers'] ) || $input['chapter_numbers'] != '1' ) {
		$options['chapter_numbers'] = 0;
	} else {
		$options['chapter_numbers'] = 1;
	}

	if ( ! isset( $input['parse_sections'] ) || $input['parse_sections'] != '1' ) {
		$options['parse_sections'] = 0;
	} else {
		$options['parse_sections'] = 1;
	}

	if ( ! isset( $input['copyright_license'] ) || $input['copyright_license'] != '1' ) {
		$options['copyright_license'] = 0;
	} else {
		$options['copyright_license'] = 1;
	}

	return $options;
}

function pressbooks_theme_pressbooks_global_typography_sanitize( $input ) {
	if ( !is_array( $input ) ) {
		$input = array();
	}
	return $input;
}

/* ------------------------------------------------------------------------ *
 * Web Options Tab
 * ------------------------------------------------------------------------ */

function pressbooks_theme_options_web_init() {

	$_page = $_option = 'pressbooks_theme_options_web';
	$_section = 'web_options_section';
	$defaults = array(
	);

	if ( false == get_option( $_option ) ) {
		add_option( $_option, $defaults );
	}

	add_settings_section(
		$_section,
		__( 'Web Options', 'pressbooks' ),
		'pressbooks_theme_options_web_callback',
		$_page
	);

	add_settings_field(
		'social_media_buttons',
		__( 'Enable Social Media', 'pressbooks' ),
		'pressbooks_theme_social_media_callback',
		$_page,
		$_section,
		array(
		    __('Add buttons to cover page and each chapter so that readers may share links to your book through social media: Facebook, Twitter, Google+', 'pressbooks' )
		)
	);
	register_setting(
		$_option,
		$_option,
		'pressbooks_theme_options_web_sanitize'
	);
}

// Web Options Section Callback
function pressbooks_theme_options_web_callback() {
	echo '<p>' . __( 'These options apply to the webbook.', 'pressbooks' ) . '</p>';
}

// Web Options Field Callback
function pressbooks_theme_social_media_callback( $args ) {
	$options = get_option( 'pressbooks_theme_options_web' );

	if ( ! isset( $options['social_media'] ) ) {
		$options['social_media'] = 1;
	}
	$html = '<input type="checkbox" id="social_media" name="pressbooks_theme_options_web[social_media]" value="1" ' . checked( 1, $options['social_media'], false ) . '/>';
	$html .= '<label for="social_media"> ' . $args[0] . '</label>';
	echo $html;
}

// Web Options Sanitize
function pressbooks_theme_options_web_sanitize( $input ) {

	$options = get_option( 'pressbooks_theme_options_web' );

	if ( ! isset( $input['social_media'] ) || $input['social_media'] != '1' ) {
		$options['social_media'] = 0;
	} else {
		$options['social_media'] = 1;
	}

	return $options;
}

add_action( 'admin_init', 'pressbooks_theme_options_web_init' );

/* ------------------------------------------------------------------------ *
 * PDF Options Tab
 * ------------------------------------------------------------------------ */

use Pressbooks\CustomCss;

// PDF Options Registration
function pressbooks_theme_options_pdf_init() {

	$_page = $_option = 'pressbooks_theme_options_pdf';
	$_section = 'pdf_options_section';
	$defaults = array(
		'pdf_paragraph_separation' => 1,
		'pdf_blankpages' => 1,
		'pdf_toc' => 1,
		'pdf_romanize_parts' => 0,
		'pdf_footnotes_style' => 1,
		'pdf_crop_marks' => 0,
		'pdf_hyphens' => 0,
		'widows' => 2,
		'orphans' => 1,
		'pdf_fontsize' => 0,
	);

	if ( false == get_option( $_option ) ) {
		add_option( $_option, $defaults );
	}

	add_settings_section(
		$_section,
		__( 'PDF Options', 'pressbooks' ),
		'pressbooks_theme_options_pdf_callback',
		$_page
	);

	if ( pb_is_scss( 2 ) ) {
		add_settings_field(
			'pdf_body_font_size',
			__( 'Body Font Size', 'pressbooks' ),
			'pressbooks_theme_pdf_body_font_size_callback',
			$_page,
			$_section,
			array(
				__( 'Heading sizes are proportional to the body font size and will also be affected by this setting.', 'pressbooks' )
			)
		);

		add_settings_field(
			'pdf_body_line_height',
			__( 'Body Line Height', 'pressbooks' ),
			'pressbooks_theme_pdf_body_line_height_callback',
			$_page,
			$_section,
			array()
		);
	}

	$pagesizes = array(
		 __( 'Digest (5.5&quot; &times; 8.5&quot;)', 'pressbooks' ),
		 __( 'US Trade (6&quot; &times; 9&quot;)', 'pressbooks' ),
		 __( 'US Letter (8.5&quot; &times; 11&quot;)', 'pressbooks' ),
		 __( 'Custom (8.5&quot; &times; 9.25&quot;)', 'pressbooks' ),
		 __( 'Duodecimo (5&quot; &times; 7.75&quot;)', 'pressbooks' ),
		 __( 'Pocket (4.25&quot; &times; 7&quot;)', 'pressbooks' ),
		 __( 'A4 (21cm &times; 29.7cm)', 'pressbooks' ),
		 __( 'A5 (14.8cm &times; 21cm)', 'pressbooks' ),
		 __( '5&quot; &times; 8&quot;', 'pressbooks' ),
		 __( 'Custom…', 'pressbooks' )
	);

	add_settings_field(
		'pdf_page_size',
		__( 'Page Size', 'pressbooks' ),
		'pressbooks_theme_pdf_page_size_callback',
		$_page,
		$_section,
		$pagesizes
	);

	add_settings_field(
		'pdf_page_width',
		__( 'Page Width', 'pressbooks' ),
		'pressbooks_theme_pdf_page_width_callback',
		$_page,
		$_section,
		array(
			__( 'Page width must be expressed in CSS-compatible units, e.g. &lsquo;5.5in&rsquo;.')
		)
	);

	add_settings_field(
		'pdf_page_height',
		__( 'Page Height', 'pressbooks' ),
		'pressbooks_theme_pdf_page_height_callback',
		$_page,
		$_section,
		array(
			__( 'Page height must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo;.')
		)
	);

	add_settings_field(
		'pdf_hyphens',
		__( 'Hyphens', 'pressbooks' ),
		'pressbooks_theme_pdf_hyphens_callback',
		$_page,
		$_section,
		array(
			 __( 'Enable hyphenation', 'pressbooks' )
		)
	);

	add_settings_field(
		'pdf_paragraph_separation',
		__( 'Paragraph Separation', 'pressbooks' ),
		'pressbooks_theme_pdf_paragraph_separation_callback',
		$_page,
		$_section,
		array(
			 __( 'Indent paragraphs', 'pressbooks' ),
			 __( 'Skip lines between paragraphs', 'pressbooks' )
		)
	);

	add_settings_field(
		'pdf_blankpages',
		__( 'Blank Pages', 'pressbooks' ),
		'pressbooks_theme_pdf_blankpages_callback',
		$_page,
		$_section,
		array(
			 __( 'Include blank pages (for print PDF)', 'pressbooks' ),
			 __( 'Remove all blank pages (for web PDF)', 'pressbooks' )
		)
	);

	add_settings_field(
		'pdf_toc',
		__( 'Table of Contents', 'pressbooks' ),
		'pressbooks_theme_pdf_toc_callback',
		$_page,
		$_section,
		array(
			 __( 'Display table of contents', 'pressbooks' )
		)
	);

	add_settings_field(
		'pdf_image_resolution',
		__( 'Image resolution', 'pressbooks' ),
		'pressbooks_theme_pdf_image_resolution_callback',
		$_page,
		$_section,
		array(
			'300dpi' => __( 'High (300 DPI)', 'pressbooks' ),
			'72dpi' => __( 'Low (72 DPI)', 'pressbooks' )
		)
	);

	add_settings_field(
		'pdf_crop_marks',
		__( 'Crop Marks', 'pressbooks' ),
		'pressbooks_theme_pdf_crop_marks_callback',
		$_page,
		$_section,
		array(
			 __( 'Display crop marks', 'pressbooks' )
		)
	);

	if ( CustomCss::isCustomCss() ) {
		add_settings_field(
			'pdf_romanize_parts',
			__( 'Romanize Part Numbers', 'pressbooks' ),
			'pressbooks_theme_pdf_romanize_parts_callback',
			$_page,
			$_section,
			array(
				 __( 'Convert part numbers into Roman numerals', 'pressbooks' )
			)
		);
	}

	add_settings_field(
		'pdf_footnotes_style',
		__( 'Footnotes Style', 'pressbooks' ),
		'pressbooks_theme_pdf_footnotes_callback',
		$_page,
		$_section,
		array(
			 __( 'Regular footnotes', 'pressbooks' ),
			 __( 'Display as chapter endnotes', 'pressbooks' )
		)
	);
	add_settings_field(
		'widows',
		__( 'Widows', 'pressbooks' ),
		'pressbooks_theme_pdf_widows_callback',
		$_page,
		$_section
	);
	add_settings_field(
		'orphans',
		__( 'Orphans', 'pressbooks' ),
		'pressbooks_theme_pdf_orphans_callback',
		$_page,
		$_section
	);

	if ( ! pb_is_scss( 2 ) ) {
	 	add_settings_field(
			'pdf_fontsize',
			__( 'Increase Font Size', 'pressbooks' ),
			'pressbooks_theme_pdf_fontsize_callback',
			$_page,
			$_section,
			array(
			    __('Increases font size and line height for greater accessibility', 'pressbooks' )
			)
		);
	}

	register_setting(
		$_option,
		$_option,
		'pressbooks_theme_options_pdf_sanitize'
	);
}
add_action( 'admin_init', 'pressbooks_theme_options_pdf_init' );


// PDF Options Section Callback
function pressbooks_theme_options_pdf_callback() {
	echo '<p>' . __( 'These options apply to PDF exports.', 'pressbooks' ) . '</p>';
}

// PDF Options Field Callback
function pressbooks_theme_pdf_body_font_size_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_body_font_size'] ) ) {
		$options['pdf_body_font_size'] = 11;
	}

	$html = '<input type="text" id="pdf_body_font_size" name="pressbooks_theme_options_pdf[pdf_body_font_size]" value="' . $options['pdf_body_font_size'] . '" size="3" /> pt';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_pdf_body_line_height_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_body_line_height'] ) ) {
		$options['pdf_body_line_height'] = 1.4;
	}

	$html = '<input type="text" id="pdf_body_line_height" name="pressbooks_theme_options_pdf[pdf_body_line_height]" value="' . $options['pdf_body_line_height'] . '" size="3" /> em';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_pdf_page_size_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_page_size'] ) ) {
		if ( isset( $options['pdf_page_width'] ) && isset( $options['pdf_page_height'] ) ) {
			if ( $options['pdf_page_width'] == '5.5in' && $options['pdf_page_height'] == '8.5in' ) {
				$options['pdf_page_size'] = 1;
			} elseif ( $options['pdf_page_width'] == '6in' && $options['pdf_page_height'] == '9in' ) {
				$options['pdf_page_size'] = 2;
			} elseif ( $options['pdf_page_width'] == '8.5in' && $options['pdf_page_height'] == '11in' ) {
				$options['pdf_page_size'] = 3;
			} elseif ( $options['pdf_page_width'] == '8.5in' && $options['pdf_page_height'] == '9.25in' ) {
				$options['pdf_page_size'] = 4;
			} elseif ( $options['pdf_page_width'] == '5in' && $options['pdf_page_height'] == '7.75in' ) {
				$options['pdf_page_size'] = 5;
			} elseif ( $options['pdf_page_width'] == '4.25in' && $options['pdf_page_height'] == '7in' ) {
				$options['pdf_page_size'] = 6;
			} elseif ( $options['pdf_page_width'] == '21cm' && $options['pdf_page_height'] == '29.7cm' ) {
				$options['pdf_page_size'] = 7;
			} elseif ( $options['pdf_page_width'] == '14.8cm' && $options['pdf_page_height'] == '21cm' ) {
				$options['pdf_page_size'] = 8;
			} elseif ( $options['pdf_page_width'] == '5in' && $options['pdf_page_height'] == '8in' ) {
				$options['pdf_page_size'] = 9;
			} else {
				$options['pdf_page_size'] = 10;
			}
		} else {
			$options['pdf_page_size'] = 1;
		}
	}

	$html = "<select name='pressbooks_theme_options_pdf[pdf_page_size]' id='pdf_page_size' >";
	foreach ( $args as $key => $val ) {
		$html .= "<option value='" . ( $key + 1 ) . "' " . selected( $key + 1, $options['pdf_page_size'], false ) . ">$val</option>";
	}
	$html .= '<select>';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_pdf_page_width_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_page_width'] ) ) {
		$options['pdf_page_width'] = '5.5in';
	}

	$html = '<input type="text" id="pdf_page_width" name="pressbooks_theme_options_pdf[pdf_page_width]" value="' . $options['pdf_page_width'] . '" size="4" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_page_height_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_page_height'] ) ) {
		$options['pdf_page_height'] = '8.5in';
	}

	$html = '<input type="text" id="pdf_page_height" name="pressbooks_theme_options_pdf[pdf_page_height]" value="' . $options['pdf_page_height'] . '" size="4" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_paragraph_separation_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_paragraph_separation'] ) ) {
		$options['pdf_paragraph_separation'] = 1;
	}

	$html = '<input type="radio" id="paragraph_indent" name="pressbooks_theme_options_pdf[pdf_paragraph_separation]" value="1"' . checked( 1, $options['pdf_paragraph_separation'], false ) . '/> ';
	$html .= '<label for="paragraph_indent">' . $args[0] . '</label><br />';
	$html .= '<input type="radio" id="paragraph_skiplines" name="pressbooks_theme_options_pdf[pdf_paragraph_separation]" value="2"' . checked( 2, $options['pdf_paragraph_separation'], false ) . '/> ';
	$html .= '<label for="paragraph_skiplines">' . $args[1] . '</label>';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_pdf_image_resolution_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_image_resolution'] ) ) {
		$options['pdf_image_resolution'] = '300dpi';
	}

	$html = '<input type="radio" id="include" name="pressbooks_theme_options_pdf[pdf_image_resolution]" value="300dpi"' . checked( '300dpi', $options['pdf_image_resolution'], false ) . '/> ';
	$html .= '<label for="include">' . $args['300dpi'] . '</label><br />';
	$html .= '<input type="radio" id="remove" name="pressbooks_theme_options_pdf[pdf_image_resolution]" value="72dpi"' . checked( '72dpi', $options['pdf_image_resolution'], false ) . '/> ';
	$html .= '<label for="remove">' . $args['72dpi'] . '</label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_blankpages_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_blankpages'] ) ) {
		$options['pdf_blankpages'] = 1;
	}

	$html = '<input type="radio" id="include" name="pressbooks_theme_options_pdf[pdf_blankpages]" value="1"' . checked( 1, $options['pdf_blankpages'], false ) . '/> ';
	$html .= '<label for="include">' . $args[0] . '</label><br />';
	$html .= '<input type="radio" id="remove" name="pressbooks_theme_options_pdf[pdf_blankpages]" value="2"' . checked( 2, $options['pdf_blankpages'], false ) . '/> ';
	$html .= '<label for="remove">' . $args[1] . '</label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_toc_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_toc'] ) ) {
		$options['pdf_toc'] = 1;
	}

	$html = '<input type="checkbox" id="pdf_toc" name="pressbooks_theme_options_pdf[pdf_toc]" value="1" ' . checked( 1, $options['pdf_toc'], false ) . '/>';
	$html .= '<label for="pdf_toc"> ' . $args[0] . '</label>';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_pdf_romanize_parts_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_romanize_parts'] ) ) {
		$options['pdf_romanize_parts'] = 0;
	}

	$html = '<input type="checkbox" id="pdf_romanize_parts" name="pressbooks_theme_options_pdf[pdf_romanize_parts]" value="1" ' . checked( 1, $options['pdf_romanize_parts'], false ) . '/>';
	$html .= '<label for="pdf_romanize_parts"> ' . $args[0] . '</label>';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_pdf_footnotes_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_footnotes_style'] ) ) {
		$options['pdf_footnotes_style'] = 1;
	}

	$html = '<input type="radio" id="footnotes" name="pressbooks_theme_options_pdf[pdf_footnotes_style]" value="1"' . checked( 1, $options['pdf_footnotes_style'], false ) . '/> ';
	$html .= '<label for="footnotes">' . $args[0] . '</label><br />';
	$html .= '<input type="radio" id="endnotes" name="pressbooks_theme_options_pdf[pdf_footnotes_style]" value="2"' . checked( 2, $options['pdf_footnotes_style'], false ) . '/> ';
	$html .= '<label for="endnotes">' . $args[1] . '</label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_crop_marks_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_crop_marks'] ) ) {
		$options['pdf_crop_marks'] = 0;
	}

	$html = '<input type="checkbox" id="pdf_crop_marks" name="pressbooks_theme_options_pdf[pdf_crop_marks]" value="1" ' . checked( 1, $options['pdf_crop_marks'], false ) . '/>';
	$html .= '<label for="pdf_crop_marks"> ' . $args[0] . '</label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_hyphens_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_hyphens'] ) ) {
		$options['pdf_hyphens'] = 0;
	}

	$html = '<input type="checkbox" id="pdf_hyphens" name="pressbooks_theme_options_pdf[pdf_hyphens]" value="1" ' . checked( 1, $options['pdf_hyphens'], false ) . '/>';
	$html .= '<label for="pdf_hyphens"> ' . $args[0] . '</label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_widows_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['widows'] ) ) {
		$options['widows'] = 2;
	}

	$html = '<input type="text" id="widows" name="pressbooks_theme_options_pdf[widows]" value="' . $options['widows'] . '" size="3" />';
	$html .= '<label for="widows"></label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_orphans_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['orphans'] ) ) {
		$options['orphans'] = 1;
	}

	$html = '<input type="text" id="orphans" name="pressbooks_theme_options_pdf[orphans]" value="' . $options['orphans'] . '" size="3" />';
	$html .= '<label for="orphans"></label>';
	echo $html;
}

//PDF Options Field Callback
function pressbooks_theme_pdf_fontsize_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_fontsize'] ) ){
		$options['pdf_fontsize'] = 0;
	}

	$html = '<input type="checkbox" id="pdf_fontsize" name="pressbooks_theme_options_pdf[pdf_fontsize]" value="1" ' . checked( 1, $options['pdf_fontsize'], false ) . '/>';
	$html .= '<label for="pdf_fontsize"> ' . $args[0] . '</label>';
	echo $html;
}

// PDF Options Input Sanitization
function pressbooks_theme_options_pdf_sanitize( $input ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );
	$defaults = array(
		'pdf_body_font_size' => 11,
		'pdf_body_line_height' => 1.4,
		'pdf_page_width' => '5.5in',
		'pdf_page_height' => '8.5in',
		'pdf_image_resolution' => '300dpi'
	);

	// Sanitize basic text
	foreach ( array( 'pdf_page_width', 'pdf_page_height', 'pdf_image_resolution' )  as $val ) {
		if ( isset( $defaults[$val] ) && $input[$val] == $defaults[$val] ) { // Don't save defaults for SCSS v2 values
			unset( $options[$val] );
		} else {
			$options[$val] = sanitize_text_field( $input[$val] );
		}
	}

	// Validate integer
	foreach ( array( 'pdf_body_font_size', 'pdf_paragraph_separation', 'pdf_blankpages', 'pdf_footnotes_style', 'widows', 'orphans' ) as $val ) {
		if ( isset( $defaults[$val] ) && $input[$val] == $defaults[$val] ) { // Don't save defaults for SCSS v2 values
			unset( $options[$val] );
		} else {
			$options[$val] = absint( $input[$val] );
		}
	}

	foreach ( array( 'pdf_body_font_size' ) as $val ) {
		if ( isset( $defaults[$val] ) && $input[$val] == $defaults[$val] || empty( $input[$val] ) ) { // Don't save defaults for SCSS v2 values
			unset( $options[$val] );
		} else {
			$options[$val] = absint( $input[$val] );
		}
	}

	// Validate float
	foreach ( array( 'pdf_body_line_height' ) as $val ) {
		if ( isset( $defaults[$val] ) && $input[$val] == $defaults[$val] || empty( $input[$val] ) ) { // Don't save defaults for SCSS v2 values
			unset( $options[$val] );
		} else {
			$options[$val] = filter_var( $input[$val], FILTER_VALIDATE_FLOAT );
		}
	}

	// Checkmarks
	foreach ( array( 'pdf_toc', 'pdf_romanize_parts', 'pdf_crop_marks', 'pdf_hyphens', 'pdf_fontsize' ) as $val ) {
		if ( ! isset( $input[$val] ) || $input[$val] != '1' ) $options[$val] = 0;
		else $options[$val] = 1;
	}

	unset( $options['pdf_page_size'] );

	return $options;
}


/* ------------------------------------------------------------------------ *
 * mPDF Options Tab
 * ------------------------------------------------------------------------ */

// mPDF Options Registration
function pressbooks_theme_options_mpdf_init() {

	$_page = $_option = 'pressbooks_theme_options_mpdf';
	$_section = 'mpdf_options_section';
	$defaults = array(
		'mpdf_page_size' => 'Letter',
		'mpdf_include_cover' => 1,
		'mpdf_indent_paragraphs' => 0,
		'mpdf_include_toc' => 1,
		'mpdf_mirror_margins' => 1,
		'mpdf_margin_left' => 15,
		'mpdf_margin_right' => 30,
		'mpdf_hyphens' => 0,
	);

	if ( false == get_option( $_option ) ) {
		add_option( $_option, $defaults );
	}

	add_settings_section(
		$_section,
		__( 'mPDF Options', 'pressbooks' ),
		'pressbooks_theme_options_mpdf_callback',
		$_page
	);

	add_settings_field(
		'mpdf_page_size',
		__( 'Page Size', 'pressbooks' ),
		'pressbooks_theme_mpdf_page_size_callback',
		$_page,
		$_section,
		array(
			'A0' => __( 'A0', 'pressbooks' ),
			'A1' => __( 'A1', 'pressbooks' ),
			'A2' => __( 'A2', 'pressbooks' ),
			'A3' => __( 'A3', 'pressbooks' ),
			'A4' => __( 'A4', 'pressbooks' ),
			'A5' => __( 'A5', 'pressbooks' ),
			'A6' => __( 'A6', 'pressbooks' ),
			'A7' => __( 'A7', 'pressbooks' ),
			'A8' => __( 'A8', 'pressbooks' ),
			'A9' => __( 'A9', 'pressbooks' ),
			'A10' => __( 'A10', 'pressbooks' ),
			'B0' => __( 'B0', 'pressbooks' ),
			'B1' => __( 'B1', 'pressbooks' ),
			'B2' => __( 'B2', 'pressbooks' ),
			'B3' => __( 'B3', 'pressbooks' ),
			'B4' => __( 'B4', 'pressbooks' ),
			'B5' => __( 'B5', 'pressbooks' ),
			'B6' => __( 'B6', 'pressbooks' ),
			'B7' => __( 'B7', 'pressbooks' ),
			'B8' => __( 'B8', 'pressbooks' ),
			'B9' => __( 'B9', 'pressbooks' ),
			'B10' => __( 'B10', 'pressbooks' ),
			'C0' => __( 'C0', 'pressbooks' ),
			'C1' => __( 'C1', 'pressbooks' ),
			'C2' => __( 'C2', 'pressbooks' ),
			'C3' => __( 'C3', 'pressbooks' ),
			'C4' => __( 'C4', 'pressbooks' ),
			'C5' => __( 'C5', 'pressbooks' ),
			'C6' => __( 'C6', 'pressbooks' ),
			'C7' => __( 'C7', 'pressbooks' ),
			'C8' => __( 'C8', 'pressbooks' ),
			'C9' => __( 'C9', 'pressbooks' ),
			'C10' => __( 'C10', 'pressbooks' ),
			'4A0' => __( '4A0', 'pressbooks' ),
			'2A0' => __( '2A0', 'pressbooks' ),
			'RA0' => __( 'RA0', 'pressbooks' ),
			'RA1' => __( 'RA1', 'pressbooks' ),
			'RA2' => __( 'RA2', 'pressbooks' ),
			'RA3' => __( 'RA3', 'pressbooks' ),
			'RA4' => __( 'RA4', 'pressbooks' ),
			'SRA0' => __( 'SRA0', 'pressbooks' ),
			'SRA1' => __( 'SRA1', 'pressbooks' ),
			'SRA2' => __( 'SRA2', 'pressbooks' ),
			'SRA3' => __( 'SRA3', 'pressbooks' ),
			'SRA4' => __( 'SRA4', 'pressbooks' ),
			'Letter' => __( 'Letter', 'pressbooks' ),
			'Legal' => __( 'Legal' , 'pressbooks' ),
			'Executive' => __( 'Executive' , 'pressbooks' ),
			'Folio' => __( 'Folio' , 'pressbooks' ),
			'Demy' => __( 'Demy' , 'pressbooks' ),
			'Royal' => __( 'Royal' , 'pressbooks' ),
			'A' => __( 'Type A paperback 111x178mm' , 'pressbooks' ),
			'B' => __( 'Type B paperback 128x198mm' , 'pressbooks' ),
		)
	);

	add_settings_field(
		'mpdf_margin_left',
		__( 'Left margin', 'pressbooks' ),
		'pressbooks_theme_mpdf_margin_left_callback',
		$_page,
		$_section,
		array(
			__(  ' Left Margin (in milimeters)', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_margin_right',
		__( 'Right margin', 'pressbooks' ),
		'pressbooks_theme_mpdf_margin_right_callback',
		$_page,
		$_section,
		array(
			__(  ' Right margin (in milimeters)', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_mirror_margins',
		__( 'Mirror Margins', 'pressbooks' ),
		'pressbooks_theme_mpdf_mirror_margins_callback',
		$_page,
		$_section,
		array(
			 __( 'The document will mirror the left and right margin values on odd and even pages (i.e. they become inner and outer margins)', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_include_cover',
		__( 'Cover Image', 'pressbooks' ),
		'pressbooks_theme_mpdf_include_cover_callback',
		$_page,
		$_section,
		array(
			 __( 'Display cover image', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_include_toc',
		__( 'Table of Contents', 'pressbooks' ),
		'pressbooks_theme_mpdf_include_toc_callback',
		$_page,
		$_section,
		array(
			 __( 'Display table of contents', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_indent_paragraphs',
		__( 'Indent paragraphs', 'pressbooks' ),
		'pressbooks_theme_mpdf_indent_paragraphs_callback',
		$_page,
		$_section,
		array(
			 __( 'Indent paragraphs', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_hyphens',
		__( 'Hyphens', 'pressbooks' ),
		'pressbooks_theme_mpdf_hyphens_callback',
		$_page,
		$_section,
		array(
			 __( 'Enable hyphenation', 'pressbooks' )
		)
	);

	add_settings_field(
		'mpdf_fontsize',
		__( 'Increase Font Size', 'pressbooks' ),
		'pressbooks_theme_mpdf_fontsize_callback',
		$_page,
		$_section,
		array(
		    __('Increases font size and line height for greater accessibility', 'pressbooks' )
		)
	);
	register_setting(
		$_option,
		$_option,
		'pressbooks_theme_options_mpdf_sanitize'
	);

}
add_action( 'admin_init', 'pressbooks_theme_options_mpdf_init' );


// mPDF Options Section Callback
function pressbooks_theme_options_mpdf_callback() {
	echo '<p>' . __( 'These options apply to mPDF exports.', 'pressbooks' ) . '</p>';
}


// mPDF Options Field Callback
function pressbooks_theme_mpdf_page_size_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_page_size'] ) ) {
		$options['mpdf_page_size'] = 'Letter';
	}

	$html = "<select name='pressbooks_theme_options_mpdf[mpdf_page_size]' id='mpdf_page_size' >";
	foreach ( $args as $key => $val ) {
		$html .= "<option value='" . $key . "' " . selected( $key , $options['mpdf_page_size'], false ) . ">$val</option>";
	}
	$html .= '<select>';
	echo $html;
}

function pressbooks_theme_mpdf_margin_left_callback ( $args ) {
	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_left_margin'] ) ) {
		$options['mpdf_left_margin'] = '15';
	}

	$html = '<input type="text" id="mpdf_left_margin" name="pressbooks_theme_options_mpdf[mpdf_left_margin]" value="' . $options['mpdf_left_margin'] . '" size="3" />';
	$html .= '<label for="mpdf_left_margin">' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_margin_right_callback ( $args ) {
	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_right_margin'] ) ) {
		$options['mpdf_right_margin'] = '30';
	}

	$html = '<input type="text" id="mpdf_right_margin" name="pressbooks_theme_options_mpdf[mpdf_right_margin]" value="' . $options['mpdf_right_margin'] . '" size="3" />';
	$html .= '<label for="mpdf_right_margin">' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_mirror_margins_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_mirror_margins'] ) ) {
		$options['mpdf_mirror_margins'] = 1;
	}

	$html = '<input type="checkbox" id="mpdf_mirror_margins" name="pressbooks_theme_options_mpdf[mpdf_mirror_margins]" value="1" ' . checked( 1, $options['mpdf_mirror_margins'], false ) . '/>';
	$html .= '<label for="mpdf_mirror_margins"> ' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_include_cover_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_include_cover'] ) ) {
		$options['mpdf_include_cover'] = 0;
	}

	$html = '<input type="checkbox" id="mpdf_include_cover" name="pressbooks_theme_options_mpdf[mpdf_include_cover]" value="1" ' . checked( 1, $options['mpdf_include_cover'], false ) . '/>';
	$html .= '<label for="mpdf_include_cover"> ' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_include_toc_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_include_toc'] ) ) {
		$options['mpdf_include_toc'] = 1;
	}

	$html = '<input type="checkbox" id="mpdf_include_toc" name="pressbooks_theme_options_mpdf[mpdf_include_toc]" value="1" ' . checked( 1, $options['mpdf_include_toc'], false ) . '/>';
	$html .= '<label for="mpdf_include_toc"> ' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_indent_paragraphs_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_indent_paragraphs'] ) ) {
		$options['mpdf_indent_paragraphs'] = 1;
	}

	$html = '<input type="checkbox" id="mpdf_indent_paragraphs" name="pressbooks_theme_options_mpdf[mpdf_indent_paragraphs]" value="1" ' . checked( 1, $options['mpdf_indent_paragraphs'], false ) . '/>';
	$html .= '<label for="mpdf_indent_paragraphs"> ' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_hyphens_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_hyphens'] ) ) {
		$options['mpdf_hyphens'] = 0;
	}

	$html = '<input type="checkbox" id="mpdf_hyphens" name="pressbooks_theme_options_mpdf[mpdf_hyphens]" value="1" ' . checked( 1, $options['mpdf_hyphens'], false ) . '/>';
	$html .= '<label for="mpdf_hyphens"> ' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_mpdf_fontsize_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	if ( ! isset( $options['mpdf_fontsize'] ) ){
		$options['mpdf_fontsize'] = 0;
	}

	$html = '<input type="checkbox" id="mpdf_fontsize" name="pressbooks_theme_options_mpdf[mpdf_fontsize]" value="1" ' . checked( 1, $options['mpdf_fontsize'], false ) . '/>';
	$html .= '<label for="mpdf_fontsize"> ' . $args[0] . '</label>';
	echo $html;
}

function pressbooks_theme_options_mpdf_sanitize ( $input ){

	$options = get_option( 'pressbooks_theme_options_mpdf' );

	// Absint
	foreach ( array( 'mpdf_right_margin', 'mpdf_left_margin' ) as $val ) {
		$options[$val] = absint( $input[$val] );
	}

	// Checkmarks
	foreach ( array( 'mpdf_indent_paragraphs', 'mpdf_include_cover', 'mpdf_mirror_margins', 'mpdf_include_toc', 'mpdf_hyphens', 'mpdf_fontsize' ) as $val ) {
		if ( ! isset( $input[$val] ) || $input[$val] != '1' ) $options[$val] = 0;
		else $options[$val] = 1;
	}

	// nothing to do, select list
	$options['mpdf_page_size'] = $input['mpdf_page_size'];

	return $options;
}

/* ------------------------------------------------------------------------ *
 * Ebook Options Tab
 * ------------------------------------------------------------------------ */

// Ebook Options Registration
function pressbooks_theme_options_ebook_init() {

	$_page = $_option = 'pressbooks_theme_options_ebook';
	$_section = 'ebook_options_section';
	$defaults = array(
		'ebook_paragraph_separation' => 1,
		'ebook_compress_images' => 0,
	);

	if ( false == get_option( $_option ) ) {
		add_option( $_option, $defaults );
	}

	add_settings_section(
		$_section,
		__( 'Ebook Options', 'pressbooks' ),
		'pressbooks_theme_options_ebook_callback',
		$_page
	);

	add_settings_field(
		'ebook_paragraph_separation',
		__( 'Paragraph Separation', 'pressbooks' ),
		'pressbooks_theme_ebook_paragraph_separation_callback',
		$_page,
		$_section,
		array(
			 __( 'Indent paragraphs', 'pressbooks' ),
			 __( 'Skip lines between paragraphs', 'pressbooks' )
		)
	);

	add_settings_field(
		'ebook_compress_images',
		__( 'Compress images', 'pressbooks' ),
		'pressbooks_theme_ebook_compress_images_callback',
		$_page,
		$_section,
		array(
			__( 'Reduce image size and quality', 'pressbooks' )
		)
	);

	register_setting(
		$_option,
		$_option,
		'pressbooks_theme_options_ebook_sanitize'
	);
}
add_action( 'admin_init', 'pressbooks_theme_options_ebook_init' );


// Ebook Options Section Callback
function pressbooks_theme_options_ebook_callback() {
	echo '<p>' . __( 'These options apply to ebook exports.', 'pressbooks' ) . '</p>';
}

// Ebook Options Field Callbacks
function pressbooks_theme_ebook_paragraph_separation_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_ebook' );

	if ( ! isset( $options['ebook_paragraph_separation'] ) ) {
		$options['ebook_paragraph_separation'] = 1;
	}

	$html = '<input type="radio" id="paragraph_indent" name="pressbooks_theme_options_ebook[ebook_paragraph_separation]" value="1"' . checked( 1, $options['ebook_paragraph_separation'], false ) . '/> ';
	$html .= '<label for="paragraph_indent">' . $args[0] . '</label><br />';
	$html .= '<input type="radio" id="paragraph_skiplines" name="pressbooks_theme_options_ebook[ebook_paragraph_separation]" value="2"' . checked( 2, $options['ebook_paragraph_separation'], false ) . '/> ';
	$html .= '<label for="paragraph_skiplines">' . $args[1] . '</label>';
	echo $html;
}

// PDF Options Field Callback
function pressbooks_theme_ebook_compress_images_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_ebook' );

	if ( ! isset( $options['ebook_compress_images'] ) ) {
		$options['ebook_compress_images'] = 0;
	}

	$html = '<input type="checkbox" id="ebook_compress_images" name="pressbooks_theme_options_ebook[ebook_compress_images]" value="1" ' . checked( 1, $options['ebook_compress_images'], false ) . '/>';
	$html .= '<label for="ebook_compress_images"> ' . $args[0] . '</label>';
	echo $html;
}


// Ebook Options Input Sanitization
function pressbooks_theme_options_ebook_sanitize( $input ) {

	$options = get_option( 'pressbooks_theme_options_ebook' );

	// Absint
	foreach ( array( 'ebook_paragraph_separation' ) as $val ) {
		$options[$val] = absint( $input[$val] );
	}

	// Checkmarks
	foreach ( array( 'ebook_compress_images' ) as $val ) {
		if ( ! isset( $input[$val] ) || $input[$val] != '1' ) $options[$val] = 0;
		else $options[$val] = 1;
	}

	return $options;
}


/* ------------------------------------------------------------------------ *
 * Hooks, Actions and Filters
 * ------------------------------------------------------------------------ */

function pressbooks_theme_pdf_css_override( $scss ) {

	// --------------------------------------------------------------------
	// Global Options

	$sass = \Pressbooks\Container::get( 'Sass' );
	$options = get_option( 'pressbooks_theme_options_global' );

	// Display chapter numbers? true (default) / false
	if ( ! @$options['chapter_numbers'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$chapter-number-display: none; \n";
		} else {
			$scss .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number, #toc .part a::before, #toc .chapter a::before { display: none !important; } \n";
		}
	}

	// --------------------------------------------------------------------
	// PDF Options

	$options = get_option( 'pressbooks_theme_options_pdf' );

	// Change body font size
	if ( $sass->isCurrentThemeCompatible( 2 ) && @$options['pdf_body_font_size'] ) {
		$fontsize = $options['pdf_body_font_size'] . 'pt';
		$scss .= "\$body-font-size: $fontsize; \n";
	}

	// Change body line height
	if ( $sass->isCurrentThemeCompatible( 2 ) && @$options['pdf_body_line_height'] ) {
		$lineheight = $options['pdf_body_line_height'] . 'em';
		$scss .= "\$body-line-height: $lineheight; \n";
	}


	/*
	Page sizes:
	1 = 5.5 x 8.5"
	2 = 6 x 9"
	3 = 8.5 x 11"
	4 = 8.5 x 9.25"
	5 = 5 x 7.75"
	6 = 4.25 x 7"
	7 = 21 x 29.7cm
	8 = 14.8 x 21cm
	9 = 5in x 8in
	*/

	if ( isset( $options['pdf_page_size'] ) ) {
		switch ( $options['pdf_page_size'] ) {
			case 1:
				$width = '5.5in';
				$height = '8.5in';
				break;
			case 2:
				$width = '6in';
				$height = '9in';
				break;
			case 3:
				$width = '8.5in';
				$height = '11in';
				break;
			case 4:
				$width = '8.5in';
				$height = '9.25in';
				break;
			case 5:
				$width = '5in';
				$height = '7.75in';
				break;
			case 6:
				$width = '4.25in';
				$height = '7in';
				break;
			case 7:
				$width = '21cm';
				$height = '29.7cm';
				break;
			case 8:
				$width = '14.8cm';
				$height = '21cm';
				break;
			case 9:
				$width = '5in';
				$height = '8in';
				break;
		}
		$options['pdf_page_width'] = $width;
		$options['pdf_page_height'] = $height;
		unset( $options['pdf_page_size'] );
		update_option( 'pressbooks_theme_options_pdf', $options );
	} elseif ( isset( $options['pdf_page_width'] ) && isset( $options['pdf_page_height'] ) ) {
		$width = $options['pdf_page_width'];
		$height = $options['pdf_page_height'];
	} else {
		$width = '5.5in';
		$height = '8.5in';
	}

	if ( $sass->isCurrentThemeCompatible( 2 ) ) {
		$scss .= "\$page-width: $width; \n";
		$scss .= "\$page-height: $height; \n";
	} else {
		$scss .= "@page { size: $width $height; } \n";
	}

	// Image resolution
	if ( isset( $options['pdf_image_resolution'] ) ) {
		$resolution = $options['pdf_image_resolution'];
	} else {
		$resolution = '300dpi';
	}
	if ( $sass->isCurrentThemeCompatible( 2 ) ) {
		$scss .= "\$prince-image-resolution: $resolution; \n";
	} else {
		$scss .= "img { prince-image-resolution: $resolution; } \n";
	}

	// Display crop marks? true / false (default)
	if ( @$options['pdf_crop_marks'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$page-cropmarks: crop; \n";
		} else {
			$scss .= "@page { marks: crop } \n";
		}
	}

	// Hyphens?
	if ( @$options['pdf_hyphens'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$para-hyphens: auto; \n"; // TODO
		} else {
			$scss .= "p { hyphens: auto; } \n";
		}
	}

	// Indent paragraphs? 1 = Indent (default), 2 = Skip Lines
	if ( 2 == @$options['pdf_paragraph_separation'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$para-margin-top: 1em; \n";
			$scss .= "\$para-indent: 0; \n";
		} else {
			$scss .= "p + p { text-indent: 0em; margin-top: 1em; } \n";
		}
	}

	// Include blank pages? 1 = Yes (default), 2 = No
	if ( 2 == @$options['pdf_blankpages'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$recto-verso-standard-opening: auto; \n";
			$scss .= "\$recto-verso-first-section-opening: auto; \n";
			$scss .= "\$recto-verso-section-opening: auto; \n";
		} else {
			$scss .= "#title-page, #copyright-page, #toc, div.part, div.front-matter, div.back-matter, div.chapter, #half-title-page h1.title:first-of-type  { page-break-before: auto; } \n";
		}
	}

	// Display TOC? true (default) / false
	if ( ! @$options['pdf_toc'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$toc-display: none; \n";
		} else {
			$scss .= "#toc { display: none; } \n";
		}
	}

	// Widows
	if ( @$options['widows'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$widows: " . $options['widows'] . "; \n";
		} else {
			$scss .= 'p { widows: ' . $options['widows'] . '; }' . "\n";
		}
	} else {
		if ( ! $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= 'p { widows: 2; }' . "\n";
		}
	}

	// Orphans
	if ( @$options['orphans'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$orphans: " . $options['orphans'] . "; \n";
		} else {
			$scss .= 'p { orphans: ' . $options['orphans'] . '; }' . "\n";
		}
	} else {
		if ( ! $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= 'p { orphans: 1; }' . "\n";
		}
	}

	// a11y Font Size
	if ( @$options['pdf_fontsize'] ){
		if ( ! $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= 'body { font-size: 1.3em; line-height: 1.3; }' . "\n";
		}
	}

	return $scss;
}
add_filter( 'pb_pdf_css_override', 'pressbooks_theme_pdf_css_override' );

function pressbooks_theme_mpdf_css_override( $scss ) {
	$options = get_option( 'pressbooks_theme_options_mpdf' );
	$global_options = get_option( 'pressbooks_theme_options_global' );

	// indent paragraphs
	if ( $options['mpdf_indent_paragraphs'] ) {
		$scss .= "p + p, .indent {text-indent: 2.0 em; }" . "\n";
	}
	// hyphenation
	if ( $options['mpdf_hyphens'] ) {
		$scss .= "p {hyphens: auto;}" . "\n";
	}
	// font-size
	if ( $options['mpdf_fontsize'] ){
                $scss .= 'body {font-size: 1.3em; line-height: 1.3; }' . "\n";
        }
	// chapter numbers
	if ( ! $global_options['chapter_numbers'] ) {
		$scss .= "h3.chapter-number {display: none;}" . "\n";
	}
	return $scss;
}

add_filter( 'pb_mpdf_css_override', 'pressbooks_theme_mpdf_css_override' );

function pressbooks_theme_ebook_css_override( $scss ) {

	// --------------------------------------------------------------------
	// Global Options

	$sass = \Pressbooks\Container::get( 'Sass' );
	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! @$options['chapter_numbers'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$chapter-number-display: none; \n";
		} else {
			$scss .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number { display: none !important; } \n";
		}
	}

	// --------------------------------------------------------------------
	// Ebook Options

	$options = get_option( 'pressbooks_theme_options_ebook' );

	// Indent paragraphs? 1 = Indent (default), 2 = Skip Lines
	if ( 2 == @$options['ebook_paragraph_separation'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$para-margin-top: 1em; \n";
			$scss .= "\$para-indent: 0; \n";
		} else {
			$scss .= "p + p, .indent, div.ugc p.indent { text-indent: 0; margin-top: 1em; } \n";
		}
	}

	return $scss;

}
add_filter( 'pb_epub_css_override', 'pressbooks_theme_ebook_css_override' );


function pressbooks_theme_pdf_hacks( $hacks ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	// 1 = Footnotes (default), 2 = Endnotes
	$hacks['pdf_footnotes_style'] = @$options['pdf_footnotes_style'];

	return $hacks;
}
add_filter( 'pb_pdf_hacks', 'pressbooks_theme_pdf_hacks' );


function pressbooks_theme_ebook_hacks( $hacks ) {

	// --------------------------------------------------------------------
	// Global Options

	$options = get_option( 'pressbooks_theme_options_global' );

	// Display chapter numbers?
	$hacks['chapter_numbers'] = $options['chapter_numbers'];

	// --------------------------------------------------------------------
	// Ebook Options

	$options = get_option( 'pressbooks_theme_options_ebook' );

	// Indent paragraphs? 1 = Indent (default), 2 = Skip Lines
	if ( @$options['ebook_compress_images'] ) {
		$hacks['ebook_compress_images'] = true;
	}

	// --------------------------------------------------------------------
	// Luther features we inject ourselves, (not user options, this theme not child)

	$theme = strtolower( '' . wp_get_theme() );
	if ( 'luther' == $theme ) {
		$hacks['ebook_romanize_part_numbers'] = true;
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
