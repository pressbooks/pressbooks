<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Editor;

use function Pressbooks\Sanitize\normalize_css_urls;
use PressbooksMix\Assets;
use Pressbooks\Container;
use Pressbooks\HtmlParser;
use Pressbooks\Shortcodes\Glossary\Glossary;

/**
 * Ensure that Word formatting that we like doesn't get filtered out.
 *
 * @param array $init_array
 *
 * @return array
 */
function mce_valid_word_elements( $init_array ) {

	$init_array['paste_word_valid_elements'] = '@[class],p,h3,h4,h5,h6,a[href|target],strong/b,em/i,div[align],br,table,tbody,thead,tr,td,ul,ol,li,img[src]';

	return $init_array;
}

/**
 * Localize TinyMCE plugins.
 *
 * @param array $array
 *
 * @return array
 */
function add_languages( $array ) {
	$array[] = PB_PLUGIN_DIR . 'languages/tinymce.php';
	return $array;
}

/**
 * Adds style select dropdown, textbox and background color buttons to the MCE buttons array.
 *
 * @param array $buttons
 *
 * @return array
 */
function mce_buttons_2( $buttons ) {
	// Prepend elements to the beginning of array
	array_unshift( $buttons, 'styleselect', 'textboxes', 'underline' );
	// Insert after hr button
	$p = array_search( 'hr', $buttons, true );
	array_splice( $buttons, $p + 1, 0, 'alignjustify' );
	// Insert after forecolor button
	$p = array_search( 'forecolor', $buttons, true );
	array_splice( $buttons, $p + 1, 0, 'backcolor' );

	return $buttons;
}

/**
 * Adds anchor, superscript and subscript buttons to the MCE buttons array.
 *
 * @param array $buttons
 *
 * @return array
 */
function mce_buttons_3( $buttons ) {
	// Prepend element to the beginning of array
	array_unshift( $buttons, 'table' );
	// Push elements onto the end of array
	array_push( $buttons, 'apply_class', 'anchor', 'superscript', 'subscript', 'wp_code' );
	// Footnotes
	array_push( $buttons, 'footnote', 'ftnref_convert' );
	// Glossary
	// to avoid 'inception' like glossary within a glossary, restricting
	// glossary buttons means less chance of needing to untangle the labyrinth
	global $typenow;
	if ( empty( $typenow ) && ! empty( $_GET['post'] ) && 'edit' === $_GET['action'] ) {
		$post = get_post( $_GET['post'] );
		$typenow = $post->post_type;
	} elseif ( ! empty( $_GET['post_type'] ) ) {
		$typenow = $_GET['post_type'];
	}
	if ( 'glossary' !== $typenow ) {
		array_push( $buttons, 'glossary', 'glossary_all' );
	}

	return $buttons;
}

/**
 * @param string $hook
 */
function admin_enqueue_scripts( $hook ) {
	$assets = new Assets( 'pressbooks', 'plugin' );

	// Footnotes
	wp_localize_script(
		'editor', 'PB_FootnotesToken', [
			'nonce' => wp_create_nonce( 'pb-footnote-convert' ),
			'fn_title' => __( 'Insert Footnote', 'pressbooks' ),
			'ftnref_title' => __( 'Convert MS Word Footnotes', 'pressbooks' ),
		]
	);

	// Glossary
	$glossary_term = get_term_by( 'slug', 'glossary', 'back-matter-type' );
	if ( $glossary_term ) {
		$glossary_term_id = $glossary_term->term_id;
	} else {
		$glossary_term_id = 0;
	}
	wp_localize_script(
		'editor', 'PB_GlossaryToken', [
			'cancel' => __( 'Cancel', 'pressbooks' ),
			'description' => __( 'Description', 'pressbooks' ),
			'glossary_button_title' => __( 'Insert Glossary Term', 'pressbooks' ),
			'insert' => __( 'Insert', 'pressbooks' ),
			'listbox_values' => Glossary::init()->getGlossaryTermsListbox(),
			'not_found' => _x( 'Glossary term <em>${templateString1}</em> not found. Please create it.', 'JS template string', 'pressbooks' ),
			'select_a_term' => __( 'Select a Term', 'pressbooks' ),
			'tab0_title' => __( 'Create and Insert Term', 'pressbooks' ),
			'tab1_title' => __( 'Choose Existing Term', 'pressbooks' ),
			'term_already_exists' => __( 'Glossary term already exists.', 'pressbooks' ),
			'term_id' => $glossary_term_id,
			'term_is_empty' => __( 'Cannot submit empty Glossary term.', 'pressbooks' ),
			'term_not_selected' => __( 'A term was not selected?', 'pressbooks' ),
			'term_notice' => __( "To display a list of glossary terms, leave this back matter's content blank.", 'pressbooks' ),
			'term_title' => __( 'Term', 'pressbooks' ),
			'window_title' => __( 'Glossary Terms', 'pressbooks' ),
		]
	);

	if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
		wp_enqueue_script( 'my_custom_quicktags', $assets->getPath( 'scripts/quicktags.js' ), [ 'quicktags' ] );
		wp_enqueue_script( 'wp-api' );
	}
}


