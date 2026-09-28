<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! apply_filters( 'pb_access_to_custom_styles', true ) ) {
    wp_die( __( 'You do not have sufficient permissions to access this page.', 'pressbooks' ) );
}

/**
 * @see \Pressbooks\Styles::editor
 * @var \WP_Post $style_post
 * @var string $slug
 */

$styles = \Pressbooks\Container::get( 'Styles' );
$custom_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/themes.php?page=' . $styles::PAGE . '&custom_styles=yes' ), 'pb-custom-styles' );
$slugs_dropdown = $styles->renderDropdownForSlugs( $slug );
$current_label = ( $styles->getSupported()[ $slug ] !== 'Web' ) ? $styles->getSupported()[ $slug ] : __( 'Web', 'pressbooks' );
$revisions_table = $styles->renderRevisionsTable( $slug, $style_post->ID );
$post_id = absint( $style_post->ID );
$theme = wp_get_theme();
$theme_styles = $styles->customize( $slug, \Pressbooks\Utility\get_contents( $styles->getPathToScss( $slug ) ) );
$your_styles = $style_post->post_content;

// -------------------------------------------------------------------------------------------------------------------
// Template
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $_GET['debug'] ) ) { // Debug
	$theme_styles = \Pressbooks\Sanitize\normalize_css_urls( $theme_styles, 'http://DEBUG' );
}

if ( ! empty( $_GET['custom_styles_error'] ) ) {
	// Conversion failed
	printf( '<div class="error" role="alert">%s</div>', __( 'Error: Something went wrong. See logs for more details.', 'pressbooks' ) );
}

?>
<div class="wrap">
	<h1><?php _e( 'Custom Styles', 'pressbooks' ); ?></h1>
	<p class="description" id="pb-editor-keyboard-trap-help-1"><?php _e( 'When using a keyboard to navigate the code editors below:', 'pressbooks' ); ?></p>
	<ul class="description">
		<li id="pb-editor-keyboard-trap-help-2"><?php _e( 'In the editing area, the Tab key enters a tab character.', 'pressbooks' ); ?></li>
		<li id="pb-editor-keyboard-trap-help-3"><?php _e( 'To move away from this area, press the Esc key followed by the Tab key (or Shift+Tab to move backward).', 'pressbooks' ); ?></li>
		<li id="pb-editor-keyboard-trap-help-4"><?php _e( 'Screen reader users: when in forms mode, you may need to press the Esc key twice.', 'pressbooks' ); ?></li>
	</ul>
	<div class="custom-styles-page">
		<form id="pb-custom-styles-form" action="<?php echo $custom_form_url ?>" method="post">
			<input type="hidden" name="post_id" value="<?php echo $post_id; ?>"/>
			<input type="hidden" name="post_id_integrity" value="<?php echo md5( NONCE_KEY . $post_id ); ?>"/>
			<div><label for="slug"><?php echo __( 'You are currently editing styles for', 'pressbooks' ) . ':</label> ' . $slugs_dropdown; ?></div>
			<h2><label for="theme_styles"><?php printf( __( 'Theme %1$s Styles (%2$s)', 'pressbooks' ), $current_label, $theme ); ?></label></h2>
			<textarea readonly id="theme_styles" name="theme_styles"><?php echo esc_textarea( $theme_styles ); ?></textarea>
			<h2><label for="your_styles"><?php printf( __( 'Your %s Styles', 'pressbooks' ), $current_label ); ?></label></h2>
			<textarea id="your_styles" name="your_styles"><?php echo esc_textarea( $your_styles ); ?></textarea>
			<?php submit_button( __( 'Save', 'pressbooks' ), 'primary', 'save' ); ?>
		</form>
	</div>
	<?php echo $revisions_table; ?>
</div>
<script>
(function( $, wp ) {
	// wp.codeEditor.initialize() (not raw CodeMirror) wires up Esc-then-Tab to escape the editor.
	if ( ! wp.codeEditor ) {
		return; // Not enqueued if user disabled syntax highlighting; plain textareas have no trap.
	}

	var describedBy = 'pb-editor-keyboard-trap-help-1 pb-editor-keyboard-trap-help-2 pb-editor-keyboard-trap-help-3 pb-editor-keyboard-trap-help-4';

	var themeStyles = wp.codeEditor.initialize( 'theme_styles', {
		codemirror: {
			readOnly: true
		},
		onTabPrevious: function() {
			$( '#slug' ).trigger( 'focus' );
		},
		onTabNext: function() {
			yourStyles.codemirror.focus();
		}
	} );

	var yourStyles = wp.codeEditor.initialize( 'your_styles', {
		onTabPrevious: function() {
			themeStyles.codemirror.focus();
		},
		onTabNext: function() {
			$( '#pb-custom-styles-form' ).find( '#submit' ).trigger( 'focus' );
		}
	} );

	$( themeStyles.codemirror.display.input.textarea ).attr({
		'aria-label': '<?php printf( __( 'Theme %1$s Styles (%2$s)', 'pressbooks' ), $current_label, $theme ); ?>',
		'aria-disabled': true,
		'aria-describedby': describedBy
	});
	$( yourStyles.codemirror.display.input.textarea ).attr({
		'aria-label': '<?php printf( __( 'Your %s Styles', 'pressbooks' ), $current_label ); ?>',
		'aria-describedby': describedBy
	});
})( window.jQuery, window.wp );
</script>
