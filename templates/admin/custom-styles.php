<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Outputs the content of the Edit CSS page */

// -------------------------------------------------------------------------------------------------------------------
// Reusables
// -------------------------------------------------------------------------------------------------------------------

$custom_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/themes.php?page=pb_custom_styles&customstyles=yes' ), 'pb-custom-styles' );

// TODO: Testing
$styles = \Pressbooks\Container::get( 'Styles' );
$output = $styles->customizePrince();

// -------------------------------------------------------------------------------------------------------------------
// Warnings and errors
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $_GET['customstyles_error'] ) ) {
	// Conversion failed
	printf( '<div class="error">%s</div>', __( 'Error: Something went wrong. See logs for more details.', 'pressbooks' ) );
}

?>
<div class="wrap">

	<div id="icon-themes" class="icon32"></div>
	<h2><?php _e( 'Custom Styles', 'pressbooks' ); ?></h2>

	<div class="custom-styles-page">

		<form id="pb-custom-styles-form" action="<?php echo $custom_form_url ?>" method="post">

			<h3><?php _e( 'Theme Styles', 'pressbooks' ); ?></h3>
			<textarea readonly id="theme_css" name="theme_css"><?php echo esc_textarea( $output ); ?></textarea>

			<h3><?php _e( 'Your Styles', 'pressbooks' ); ?></h3>
			<textarea id="custom_css" name="custom_css"></textarea>

			<?php submit_button( __( 'Save', 'pressbooks' ), 'primary', 'save' ); ?>

		</form>

	</div>


</div>

<script>
	var editor1 = CodeMirror.fromTextArea( document.getElementById( 'theme_css' ), {
		lineNumbers: true,
		matchBrackets: true,
		readOnly: true,
		mode: 'text/x-scss'
	} );
	var editor2 = CodeMirror.fromTextArea( document.getElementById( 'custom_css' ), {
		lineNumbers: true,
		matchBrackets: true,
		mode: 'text/css'
	} );
</script>