/**
 * Adds Javascript for buttons above.
 *
 * @param array $plugin_array
 *
 * @return array
 */
function mce_button_scripts( $plugin_array ) {
	$assets = new Assets( 'pressbooks', 'plugin' );
	$styles = Container::get( 'Styles' );

	$plugin_array['apply_class'] = $assets->getPath( 'scripts/applyclass.js' );
	if ( $styles->hasBuckram( '1.0' ) ) {
		$plugin_array['textboxes'] = $assets->getPath( 'scripts/textboxes.js' );
	} else {
		$plugin_array['textboxes'] = $assets->getPath( 'scripts/textboxes-legacy.js' );
	}
	$plugin_array['anchor'] = $assets->getPath( 'scripts/anchor.js' );
	$plugin_array['table'] = $assets->getPath( 'scripts/table.js' );

	// Footnotes
	$plugin_array['footnote'] = $assets->getPath( 'scripts/footnote.js' );
	$plugin_array['ftnref_convert'] = $assets->getPath( 'scripts/ftnref-convert.js' );

	// Glossary
	$plugin_array['glossary'] = $assets->getPath( 'scripts/glossary.js' );

	return $plugin_array;
}

/**
 * Adds Pressbooks custom CSS classes to the style select dropdown initiated above.
 *
 * @see https://codex.wordpress.org/TinyMCE_Custom_Styles#Enabling_styleselect
 *
 * @param array $init_array
 *
 * @return array
 */
function mce_before_init_insert_formats( $init_array ) {

	$style_formats = [
		[
			'title' => __( 'Indent', 'pressbooks' ),
			'block' => 'p',
			'classes' => 'indent',
			'wrapper' => false,
		],
		[
			'title' => __( 'Hanging indent', 'pressbooks' ),
			'block' => 'p',
			'classes' => 'hanging-indent',
			'wrapper' => false,
		],
		[
			'title' => __( 'No indent', 'pressbooks' ),
			'block' => 'p',
			'classes' => 'no-indent',
			'wrapper' => false,
		],
		[
			'title' => __( 'Tight tracking', 'pressbooks' ),
			'block' => 'span',
			'classes' => 'tight',
			'wrapper' => false,
		],
		[
			'title' => __( 'Very tight tracking', 'pressbooks' ),
			'block' => 'span',
			'classes' => 'very-tight',
			'wrapper' => false,
		],
		[
			'title' => __( 'Loose tracking', 'pressbooks' ),
			'block' => 'span',
			'classes' => 'loose',
			'wrapper' => false,
		],
		[
			'title' => __( 'Very loose tracking', 'pressbooks' ),
			'block' => 'span',
			'classes' => 'very-loose',
			'wrapper' => false,
		],
		[
			'title' => __( 'Pullquote (left)', 'pressbooks' ),
			'inline' => 'span',
			'classes' => 'pullquote-left',
			'wrapper' => false,
		],
		[
			'title' => __( 'Pullquote (right)', 'pressbooks' ),
			'inline' => 'span',
			'classes' => 'pullquote-right',
			'wrapper' => false,
		],
	];

	$style_formats = apply_filters( 'pressbooks_editor_custom_styles', $style_formats );

	$init_array['style_formats'] = wp_json_encode( $style_formats );

	$init_array['table_toolbar'] = false;

	return $init_array;
}


