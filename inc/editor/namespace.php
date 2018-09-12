<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Editor;

use function Pressbooks\Sanitize\normalize_css_urls;
use PressbooksMix\Assets;
use Pressbooks\Container;

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
 */
function add_languages( $array ) {
	$array[] = PB_PLUGIN_DIR . 'languages/tinymce.php';
	return $array;
}

/**
 * Adds style select dropdown, textbox and background color buttons to the MCE buttons array.
 */
function mce_buttons_2( $buttons ) {

	array_splice( $buttons, 0, 0, 'styleselect' );
	$p = array_search( 'styleselect', $buttons, true );
	array_splice( $buttons, $p + 1, 0, 'textboxes' );
	$p = array_search( 'textboxes', $buttons, true );
	array_splice( $buttons, $p + 1, 0, 'underline' );
	$p = array_search( 'hr', $buttons, true );
	array_splice( $buttons, $p + 1, 0, 'alignjustify' );
	$p = array_search( 'forecolor', $buttons, true );
	array_splice( $buttons, $p + 1, 0, 'backcolor' );
	return $buttons;
}

/**
 * Adds anchor, superscript and subscript buttons to the MCE buttons array.
 */
function mce_buttons_3( $buttons ) {
	array_unshift( $buttons, 'table' );
	array_push( $buttons, 'apply_class', 'anchor', 'superscript', 'subscript' );
	return $buttons;
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

	return $plugin_array;
}

/**
 * Adds Pressbooks custom CSS classes to the style select dropdown initiated above.
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
 * @param array $parent_query
 *
 * @return array
 */
function add_anchors_to_wp_link_query( $results, $parent_query ) {

	$url = wp_parse_url( $_SERVER['HTTP_REFERER'] );
	parse_str( $url['query'], $query );

	if ( ! isset( $query['post'] ) ) {
		return $results;
	}

	$anchors = [];

	$post = get_post( $query['post'] );

	libxml_use_internal_errors( true );

	$content = mb_convert_encoding( apply_filters( 'the_content', $post->post_content ), 'HTML-ENTITIES', 'UTF-8' );

	if ( ! empty( $content ) ) {
		$doc = new \DOMDocument();
		$doc->loadHTML( $content );

		/** @var \DOMElement $node */
		foreach ( $doc->getElementsByTagName( 'a' ) as $node ) {
			if ( $node->hasAttribute( 'id' ) ) {
				$anchors[] = [
					'ID' => $post->ID,
					'title' => '#' . $node->getAttribute( 'id' ) . ' (' . $post->post_title . ')',
					'permalink' => '#' . $node->getAttribute( 'id' ),
					'info' => __( 'Internal Link', 'pressbooks' ),
				];
			}
		}
	}

	// Find the position of the current post in $results, put array of anchors right after that post (ie. put array one in the middle of array two)
	$offset = false;
	foreach ( $results as $key => $result ) {
		if ( (int) $results[ $key ]['ID'] === (int) $query['post'] ) {
			$offset = $key + 1;
			break;
		}
	}
	if ( $offset === false ) {
		// If we could not find the position of the current post in $results, then do nothing.
		// The $results are paginated. If the user scrolls down more ajax calls will happen. We wait until we see our post to insert $anchors.
		return $results;
	} else {
		array_splice( $results, $offset, 0, $anchors );
		return $results;
	}
}
