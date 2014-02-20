<?php
/**
 * @author  PressBooks <code@pressbooks.com>
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
		wp_enqueue_style( 'book-info-fonts', 'http://fonts.googleapis.com/css?family=Droid+Serif:400,700|Oswald:300,400,700' );
		
		// Book info page Table of Content columns
		wp_enqueue_script( 'columnizer',  PB_PLUGIN_URL . 'symbionts/jquery/jquery.columnizer.js', array( 'jquery' ), '1.6.0', false );
		wp_enqueue_script( 'columnizer-load', get_template_directory_uri() . '/js/columnizer-load.js', array( 'jquery', 'columnizer' ), '20130819', false );
		
		// Sharrre
		wp_enqueue_script( 'sharrre', PB_PLUGIN_URL . 'symbionts/jquery/sharrre/jquery.sharrre-1.3.4.min.js', array( 'jquery' ), '20130712', false );
		wp_enqueue_script( 'sharrre-load', get_template_directory_uri() . '/js/sharrre-load.js', array( 'jquery', 'sharrre' ), '20130712', false );
		wp_localize_script( 'sharrre-load', 'PB_SharrreToken', array(
			'urlCurl' => PB_PLUGIN_URL . 'symbionts/jquery/sharrre/sharrre.php',
		) );
	}   		 		   		   		       		           
}     
add_action('wp_enqueue_scripts', 'pressbooks_book_info_page');

/* ------------------------------------------------------------------------ *
 * Register and enqueue scripts and stylesheets.
 * ------------------------------------------------------------------------ */