/**
 * We don't support "the kitchen sink" when using the custom metadata plugin,
 * render the WYSIWYG editor accordingly.
 *
 * @param array $args
 *
 * @return array
 */
function metadata_manager_default_editor_args( $args ) {

	// Precedence when using the + operator to merge arrays is from left to right

	$args = [
		'media_buttons' => false,
		'tinymce' => [
			'toolbar1' => 'bold,italic,underline,strikethrough,|,link,unlink,|,numlist,bullist,|,undo,redo,pastetext,pasteword,|',
			'toolbar2' => '',
			'toolbar3' => '',
		],
	] + $args;

	return $args;
}


/**
 * Builds custom list of classes and adjusts other aspects of the table editor plugin.
 *
 * @param array $settings
 *
 * @return array
 */
function mce_table_editor_options( $settings ) {
	$table_classes = [
		[
			'title' => __( 'Standard', 'pressbooks' ),
			'value' => '',
		],
		[
			'title' => __( 'No lines', 'pressbooks' ),
			'value' => 'no-lines',
		],
		[
			'title' => __( 'Lines', 'pressbooks' ),
			'value' => 'lines',
		],
		[
			'title' => __( 'Shaded', 'pressbooks' ),
			'value' => 'shaded',
		],
	];
	$cell_classes = [
		[
			'title' => __( 'Standard', 'pressbooks' ),
			'value' => '',
		],
		[
			'title' => __( 'Border', 'pressbooks' ),
			'value' => 'border',
		],
		[
			'title' => __( 'Shaded', 'pressbooks' ),
			'value' => 'shaded',
		],
	];
	$row_classes = [
		[
			'title' => __( 'Standard', 'pressbooks' ),
			'value' => '',
		],
		[
			'title' => __( 'Border', 'pressbooks' ),
			'value' => 'border',
		],
		[
			'title' => __( 'Shaded', 'pressbooks' ),
			'value' => 'shaded',
		],
	];

	$settings['table_appearance_options'] = true; // This allows captions to be added.
	$settings['table_advtab'] = false; // Hides border and background colour options.
	$settings['table_cell_advtab'] = false; // Hides border and background colour options.
	$settings['table_row_advtab'] = false; // Hides border and background colour options.
	$settings['table_responsive_width'] = true; // Forces percentage width when resizing.
	$settings['table_default_attributes'] = wp_json_encode( [ 'border' => 0 ] ); // Set border to 0 for accurate editor display
	$settings['table_class_list'] = wp_json_encode( apply_filters( 'pressbooks_editor_table_classes', $table_classes ) );
	$settings['table_cell_class_list'] = wp_json_encode( apply_filters( 'pressbooks_editor_cell_classes', $cell_classes ) );
	$settings['table_row_class_list'] = wp_json_encode( apply_filters( 'pressbooks_editor_row_classes', $row_classes ) );

	return $settings;
}


/**
 * Updates custom stylesheet for MCE previewing.
 */
function update_editor_style() {

	$styles = Container::get( 'Styles' );
	$sass = Container::get( 'Sass' );

	if ( $styles->isCurrentThemeCompatible( 1 ) ) {
		$scss = \Pressbooks\Utility\get_contents( $sass->pathToPartials() . '/_editor-with-custom-fonts.scss' );
	} elseif ( $styles->isCurrentThemeCompatible( 2 ) ) {
		$scss = \Pressbooks\Utility\get_contents( $sass->pathToGlobals() . '/editor/_editor.scss' );
	} else {
		$scss = \Pressbooks\Utility\get_contents( $sass->pathToPartials() . '/_editor.scss' );
	}

	$custom_styles = $styles->getWebPost();
	if ( $custom_styles && ! empty( $custom_styles->post_content ) ) {
		// Append the user's custom styles to the editor stylesheet prior to compilation
		$scss .= "\n" . $custom_styles->post_content;
	}

	$css = $styles->customize( 'web', $scss );

	$css = normalize_css_urls( $css );

	$output = $sass->pathToUserGeneratedCss() . '/editor.css';
	\Pressbooks\Utility\put_contents( $output, $css );
}


