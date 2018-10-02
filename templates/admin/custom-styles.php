<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	printf( '<div class="error">%s</div>', __( 'Error: Something went wrong. See logs for more details.', 'pressbooks' ) );
}

?>
<div class="wrap">
	<h1><?php _e( 'Custom Styles', 'pressbooks' ); ?></h1>
	<div class="custom-styles-page">
		<form id="pb-custom-styles-form" action="<?php echo $custom_form_url ?>" method="post">
			<input type="hidden" name="post_id" value="<?php echo $post_id; ?>"/>
			<input type="hidden" name="post_id_integrity" value="<?php echo md5( NONCE_KEY . $post_id ); ?>"/>
			<div><?php echo __( 'You are currently editing styles for', 'pressbooks' ) . ': ' . $slugs_dropdown; ?></div>
			<h3><?php printf( __( 'Theme %1$s Styles (%2$s)', 'pressbooks' ), $current_label, $theme ); ?></h3>
			<textarea readonly id="theme_styles" name="theme_styles"><?php echo esc_textarea( $theme_styles ); ?></textarea>
			<h3><?php printf( __( 'Your %s Styles', 'pressbooks' ), $current_label ); ?></h3>
			<textarea id="your_styles" name="your_styles"><?php echo esc_textarea( $your_styles ); ?></textarea>
			<?php submit_button( __( 'Save', 'pressbooks' ), 'primary', 'save' ); ?>
		</form>
	</div>
	<?php echo $revisions_table; ?>
</div>
<script>
(function( $, wp ) {
	var e1 = wp.CodeMirror.fromTextArea( document.getElementById( 'theme_styles' ), {
		lineNumbers: true,
		matchBrackets: true,
		readOnly: true,
		mode: 'text/x-scss'
	} );
	var e2 = wp.CodeMirror.fromTextArea( document.getElementById( 'your_styles' ), {
		lineNumbers: true,
		matchBrackets: true,
		mode: 'text/x-scss'
	} );
})( window.jQuery, window.wp );
</script>
