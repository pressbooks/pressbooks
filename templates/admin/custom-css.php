<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content of the Edit CSS page */

// -------------------------------------------------------------------------------------------------------------------
// Reusables
// -------------------------------------------------------------------------------------------------------------------

$custom_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/themes.php?page=pb_custom_css&customcss=yes' ), 'pb-custom-css' );

// -------------------------------------------------------------------------------------------------------------------
// Warnings and errors
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $_GET['customcss_error'] ) ) {
	// Conversion failed
	printf( '<div class="error">%s</div>', __( 'Error: Something went wrong. See logs for more details.', 'pressbooks' ) );
}

?>
<div class="wrap">

	<div id="icon-themes" class="icon32"></div>
	<h2><?php _e( 'Edit CSS', 'pressbooks' ); ?></h2>

	<div class="custom-css-page">

		<form id="pb-custom-css-form" action="<?php echo $custom_form_url ?>" method="post">

			<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
			<input type="hidden" name="post_id_integrity" value="<?php echo md5( NONCE_KEY . $post_id ); ?>" />

			<div style="float:left;"><?php echo __( 'You are currently editing CSS for', 'pressbooks' ) . ': ' . $slugs_dropdown; ?></div>
			<div style="float:right;"><?php echo __( 'Copy CSS from', 'pressbooks' ) . ': ' . $css_copy_dropdown; ?></div>

			<label for="my_custom_css"></label>
			<textarea id="my_custom_css" name="my_custom_css" cols="70" rows="30"><?php echo esc_textarea( $my_custom_css ); ?></textarea>

			<?php submit_button( __( 'Save', 'pressbooks' ), 'primary', 'save' ); ?>

		</form>

	</div>

	<?php echo $revisions_table; ?>

</div>