function pb_enqueue_scripts() {

	if ( pb_is_custom_theme() ) {
		$deps = array();
		if ( ! pb_custom_stylesheet_imports_base() ) {
			// Use default stylesheet as base (to avoid horribly broken webbook)
			wp_register_style( 'pressbooks', PB_PLUGIN_URL . 'themes-book/pressbooks-book/style.css', array(), null, 'screen' );
			wp_enqueue_style( 'pressbooks' );
			$deps = array( 'pressbooks' );
		}
		wp_register_style( 'pressbooks-custom-css', pb_get_custom_stylesheet_url(), $deps, get_option( 'pressbooks_last_custom_css' ), 'screen' );
		wp_enqueue_style( 'pressbooks-custom-css' );
	} else {
		wp_register_style( 'pressbooks', get_bloginfo( 'stylesheet_url' ), array(), null, 'screen' );
		wp_enqueue_style( 'pressbooks' );
	}
	if (! is_front_page() ) {
		wp_enqueue_script( 'pressbooks-script', get_template_directory_uri() . "/js/script.js", array( 'jquery' ), '1.0', false );
	}
	wp_enqueue_script( 'keyboard-nav', get_template_directory_uri() . '/js/keyboard-nav.js', array( 'jquery' ), '20130306', true );

	if ( is_single() ) {
		wp_enqueue_script( 'pb-pop-out-toc', get_template_directory_uri() . '/js/pop-out.js', array( 'jquery' ), '1.0', false );
	}
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


/* Add Custom Login Graphic TODO: Import user customized logo here if available */
add_action('login_head', create_function('', 'echo \'<link rel="stylesheet" type="text/css" href="'. PB_PLUGIN_URL .'assets/css/colors-pb.css" media="screen" />\';'));


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
				<div class="entry_content denied-text"><?php _e('This book is private, and accessible only to registered users. If you have an account you can <a href="'. $bloginfourl .'/wp-login.php" class="login">login here</a>  <p class="sign-up">You can also set up your own PressBooks book at: <a href="http://pressbooks.com">PressBooks.com</a>.', 'pressbooks'); ?></p></div>
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
 * Google Webfonts
 * ------------------------------------------------------------------------ */
 
function pressbooks_enqueue_styles() {
   		 wp_enqueue_style( 'pressbooks-fonts', 'http://fonts.googleapis.com/css?family=Cardo:400,400italic,700|Oswald');  		   		   		       		           
}     
add_action('wp_print_styles', 'pressbooks_enqueue_styles'); 






/* ------------------------------------------------------------------------ *
 * Theme Options Display (Appearance -> Theme Options)
 * ------------------------------------------------------------------------ */

if ( ! function_exists( 'pressbooks_theme_options_display' ) ) :

/**
 * Function called by the PressBooks plugin when user is on [ Appearance → Theme Options ] page
 */
function pressbooks_theme_options_display() { ?>
	<div class="wrap">
		<div id="icon-themes" class="icon32"></div>
		<h2><?php echo wp_get_theme(); ?> Theme Options</h2>
		<?php settings_errors(); ?>
		<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'global_options'; ?>
		<h2 class="nav-tab-wrapper">
		<a href="?page=pressbooks_theme_options&tab=global_options" class="nav-tab <?php echo $active_tab == 'global_options' ? 'nav-tab-active' : ''; ?>">Global Options</a>
		<a href="?page=pressbooks_theme_options&tab=pdf_options" class="nav-tab <?php echo $active_tab == 'pdf_options' ? 'nav-tab-active' : ''; ?>">PDF Options</a>
		<a href="?page=pressbooks_theme_options&tab=ebook_options" class="nav-tab <?php echo $active_tab == 'ebook_options' ? 'nav-tab-active' : ''; ?>">Ebook Options</a>
		</h2>
		<!-- Create the form that will be used to render our options -->
		<form method="post" action="options.php">
			<?php if( $active_tab == 'global_options' ) { 
				settings_fields( 'pressbooks_theme_options_global' );
				do_settings_sections( 'pressbooks_theme_options_global' );
			} elseif( $active_tab == 'pdf_options' ) {
				settings_fields( 'pressbooks_theme_options_pdf' );
				do_settings_sections( 'pressbooks_theme_options_pdf' );
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
 * Theme Options Summary (displayed on Export Page)
 * ------------------------------------------------------------------------ */

if ( ! function_exists( 'pressbooks_theme_options_summary' ) ) :

/**
 * Function called by the PressBooks plugin when user is on [ Export ] page
 */
function pressbooks_theme_options_summary() { ?>
	<p><strong><?php _e('Global options', 'pressbooks' ); ?>:</strong></p>
	<ul>
	<?php
	$global_options = get_option('pressbooks_theme_options_global');
	foreach ($global_options as $key => $value) {
		switch($key) {
			case 'chapter_numbers': ?>
			<li><?php _e('Chapter numbers', 'pressbooks' ); ?>: <em><?php echo $value == 1 ? __( 'display chapter numbers', 'pressbooks' ) : __( 'do not display chapter numbers', 'pressbooks' ); ?></em></li>
			<?php break;
		}
	}
	?>
	</ul>
	<p><strong><?php _e('PDF options', 'pressbooks' ) ?>:</strong></p>
	<ul>
		<?php
		// TODO: Control the order of display.
		$pdf_options = get_option('pressbooks_theme_options_pdf');
		foreach ($pdf_options as $key => $value) {
			switch($key) {
				case 'pdf_page_size': ?>
					<li><?php _e( 'Page size', 'pressbooks' ) ?>: <em><?php
						if ( $value == 1 ) { _e( 'digest', 'pressbooks' ); }
						elseif ( $value == 2 ) { _e( 'US trade', 'pressbooks' ); }
						elseif ( $value == 3 ) { _e( 'US letter', 'pressbooks' ); }
						elseif ( $value == 4 ) { _e( '8.5 x 9.25"', 'pressbooks' ); }
						elseif ( $value == 5 ) { _e( 'duodecimo', 'pressbooks' ); }
						elseif ( $value == 6 ) { _e( 'pocket', 'pressbooks' ); }
						elseif ( $value == 7 ) { _e( 'A4', 'pressbooks' ); }
						elseif ( $value == 8 ) { _e( 'A5', 'pressbooks' ); } ?></em></li>
					<?php break;
				case 'pdf_paragraph_separation': ?>
					<li><?php _e( 'Paragraph separator', 'pressbooks' ) ?>: <em><?php
						if ( $value == 1 ) { _e( 'indent', 'pressbooks' ); }
						elseif ( $value == 2 ) { _e( 'skip lines', 'pressbooks' ); } ?></em></li>
					<?php break;
				case 'pdf_blankpages': ?>
					<li><?php _e( 'Blank pages' , 'pressbooks' ) ?>: <em><?php
						if ( $value == 1 ) { _e( 'include blank pages (for print PDF)', 'pressbooks' ); }
						elseif ( $value == 2 ) { _e( 'remove blank pages (for web PDF)', 'pressbooks' ); } ?></em></li>
					<?php break;
				case 'pdf_toc': ?>
					<li><?php _e( 'Table of contents' , 'pressbooks' ) ?>: <em><?php echo $value == 1 ? __( 'display', 'pressbooks' ) : __( 'do not display', 'pressbooks' ); ?></em></li>
					<?php break;
				case 'pdf_footnotes_style': ?>
					<li><?php _e( 'Footnotes style' , 'pressbooks' ) ?>: <em><?php echo $value == 1 ? __( 'normal', 'pressbooks' ) : __( 'force as endnotes', 'pressbooks' ); ?></em></li>
					<?php break;
				case 'pdf_crop_marks': ?>
					<li><?php _e( 'Crop marks' , 'pressbooks' ) ?>: <em><?php echo $value == 1 ? __( 'display', 'pressbooks' ) : __( 'do not display', 'pressbooks' ); ?></em></li>
					<?php break;
				case 'pdf_hyphens': ?>
					<li><?php _e( 'Hyphens' , 'pressbooks' ) ?>: <em><?php echo $value == 1 ? __( 'enabled', 'pressbooks' ) : __( 'disabled', 'pressbooks' ); ?></em></li>
					<?php break;
				case 'pdf_widows': ?>
					<li><?php _e( 'Widows' , 'pressbooks' ) ?>: <em><?php echo $value; ?></em></li>
					<?php break;
				case 'pdf_orphans': ?>
					<li><?php _e( 'Orphans' , 'pressbooks' ) ?>: <em><?php echo $value; ?></em></li>
					<?php break;
			}
		}
		?>
	</ul>
	<p><strong><?php _e( 'Ebook options' , 'pressbooks' ) ?>:</strong></p>
	<ul>
		<?php
		$ebook_options = get_option('pressbooks_theme_options_ebook');
		foreach ($ebook_options as $key => $value) {
			switch($key) {
				case 'ebook_paragraph_separation': ?>
					<li><?php _e( 'Paragraph separator' , 'pressbooks' ) ?>: <em><?php
						if ( $value == 1 ) { _e( 'indent', 'pressbooks' ); }
						elseif ( $value == 2 ) { _e( 'skip lines', 'pressbooks' ); } ?></em></li>
					<?php break;
				case 'ebook_compress_images': ?>
					<li><?php _e( 'Compress images' , 'pressbooks' ) ?>: <em><?php echo $value == 1 ? __( 'enabled', 'pressbooks' ) : __( 'disabled', 'pressbooks' ); ?></em></li>
					<?php break;
			}
		}
		?>
	</ul>
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
		'chapter_numbers' => 1
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

	register_setting(
		$_option,
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


// Global Options Input Sanitization
function pressbooks_theme_options_global_sanitize( $input ) {

	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! isset( $input['chapter_numbers'] ) || $input['chapter_numbers'] != '1' ) {
		$options['chapter_numbers'] = 0;
	} else {
		$options['chapter_numbers'] = 1;
	}

	return $options;
}


/* ------------------------------------------------------------------------ *
 * PDF Options Tab
 * ------------------------------------------------------------------------ */

use PressBooks\CustomCss;

// PDF Options Registration
function pressbooks_theme_options_pdf_init() {

	$_page = $_option = 'pressbooks_theme_options_pdf';
	$_section = 'pdf_options_section';
	$defaults = array(
		'pdf_page_size' => 1,
		'pdf_paragraph_separation' => 1,
		'pdf_blankpages' => 1,
		'pdf_toc' => 1,
		'pdf_romanize_parts' => 0,
		'pdf_footnotes_style' => 1,
		'pdf_crop_marks' => 0,
		'pdf_hyphens' => 0,
		'pdf_widows' => 3,
		'pdf_orphans' => 3,
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

	add_settings_field(
		'pdf_page_size',
		__( 'Page Size', 'pressbooks' ),
		'pressbooks_theme_pdf_page_size_callback',
		$_page,
		$_section,
		array(
			 __( 'Digest (5.5&quot; &times; 8.5&quot;)', 'pressbooks' ),
			 __( 'US Trade (6&quot; &times; 9&quot;)', 'pressbooks' ),
			 __( 'US Letter (8.5&quot; &times; 11&quot;)', 'pressbooks' ),
			 __( 'Custom (8.5&quot; &times; 9.25&quot;)', 'pressbooks' ),
			 __( 'Duodecimo (5&quot; &times; 7.75&quot;)', 'pressbooks' ),
			 __( 'Pocket (4.25&quot; &times; 7&quot;)', 'pressbooks' ),
			 __( 'A4 (21cm &times; 29.7cm)', 'pressbooks' ),
			 __( 'A5 (14.8cm &times; 21cm)', 'pressbooks' ),

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
			 __( 'Force as endnotes', 'pressbooks' )
		)
	);
	add_settings_field(
		'pdf_widows',
		__( 'Widows', 'pressbooks' ),
		'pressbooks_theme_pdf_widows_callback',
		$_page,
		$_section
	);
	add_settings_field(
		'pdf_orphans',
		__( 'Orphans', 'pressbooks' ),
		'pressbooks_theme_pdf_orphans_callback',
		$_page,
		$_section
	);

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
function pressbooks_theme_pdf_page_size_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_page_size'] ) ) {
		$options['pdf_page_size'] = 1;
	}

	$html = "<select name='pressbooks_theme_options_pdf[pdf_page_size]' id='pdf_page_size' >";
	foreach ( $args as $key => $val ) {
		$html .= "<option value='" . ( $key + 1 ) . "' " . selected( $key + 1, $options['pdf_page_size'], false ) . ">$val</option>";
	}
	$html .= '<select>';
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

	if ( ! isset( $options['pdf_widows'] ) ) {
		$options['pdf_widows'] = 3;
	}

	$html = '<input type="text" id="pdf_widows" name="pressbooks_theme_options_pdf[pdf_widows]" value="' . $options['pdf_widows'] . '" size="3" />';
	$html .= '<label for="pdf_widows"></label>';
	echo $html;
}


// PDF Options Field Callback
function pressbooks_theme_pdf_orphans_callback( $args ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	if ( ! isset( $options['pdf_orphans'] ) ) {
		$options['pdf_orphans'] = 3;
	}

	$html = '<input type="text" id="pdf_orphans" name="pressbooks_theme_options_pdf[pdf_orphans]" value="' . $options['pdf_orphans'] . '" size="3" />';
	$html .= '<label for="pdf_orphans"></label>';
	echo $html;
}


// PDF Options Input Sanitization
function pressbooks_theme_options_pdf_sanitize( $input ) {

	$options = get_option( 'pressbooks_theme_options_pdf' );

	// Absint
	foreach ( array( 'pdf_page_size', 'pdf_paragraph_separation', 'pdf_blankpages', 'pdf_footnotes_style', 'pdf_widows', 'pdf_orphans' ) as $val ) {
		$options[$val] = absint( $input[$val] );
	}

	// Checkmarks
	foreach ( array( 'pdf_toc', 'pdf_romanize_parts', 'pdf_crop_marks', 'pdf_hyphens' ) as $val ) {
		if ( ! isset( $input[$val] ) || $input[$val] != '1' ) $options[$val] = 0;
		else $options[$val] = 1;
	}

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

function pressbooks_theme_pdf_css_override( $css ) {

	// --------------------------------------------------------------------
	// Global Options

	$options = get_option( 'pressbooks_theme_options_global' );

	// Display chapter numbers? true (default) / false
	if ( ! @$options['chapter_numbers'] ) {
		$css .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number, #toc .part a::before, #toc .chapter a::before { display: none !important; } \n";
	}

	// --------------------------------------------------------------------
	// PDF Options

	$options = get_option( 'pressbooks_theme_options_pdf' );

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
	*/
	switch ( @$options['pdf_page_size'] ) {
		case 1:
			$css .= "@page { size: 5.5in 8.5in; } \n";
			break;
		case 2:
			$css .= "@page { size: 6in 9in; } \n";
			break;
		case 3:
			$css .= "@page { size: 8.5in 11in; } \n";
			break;
		case 4:
			$css .= "@page { size: 8.5in 9.25in } \n";
			break;
		case 5:
			$css .= "@page { size: 5in 7.75in } \n";
			break;
		case 6:
			$css .= "@page { size: 4.25in 7in } \n";
			break;
		case 7:
			$css .= "@page { size: 21cm 29.7cm } \n";
			break;
		case 8:
			$css .= "@page { size: 14.8cm 21cm; } \n";
			break;
	}

	// Display crop marks? true / false (default)
	if ( @$options['pdf_crop_marks'] ) {
		$css .= "@page { marks: crop } \n";
	}

	// Hyphens?
	// To debug use `hyphens: prince-expand-all;` (then every hyphenation point will be shown with a dot)
	if ( @$options['pdf_hyphens'] ) {
		$css .= 'p { hyphens: auto; ';
		$hyphens_path = pb_get_hyphens_path();
		if ( $hyphens_path ) {
			$css .= "prince-hyphenate-patterns: '" . $hyphens_path . "'; ";
		}
		$css .= "} \n";
	}

	// Indent paragraphs? 1 = Indent (default), 2 = Skip Lines
	if ( 2 == @$options['pdf_paragraph_separation'] ) {
		$css .= "p + p { text-indent: 0em; margin-top: 1em; } \n";
	}

	// Include blank pages? 1 = Yes (default), 2 = No
	if ( 2 == @$options['pdf_blankpages'] ) {
		$css .= "#title-page, #copyright-page, #toc, div.part, div.front-matter, div.back-matter, div.chapter, #half-title-page h1.title:first-of-type  { page-break-before: auto; } \n";
	}

	// Display TOC? true (default) / false
	if ( ! @$options['pdf_toc'] ) {
		$css .= "#toc { display: none; } \n";
	}

	// Widows & Orphans
	if ( @$options['pdf_widows'] ) {
		$css .= '@page, p { widows: ' . $options['pdf_widows'] . '; }' . "\n";
	}
	if ( @$options['pdf_orphans'] ) {
		$css .= '@page, p { orphans: ' . $options['pdf_orphans'] . '; }' . "\n";
	}


	// --------------------------------------------------------------------
	// Luther features we inject ourselves, (not user options, this theme not child)

	$theme = strtolower( '' . wp_get_theme() );
	if ( 'luther' == $theme ) {
		// Translate "Part" to whatever language this book is in
		$css .= '#toc .part a::before { content: "' . __( 'Part', 'pressbooks' ) . ' "counter(part, upper-roman) ". "; }' . "\n";
		$css .= 'div.part-title-wrap > h3.part-number:before { content: "' . __( 'Part', 'pressbooks' ) . ' "; }' . "\n";
	}

	return $css;
}
add_filter( 'pb_pdf_css_override', 'pressbooks_theme_pdf_css_override' );


function pressbooks_theme_ebook_css_override( $css ) {

	// --------------------------------------------------------------------
	// Global Options

	$options = get_option( 'pressbooks_theme_options_global' );

	if ( ! @$options['chapter_numbers'] ) {
		$css .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number { display: none !important; } \n";
	}

	// --------------------------------------------------------------------
	// Ebook Options

	$options = get_option( 'pressbooks_theme_options_ebook' );

	// Indent paragraphs? 1 = Indent (default), 2 = Skip Lines
	if ( 2 == @$options['ebook_paragraph_separation'] ) {
		$css .= "p + p, .indent { text-indent: 0; margin-top: 1em; } \n";
	}

	// --------------------------------------------------------------------
	// Luther features we inject ourselves, (not user options, this theme not child)

	$theme = strtolower( '' . wp_get_theme() );
	if ( 'luther' == $theme ) {
		// Translate "Part" to whatever language this book is in
		$css .= 'div.part-title-wrap > h3.part-number:before { content: "' . __( 'Part', 'pressbooks' ) . ' "; }' . "\n";
	}

	return $css;

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
