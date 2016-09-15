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

function replace_running_content_tags( $input ) {
	$input = '"' . $input . '"';
	error_log( $input );

	return str_replace(
		array(
			'%book_title%',
			'%book_subtitle%',
			'%book_author%',
			'%part_number%',
			'%part_title%',
			'%section_title%',
			'%section_author%',
			'%section_subtitle%',
			'%blank%'
		),
		array(
			'" string(book-title) "',
			'" string(book-subtitle) "',
			'" string(book-author) "',
			'" string(part-number) "',
			'" string(part-title) "',
			'" string(section-title) "',
			'" string(chapter-author) "',
			'" string(chapter-subtitle) "',
			''
		),
		$input
	);
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
	if ( ! $options['chapter_numbers'] ) {
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
	if ( $sass->isCurrentThemeCompatible( 2 ) && isset( $options['pdf_body_font_size'] ) ) {
		$fontsize = $options['pdf_body_font_size'] . 'pt';
		$scss .= "\$body-font-size: $fontsize; \n";
	}

	// Change body line height
	if ( $sass->isCurrentThemeCompatible( 2 ) && isset( $options['pdf_body_line_height'] ) ) {
		$lineheight = $options['pdf_body_line_height'] . 'em';
		$scss .= "\$body-line-height: $lineheight; \n";
	}

	// Page dimensions
	$width = $options['pdf_page_width'];
	$height = $options['pdf_page_height'];

	if ( $sass->isCurrentThemeCompatible( 2 ) ) {
		$scss .= "\$page-width: $width; \n";
		$scss .= "\$page-height: $height; \n";
	} else {
		$scss .= "@page { size: $width $height; } \n";
	}

	// Margins
	$outside = $options['pdf_page_margin_outside'];
	$inside = $options['pdf_page_margin_inside'];
	$top = $options['pdf_page_margin_top'];
	$bottom = $options['pdf_page_margin_bottom'];

	if ( $sass->isCurrentThemeCompatible( 2 ) ) {
		$scss .= "\$page-margin-left-top: $top; \n";
		$scss .= "\$page-margin-left-right: $inside; \n";
		$scss .= "\$page-margin-left-bottom: $bottom; \n";
		$scss .= "\$page-margin-left-left: $outside; \n";
		$scss .= "\$page-margin-right-top: $top; \n";
		$scss .= "\$page-margin-right-right: $outside; \n";
		$scss .= "\$page-margin-right-bottom: $bottom; \n";
		$scss .= "\$page-margin-right-left: $inside; \n";
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
	if ( $options['pdf_crop_marks'] == 1 ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$page-cropmarks: crop; \n";
		} else {
			$scss .= "@page { marks: crop } \n";
		}
	}

	// Hyphens?
	if ( $options['pdf_hyphens'] == 1 ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$para-hyphens: auto; \n"; // TODO
		} else {
			$scss .= "p { hyphens: auto; } \n";
		}
	}

	// Indent paragraphs?
	if ( $options['pdf_paragraph_separation'] == 'indent' ) {
		// Default, no change needed
	} elseif ( $options['pdf_paragraph_separation'] == 'skiplines' ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$para-margin-top: 1em; \n";
			$scss .= "\$para-indent: 0; \n";
		} else {
			$scss .= "p + p { text-indent: 0em; margin-top: 1em; } \n";
		}
	}

	// Include blank pages?
	if ( $options['pdf_blankpages'] == 'include' ) {
		// Default, no change needed
	} elseif ( $options['pdf_blankpages'] == 'remove' ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$recto-verso-standard-opening: auto; \n";
			$scss .= "\$recto-verso-first-section-opening: auto; \n";
			$scss .= "\$recto-verso-section-opening: auto; \n";
		} else {
			$scss .= "#title-page, #copyright-page, #toc, div.part, div.front-matter, div.back-matter, div.chapter, #half-title-page h1.title:first-of-type  { page-break-before: auto; } \n";
		}
	}

	// Display TOC? true (default) / false
	if ( ! $options['pdf_toc'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$toc-display: none; \n";
		} else {
			$scss .= "#toc { display: none; } \n";
		}
	}

	// Widows
	if ( isset( $options['widows'] ) ) {
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
	if ( isset( $options['orphans'] ) ) {
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

	// Running Content
	if ( $sass->isCurrentThemeCompatible( 2 ) ) {
		$front_matter_running_content_left = ( isset( $options['running_content_front_matter_left'] ) ) ? replace_running_content_tags( $options['running_content_front_matter_left'] ) : 'string(book-title)';
		$front_matter_running_content_right = ( isset( $options['running_content_front_matter_right'] ) ) ? replace_running_content_tags( $options['running_content_front_matter_right'] ) : 'string(section-title)';
		$introduction_running_content_left = ( isset( $options['running_content_introduction_left'] ) ) ? replace_running_content_tags( $options['running_content_introduction_left'] ) : 'string(book-title)';
		$introduction_running_content_right = ( isset( $options['running_content_introduction_right'] ) ) ? replace_running_content_tags( $options['running_content_introduction_right'] ) : 'string(section-title)';
		$part_running_content_left = ( isset( $options['running_content_part_left'] ) ) ? replace_running_content_tags( $options['running_content_part_left'] ) : 'string(book-title)';
		$part_running_content_right = ( isset( $options['running_content_part_right'] ) ) ? replace_running_content_tags( $options['running_content_part_right'] ) : 'string(part-title)';
		$chapter_running_content_left = ( isset( $options['running_content_chapter_left'] ) ) ? replace_running_content_tags( $options['running_content_chapter_left'] ) : 'string(book-title)';
		$chapter_running_content_right = ( isset( $options['running_content_chapter_right'] ) ) ? replace_running_content_tags( $options['running_content_chapter_right'] ) : 'string(section-title)';
		$back_matter_running_content_left = ( isset( $options['running_content_back_matter_left'] ) ) ? replace_running_content_tags( $options['running_content_back_matter_left'] ) : 'string(book-title)';
		$back_matter_running_content_right = ( isset( $options['running_content_back_matter_right'] ) ) ? replace_running_content_tags( $options['running_content_back_matter_right'] ) : 'string(section-title)';
		$scss .= "\$front-matter-running-content-left: $front_matter_running_content_left; \n";
		$scss .= "\$front-matter-running-content-left: $front_matter_running_content_right; \n";
		$scss .= "\$introduction-running-content-left: $introduction_running_content_left; \n";
		$scss .= "\$introduction-running-content-left: $introduction_running_content_right; \n";
		$scss .= "\$part-running-content-left: $part_running_content_left; \n";
		$scss .= "\$part-running-content-right: $part_running_content_right; \n";
		$scss .= "\$chapter-running-content-left: $chapter_running_content_left; \n";
		$scss .= "\$chapter-running-content-right: $chapter_running_content_right; \n";
		$scss .= "\$back-matter-running-content-left: $back_matter_running_content_left; \n";
		$scss .= "\$back-matter-running-content-right: $back_matter_running_content_right; \n";
	}

	// a11y Font Size
	if ( @$options['pdf_fontsize'] ) {
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

	if ( ! $options['chapter_numbers'] ) {
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$chapter-number-display: none; \n";
		} else {
			$scss .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number { display: none !important; } \n";
		}
	}

	// --------------------------------------------------------------------
	// Ebook Options

	$options = get_option( 'pressbooks_theme_options_ebook' );

	// Indent paragraphs?
	if ( $options['ebook_paragraph_separation'] == 'indent' ) {
		// Default, no change needed
	} elseif ( $options['ebook_paragraph_separation'] == 'skiplines' ) {
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

function pressbooks_cover_promo() { ?>
	<?php if ( !defined( 'PB_HIDE_COVER_PROMO' ) || PB_HIDE_COVER_PROMO == false ) : ?>
	<a href="https://pressbooks.com" class="pressbooks-brand"><img src="<?php bloginfo('template_url'); ?>/images/pressbooks-branding-2x.png" alt="pressbooks-branding" width="186" height="123" /> <span><?php _e('Make your own books on Pressbooks', 'pressbooks'); ?></span></a>
	<?php else : ?>
	<div class="spacer"></div>
	<?php endif;
}

add_action( 'pb_cover_promo', 'pressbooks_cover_promo' );
