<?php
/**
 * Contains Pressbooks-specific additions to TinyMCE, specifically custom CSS classes.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class Editor {

	/**
	 * Ensure that Word formatting that we like doesn't get filtered out.
	 */

	static function mceValidWordElements( $init_array ) {

		$init_array['paste_word_valid_elements'] = '@[class],p,h3,h4,h5,h6,a[href|target],strong/b,em/i,div[align],br,table,tbody,thead,tr,td,ul,ol,li,img[src]';

		return $init_array;

	}

	/**
	 * Localize TinyMCE plugins.
	 */
	static function addLanguages( $array ) {
	    $array[] = PB_PLUGIN_DIR . 'languages/tinymce.php';
	    return $array;
	}

	/**
	 * Adds style select dropdown and textbox buttons to MCE buttons array.
	 */
	static function mceButtons( $buttons ) {

		$p = array_search( 'formatselect', $buttons );
		array_splice( $buttons, $p + 1, 0, 'styleselect' );
		$p = array_search( 'styleselect', $buttons );
		array_splice( $buttons, $p + 1, 0, 'textboxes' );

		return $buttons;
	}


	/**
	 * Adds Javascript for buttons above.
	 */
	static function mceButtonScripts($plugin_array) {
	   $plugin_array['textboxes'] = PB_PLUGIN_URL . 'assets/js/textboxes.min.js';
	   return $plugin_array;
	}

	/**
	 * Adds Pressbooks custom CSS classes to the style select dropdown initiated above.
	 */
	static function mceBeforeInitInsertFormats( $init_array ) {

		$style_formats = array(
			array(
				'title' => __( 'Indent', 'pressbooks' ),
				'block' => 'p',
				'classes' => 'indent',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Hanging indent', 'pressbooks' ),
				'block' => 'p',
				'classes' => 'hanging-indent',
				'wrapper' => false,
			),
			array(
				'title' => __( 'No indent', 'pressbooks' ),
				'block' => 'p',
				'classes' => 'no-indent',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Tight tracking', 'pressbooks' ),
				'block' => 'span',
				'classes' => 'tight',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Very tight tracking', 'pressbooks' ),
				'block' => 'span',
				'classes' => 'very-tight',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Loose tracking', 'pressbooks' ),
				'block' => 'span',
				'classes' => 'loose',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Very loose tracking', 'pressbooks' ),
				'block' => 'span',
				'classes' => 'very-loose',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Pullquote (left)', 'pressbooks' ),
				'inline' => 'span',
				'classes' => 'pullquote-left',
				'wrapper' => false,
			),
			array(
				'title' => __( 'Pullquote (right)', 'pressbooks' ),
				'inline' => 'span',
				'classes' => 'pullquote-right',
				'wrapper' => false,
			),
		);

		$init_array['style_formats'] = json_encode( $style_formats );

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
	static function metadataManagerDefaultEditorArgs( $args ) {

		// Precedence when using the + operator to merge arrays is from left to right

		$args = array(
			'media_buttons' => false,
			'tinymce' => array(
				'theme_advanced_buttons1' => 'bold,italic,underline,strikethrough,|,link,unlink,|,numlist,bullist,|,undo,redo,pastetext,pasteword,|',
				'theme_advanced_buttons2' => '',
				'theme_advanced_buttons3' => ''
			)
		) + $args;

		return $args;
	}


	/**
	 * Builds custom list of classes and adjusts other aspects of the table editor plugin.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	static function mceTableEditorOptions( $settings ) {
		$table_classes = array(
			array(
				'title' => __( 'Standard', 'pressbooks' ),
				'value'	=> ''
			),
			array(
				'title'	=> __( 'No lines', 'pressbooks' ),
				'value'	=> 'no-lines',
			),
			array(
				'title'	=> __( 'Lines', 'pressbooks' ),
				'value'	=> 'lines',
			),
			array(
				'title'	=> __( 'Shaded', 'pressbooks' ),
				'value'	=> 'shaded',
			),
			array(
				'title'	=> __( 'Custom...', 'pressbooks' ),
				'value'	=> 'custom',
			)
		);
		$cell_classes = array(
			array(
				'title' => __( 'Standard', 'pressbooks' ),
				'value'	=> ''
			),
			array(
				'title'	=> __( 'Border', 'pressbooks' ),
				'value'	=> 'border',
			),
			array(
				'title'	=> __( 'Shaded', 'pressbooks' ),
				'value'	=> 'shaded',
			),
		);
		$row_classes = array(
			array(
				'title' => __( 'Standard', 'pressbooks' ),
				'value'	=> ''
			),
			array(
				'title'	=> __( 'Border', 'pressbooks' ),
				'value'	=> 'border',
			),
			array(
				'title'	=> __( 'Shaded', 'pressbooks' ),
				'value'	=> 'shaded',
			),
		);
		$settings['table_advtab'] = false;
		$settings['table_class_list'] = json_encode( $table_classes );
		$settings['table_cell_advtab'] = false;
		$settings['table_cell_class_list'] = json_encode( $cell_classes );
		$settings['table_row_advtab'] = false;
		$settings['table_row_class_list'] = json_encode( $row_classes );
		return $settings;
	}


	/**
	 * Updates custom stylesheet for MCE previewing.
	 *
	 * @param int $pid
	 * @param \WP_Post $post
	 * @throws \Exception
	 */
	static function updateEditorStyle( $pid = null, $post = null ) {

		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail

		$scss = '$type: \'web\';' . "\n";

		$scss .= "@import 'mixins';" . "\n";

		$scss .= '@if variable-exists(font-1) {' . "\n";
		$scss .= 'body#tinymce.wp-editor { font-family: $font-1; }' . "\n";
		$scss .= '}' . "\n";

		$scss .= '@if variable-exists(font-2) {' . "\n";
		$scss .= 'body#tinymce.wp-editor { h1, h2, h3, h4, h5, h6 { font-family: $font-2; }' . "\n";
		$scss .= '} }' . "\n";

		$scss .= "@import 'editor';" . "\n";

		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/css';

		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir, 0777, true );
		}

		if ( ! is_dir( $upload_dir ) ) {
			throw new \Exception( 'Could not create stylesheet directory.' );
		}

		$css_file = $upload_dir . '/editor.css';

		$global_typography = $wp_upload_dir['basedir'] . '/css/scss/_global-font-stack.scss';

		if ( !is_file( $global_typography ) ) {
			\PressBooks\GlobalTypography::updateGlobalTypographyMixin();
		}

		$css = \PressBooks\SASS\compile( $scss, array( PB_PLUGIN_DIR . 'assets/scss/partials', $wp_upload_dir['basedir'] . '/css/scss/', get_stylesheet_directory() ) );

		if ( ! file_put_contents( $css_file, $css ) ) {
			throw new \Exception( 'Could not write custom CSS file.' );
		}

	}

	/**
	 * Adds stylesheet for MCE previewing.
	 */
	static function addEditorStyle() {

		$wp_upload_dir = wp_upload_dir();

		$path = $wp_upload_dir['basedir'] . '/css/editor.css';
		$uri = $wp_upload_dir['baseurl'] . '/css/editor.css';

		if ( !is_file( $path ) ) {
			\PressBooks\Editor::updateEditorStyle();
		}

		add_editor_style( $uri );

	}

}
