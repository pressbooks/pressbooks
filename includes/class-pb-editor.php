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
	 * Adds style select dropdown to MCE buttons array.
	 */
	static function mceButtons( $buttons ) {

		$p = array_search( 'formatselect', $buttons );
		array_splice( $buttons, $p + 1, 0, 'styleselect' );

		return $buttons;
	}

	/**
	 * Updates custom stylesheet for MCE previewing.
	 *
	 * @param int $pid
	 * @param \WP_Post $post
	 */
	static function updateEditorStyle( $pid = null, $post = null ) {
		
		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail
		
		$scss = "@import 'mixins';\n";
				
		$body_font_stack = 'body { font-family: $body-font-stack-web; }';
						
		$scss .= $body_font_stack;
		
		$scss .= "@import 'editor';\n";
						
		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/editor';

		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir );
		}
		
		if ( ! is_dir( $upload_dir ) ) {
			throw new \Exception( 'Could not create stylesheet directory.' );
		}
					
		$css_file = $upload_dir . '/editor.css';

		$css = \PressBooks\SASS\compile( $scss, array( 'load_paths' => array( PB_PLUGIN_DIR . 'assets/css/sass', PB_PLUGIN_DIR . 'assets/export/', $wp_upload_dir['basedir'] . '/global-typography', get_stylesheet_directory() ) ) );
						
		if ( ! file_put_contents( $css_file, $css ) ) {
			throw new \Exception( 'Could not write custom CSS file.' );
		}
		
	}	

	/**
	 * Adds stylesheet for MCE previewing.
	 */
	static function addEditorStyle() {
		
		$wp_upload_dir = wp_upload_dir();
		
		$path = $wp_upload_dir['basedir'] . '/editor/editor.css';
		$uri = $wp_upload_dir['baseurl'] . '/editor/editor.css';

		if ( !is_file( $path ) ) {
			\PressBooks\Editor::updateEditorStyle();
		}
		
		add_editor_style( $uri );
		
	}


	/**
	 * Adds Pressbooks custom CSS classes to the style select dropdown initiated above.
	 */
	static function mceBeforeInitInsertFormats( $init_array ) {

		$style_formats = array(
			array(
				'title' => 'Indent',
				'block' => 'p',
				'classes' => 'indent',
				'wrapper' => false,
			),
			array(
				'title' => 'Hanging indent',
				'block' => 'p',
				'classes' => 'hanging-indent',
				'wrapper' => false,
			),
			array(
				'title' => 'No indent',
				'block' => 'p',
				'classes' => 'no-indent',
				'wrapper' => false,
			),
			array(
				'title' => 'Tight tracking',
				'block' => 'span',
				'classes' => 'tight',
				'wrapper' => false,
			),
			array(
				'title' => 'Very tight tracking',
				'block' => 'span',
				'classes' => 'very-tight',
				'wrapper' => false,
			),
			array(
				'title' => 'Loose tracking',
				'block' => 'span',
				'classes' => 'loose',
				'wrapper' => false,
			),
			array(
				'title' => 'Very loose tracking',
				'block' => 'span',
				'classes' => 'very-loose',
				'wrapper' => false,
			),
			array(
				'title' => 'Text box',
				'block' => 'div',
				'classes' => 'textbox',
				'wrapper' => false,
			),
			array(
				'title' => 'Text box (shaded)',
				'block' => 'div',
				'classes' => 'textbox shaded',
				'wrapper' => false,
			),	
			array(
				'title' => 'Pullquote (left)',
				'inline' => 'span',
				'classes' => 'pullquote-left',
				'wrapper' => false,
			),
			array(
				'title' => 'Pullquote (right)',
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

}