/**
 * Adds stylesheet for MCE previewing.
 *
 * @return bool
 */
function add_editor_style() {

	$sass = Container::get( 'Sass' );
	$path = $sass->pathToUserGeneratedCss() . '/editor.css';
	if ( file_exists( $path ) ) {
		$hash = md5( filemtime( $path ) );
		$uri = $sass->urlToUserGeneratedCss() . '/editor.css?ver=' . $hash;
		\add_editor_style( $uri );
		return true;
	}

	return false;
}


/**
 * Only show book contents post types in link insertion modal.
 *
 * @param array $query
 *
 * @return array
 */
function customize_wp_link_query_args( $query ) {

	$query['post_type'] = [ 'part', 'chapter', 'front-matter', 'back-matter' ];

	return $query;
}

/**
 * Add anchors to link insertion modal query results.
 *
 * @param array $results
 * @param array $query
 *
 * @return array
 */
function add_anchors_to_wp_link_query( $results, $query ) {
	// Note to future-self: $results are paginated. If the user scrolls down, ajax is triggered, more function calls will happen.
	$url = wp_parse_url( $_SERVER['HTTP_REFERER'] );
	parse_str( $url['query'], $query );
	$current_post_id = isset( $query['post'] ) ? $query['post'] : 0;
	$new_results = [];
	foreach ( $results as $result ) {
		$new_results[] = $result;
		$url = rtrim( $result['permalink'], '/' );
		$post_id = $result['ID'];
		$post = get_post( $post_id );
		if ( $post && ! empty( trim( $post->post_content ) ) ) {
			$html5 = new HtmlParser( true );
			$doc = $html5->loadHTML( $post->post_content );
			/** @var \DOMElement $node */
			foreach ( $doc->getElementsByTagName( 'a' ) as $node ) {
				if ( $node->hasAttribute( 'id' ) ) {
					$id_attribute = $node->getAttribute( 'id' );
					$permalink = ( (int) $current_post_id !== (int) $post->ID ) ? $url : '';
					$permalink .= "#{$id_attribute}";
					$new_results[] = [
						'ID' => $post->ID,
						'title' => '#' . $id_attribute . ' (' . $post->post_title . ')',
						'permalink' => $permalink,
						'info' => __( 'Internal Link', 'pressbooks' ),
					];
				}
			}
		}
	}
	return $new_results;
}

/**
 * Show the Kitchen Sink by default.
 *
 * @since 5.6.0
 *
 * @param array $args
 * @return array
 */
function show_kitchen_sink( $args ) {
	$args['wordpress_adv_hidden'] = false;
	return $args;
}

/**
 * Force classic editor mode
 */
function hide_gutenberg() {
	// 4.9.X and below
	deactivate_plugins( [ 'gutenberg/gutenberg.php' ] );

	// 5.X and up, Classic Editor not present
	if ( ! function_exists( 'classic_editor_init_actions' ) ) {

		// Don't use block editor for any post types
		add_filter( 'use_block_editor_for_post_type', function ( $use_block_editor, $post_type ) {
			return false;
		}, 10, 2 );
	}

	// 5.x and up, Classic Editor present

	// Hide "Classic Editor" Settings page, because we don't want people turning Gutenberg back on
	remove_filter( 'plugin_action_links', 'classic_editor_add_settings_link' );
	remove_action( 'admin_init', 'classic_editor_admin_init' );

	// Short circuit the classic-editor-replace option, always replace
	add_filter(
		'pre_option_classic-editor-replace', function () {
			return 'replace';
		}
	);
}
